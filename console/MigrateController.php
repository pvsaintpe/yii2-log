<?php

namespace pvsaintpe\log\console;

use yii\console\controllers\MigrateController as BaseMigrateController;
use Yii;
use yii\db\Connection;

/**
 * Class MigrateController
 * @package pvsaintpe\log\console
 */
class MigrateController extends BaseMigrateController
{
    /**
     * @return array|string|Connection
     */
    public function getDb()
    {
        return Yii::$app->changelog->db;
    }
}