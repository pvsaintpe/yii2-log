<?php

namespace pvsaintpe\log\components;

use pvsaintpe\db\components\Connection;
use pvsaintpe\db\components\TableSchema;
use pvsaintpe\log\interfaces\ChangeLogInterface;
use yii\db\Expression;
use yii\db\Query;
use Yii;
use pvsaintpe\search\components\ActiveRecord as ActiveRecordBase;

/**
 * Class ActiveRecord
 * @package pvsaintpe\log\components
 */
class ActiveRecord extends ActiveRecordBase implements ChangeLogInterface
{
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
     * @param array $columns
     * @param string|array $condition
     * @return int
     * @throws
     */
    public static function batchUpdate(array $columns, $condition)
    {
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
     * @todo implement via TableSchema
     * @return string[]
     */
    private static function getPrimaryKeys()
    {
        return static::primaryKey();
    }

    /**
     * @todo implement via TableSchema
     * @return string[]
     */
    private static function getDateAttributes()
    {
        return array_merge(
            static::dateAttributes(),
            static::datetimeAttributes()
        );
    }

    /**
     * @maybe
     * @todo implement via Configs::systemAttributes
     * @return string[]
     */
    private static function getReservedAttributes()
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
     * @return array
     */
    public static function skipLogAttributes()
    {
        return array_merge(
            static::getPrimaryKeys(),
            static::getDateAttributes(),
            static::getReservedAttributes()
        );
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
        if (static::logEnabled() && Yii::$app->user->can(Configs::instance()->id) && static::existLogTable()) {
            $period = Yii::$app->request->get('revisionPeriod', 1);
            $logTableName = static::getLogTableName();
            $whereConditions = [];
            foreach ($attributes as $attribute) {
                if (in_array($attribute, static::getLogDb()->getTableSchema($logTableName)->getColumnNames())) {
                    $whereConditions[] = '{alias}.' . $attribute . ' IS NOT NULL';
                }
            }

            $whereCondition = [];
            if (count($whereConditions) > 0) {
                $whereCondition[] = join(' OR ', $whereConditions);
            }

            return static::getLastChanges(array_merge(
                [['>=', 'timestamp', new Expression("NOW() - INTERVAL {$period} DAY")]],
                $whereCondition
            ));
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
        if (static::logEnabled() && Yii::$app->user->can(Configs::instance()->id) && static::existLogTable()) {
            $period = Yii::$app->request->get('revisionPeriod', Configs::instance()->revisionPeriod);
            return static::getLastChanges(array_merge(
                [
                    ['>=', 'timestamp', new Expression("NOW() - INTERVAL {$period} DAY")],
                ],
                $where,
                !$attribute ? [] : [['NOT', [$attribute => null]]]
            ));
        }
        return 0;
    }

    /**
     * @param array $conditions
     * @return int
     */
    final public static function getLastChanges($conditions = [])
    {
        $query = new Query();
        $alias = uniqid('t');
        $query->from(static::getLogDb()->getName() . '.' . static::getLogTableName() . " {$alias}");
        foreach ($conditions as $attribute => $condition) {
            if (is_array($condition)) {
                if ($condition[0] == 'NOT') {
                    $arrayKeys = array_keys($condition[1]);
                    $arrayValues = array_values($condition[1]);
                    $query->andWhere([
                        $condition[0],
                        [$alias . '.' . $arrayKeys[0] => $arrayValues[0][1]],
                    ]);
                } else {
                    if (count($condition) === 2) {
                        $query->andWhere([
                            $alias . '.' . $attribute => $condition
                        ]);
                    } else {
                        $query->andWhere([
                            $condition[0],
                            $alias . '.' . $condition[1],
                            $condition[2]
                        ]);
                    }
                }
            } else {
                if (strpos($condition, '{alias}.') !== false) {
                    $query->andWhere(str_replace('{alias}', $alias, $condition));
                } else {
                    $query->andFilterWhere([$alias . '.' . $attribute => $condition]);
                }
            }
        }
        return $query->count();
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
     * @return bool
     */
    final public static function existLogTable()
    {
        try {
            return static::getLogDb()->existTable(static::getLogTableName());
        } catch (\Exception $e) {
            Yii::$app->session->setFlash(
                'warning',
                Yii::t('changelog', 'Missing target table {schema}.{table} for logging', [
                    'schema' => static::getLogDb()->getName(),
                    'table' => static::getLogTableName(),
                ])
            );
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
        $attributes = array_merge(static::getReservedAttributes(), [Configs::instance()->adminColumn]);

        foreach (static::getStorageDb()->getColumns(static::tableName(), $attributes) as $tableColumn) {
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

        if (empty($addColumns) && empty($removeColumns) && empty($updateColumns)
            && empty($dropForeignKeys) && empty($addForeignKeys)
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
                    } else {
                        $affectedAttributes[Configs::instance()->adminColumn] = Yii::$app->user->getId();
                    }

                    static::getLogDb()->insert(static::getLogTableName(), $affectedAttributes);
                }
            }
        }
    }
}
