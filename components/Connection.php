<?php

namespace pvsaintpe\log\components;

/**
 * Class Connection
 * @package pvsaintpe\components
 */
class Connection extends \yii\db\Connection
{
    /**
     * @var string
     */
    private $dbName;

    /**
     * @return bool|string
     */
    public function getName()
    {
        if (!$this->dbName) {
            parse_str(str_replace(';', '&', substr(strstr($this->dsn, ':'), 1)), $dsn);
            if (!array_key_exists('host', $dsn)
                || !array_key_exists('port', $dsn)
                || !array_key_exists('dbname', $dsn)
            ) {
                $this->dbName = null;
            } else {
                $this->dbName = $dsn['dbname'];
            }
        }
        return $this->dbName;
    }

}