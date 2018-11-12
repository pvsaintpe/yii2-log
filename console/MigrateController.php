<?php

namespace pvsaintpe\log\console;

use pvsaintpe\db\components\Connection;
use pvsaintpe\log\components\Configs;
use yii\console\controllers\MigrateController as BaseMigrateController;
use Yii;

/**
 * Class MigrateController
 * @package pvsaintpe\log\console
 */
class MigrateController extends BaseMigrateController
{
    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->db = Configs::db();
    }

    /**
     * @return array|string|yii\db\Connection|Connection
     * @throws \yii\base\InvalidConfigException
     */
    public function getDb()
    {
        return Configs::db();
    }
}
