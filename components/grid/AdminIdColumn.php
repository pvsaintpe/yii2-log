<?php

namespace pvsaintpe\log\components\grid;

/**
 * Class AdminIdColumn
 * @package pvsaintpe\log\components\grid
 */
class AdminIdColumn extends RelationColumn
{
    /**
     * @var string
     */
    public $attribute = 'admin_id';

    /**
     * @var bool
     */
    public $allowNotSet = false;
}
