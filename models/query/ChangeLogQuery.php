<?php

namespace pvsaintpe\log\models\query;

use pvsaintpe\log\models\base\ChangeLogSearchBase;
use pvsaintpe\search\components\ActiveQuery;

/**
 * Class ChangeLogQuery
 * @package pvsaintpe\log\models\query
 */
class ChangeLogQuery extends ActiveQuery
{
    /**
     * @inheritdoc
     * @return ChangeLogSearchBase[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return ChangeLogSearchBase|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
