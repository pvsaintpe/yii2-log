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

/**
 * @author Veselov Pavel
 */
class <?= $className ?> extends Migration
{
    use MigrationTrait;

    public function safeUp()
    {
<?php
    if (!empty($dropForeignKeys)) {
        // удаляем внешние ключи
        foreach ($dropForeignKeys as $key) {
            echo "\t\t\$this->dropForeignKey('{$key}', '{$logTableName}');\n";
        }

        echo "\n";
    }

    if (!empty($addColumns)) {
        // добавляем колонки
        foreach ($addColumns as $column) {
            if (!in_array($column['name'], $primaryKeys)) {
                $nullExp = 'NULL default null';
            } else {
                $nullExp = 'NOT NULL';
            }
            echo "\t\t\$this->addColumn(";
            echo "\n\t\t\t'{$logTableName}',";
            echo "\n\t\t\t'{$column['name']}',";
            echo "\n\t\t\t\"{$column['type']} {$nullExp} COMMENT '{$column['comment']}'\"";
            echo "\n\t\t);\n";
        }

        echo "\n";
    }

    if (!empty($updateColumns)) {
        // обновляем колонки
        foreach ($updateColumns as $column) {
            if (!in_array($column['name'], $primaryKeys)) {
                $nullExp = 'NULL default null';
            } else {
                $nullExp = 'NOT NULL';
            }
            echo "\t\t\$this->alterColumn(";
            echo "\n\t\t\t'{$logTableName}',";
            echo "\n\t\t\t'{$column['name']}',";
            echo "\n\t\t\t\"{$column['type']} {$nullExp} COMMENT '{$column['comment']}'\"";
            echo "\n\t\t);\n";
        }

        echo "\n";
    }

    if (!empty($removeColumns)) {
        // удаляем колонки
        foreach ($removeColumns as $column) {
            echo "\t\t\$this->dropColumn('{$logTableName}', '{$column}');\n";
        }

        echo "\n";
    }

    if (!empty($addForeignKeys)) {
        // добавляем внешние ключи
        foreach ($addForeignKeys as $column => $key) {
            echo "\t\t\$this->addForeignKey(";
            echo "\n\t\t\t'{$key['name']}',";
            echo "\n\t\t\t'{$logTableName}',";
            echo "\n\t\t\t['{$column}'],";
            echo "\n\t\t\t" . '$this->getStorageDb()->getName()' . " . '.{$key['relation_table']}',";
            echo "\n\t\t\t'{$key['relation_column']}'";
            echo "\n\t\t);\n";
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
