<?php

namespace pvsaintpe\log;

use pvsaintpe\log\components\Configs;
use yii\base\BootstrapInterface;
use yii\base\Application;

/**
 * Class Bootstrap
 * @package pvsaintpe\log
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     */
    public function bootstrap($app)
    {
        $app->setModule(Configs::instance()->id, ['class' => 'pvsaintpe\log\Module']);
    }
}
