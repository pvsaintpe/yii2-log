<?php

namespace pvsaintpe\log\components;

use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class Migration
 * @property Connection $db
 * @package console\components
 */
class Migration extends \yii\boost\db\Migration
{
    /**
     * @return array|string|\yii\db\Connection|Connection
     */
    protected function getDb()
    {
        return $this->db;
    }

    /**
     * @param string $name
     * @param string $sql
     */
    public function createView($name, $sql)
    {
        $this->dropView($name);
        $this->execute("CREATE VIEW `{$name}` AS {$sql}");
    }

    /**
     * @param string $conditions
     * @param array $params
     * @return false|null|string
     * @throws \yii\db\Exception
     */
    public function selectScalar($conditions = '', $params = [])
    {
        return $this->db->createCommand($conditions, $params)->queryScalar();
    }

    /**
     * @param string $conditions
     * @param array $params
     * @param integer $fetchMode
     * @return false|null|string
     * @throws \yii\db\Exception
     */
    public function selectOne($conditions = '', $params = [], $fetchMode = null)
    {
        return $this->db->createCommand($conditions, $params)->queryOne($fetchMode);
    }

    /**
     * @param string $conditions
     * @param array $params
     * @return array
     * @throws \yii\db\Exception
     */
    public function selectColumn($conditions = '', $params = [])
    {
        return $this->db->createCommand($conditions, $params)->queryColumn();
    }

    /**
     * @param string $conditions
     * @param array $params
     * @param integer $fetchMode
     * @return array
     * @throws \yii\db\Exception
     */
    public function selectAll($conditions = '', $params = [], $fetchMode = null)
    {
        return $this->db->createCommand($conditions, $params)->queryAll($fetchMode);
    }

    /**
     * @param string $name
     */
    public function dropView($name)
    {
        $this->execute("DROP VIEW IF EXISTS `{$name}`;");
    }

    /**
     * @param string $table
     * @param string $column
     */
    public function dropColumn($table, $column)
    {
        if ($this->hasColumn($table, $column)) {
            parent::dropColumn($table, $column);
        }
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $type
     */
    public function alterColumn($table, $column, $type)
    {
        if ($this->hasColumn($table, $column)) {
            parent::alterColumn($table, $column, $type);
        }
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $type
     */
    public function addColumn($table, $column, $type)
    {
        if (!$this->hasColumn($table, $column)) {
            parent::addColumn($table, $column, $type);
        }
    }

    /**
     * @param string $table
     * @param string $name
     * @param string $newName]
     */
    public function renameColumn($table, $name, $newName)
    {
        if ($this->hasColumn($table, $name) && !$this->hasColumn($table, $newName)) {
            parent::renameColumn($table, $name, $newName);
        }
    }

    /**
     * @param string $tableName
     * @param string $name
     * @param string $event (
     *      'BEFORE INSERT',
     *      'BEFORE UPDATE',
     *      'BEFORE_DELETE',
     *      'AFTER INSERT',
     *      'AFTER UPDATE',
     *      'AFTER DELETE'
     *  )
     * @param string $sql
     * @param string $definer CURRENT_USER
     * @param string $leavePrefix
     */
    public function createTrigger(
        $tableName,
        $name,
        $sql,
        $event = 'BEFORE INSERT',
        $definer = 'CURRENT_USER',
        $leavePrefix = 'thisTrigger:'
    ) {
        if (($db = $this->getDb()->getName()) !== false) {
            $leaveSuffix = '';
            if (YII_ENV_TEST) {
                $leaveSuffix = "
                    IF (@TRIGGER_CHECKS = FALSE) THEN
                      LEAVE thisTrigger;
                    END IF;
                ";
            }

            $this->dropTrigger($name);
            $this->execute("
                CREATE DEFINER = {$definer}
                TRIGGER `{$db}`.`{$name}`
                {$event} ON `{$db}`.`{$tableName}`
                FOR EACH ROW
                {$leavePrefix} BEGIN {$leaveSuffix}
                    {$sql}
                END
            ");
        }
    }

    /**
     * @param string $name
     * @param array $params @example [['type' => 'IN', 'name' => 'arg_name', 'format' => 'INT UNSIGNED'], ...]
     * @param string $sql
     * @param string $definer CURRENT_USER
     * @param array $options @example ['NOT DETERMINISTIC', 'CONTAINS SQL', 'SQL SECURITY DEFINER']
     */
    public function createProcedure($name, $sql, $params = [], $definer = 'CURRENT_USER', $options = [])
    {
        if (($db = $this->getDb()->getName()) !== false) {
            $this->dropProcedure($name);
            $this->execute(
                "
                    CREATE DEFINER = {$definer}
                    PROCEDURE {$db}.{$name}("
                . join(
                    ', ',
                    array_map(
                        function ($item) {
                            return join(' ', [
                                $item['type'],
                                $item['name'],
                                $item['format'],
                            ]);
                        },
                        $params
                    )
                )
                . ") " . join(' ', $options)
                . " BEGIN {$sql} END
                "
            );
        }
    }

    /**
     * @param string $name
     */
    public function dropProcedure($name)
    {
        if (($db = $this->getDb()->getName()) !== false) {
            $this->execute("DROP PROCEDURE IF EXISTS `{$db}`.`{$name}`");
        }
    }

    /**
     * @param string $name
     */
    public function dropTrigger($name)
    {
        if (($db = $this->getDb()->getName()) !== false) {
            $this->execute("DROP TRIGGER IF EXISTS `{$db}`.`{$name}`");
        }
    }

    /**
     * Raw SQL
     * @var string
     */
    protected $batchUpdateRawSql;

    /**
     * Example
     *
     * ```php
     * $this->batchUpdate(
     *      $tableName,
     *      [
     *          'name' => ['Alice', 'Bob'],
     *          'age' => '18'
     *      ],
     *      [
     *          'id' => [1, 2, 3],
     *          'enabled' => '1'
     *      ]
     * );
     * ```
     *
     * @param string $table
     * @param array $columns
     * @param array $condition
     * @return string
     * @throws \yii\db\Exception
     */
    public function batchUpdate($table, array $columns, $condition)
    {
        $command = $this->db->createCommand();

        if (($tableSchema = $command->db->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
        } else {
            $columnSchemas = [];
        }

        $lines = [];
        foreach ($columns as $name => $value) {
            if ($value instanceof Expression) {
                $lines[] = $command->db->quoteColumnName($name) . '=' . $value->expression;
            } elseif (is_array($value)) {
                $line = $command->db->quoteColumnName($name) . " = (CASE ";
                foreach ($value as $valueIndex => $val) {
                    $line .= " WHEN ";
                    $when = [];
                    foreach ($condition as $whenKey => $conditions) {
                        $param = (is_array($conditions) && isset($conditions[$valueIndex]))
                            ? $conditions[$valueIndex]
                            : $conditions;
                        $whenValue = !is_array($param) && isset($columnSchemas[$whenKey])
                            ? $columnSchemas[$whenKey]->dbTypecast($param)
                            : $param;
                        $when[] = $command->db->quoteColumnName($whenKey)
                            . " = "
                            . $command->db->quoteValue($whenValue);
                    }
                    $conditionValue = !is_array($val) && isset($columnSchemas[$name])
                        ? $columnSchemas[$name]->dbTypecast($val)
                        : $val;
                    $line .= join(' AND ', $when) . " THEN " . $command->db->quoteValue($conditionValue);
                }
                $line .= " END )";
                $lines[] = $line;
            } else {
                $setValue = !is_array($value) && isset($columnSchemas[$name])
                    ? $columnSchemas[$name]->dbTypecast($value)
                    : $value;
                $lines[] = $command->db->quoteColumnName($name) . '=' . $setValue;
            }
        }

        $sql = 'UPDATE ' . $command->db->quoteTableName($table) . ' SET ' . implode(', ', $lines);

        $parts = [];
        foreach ($condition as $whereKey => $whereValue) {
            if (ArrayHelper::isTraversable($whereValue) || $whereValue instanceof Query) {
                $parts[] = $command->db->quoteColumnName($whereKey)
                    . " IN ('" . join("', '", $whereValue)
                    . "')";
            } else {
                $parts[] = $command->db->quoteColumnName($whereKey) . " = '" . $whereValue . "'";
            }
        }

        $where = join(' AND ', $parts);
        $this->batchUpdateRawSql = $where === '' ? $sql : $sql . ' WHERE ' . $where;
        $command->setSql($this->batchUpdateRawSql);
        return $command->execute();
    }

    /**
     * @param $tableName
     * @param $columnName
     * @return bool
     */
    public function hasColumn($tableName, $columnName)
    {
        $tableColumns = $this->db->getTableSchema($tableName)->columnNames;
        if (in_array($columnName, $tableColumns)) {
            return true;
        }
        return false;
    }
}
