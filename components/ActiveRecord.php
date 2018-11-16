<?php

namespace pvsaintpe\log\components;

use pvsaintpe\behaviors\BlameableBehavior;
use pvsaintpe\db\components\Connection;
use pvsaintpe\gii\plus\db\TableSchema;
use pvsaintpe\log\interfaces\ChangeLogInterface;
use yii\db\Expression;
use yii\helpers\Inflector;
use Yii;
use pvsaintpe\search\components\ActiveRecord as ActiveRecordBase;

/**
 * Class ActiveRecord
 * @package pvsaintpe\log\components
 */
class ActiveRecord extends ActiveRecordBase implements ChangeLogInterface
{
    /**
     * @return Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    public static function getLogDb()
    {
        return Configs::db();
    }

    /**
     * @return Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    public static function getStorageDb()
    {
        return Configs::storageDb();
    }

    /**
     * @return array
     */
    public function skipLogAttributes()
    {
        return [
            'created_at',
            'updated_at',
            'created_by',
            'updated_by',
            'timestamp',
        ];
    }

    /**
     * @return bool
     */
    public static function logEnabled()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isLogEnabled()
    {
        return static::logEnabled();
    }

    /**
     * @param array $attributes
     * @return int
     */
    public static function getRevisionCountByAttr($attributes = [])
    {
        if (static::logEnabled() && Yii::$app->user->can('changelog')) {
            $period = Yii::$app->request->get('revisionPeriod', 1);
            /** @var ActiveRecord $logClassName */
            $logClassName = static::getLogClassName();

            $whereConditions = [];
            /** @var ActiveRecord $logClass */
            $logClass = new $logClassName();
            foreach ($attributes as $attribute) {
                if (!$logClass->hasAttribute($attribute)) {
                    continue;
                }
                $whereConditions[] = '{alias}.' . $attribute . ' IS NOT NULL';
            }

            $whereCondition = [];
            if (count($whereConditions) > 0) {
                $whereCondition[] = join(' OR ', $whereConditions);
            }

            return count($logClassName::getLastChanges(array_merge(
                [['>=', 'timestamp', new Expression("NOW() - INTERVAL {$period} DAY")]],
                $whereCondition
            )));
        }
        return 0;
    }

    /**
     * @param null $attribute
     * @param array $where
     * @return int
     */
    public static function getLastRevisionCount($attribute = null, $where = [])
    {
        if (static::logEnabled() && Yii::$app->user->can('changelog')) {
            $period = Yii::$app->request->get('revisionPeriod', 1);
            /** @var ActiveRecord $logClassName */
            $logClassName = static::getLogClassName();
            return count($logClassName::getLastChanges(array_merge(
                [
                    ['>=', 'timestamp', new Expression("NOW() - INTERVAL {$period} DAY")],
                ],
                $where,
                !$attribute ? [] : [['NOT', [$attribute => null]]]
            )));
        }
        return 0;
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getReferenceBy()
    {
        return $this->hasOne(Configs::instance()->adminClass, ['id' => Configs::instance()->adminColumn]);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            static::customBehaviors()
        );
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function customBehaviors()
    {
        $behaviors = [];
        if (Yii::$app->id == 'app-backend') {
            $behaviors['blameable'] = [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => Configs::instance()->adminColumn
            ];
        }
        return $behaviors;
    }

    /**
     * @param array $conditions
     * @return array
     */
    public static function getLastChanges($conditions = [])
    {
        $query = static::find();
        foreach ($conditions as $attribute => $condition) {
            if (is_array($condition)) {
                if ($condition[0] == 'NOT') {
                    $arrayKeys = array_keys($condition[1]);
                    $arrayValues = array_values($condition[1]);
                    $query->andWhere([
                        $condition[0],
                        [$query->a($arrayKeys[0]) => $arrayValues[0][1]],
                    ]);
                } else {
                    if (count($condition) === 2) {
                        $query->andWhere([
                            $query->a($attribute) => $condition
                        ]);
                    } else {
                        $query->andWhere([
                            $condition[0],
                            $query->a($condition[1]),
                            $condition[2]
                        ]);
                    }
                }
            } else {
                if (strpos($condition, '{alias}.') !== false) {
                    $query->andWhere(str_replace('{alias}', $query->getAlias(), $condition));
                } else {
                    $query->andFilterWhere([
                        $query->a($attribute) => $condition
                    ]);
                }
            }
        }
        return $query->all() ?: [];
    }

    /**
     * Проверяет необходимость выхода при отсутствии ревизий (если они включены)
     * @param $attribute
     * @return bool
     */
    public function isExitWithoutRevisions($attribute)
    {
        $revisionEnabled = (bool) Yii::$app->request->get('revisionEnabled', 0);
        if ($this->isLogEnabled() && $revisionEnabled && in_array($attribute, $this->skipLogAttributes())) {
            return true;
        }
        if (!$this->hasAttribute($attribute)) {
            return false;
        }
        $whereConditions = [];
        foreach (static::primaryKey() as $key) {
            $whereConditions[$key] = $this->getAttribute($key);
        }
        $revisionCount = static::getLastRevisionCount($attribute, $whereConditions);
        return $this->isLogEnabled() && $revisionEnabled && !$revisionCount;
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function beforeSave($insert)
    {
        if (!$this->getIsNewRecord()) {
            $this->saveToLog();
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return null|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function getLogTableName()
    {
        return Configs::instance()->tablePrefix . static::tableName() . Configs::instance()->tableSuffix;
    }

    /**
     * @return array
     */
    public function securityLogAttributes()
    {
        return array_merge(
            static::primaryKey(),
            static::dateAttributes(),
            static::datetimeAttributes(),
            [
                'created_by',
                'updated_by',
            ]
        );
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function getLogClassName()
    {
        return join('\\', [
            Configs::instance()->classNamespace,
            Inflector::camelize(Inflector::id2camel(static::getLogTableName(), '_'))
        ]);
    }

    /**
     * @return bool
     */
    public static function existLogTable()
    {
        try {
            return static::getLogDb()->existTable(static::getLogTableName());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    private static function getCreateParams()
    {
        $keys = [];
        if (($uniqueKeys = static::getStorageDb()->getUniqueKeys(static::tableName()))) {
            foreach ($uniqueKeys as $uniqueKey) {
                $keys[] = $uniqueKey['Key_name'];
            }
            $keys = array_unique($keys);
        }

        return [
            'storageDb' => static::getStorageDb(),
            'view' => Configs::instance()->createTemplatePath,
            'migration_prefix' => 'create_table',
            'tableName' => static::tableName(),
            'logTableName' => static::getLogTableName(),
            'columns' => static::getStorageDb()->getColumns(static::tableName()),
            'uniqueKeys' => $keys,
            'primaryKeys' => static::primaryKey(),
        ];
    }

    /**
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    private function getUpdateParams()
    {
        /**
         * Получить список колонок tableName (кроме created_at, updated_at, timestamp)
         * Получить список внешних ключей
         * Получить список индексов (кроме первичного и уникального)
         * Получить список колонок logTableName (кроме log_id, timestamp)
         * Получить список внешних ключей
         * Получить список индексов (кроме первичного и уникального)
         * Пересечение колонок tableName, logTableName - в цикле обновить тип из tableName => logTableName (alterColumn)
         * Разницу между колонками tableName и logTableName - добавить в logTableName (addColumn)
         * Разницу между колонками logTableName и tableName - удалить из logTableName (dropColumn)
         * Удалить индексы и внешние ключи для удаленных колонок (dropIndex, dropForeignKey)
         * Добавить индексы и внешние ключи для добавленных колонок (createIndex, addForeignKey - при необходимости)
         *
         * @var array $addColumns Список полей, которые нужно добавить
         * @var array $removeColumns Список полей, которые нужно удалить
         * @var array $updateColumns Список полей, которые необходимо обновить
         * @var array $dropIndexes Список индексов, которые нужно удалить
         * @var array $dropForeignKeys Список внешних ключей, которые нужно удалить
         * @var array $createIndexes Список индексов, которые нужно добавить
         * @var array $addForeignKeys Список внешних ключей, которые нужно добавить
         */

        $tableColumns = [];
        $comments = [];
        $columns = [];

        foreach (static::getStorageDb()->getColumns(static::tableName(), [
            'created_at',
            'updated_at',
            'timestamp',
            'created_by',
            'updated_by',
            Configs::instance()->adminColumn,
        ]) as $tableColumn) {
            $tableColumns[$tableColumn['Field']] = $tableColumn['Type'];
            $comments[$tableColumn['Field']] = $tableColumn['Comment'];
            $columns[] = $tableColumn['Field'];
        }

        $logTableColumns = [];
        $logComments = [];
        $logColumns = [];

        foreach (static::getLogDb()->getColumns(static::getLogTableName(), [
            'log_id',
            'timestamp',
            Configs::instance()->adminColumn
        ]) as $logTableColumn) {
            $logTableColumns[$logTableColumn['Field']] = $logTableColumn['Type'];
            $logComments[$logTableColumn['Field']] = $logTableColumn['Comment'];
            $logColumns[] = $logTableColumn['Field'];
        }

        $removeColumns = array_keys(array_diff_key($logTableColumns, $tableColumns));

        $addColumns = [];
        foreach (array_diff_key($tableColumns, $logTableColumns) as $column => $type) {
            $addColumns[] = [
                'name' => $column,
                'type' => $type,
                'comment' => $comments[$column],
            ];
        }

        $keys = array_intersect($columns, $logColumns);

        $alterColumns = array_intersect_key($tableColumns, array_flip($keys));
        $logAlterColumns = array_intersect_key($logTableColumns, array_flip($keys));

        $updateColumns = [];
        foreach (array_diff_assoc($alterColumns, $logAlterColumns) as $column => $type) {
            $updateColumns[] = [
                'name' => $column,
                'type' => $type,
                'comment' => $comments[$column],
            ];
        }

        $addForeignKeys = [];
        $foreignKeys = [];
        /** @var TableSchema $tableSchema */
        $tableSchema = static::getStorageDb()->getTableSchema(static::tableName());
        foreach ($tableSchema->foreignKeys as $foreignParams) {
            $relationTable = array_shift($foreignParams);
            foreach ($foreignParams as $column => $relationColumn) {
                if (!in_array($column, $columns)) {
                    continue;
                }
                $addForeignKeys[$column] = [
                    'name' => static::generateForeignKeyName(static::getLogTableName(), $column),
                    'relation_table' => $relationTable,
                    'relation_column' => $relationColumn,
                ];
                $foreignKeys[] = $column;
            }
        }

        $dropForeignKeys = [];
        $logForeignKeys = [];
        /** @var TableSchema $logTableSchema */
        $logTableSchema = static::getLogDb()->getTableSchema(static::getLogTableName());
        foreach ($logTableSchema->foreignKeys as $logForeignKey => $logForeignParams) {
            if (preg_match('/fk-reference-/i', $logForeignKey)) {
                continue;
            }
            array_shift($logForeignParams);
            foreach ($logForeignParams as $logColumn => $logRelationColumn) {
                if ($logColumn == Configs::instance()->adminColumn) {
                    continue;
                }
                $dropForeignKeys[$logColumn] = $logForeignKey;
                $logForeignKeys[] = $logColumn;
            }
        }

        $createForeignKeys = array_diff($foreignKeys, $logForeignKeys);
        $removeForeignKeys = array_diff($logForeignKeys, $foreignKeys);

        $addForeignKeys = array_intersect_key($addForeignKeys, array_flip($createForeignKeys));
        $dropForeignKeys = array_intersect_key($dropForeignKeys, array_flip($removeForeignKeys));

        if (empty($addColumns)
            && empty($removeColumns)
            && empty($updateColumns)
            && empty($dropForeignKeys)
            && empty($addForeignKeys)
        ) {
            return false;
        }

        if (empty($addColumns) && empty($removeColumns) && empty($updateColumns)) {
            if (!empty($addForeignKeys) && empty($dropForeignKeys)) {
                $prefix = 'add_foreign_keys';
            } elseif (empty($addForeignKeys) && !empty($dropForeignKeys)) {
                $prefix = 'drop_foreign_keys';
            } else {
                $prefix = 'add_drop_foreign_keys';
            }
        } elseif (empty($addForeignKeys) && empty($dropForeignKeys)) {
            if (!empty($addColumns) && empty($removeColumns) && empty($updateColumns)) {
                $prefix = 'add_columns';
            } elseif (empty($addColumns) && !empty($removeColumns) && empty($updateColumns)) {
                $prefix = 'remove_columns';
            } elseif (empty($addColumns) && empty($removeColumns) && !empty($updateColumns)) {
                $prefix = 'alter_columns';
            } elseif (!empty($addColumns) && !empty($removeColumns) && empty($updateColumns)) {
                $prefix = 'add_remove_columns';
            } elseif (!empty($addColumns) && empty($removeColumns) && !empty($updateColumns)) {
                $prefix = 'add_alter_columns';
            } elseif (empty($addColumns) && !empty($removeColumns) && !empty($updateColumns)) {
                $prefix = 'remove_alter_columns';
            } else {
                $prefix = 'update_columns';
            }
        } else {
            $prefix = 'update_table';
        }

        return [
            'view' => Configs::instance()->updateTemplatePath,
            'migration_prefix' => $prefix,
            'tableName' => static::tableName(),
            'logTableName' => static::getLogTableName(),
            'primaryKeys' => static::primaryKey(),
            'addColumns' => $addColumns,
            'removeColumns' => $removeColumns,
            'updateColumns' => $updateColumns,
            'dropForeignKeys' => $dropForeignKeys,
            'addForeignKeys' => $addForeignKeys,
        ];
    }

    /**
     * @param $table
     * @param $column
     * @return string
     */
    private static function generateForeignKeyName($table, $column)
    {
        $foreignKey = join('-', [$table, $column]);
        if (strlen($foreignKey) >= 64) {
            $shortTableName = '';
            foreach (explode('_', $table) as $table_part) {
                $shortTableName .= substr($table_part, 0, 1);
            }

            $foreignKey = join('-', [$shortTableName, $column]);
            if (strlen($foreignKey) >= 64) {
                $shortColumnName = '';
                foreach (explode('_', $column) as $column_part) {
                    $shortColumnName .= substr($column_part, 0, 1);
                }
                $foreignKey = join('-', [$shortTableName, $shortColumnName]);
            }
        }

        return $foreignKey;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function createLogTable()
    {
        if (!static::existLogTable()) {
            // create log table
            return static::getCreateParams();
        } else {
            // update for appeared a new columns
            return static::getUpdateParams();
        }
    }

    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * The comparison of new and old values is made for identical values using `===`.
     *
     * @param string[]|null $names the names of the attributes whose values may be returned if they are
     * changed recently. If null, [[attributes()]] will be used.
     * @return array the changed attribute values (name-value pairs)
     */
    public function getRealDirtyAttributes($names = null)
    {
        if ($names === null) {
            $names = $this->attributes();
        }
        $names = array_flip($names);
        $attributes = [];
        if (parent::getOldAttributes() === null) {
            foreach (parent::getAttributes() as $name => $value) {
                if (isset($names[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach (parent::getAttributes() as $name => $value) {
                if (isset($names[$name]) && (!array_key_exists($name, parent::getOldAttributes()) || $value != parent::getOldAttribute($name))) {
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Example
     *
     * ```php
     * User::batchUpdate([
     *      'name' => ['Alice', 'Bob'],
     *      'age' => '18'
     * ], [
     *      'id' => [1, 2, 3],
     *      'enabled' => '1'
     * ]);
     * ```
     *
     * @param array $columns
     * @param array $condition
     * @return int
     * @throws
     */
    public static function batchUpdate(array $columns, $condition)
    {
        $command = static::getDb()->createCommand();
        $command->batchUpdate(static::tableName(), $columns, $condition);
        return $command->execute();
    }

    /**
     * Example
     *
     * ```php
     * User::batchUpdate([
     *      'name' => ['Alice', 'Bob'],
     *      'age' => '18'
     * ], [
     *      'id' => [1, 2, 3],
     *      'enabled' => '1'
     * ]);
     * ```
     *
     * @param array $columns
     * @param string|array $condition
     * @return int
     * @throws
     */
    public static function logBatchUpdate(array $columns, $condition)
    {
        static::saveToLogBatchUpdate($columns, $condition);
        return static::batchUpdate($columns, $condition);
    }

    /**
     * @param array $attributes
     * @param string|array $condition
     * @param int|null $updatedBy
     */
    public static function saveToLogBatchUpdate($attributes, $condition = '',  $updatedBy = null)
    {
        if (static::logEnabled() && static::existLogTable()) {
//            $affectedRows = static::find()->where(null)->andWhere($condition, $params)->all();
//            /** @var ActiveRecord $affectedRow */
//            foreach ($affectedRows as $affectedRow) {
//                $logAttributes = array_merge(
//                    array_intersect_key(
//                        $affectedRow->getAttributes(),
//                        array_flip(static::primaryKey())
//                    ),
//                    $attributes
//                );
//                $logClassName = static::getLogClassName();
//                /** @var ActiveRecord $log */
//                $log = new $logClassName();
//                $log->setAttributes($logAttributes);
//                if ($updatedBy) {
//                    $log->setAttribute(Configs::instance()->adminColumn, $updatedBy);
//                }
//                $log->save(false);
//            }
        }
    }

    /**
     * @param array $attributes
     * @param string|array $condition
     * @param array $params
     * @return int|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function logUpdateAll($attributes, $condition = '', $params = [])
    {
        static::saveToLogUpdateAll($attributes, $condition, $params);
        return parent::updateAll($attributes, $condition, $params);
    }

    /**
     * @param array $attributes
     * @param string|array $condition
     * @param array $params
     * @param int|null $updatedBy
     * @throws \yii\base\InvalidConfigException
     */
    public static function saveToLogUpdateAll($attributes, $condition = '', $params = [], $updatedBy = null)
    {
        if (static::logEnabled() && static::existLogTable()) {
            $affectedRows = static::find()->where(null)->andWhere($condition, $params)->all();
            /** @var ActiveRecord $affectedRow */
            foreach ($affectedRows as $affectedRow) {
                $logAttributes = array_merge(
                    array_intersect_key(
                        $affectedRow->getAttributes(),
                        array_flip(static::primaryKey())
                    ),
                    $attributes
                );
                $logClassName = static::getLogClassName();
                /** @var ActiveRecord $log */
                $log = new $logClassName();
                $log->setAttributes($logAttributes);
                if ($updatedBy) {
                    $log->setAttribute(Configs::instance()->adminColumn, $updatedBy);
                }
                $log->save(false);
            }
        }
    }

    /**
     * @return bool|ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function saveToLog()
    {
        if (static::existLogTable()) {
            $dirtyAttributes = array_intersect_key(
                parent::getOldAttributes(),
                array_diff_key(
                    static::getRealDirtyAttributes(),
                    array_flip(static::skipLogAttributes())
                )
            );

            if (count($dirtyAttributes) > 0) {
                $logAttributes = array_merge(
                    array_intersect_key(
                        $this->getAttributes(),
                        array_flip(static::primaryKey())
                    ),
                    $dirtyAttributes
                );

                $logClassName = static::getLogClassName();
                /** @var ActiveRecord $log */
                $log = new $logClassName();
                $log->setAttributes($logAttributes);
                $log->save(false);
                return $log;
            }
        }

        return false;
    }
}
