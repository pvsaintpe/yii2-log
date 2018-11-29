
**Installation**

The preferred way to install this extension is through composer.

Check the composer.json for this extension's requirements and dependencies. Read this web tip /wiki on setting the minimum-stability settings for your application's composer.json.
Either run

```BASH
$ php composer.phar require pvsaintpe/yii2-log "5.*"
```

or add

```JS
"pvsaintpe/yii2-log": "5.*"
```
to the require section of your composer.json file.

**Additional information**

All base models and query must be inherited from:

`ActiveRecord extends \pvsaintpe\log\components\ActiveRecord`
`ActiveQuery extends \pvsaintpe\log\components\ActiveQuery`

To track changes, add to your model:
```php
/**
 * @return bool
 */
public static function logEnabled()
{
    return true;
}
```

**Build project**

Every time when you change the database schema to be logged, run the command:

```BASH
#!/usr/bin/env bash
php ./yii changelog/generate
php ./yii changelog/migrate --interactive=0
```

**Customization**

1. To access the visual part, for example, to view revisions and change history of your data, add to the config:

```php
// backend/configs/main.php
return [
    'modules' => [
        'changelog' => [
            'class' => 'pvsaintpe\log\Module',
        ],
    ]
];
```

2. To fine-tune the logging system, use the configurator.
For a complete list of available options, see pvsaintpe\log\components\Configs:

```php
// common/configs/params.php
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

**Usage**

To activate all features of the component, use pvsaintpe\log\traits\SearchTrait in your Search-models.
It is recommended to keep the log data in a separate database, although you are not limited in this.