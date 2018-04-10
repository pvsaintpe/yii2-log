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

use pvsaintpe\log\components\Migration;

/**
 * @author Veselov Pavel
 */
class <?= $className ?> extends Migration
{
    /** @var string */
    protected $dbName;

    /**
     * @return bool|string
     * @throws
     */
    private function getDbOrigName()
    {
        if (!$this->dbName) {
            $db = Yii::$app->db;
            parse_str(str_replace(';', '&', substr(strstr($db->dsn, ':'), 1)), $dsn);
            if (!array_key_exists('host', $dsn) || !array_key_exists('port', $dsn) || !array_key_exists('dbname', $dsn)) {
                throw new Exception('Log Database not found');
            }

            $this->dbName = $dsn['dbname'];
        }

        return $this->dbName;
    }

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

    /*
    if (!empty($dropIndexes)) {
        // удаляем индексы кроме (primary)
        foreach ($dropIndexes as $index) {
            echo "\t\t\$this->dropIndex('{$index}', '{$logTableName}');\n";
        }

        echo "\n";
    }
    */

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

    /*
    if (!empty($createIndexes)) {
        // добавляем индексы
        foreach ($createIndexes as $index) {
            echo "\t\t\$this->createIndex(";
            echo "\n\t\t\t'{$keyNames[$index]}',";
            echo "\n\t\t\t'{$logTableName}',";
            echo "\n\t\t\t'{$index}'";
            echo "\n\t\t);\n";
        }

        echo "\n";
    }
    */

    if (!empty($addForeignKeys)) {
        // добавляем внешние ключи
        foreach ($addForeignKeys as $column => $key) {
            echo "\t\t\$this->addForeignKey(";
            echo "\n\t\t\t'{$key['name']}',";
            echo "\n\t\t\t'{$logTableName}',";
            echo "\n\t\t\t['{$column}'],";
            echo "\n\t\t\t\$this->getDbOrigName() . '.{$key['relation_table']}',";
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
