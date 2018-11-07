<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\grid\components\DataColumn as DataColumnBase;

/**
 * Class DataColumn
 * @package pvsaintpe\log\components\grid
 */
class DataColumn extends DataColumnBase
{
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
