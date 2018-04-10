<?php

namespace pvsaintpe\log\components;

/**
 * Class Migration
 * @package pvsaintpe\log\components
 */
class Migration extends \yii\db\Migration
{

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
     */
    public function selectOne($conditions = '', $params = [], $fetchMode = null)
    {
        return $this->db->createCommand($conditions, $params)->queryOne($fetchMode);
    }

    /**
     * @param string $conditions
     * @param array $params
     * @return false|null|string
     */
    public function selectColumn($conditions = '', $params = [])
    {
        return $this->db->createCommand($conditions, $params)->queryColumn();
    }

    /**
     * @param string $conditions
     * @param array $params
     * @param integer $fetchMode
     * @return false|null|string
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
     * @param string $tableName
     * @param string $name
     * @param string $event BEFORE_INSERT
     * @param string $sql
     * @param string $definer CURRENT_USER
     *
     * @return bool
     */
    public function createTrigger($tableName, $name, $sql, $event = 'BEFORE_INSERT', $definer = 'CURRENT_USER')
    {
        if (($db = $this->getDbName()) !== false) {
            $leavePrefix = '';
            $leaveSuffix = '';
            if (YII_ENV_TEST) {
                $leavePrefix = "thisTrigger: ";
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
     */
    public function dropTrigger($name)
    {
        if (($db = $this->getDbName()) !== false) {
            $this->execute("DROP TRIGGER IF EXISTS `{$db}`.`{$name}`");
        }
    }

    /**
     * @return bool|string
     */
    public function getDbName()
    {
        $db = $this->getDb();
        parse_str(str_replace(';', '&', substr(strstr($db->dsn, ':'), 1)), $dsn);
        if (!array_key_exists('host', $dsn) || !array_key_exists('port', $dsn) || !array_key_exists('dbname', $dsn)) {
            return false;
        }

        return $dsn['dbname'];
    }
}