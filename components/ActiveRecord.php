<?php

namespace pvsaintpe\log\components;

use pvsaintpe\db\components\Connection;
use pvsaintpe\gii\plus\db\TableSchema;
use pvsaintpe\log\interfaces\ChangeLogInterface;
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
    public function getLogDb()
    {
        return Yii::$app->get(Configs::instance()->db);
    }

    /**
     * @return Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    public function getStorageDb()
    {
        return Yii::$app->get(Configs::instance()->storageDb);
    }

    /**
     * @return array
     */
    public function skipLogAttributes()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function logEnabled()
    {
        return false;
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
            static::saveToLog();
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return null|string
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogTableName()
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
            static::datetimeAttributes()
        );
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogClassName()
    {
        return join('\\', [
            Configs::instance()->classNamespace,
            Inflector::camelize(Inflector::id2camel($this->getLogTableName(), '_'))
        ]);
    }

    /**
     * @return bool
     */
    private function existLogTable()
    {
        try {
            return $this->getLogDb()->existTable($this->getLogTableName());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    private function getCreateParams()
    {
        $keys = [];
        if (($uniqueKeys = $this->getStorageDb()->getUniqueKeys(static::tableName()))) {
            foreach ($uniqueKeys as $uniqueKey) {
                $keys[] = $uniqueKey['Key_name'];
            }
            $keys = array_unique($keys);
        }

        return [
            'storageDb' => $this->getStorageDb(),
            'view' => Configs::instance()->createTemplatePath,
            'migration_prefix' => 'create_table',
            'tableName' => static::tableName(),
            'logTableName' => $this->getLogTableName(),
            'columns' => $this->getStorageDb()->getColumns(static::tableName()),
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

        foreach ($this->getStorageDb()->getColumns(static::tableName(), [
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

        foreach ($this->getLogDb()->getColumns($this->getLogTableName(), [
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
        $tableSchema = $this->getStorageDb()->getTableSchema(static::tableName());
        foreach ($tableSchema->foreignKeys as $foreignParams) {
            $relationTable = array_shift($foreignParams);
            foreach ($foreignParams as $column => $relationColumn) {
                if (!in_array($column, $columns)) {
                    continue;
                }
                $addForeignKeys[$column] = [
                    'name' => $this->generateForeignKeyName($this->getLogTableName(), $column),
                    'relation_table' => $relationTable,
                    'relation_column' => $relationColumn,
                ];
                $foreignKeys[] = $column;
            }
        }

        $dropForeignKeys = [];
        $logForeignKeys = [];
        /** @var TableSchema $logTableSchema */
        $logTableSchema = $this->getLogDb()->getTableSchema($this->getLogTableName());
        foreach ($logTableSchema->foreignKeys as $logForeignKey => $logForeignParams) {
            if ($logForeignKey === 'fk-reference-' . static::tableName()) {
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
            'logTableName' => $this->getLogTableName(),
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
    private function generateForeignKeyName($table, $column)
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
        if (!$this->existLogTable()) {
            // create log table
            return $this->getCreateParams();
        } else {
            // update for appeared a new columns
            return $this->getUpdateParams();
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
     * @return bool|ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function saveToLog()
    {
        if ($this->existLogTable()) {
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

                $logClassName = $this->getLogClassName();
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
