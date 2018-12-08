<?php

namespace pvsaintpe\log\components;

use pvsaintpe\db\components\Connection;
use yii\base\Exception;
use yii\db\Transaction;

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
     * @param null|string $reason
     */
    public function safeUpdate($table, $columns, $condition = '', $params = [], $reason = null)
    {
        $this->saveToLog($table, $columns, $condition, $params, $reason);
        parent::update($table, $columns, $condition, $params);
    }

    /**
     * @param string $tableName
     * @param array $attributes
     * @param string|array $condition
     * @param array $params
     * @param null|string $reason
     * @throws
     */
    private function saveToLog($tableName, $attributes, $condition = '', $params = [], $reason = null)
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
                $sql = join(' AND ', $conditions);
            }
            $affectedRows = $this->getStorageDb()->selectAll("SELECT * FROM `{$tableName}` WHERE {$sql}", $params);
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
                        array_intersect_key($affectedRow, array_flip($this->getStoragePrimaryKeys($tableName)))
                    );

                    $affectedAttributes[Configs::instance()->adminColumn] = $this->getUpdatedBy();
                    $affectedAttributes['log_reason'] = $this->getLogReason($reason);
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
    private function getStoragePrimaryKeys($tableName)
    {
        return $this->getStorageDb()->getTableSchema($tableName)->primaryKey;
    }

    /**
     * @param null $reason
     * @return null|string
     */
    private function getLogReason($reason = null)
    {
        if (!$reason) {
            $reason = (new \ReflectionClass($this))->getShortName();
        }
        return $reason;
    }
}
