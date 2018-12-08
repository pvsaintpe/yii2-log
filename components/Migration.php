<?php

namespace pvsaintpe\log\components;

use pvsaintpe\db\components\Connection;
use yii\base\Exception;

/**
 * Class Migration
 * @package pvsaintpe\log\components
 */
class Migration extends \pvsaintpe\db\components\Migration
{
    /**
     * @param string $table
     * @param array $columns
     * @param string $condition
     * @param array $params
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        $this->saveToLog($table, $columns, $condition, $params);
        parent::update($table, $columns, $condition, $params);
    }

    /**
     * @param string $tableName
     * @param array $attributes
     * @param string|array $condition
     * @param array $params
     * @throws
     */
    private function saveToLog($tableName, $attributes, $condition = '', $params = [])
    {
        if ($this->existLogTable($tableName)) {
            if (is_string($condition)) {
                $sql = $condition;
            } else {
                $conditions = [];
                foreach ($condition as $key => $value) {
                    if (is_array($value)) {
                        if (count($value) > 0) {
                            $conditions[] = $key . " IN ('" . join("', '", $value) . "')";
                        }
                    } else {
                        $conditions[] = "{$key} = '{$value}'";
                    }
                }
                $sql = join('', $conditions);
            }

            $affectedRows = $this->getStorageDb()->selectAll($sql, $params);
            foreach ($affectedRows as $affectedRow) {
                $affectedAttributes = [];
                foreach ($attributes as $attribute => $value) {
                    if ($affectedRow[$attribute] != $value) {
                        $affectedAttributes[$attribute] = $affectedRow[$attribute];
                    }
                }
                if (count($affectedAttributes) > 0) {
                    $affectedAttributes = array_merge(
                        $affectedAttributes,
                        array_intersect_key($affectedRow, array_flip($this->getLogPrimaryKeys($tableName)))
                    );

                    $affectedAttributes[Configs::instance()->adminColumn] = $this->getUpdatedBy();
                    $logAttributes = $this->getLogAttributes($tableName);

                    $this->getLogDb()->insert(
                        $this->getLogTableName($tableName),
                        array_intersect_key($affectedAttributes, array_flip($logAttributes))
                    );
                }
            }
        }
    }

    /**
     * Идентификатор оператора (кто инициировал изменение настроек)
     * @return int
     * @throws Exception
     */
    protected function getUpdatedBy()
    {
        throw new Exception('The identifier of the operator who made the changes is not defined.');
    }

    /**
     * @return null|object|Connection
     * @throws \yii\base\InvalidConfigException
     */
    protected function getLogDb()
    {
        return Configs::db();
    }

    /**
     * @return null|object|Connection
     * @throws \yii\base\InvalidConfigException
     */
    protected function getStorageDb()
    {
        return Configs::storageDb();
    }

    /**
     * @param $tableName
     * @return false
     */
    private function existLogTable($tableName)
    {
        return $this->getLogDb()->existTable($this->getLogTableName($tableName));
    }

    /**
     * @param string $tableName
     * @return string
     */
    private function getLogTableName($tableName)
    {
        return Configs::instance()->tablePrefix . $tableName . Configs::instance()->tableSuffix;
    }

    /**
     * @param string $tableName
     * @return array
     */
    private function getLogAttributes($tableName)
    {
        return $this->getLogDb()->getTableSchema($this->getLogTableName($tableName))->getColumnNames();
    }

    /**
     * @param string $tableName
     * @return array
     */
    private function getLogPrimaryKeys($tableName)
    {
        return $this->getLogDb()->getTableSchema($this->getLogTableName($tableName))->primaryKey;
    }
}
