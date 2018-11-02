<?php

namespace pvsaintpe\log\traits;

use pvsaintpe\db\components\Connection;
use Yii;
use pvsaintpe\log\components\Configs;
use yii\di\Instance;

/**
 * Class MigrationTrait
 * @package pvsaintpe\log\traits
 */
trait MigrationTrait
{
    /**
     * @var Connection
     */
    public $db;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public function init()
    {
        $this->db = Instance::ensure(Configs::instance()->db, Connection::class);
        $this->db->getSchema()->refresh();
        $this->db->enableSlaves = false;
    }

    /**
     * @return null|object|Connection
     * @throws \yii\base\InvalidConfigException
     */
    protected function getDb()
    {
        return Yii::$app->get(Configs::instance()->db);
    }

    /**
     * @return null|object|Connection
     * @throws \yii\base\InvalidConfigException
     */
    protected function getStorageDb()
    {
        return Yii::$app->get(Configs::instance()->storageDb);
    }
}