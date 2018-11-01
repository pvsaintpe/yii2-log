<?php

namespace pvsaintpe\log\assets;

use yii\web\AssetBundle;

/**
 * Class ChangeLogAsset
 * @package backend\assets
 */
class ChangeLogAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@vendor/pvsaintpe/yii2-log/assets';

    /**
     * @var array
     */
    public $css = [
        'css/log.css',
    ];
}
