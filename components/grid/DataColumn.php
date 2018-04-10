<?php

namespace pvsaintpe\log\components\grid;

use kartik\grid\DataColumn as KartikDataColumn;

/**
 * Class DataColumn
 * @package pvsaintpe\log\components\grid
 */
class DataColumn extends KartikDataColumn
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
