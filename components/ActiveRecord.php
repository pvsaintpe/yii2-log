<?php

namespace pvsaintpe\log\components;

use pvsaintpe\log\interfaces\ChangeLogInterface;
use pvsaintpe\log\traits\ChangeLogTrait;
use pvsaintpe\search\interfaces\MessageInterface;

/**
 * Class ActiveRecord
 * @package pvsaintpe\log\components
 */
class ActiveRecord extends \pvsaintpe\search\components\ActiveRecord implements ChangeLogInterface
{
    use ChangeLogTrait;

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($insert != static::EVENT_BEFORE_INSERT) {
            static::saveToLog();
        }
        return parent::beforeSave($insert);
    }
}