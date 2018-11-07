
Extends BaseActiveRecord from pvsaintpe\log\components\ActiveRecord

Extends BaseActiveQuery from pvsaintpe\log\components\ActiveQuery

**Create in your project ActiveQueryLog  extends your BaseActiveQuery**

```php
namespace common\components;

/**
 * Class ActiveQueryLog
 * @package common\components
 */
class ActiveQueryLog extends ActiveQuery
{
}
```

**Create in your project ActiveRecordLog extends your BaseActiveRecord**

```php
namespace common\components;

use common\models\Admin;
use pvsaintpe\log\components\Configs;

/**
 * Class ActiveRecordLog
 * @package common\components
 */
class ActiveRecordLog extends ActiveRecord
{
    /**
     * @return bool
     */
    public function logEnabled()
    {
        return false;
    }

    /**
     * @return \common\models\query\AdminQuery|\yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(Admin::class, ['id' => Configs::instance()->adminColumn]);
    }
    
    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function customBehaviors()
    {
        $behaviors = [];
        if (Yii::$app->id == 'app-backend' && Yii::$app->getUser() instanceof \backend\components\WebUser) {
            $behaviors['blameable'] = [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => Configs::instance()->adminColumn
            ];
        }
        return $behaviors;
    }

    /**
     * @param array $conditions
     * @return array
     */
    public static function getLastChanges($conditions = [])
    {
        $query = static::find();
        foreach ($conditions as $attribute => $condition) {
            if (is_array($condition)) {
                if ($condition[0] == 'NOT') {
                    $arrayKeys = array_keys($condition[1]);
                    $arrayValues = array_values($condition[1]);
                    $query->andWhere([
                        $condition[0],
                        [$query->a($arrayKeys[0]) => $arrayValues[0][1]],
                    ]);
                } else {
                    if (count($condition) === 2) {
                        $query->andWhere([
                            $query->a($attribute) => $condition
                        ]);
                    } else {
                        $query->andWhere([
                            $condition[0],
                            $query->a($condition[1]),
                            $condition[2]
                        ]);
                    }
                }
            } else {
                $query->andFilterWhere([
                    $query->a($attribute) => $condition
                ]);
            }
        }
        return $query->all() ?: [];
    }
}
```

**Add to BaseActiveRecord Model**

```php
/**
 * @param null $attribute
 * @param array $where
 * @return int|void
 */
public static function getLastRevisionCount($attribute = null, $where = [])
{
    $model = new static();
    if ($model->logEnabled() && Yii::$app->user->can('changelog')) {
        /** @var ActiveRecordLog $logClassName */
        $logClassName = $model->getLogClassName();
        return count($logClassName::getLastChanges(array_merge([
            ['>=', 'timestamp', new Expression('NOW() - INTERVAL 1 DAY')],
        ], $where,!$attribute ? [] : [['NOT', [$attribute => null]]])));
    }
    return 0;
}
```

**build.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project name="..." default="develop">
    <property name="project.id" value="1"/>
    <target name="develop">
        <phingcall target="generate-logs"/>
        <phingcall target="migrate-log"/>
    </target>
    
    <target name="migrate-log">
        <exec passthru="true" command="php ./yii changelog/migrate-log --interactive=0"/>
    </target>

    <target name="generate-logs">
        <exec passthru="true" command="php ./yii changelog/generate --interactive=0"/>
    </target>
</project>
```

**migrate-local.sh**

```BASH
#!/usr/bin/env bash
php ./yii migrate/up 1 --interactive=0
php ./yii rbac/init
php ./yii migrate --interactive=0
php ./yii changelog/migrate-log --interactive=0
php ./yii cache/flush cache --interactive=0
```

**generate.sh**

```BASH
#!/bin/sh

DIR=$(dirname $0)

### models-log (log) ##############################

MODELS_LOG_BASE_OPTIONS='
    --ns=common\models\log\base
    --tableName=payproc_log.*
    --baseClass=common\components\ActiveRecordLog
    --queryNs=common\models\log\query\base
    --queryBaseClass=common\components\ActiveQueryLog
    --enableI18N=1
    --messageCategory=models-log'

MODELS_LOG_CUSTOM_OPTIONS='
    --baseModelClass=common\models\log\base\*Base'

$DIR/yii gii/base_model --interactive=0 $MODELS_LOG_BASE_OPTIONS
$DIR/yii gii/custom_model --interactive=0 $MODELS_LOG_CUSTOM_OPTIONS
$DIR/yii gii/base_model --interactive=0 --overwrite=1 $MODELS_LOG_BASE_OPTIONS
$DIR/yii gii/base_model --interactive=0 --overwrite=1 $MODELS_LOG_BASE_OPTIONS
```

**backend/configs/main.php**

```php
return [
    'modules' => [
        'changelog' => [
            'class' => 'pvsaintpe\log\Module',
        ],
    ]
];
```

**common/configs/params.php**

```php
return [
    'changelog.configs' => [
        'db' => 'dbLog', // DB Storage for Log-tables
        'storageDb' => 'db', // DB Storage for Data-tables
        'adminTable' => 'admin', // Table Name for Admin's
        'tablePrefix' => '_log',
        'adminColumn => 'updated_by',
    ],
];
```

**console/configs/main-local.php**

```php
return [
    'modules' => [
        'gii' => [
            'class' => 'pvsaintpe\gii\plus\Module',
        ],
        'changelog' => [
            'class' => 'pvsaintpe\log\Module',
            'controllerMap' => [
                'migrate-log' => [
                    'class' => 'pvsaintpe\log\console\MigrateController',
                    'migrationPath' => '@app/migrations-log',
                ],
                'generate' => [
                    'class' => 'pvsaintpe\log\console\GenerateController',
                    'migrationPath' => '@app/migrations-log',
                ],
            ]
        ]
    ],
];
```