<?php

namespace pvsaintpe\log;

use yii\base\BootstrapInterface;

/**
 * Class Bootstrap
 * @package pvsaintpe\log
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        $app->setModule('changelog', ['class' => 'pvsaintpe\log\Module']);
        $app->setViewPath(__DIR__ . '/views');
    }
}
