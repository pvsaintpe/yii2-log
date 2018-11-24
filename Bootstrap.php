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
        //Правила маршрутизации
        $app->getUrlManager()->addRules([
            'changelog' => 'changelog\default\index',
        ], true);

        $app->setModule('changelog', ['class' => 'pvsaintpe\log\Module']);
    }
}
