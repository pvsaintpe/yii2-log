<?php
/**
 * @author Veselov Pavel
 *
 * The following variables are available in this view:
 *
 * @var $className string the new migration class name without namespace
 * @var $logTableName string code for the migration
 * @var $tableName string code for the migration
 *
 * @var array $addColumns Список полей, которые нужно добавить
 * @var array $removeColumns Список полей, которые нужно удалить
 * @var array $updateColumns Список полей, которые необходимо обновить
 *
 * @var array $dropIndexes Список индексов, которые нужно удалить
 * @var array $dropForeignKeys Список внешних ключей, которые нужно удалить
 * @var array $createIndexes Список индексов, которые нужно добавить
 * @var array $addForeignKeys Список внешних ключей, которые нужно добавить
 * @var array $primaryKeys
 * @var array $keyNames
 */

echo "<?php\n";
?>

use pvsaintpe\db\components\Migration;
use pvsaintpe\log\traits\MigrationTrait;
use pvsaintpe\log\components\Configs;

/**
 * @author Veselov Pavel
 * @since 3.5.*
 */
class <?= $className ?> extends Migration
{
    use MigrationTrait;

    public function safeUp()
    {
<?php
    // удаляем внешние ключи
    if (!empty($dropForeignKeys)) {
        echo '        try {';
        echo "\n";
        foreach ($dropForeignKeys as $column => $key) {
            echo "            \$this->dropForeignKey('{$key}', '{$logTableName}');\n";
        }
        echo '        } catch (\Exception $e) {';
        echo "               // skip error if fk not exists \n";
        echo '        }';
        echo "\n";
    }

    // добавляем колонки
    if (!empty($addColumns)) {
        foreach ($addColumns as $column) {
            if (!in_array($column['name'], $primaryKeys)) {
                $nullExp = 'NULL default null';
            } else {
                $nullExp = 'NOT NULL';
            }
            echo "        \$this->addColumn(";
            echo "\n            '{$logTableName}',";
            echo "\n            '{$column['name']}',";
            echo "\n            \"{$column['type']} {$nullExp} COMMENT '{$column['comment']}'\"";
            echo "\n        );\n";
        }

        echo "\n";
    }

    // обновляем колонки
    if (!empty($updateColumns)) {
        foreach ($updateColumns as $column) {
            if (!in_array($column['name'], $primaryKeys)) {
                $nullExp = 'NULL default null';
            } else {
                $nullExp = 'NOT NULL';
            }
            echo "        \$this->alterColumn(";
            echo "\n            '{$logTableName}',";
            echo "\n            '{$column['name']}',";
            echo "\n            \"{$column['type']} {$nullExp} COMMENT '{$column['comment']}'\"";
            echo "\n        );\n";
        }

        echo "\n";
    }

    // удаляем колонки
    if (!empty($removeColumns)) {
        foreach ($removeColumns as $column) {
            echo "        \$this->dropColumn('{$logTableName}', '{$column}');\n";
        }

        echo "\n";
    }

    // добавляем внешние ключи
    if (!empty($addForeignKeys)) {
        foreach ($addForeignKeys as $column => $key) {
            echo "        \$this->addForeignKey(";
            echo "\n            null,";
            echo "\n            '{$logTableName}',";
            echo "\n            ['{$column}'],";
            echo "\n            " . '$this->getStorageDb()->getName()' . " . '.{$key['relation_table']}',";
            echo "\n            '{$key['relation_column']}',";
            echo "\n            static::CASCADE";
            echo "\n        );\n";
        }
    }
?>
    }

    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
