<?php

namespace pvsaintpe\log\console;

use yii\db\ActiveRecord;
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
    public $pathToMigrations;
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
        if ($data && preg_match('~class\s+(\w+)\s+extends\s+\w+\s*(implements\s+\w+\s*)?[,\s+]?\{~i', $data, $classMatch)) {
            $namespace = '';
            if (preg_match('~namespace\s+([^\s;]+)\s*;~i', $data, $namespaceMatch)) {
                $namespace = $namespaceMatch[1] . '\\';
            }
            return $namespace . $classMatch[1];
        }
        return false;
    }

    /**
     * Generate migrations for log-tables
     */
    public function actionIndex()
    {
        $iterator = new RecursiveDirectoryIterator(Yii::getAlias('@common/models'));
        $iterator = new RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $filename => $file) {
            if ($file->isFile() && preg_match('~.php$~', $file->getFilename())) {
                $className = $this->parseClassFile($filename);
                if ($className && class_exists($className)) {
                    $reflectionClass = new ReflectionClass($className);
                    if ($reflectionClass->isSubclassOf('yii\db\ActiveRecord')
                        && $reflectionClass->isSubclassOf('pvsaintpe\log\interfaces\ChangeLogInterface')
                    ) {
                        $this->classNames[] = $className;
                    }
                }
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
                    Yii::getAlias($this->pathToMigrations . '/' . $fileName . '.php'),
                    $view->render(
                        $params['view'],
                        array_merge(
                            array_diff_key($params, ['view' => 0]),
                            [
                                'className' => $fileName,
                            ]
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
