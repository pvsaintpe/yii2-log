<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\grid\components\DataColumn as DataColumnBase;
use pvsaintpe\log\traits\RevisionTrait;

/**
 * Class DataColumn
 * @package pvsaintpe\log\components\grid
 */
class DataColumn extends DataColumnBase
{
    use RevisionTrait;

    /**
     * @var string
     */
    public $vAlign = 'middle';

    /**
     * @var string
     */
    public $format = 'raw';

    /**
     * @var array
     */
    public $filterOptions = [
        'pluginOptions' => ['allowClear' => true]
    ];
}
