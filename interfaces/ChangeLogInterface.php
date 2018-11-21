<?php

namespace pvsaintpe\log\interfaces;

use pvsaintpe\log\components\ActiveQuery;

/**
 * Interface ChangeLogInterface
 * @package pvsaintpe\log\interfaces
 */
interface ChangeLogInterface
{
    /**
     * @return bool
     */
    public function isLogEnabled();

    /**
     * @return \yii\db\ActiveQuery|ActiveQuery
     */
    public function getReferenceBy();
}
