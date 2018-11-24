<?php

namespace pvsaintpe\log\components;

use Yii;
use yii\base\BaseObject;
use yii\caching\Cache;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\rbac\ManagerInterface;

/**
 * Configs
 * Used to configure some values. To set config you can use [[\yii\base\Application::$params]]
 *
 * ```
 * return [
 *
 *     'changelog.configs' => [
 *         'db' => 'customDb',
 *         'storageDb' => 'customDb',
 *         'cache' => [
 *             'class' => 'yii\caching\DbCache',
 *             'db' => ['dsn' => 'sqlite:@runtime/admin-cache.db'],
 *         ],
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
    const CACHE_TAG = 'changelog';

    /**
     * @var Connection Database connection for Log Storage.
     */
    public $db = 'db';

    /**
     * @var string
     */
    public $id = 'changelog';

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
    public $classNamespace = '\common\models\log';

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
     * @var Cache Cache component.
     */
    public $cache = 'cache';

    /**
     * @var string
     */
    public $createTemplatePath = '/../views/migration-create-log.php';

    /**
     * @var string
     */
    public $updateTemplatePath = '/../views/migration-update-log.php';

    /**
     * @var integer Cache duration. Default to a hour.
     */
    public $cacheDuration = 3600;

    /**
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
     * @var string
     */
    public $revisionActiveStyle = 'color:black';

    /**
     * @var string
     */
    public $revisionStyle = 'color:lightgray';

    /**
     * @var array
     */
    public $options;

    /**
     * @var array|false
     */
    public $advanced;

    /**
     * @var self Instance of self
     */
    private static $_instance;

    /**
     * @var array
     */
    private static $_classes = [
        'cache' => 'yii\caching\Cache',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (self::$_classes as $key => $class) {
            try {
                $this->{$key} = empty($this->{$key}) ? null : Instance::ensure($this->{$key}, $class);
            } catch (\Exception $exc) {
                $this->{$key} = null;
                Yii::error($exc->getMessage());
            }
        }
    }

    /**
     * @return object|Configs
     * @throws \yii\base\InvalidConfigException
     */
    public static function instance()
    {
        if (self::$_instance === null) {
            $type = ArrayHelper::getValue(Yii::$app->params, 'changelog.configs', []);
            if (is_array($type) && !isset($type['class'])) {
                $type['class'] = static::class;
            }

            return self::$_instance = Yii::createObject($type);
        }

        return self::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = static::instance();
        if ($instance->hasProperty($name)) {
            return $instance->$name;
        } else {
            if (count($arguments)) {
                $instance->options[$name] = reset($arguments);
            } else {
                return array_key_exists($name, $instance->options) ? $instance->options[$name] : null;
            }
        }
    }

    /**
     * @return Connection
     * @throws \yii\base\InvalidConfigException
     */
    public static function db()
    {
        return Yii::$app->get(static::instance()->db);
    }

    /**
     * @return Connection
     * @throws \yii\base\InvalidConfigException
     */
    public static function storageDb()
    {
        return Yii::$app->get(static::instance()->storageDb);
    }

    /**
     * @return Cache
     * @throws \yii\base\InvalidConfigException
     */
    public static function cache()
    {
        return static::instance()->cache;
    }
}
