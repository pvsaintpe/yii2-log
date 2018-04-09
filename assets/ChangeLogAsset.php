<?php

namespace pvsaintpe\log\assets;

use yii\web\AssetBundle;

/**
 * Class ChangeLogAsset
 * @package backend\assets
 */
class ChangeLogAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/dist';

    public $css = [
        'css/log.css',
    ];
}
