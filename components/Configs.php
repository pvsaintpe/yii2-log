<?php

namespace pvsaintpe\log\components;

use Yii;
use yii\base\BaseObject;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

/**
 * Configs
 * Used to configure some values. To set config you can use [[\yii\base\Application::$params]]
 *
 * ```
 * return [
 *     'changelog.configs' => [
 *         'db' => 'customDb',
 *         'storageDb' => 'customDb',
 *     ]
 * ];
 * ```
 *
 * or use [[\Yii::$container]]
 *
 * ```
 * Yii::$container->set('pvsaintpe\log\components\Configs',[
 *     'db' => 'customDb',
 *     'storageDb' => 'customDb',
 *     ...
 * ]);
 * ```
 *
 * @author Pavel Veselov <pvsaintpe@icloud.com>
 * @since 3.0
 */
class Configs extends BaseObject
{
    /**
     * @var Connection Database connection for Log Storage.
     */
    public $db = 'db';

    /**
     * @var string
     */
    public $id = 'changelog';

    /**
     * Application Id for Backend
     * @var string
     */
    public $appId = 'app-backend';

    /**
     * @var string
     */
    public $tableSuffix = '_log';

    /**
     * @var string
     */
    public $tablePrefix = '';

    /**
     * @var string
     */
    public $modelsPath = '@common/models';

    /**
     * @var string
     */
    public $migrationPath = '@console/migrations-log';

    /**
     * @var string
     */
    public $pathToRoute = '/changelog/default/index';

    /**
     * @var Connection Database connection for Data Storage.
     */
    public $storageDb = 'db';

    /**
     * @var string
     */
    public $createTemplatePath = '/../views/migration-create-log.php';

    /**
     * @var string
     */
    public $updateTemplatePath = '/../views/migration-update-log.php';

    /**
     * @todo addOption for createTable in first migration
     * @var string Admin table name.
     */
    public $adminTable = 'admin';

    /**
     * @var string Admin table name.
     */
    public $adminClass = '\pvsaintpe\log\models\Admin';

    /**
     * @var string
     */
    public $adminPageRoute = 'operator/operator/view';

    /**
     * @var int
     */
    public $revisionPeriod = 1;

    /**
     * @var int
     */
    public $defaultPageSize = 10;

    /**
     * @var string
     */
    public $urlHelperClass = '\pvsaintpe\helpers\Url';

    /**
     * @var string Admin Column (Reference) table name.
     */
    public $adminColumn = 'updated_by';

    /**
     * @var string Admin Column (Reference) table name.
     */
    public $adminColumnType = "INT(10) UNSIGNED NULL DEFAULT NULL COMMENT 'Оператор'";

    /**
     * @var array
     */
    public $cssOptions = [
        'revisionOptions' => [
            'style' => 'color:lightgray',
            'class' => 'glyphicon glyphicon-eye'
        ],
        'revisionActiveOptions' => [
            'style' => 'color:black',
            'class' => 'glyphicon glyphicon-eye-open'
        ]
    ];

    /**
     * @var self Instance of self
     */
    private static $instance;

    /**
     * @inheritdoc
     */
    public function init()
    {
    }

    /**
     * @return object|Configs
     * @throws \yii\base\InvalidConfigException
     */
    public static function instance()
    {
        if (self::$instance === null) {
            $type = ArrayHelper::getValue(Yii::$app->params, 'changelog.configs', []);
            if (is_array($type) && !isset($type['class'])) {
                $type['class'] = static::class;
            }

            return self::$instance = Yii::createObject($type);
        }

        return self::$instance;
    }

    /**
     * @return \pvsaintpe\db\components\Connection|Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    public static function db()
    {
        return Yii::$app->get(static::instance()->db);
    }

    /**
     * @return \pvsaintpe\db\components\Connection|Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    public static function storageDb()
    {
        return Yii::$app->get(static::instance()->storageDb);
    }
}
