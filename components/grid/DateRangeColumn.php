<?php

namespace pvsaintpe\log\components\grid;

use pvsaintpe\search\widgets\GridView;
use DateTime;

/**
 * Class DateRangeColumn
 * @package pvsaintpe\log\components\grid
 */
class DateRangeColumn extends DataColumn
{
    const DATE_FORMAT = 'd/m/Y';

    const DATE_SEPARATOR = ' - ';

    public $hAlign = 'center';

    public $vAlign = 'middle';

    public $width = '10%';

    public $format = 'date';

    public $filterType = GridView::FILTER_DATE_RANGE;

    public $filterWidgetOptions = [];

    public $filterInputOptions = [
        'class' => 'form-control pull-right',
        'readonly' => true
    ];

    public function init()
    {
        $this->filterWidgetOptions = [
            'convertFormat' => true,
            'pluginOptions' => [
                'format' => self::DATE_FORMAT,
                'locale' => [
                    'format' => self::DATE_FORMAT,
                    'separator' => self::DATE_SEPARATOR
                ],
                'autoclose' => true
            ],
            'pluginEvents' => [
                'cancel.daterangepicker' => "function(ev, picker) { $(this).val('').trigger('change'); }"
            ]
        ];

        parent::init();
    }

    /**
     * @param \yii\db\QueryInterface $query
     * @param array $columns attribute => value
     * @return array
     */
    public static function modifyQuery(&$query, array $columns)
    {
        foreach ($columns as $attribute => $value) {
            if (preg_match('~^(.+)' . preg_quote(static::DATE_SEPARATOR) . '(.+)$~', $value, $match)) {
                $date1 = DateTime::createFromFormat(static::DATE_FORMAT, $match[1]);
                $date2 = DateTime::createFromFormat(static::DATE_FORMAT, $match[2]);

                if ($date1 && $date2) {
                    $columns[$attribute] = $date1 . static::DATE_SEPARATOR . $date2;
                    $query->andWhere([
                        'BETWEEN',
                        $query->a($attribute),
                        $date1->format('Y-m-d'),
                        $date2->format('Y-m-d')
                    ]);
                }
            }
        }
        return $columns;
    }
}
