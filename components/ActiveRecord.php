<?php

namespace pvsaintpe\log\components;

use pvsaintpe\log\interfaces\ChangeLogInterface;
use pvsaintpe\log\traits\ChangeLogTrait;
use pvsaintpe\search\interfaces\MessageInterface;

/**
 * Class ActiveRecord
 * @package pvsaintpe\log\components
 */
abstract class ActiveRecord extends \pvsaintpe\search\components\ActiveRecord implements ChangeLogInterface, MessageInterface
{
    use ChangeLogTrait;

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        static::saveToLog();
        return parent::beforeSave($insert);
    }
}