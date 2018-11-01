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
 *     'mdm.admin.configs' => [
 *         'db' => 'customDb',
 *         'menuTable' => '{{%admin_menu}}',
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
 * Yii::$container->set('mdm\admin\components\Configs',[
 *     'db' => 'customDb',
 *     'menuTable' => 'admin_menu',
 * ]);
 * ```
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */

class Configs extends BaseObject
{
    const CACHE_TAG = 'changelog';

    /**
     * @var Connection Database connection for Log Storage.
     */
    public $db = 'dbLog';

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
    public $createTemplatePath = __DIR__ . '/../views/migration-create-log.php';

    /**
     * @var string
     */
    public $updateTemplatePath = __DIR__ . '/../views/migration-update-log.php';

    /**
     * @var integer Cache duration. Default to a hour.
     */
    public $cacheDuration = 3600;

    /**
     * @var string Menu table name.
     */
    public $adminTable = '{{%admin}}';

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
        'db' => 'pvsaintpe\db\components\Connection',
        'storageDb' => 'pvsaintpe\db\components\Connection',
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
        return static::instance()->db;
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function storageDb()
    {
        return static::instance()->storageDb;
    }

    /**
     * @return Cache
     * @throws \yii\base\InvalidConfigException
     */
    public static function cache()
    {
        return static::instance()->cache;
    }

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function adminTable()
    {
        return static::instance()->adminTable;
    }
}
