<?php

namespace pvsaintpe\log;

use Yii;
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
        $app->getUrlManager()->addRules([
            'test' => 'changelog/default/index',
        ], false);

        $app->setModule('changelog', 'pvsaintpe\changelog\Module');
    }
}