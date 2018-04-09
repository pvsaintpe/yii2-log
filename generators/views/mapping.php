<?php

/** @var array $mappings */

echo "<?php" ?>

namespace backend\modules\changelog\helpers;

use yii\base\InvalidConfigException;

/**
 * Class LogMappingHelper
 * @package backend\modules\changelog\helpers
 */
class LogMappingHelper
{
    /**
     * @var array
     */
    protected static $classNames = [<?php
        foreach ($mappings as $model => $searchClass) {
            echo "\n\t\t'{$modelName}' => '{$searchClass}',";
        }
        echo "\n";
    ?>
    ];

    /**
     * @param $modelClass
     * @return mixed
     * @throws
     */
    public static function getSearchClass($modelClass)
    {
        if (!$searchClass = static::$classNames[$modelClass] ?? false) {
            throw new InvalidConfigException();
        }

        return $searchClass;
    }
}
