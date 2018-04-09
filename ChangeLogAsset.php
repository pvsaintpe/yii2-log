<?php

namespace pvsaintpe\log;

use yii\web\AssetBundle;

/**
 * Class ChangeLogAsset
 * @package backend\assets
 */
class ChangeLogAsset extends AssetBundle
{
    public $sourcePath = '@vendor/pvsaintpe/log/assets';

    public $css = [
        'css/log.css',
    ];
}
