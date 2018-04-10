<?php

namespace pvsaintpe\log\components\grid;

use DateTime;

/**
 * Class DatetimeRangeColumn
 * @package pvsaintpe\log\components\grid
 */
class DatetimeRangeColumn extends DateRangeColumn
{
    const DATE_FORMAT = 'm/d/Y';
    const DATETIME_FORMAT = 'm/d/Y H:i';

    const DATETIME_PATTERN = '([\d]{2})/([\d]{2})/([\d]{4}) ([\d]{2}):([\d]{2})';
    const DATE_PATTERN = '([\d]{2})/([\d]{2})/([\d]{4})';

    public $format = 'datetime';

    /**
     * @inheritdoc
     */
    public static function modifyQuery(&$query, array $columns)
    {
        foreach ($columns as $attribute => $value) {
            if (preg_match('~^' . static::DATETIME_PATTERN . preg_quote(static::DATE_SEPARATOR) . static::DATETIME_PATTERN . '$~', $value, $match)) {
                $date1 = DateTime::createFromFormat(static::DATETIME_FORMAT, $match[1] . '/' . $match[2] . '/' . $match[3] . ' ' . $match[4] . ':' . $match[5]);
                $date2 = DateTime::createFromFormat(static::DATETIME_FORMAT, $match[6] . '/' . $match[7] . '/' . $match[8] . ' ' . $match[9] . ':' . $match[10]);
                if ($date1 && $date2) {
                    $query->andWhere([
                        'BETWEEN',
                        (strpos($attribute, '.') !== false) ? $attribute : $query->a($attribute),
                        $date1->format('Y-m-d H:i:00'),
                        $date2->format('Y-m-d H:i:59')
                    ]);
                    continue;
                }
            }
            if (preg_match('~^' . static::DATE_PATTERN . preg_quote(static::DATE_SEPARATOR) . static::DATE_PATTERN . '$~', $value, $match)) {
                $date1 = DateTime::createFromFormat(static::DATE_FORMAT, $match[2] . '/' .  $match[1] . '/' . $match[3]);
                $date2 = DateTime::createFromFormat(static::DATE_FORMAT, $match[5] . '/' .  $match[4] . '/' . $match[6]);
                if ($date1 && $date2) {
                    $filter = [
                        'BETWEEN',
                        (strpos($attribute, '.') !== false) ? $attribute : $query->a($attribute),
                        $date1->format('Y-m-d 00:00:00'),
                        $date2->format('Y-m-d 23:59:59')
                    ];
                    $query->andWhere($filter);
                }
            }
        }
        return $columns;
    }
}
