<?php

namespace pvsaintpe\log\traits;

use pvsaintpe\db\components\Connection;
use Yii;
use pvsaintpe\log\components\Configs;

/**
 * Class MigrationTrait
 * @package pvsaintpe\log\traits
 */
trait MigrationTrait
{
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