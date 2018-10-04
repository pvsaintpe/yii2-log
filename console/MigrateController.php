<?php

namespace pvsaintpe\log\console;

use pvsaintpe\db\components\Connection;
use yii\console\controllers\MigrateController as BaseMigrateController;
use Yii;

/**
 * Class MigrateController
 * @package pvsaintpe\log\console
 */
class MigrateController extends BaseMigrateController
{
    /**
     * @return array|string|yii\db\Connection|Connection
     */
    public function getDb()
    {
        return $this->db;
    }
}
