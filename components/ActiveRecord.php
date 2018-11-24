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
    public static function batchUpdate(array $columns, $condition)
    {
        static::saveToLogBatchUpdate($columns, $condition);
        return parent::batchUpdate($columns, $condition);
    }

    /**
     * @param array $attributes
     * @param string|array $condition
     * @param array $params
     * @return int|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function updateAll($attributes, $condition = '', $params = [])
    {
        static::saveToLog($attributes, $condition, $params);
        return parent::updateAll($attributes, $condition, $params);
    }

    /**
     * @return bool
     */
    public static function logEnabled()
    {
        return false;
    }

    /**
     * @return array
     */
    public static function securityLogAttributes()
    {
        return [];
    }

    /**
     * @return array
     */
    public static function skipLogAttributes()
    {
        return array_merge(
            static::primaryKey(),
            static::dateAttributes(),
            static::datetimeAttributes(),
            [
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
                'timestamp',
            ]
        );
    }

    /**
     * @return \yii\db\ActiveQuery|ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    final public function getReferenceBy()
    {
        return $this->hasOne(Configs::instance()->adminClass, ['id' => Configs::instance()->adminColumn]);
    }

    /**
     * @return bool
     */
    final public function isLogEnabled()
    {
        return static::logEnabled();
    }

    /**
     * @return Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    final public static function getLogDb()
    {
        return Configs::db();
    }

    /**
     * @return Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    final public static function getStorageDb()
    {
        return Configs::storageDb();
    }

    /**
     * @param array $attributes
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    final public static function getRevisionCountByAttr($attributes = [])
    {
        if (static::logEnabled() && Yii::$app->user->can(Configs::instance()->id)) {
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
     * @throws \yii\base\InvalidConfigException
     */
    final public static function getLastRevisionCount($attribute = null, $where = [])
    {
        if (static::logEnabled() && Yii::$app->user->can(Configs::instance()->id)) {
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
     * @param array $conditions
     * @return array
     */
    final public static function getLastChanges($conditions = [])
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
     * Проверяет необходимость отсечки при отсутствии ревизий (если они доступны и разрешены)
     * @param $attribute
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    final public function isExitWithoutRevisions($attribute)
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
     * @return null|string
     * @throws \yii\base\InvalidConfigException
     */
    final public static function getLogTableName()
    {
        return Configs::instance()->tablePrefix . static::tableName() . Configs::instance()->tableSuffix;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    final public static function getLogClassName()
    {
        return join('\\', [
            Configs::instance()->classNamespace,
            Inflector::camelize(Inflector::id2camel(static::getLogTableName(), '_'))
        ]);
    }

    /**
     * @return bool
     */
    final public static function existLogTable()
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
    final public static function getCreateParams()
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
    final public static function getUpdateParams()
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
    final public static function generateForeignKeyName($table, $column)
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
    final public static function createLogTable()
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
     * @param array $attributes
     * @param string|array $condition
     * @param int|null $updatedBy
     */
    final public static function saveToLogBatchUpdate($attributes, $condition = '',  $updatedBy = null)
    {
        if (static::logEnabled() && static::existLogTable()) {
            // @todo доделать реализацию convert BatchUpdate -> BatchInsert to Log
        }
    }

    /**
     * @param array $attributes
     * @param string|array $condition
     * @param array $params
     * @param int|null $updatedBy
     * @throws \yii\base\InvalidConfigException
     */
    final public static function saveToLog($attributes, $condition = '', $params = [], $updatedBy = null)
    {
        if (static::logEnabled() && static::existLogTable()) {
            $affectedRows = static::find()->where(null)->andWhere($condition, $params)->all();
            /** @var ActiveRecord $affectedRow */
            foreach ($affectedRows as $affectedRow) {
                $affectedAttributes = [];
                foreach ($attributes as $attribute => $value) {
                    if ($affectedRow->getAttribute($attribute) != $value) {
                        $affectedAttributes[$attribute] = $affectedRow->getAttribute($attribute);
                    }
                }
                $affectedAttributes = array_diff_key($affectedAttributes, array_flip(static::skipLogAttributes()));
                if (count($affectedAttributes) > 0) {
                    $affectedAttributes = array_merge(
                        $affectedAttributes,
                        array_intersect_key(
                            $affectedRow->getAttributes(),
                            array_flip(static::primaryKey())
                        )
                    );

                    if ($updatedBy) {
                        $affectedAttributes[Configs::instance()->adminColumn] = $updatedBy;
                    }

                    $logClassName = static::getLogClassName();
                    /** @var ActiveRecord $log */
                    $log = new $logClassName();
                    $log->setAttributes($affectedAttributes);
                    $log->save(false);
                }
            }
        }
    }
}
