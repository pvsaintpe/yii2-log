<?php

namespace pvsaintpe\log\console;

use pvsaintpe\log\components\ActiveRecord;
use pvsaintpe\log\components\Configs;
use pvsaintpe\log\interfaces\ChangeLogInterface;
use Yii;
use yii\console\Controller;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Class GenerateController
 * @package pvsaintpe\log\controllers
 */
class GenerateController extends Controller
{
    /**
     * @var
     */
    public $migrationPath;

    /**
     * @var array
     */
    protected $classNames = [];

    /**
     * @param string $filename
     * @return string|false
     */
    public function parseClassFile($filename)
    {
        $data = file_get_contents($filename);
        if ($data && preg_match('~class\s+(\w+)\s+extends\s+\w+\s*(implements\s+[A-Za-z_,\s\\\\]+)?\{~i', $data, $classMatch)) {
            $namespace = '';
            if (preg_match('~namespace\s+([^\s;]+)\s*;~i', $data, $namespaceMatch)) {
                $namespace = $namespaceMatch[1] . '\\';
            }
            return $namespace . $classMatch[1];
        }
        return false;
    }

    /**
     * @return int
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function actionIndex()
    {
        $parents = [];
        $iterator = new RecursiveDirectoryIterator(Yii::getAlias(Configs::instance()->modelsPath));
        $iterator = new RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $filename => $file) {
            if ($file->isFile() && preg_match('~.php$~', $file->getFilename())) {
                $className = $this->parseClassFile($filename);
                if ($className && class_exists($className)) {
                    $reflectionClass = new ReflectionClass($className);
                    if ($reflectionClass->isSubclassOf('pvsaintpe\log\components\ActiveRecord')
                        && $reflectionClass->isSubclassOf('pvsaintpe\log\interfaces\ChangeLogInterface')
                    ) {
                        $this->classNames[$className] = $className;
                        $parents[$className] = $reflectionClass->getParentClass()->getName();
                    }
                }
            }
        }

        // исключаем классы двойники (Васю наказать!!!)
        foreach ($parents as $className => $parentClassName) {
            if (in_array($parentClassName, $this->classNames)) {
                unset($this->classNames[$className]);
            }
        }

        $view = $this->getView();
        $migrations = 0;
        foreach ($this->classNames as $className) {
            /* @var $class ActiveRecord|ChangeLogInterface */
            $class = new $className;
            if (!$class->logEnabled()) {
                continue;
            }

            if ($params = $class->createLogTable()) {
                $fileName = 'm' . date('ymd_his', time()) . '_'. $params['migration_prefix'] . '_' . $params['logTableName'];
                if (@file_put_contents(
                    Yii::getAlias($this->migrationPath . '/' . $fileName . '.php'),
                    $view->render(
                        $params['view'],
                        array_merge(
                            array_diff_key($params, ['view' => 0]),
                            ['className' => $fileName]
                        )
                    )
                )) {
                    $migrations++;
                }
            }
        }

        if ($migrations) {
            echo "{$migrations} new migrations was created successfully.", PHP_EOL;
        } else {
            echo 'New migrations not found.', PHP_EOL;
        }

        return 0;
    }
}
