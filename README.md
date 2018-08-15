
Step 1
----------------------------
1. Download the package using composer:
    ```json
    {
    ...
    "require": {
      "pvsaintpe/yii2-log": "2.*",
      ...
    },
    ...
    ```
2. Update the module configuration file for the @backend application, if you want to modify its components:
    ```php
    ...
    return [
        ...
        'modules' => [
            'changelog' => [
                'class' => 'pvsaintpe\log\Module',
            ],
            ...
        ],
        ...
    ];
    ```
3. Update the module configuration file for the @common application:
    ```php
    return [
        ...
        'components' => [
            ...
            'dbLog' => [
                'class' => 'pvsaintpe\log\components\Connection',
                'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=log',
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8',
                'enableSchemaCache' => true,
                ...
            ],
            ...
        ],
        ...
    ];
    ```
4. Update the module configuration file for the @console application, if you want to its components:
    ```php
    ...
    return [
        ...
        'controllerMap' => [
            'migrate-log' => [
                'class' => 'pvsaintpe\log\console\MigrateController',
                'migrationPath' => '@app/migrations-log',
                'db' => 'dbLog',
            ],
            'generate' => [
                'class' => 'pvsaintpe\log\console\GenerateController',
                'migrationPath' => '@app/migrations-log',
            ],
            ...
        ],
        ...
    ];
    ```
5. Add a section to the build.xml assembly, to automatically generate migration files for new logging tables:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<project name="..." default="develop">
    <property name="project.id" value="1"/>
    <target name="develop">
        ...
        <phingcall target="generate-logs"/>
        <phingcall target="migrate-log"/>
        ...
    </target>
    ...
    <target name="migrate-log">
        <exec passthru="true"
              command="php ./yii migrate-log --interactive=0"/>
    </target>
    <target name="generate-logs">
        <exec passthru="true"
              command="php ./yii generate --interactive=0"/>
    </target>
    ...
```

Step 2
----------------------------
1. Implement the ActiveRecord base class from the interface:
    ```php
    use pvsaintpe\log\interfaces\ChangeLogInterface;
    
    ...
    class ActiveRecord extends \yii\db\ActiveRecord implements ChangeLogInterface
    {
      ...
    }
    ```
2. Implement the ChangeLogInterface interface using ChangeLogTrait:
    ```php
    use pvsaintpe\log\interfaces\ChangeLogInterface;
    use pvsaintpe\log\traits\ChangeLogTrait;
    ...
    class ActiveRecord extends \yii\db\ActiveRecord implements ChangeLogInterface
    {
       use ChangeLogTrait;
       ...
    }
    ```
3. Use the logging functionality to override the **beforeSave()** method in the ActiveRecord base class:

    ```php
    use pvsaintpe\log\interfaces\ChangeLogInterface;
    use pvsaintpe\log\traits\ChangeLogTrait;
    ...
    class ActiveRecord extends \yii\db\ActiveRecord implements ChangeLogInterface
    {
       use ChangeLogTrait;
       ...
       /**
         * @param bool $insert
         * @return bool
         */
        public function beforeSave($insert)
        {
            if (!$this->getIsNewRecord()) {
                **static::saveToLog();
            }
            return parent::beforeSave($insert);
        }
       ...
    }
    
    ```
4. 