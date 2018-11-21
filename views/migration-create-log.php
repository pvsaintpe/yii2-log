<?php
/**
 * @author Veselov Pavel
 *
 * The following variables are available in this view:
 * @var $className string the new migration class name without namespace
 * @var $columns array the new migration class namespace
 * @var $primaryKeys array the new migration class namespace
 * @var $uniqueKeys array the new migration class namespace
 * @var $logTableName string code for the migration
 * @var $tableName string code for the migration
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
        $showTable = $storageDb->selectOne("SHOW CREATE TABLE {$tableName}");
        $createTable = preg_replace("~(,[\s]+CONSTRAINT.*[\)\s]+ENGINE)~s", ') ENGINE', $showTable['Create Table']);
?>
        $this->execute("
<?= $createTable?>

        ");

<?php
    foreach ($columns as $column) {
        if (!in_array($column['Field'], $primaryKeys)) {
            $nullExp = 'NULL default null';
        } else {
            $nullExp = 'NOT NULL';
        }
        echo "        \$this->alterColumn(";
        echo "\n            '{$logTableName}',";
        echo "\n            '{$column['Field']}',";
        echo "\n            \"{$column['Type']} {$nullExp} COMMENT '{$column['Comment']}'\"";
        echo "\n        );\n\n";
    }
?>
        if ($this->existKey('<?= $logTableName?>', 'PRIMARY')) {
            $this->execute("ALTER TABLE `<?= $logTableName?>` DROP PRIMARY KEY");
        }

<?php
    foreach ($uniqueKeys as $uniqueKey) {
        echo "        \$this->dropIndex('{$uniqueKey}', '{$logTableName}');\n\n";
    }
?>
        $this->db->createCommand("
            ALTER TABLE `<?= $logTableName?>`
            ADD COLUMN `log_id` BIGINT UNSIGNED NOT NULL
            AUTO_INCREMENT
            FIRST,
            ADD PRIMARY KEY (`log_id`)
        ")->execute();

        $this->dropColumns('<?= $logTableName?>', [
            'created_at',
            'created_by',
            'updated_at',
            'updated_by',
            'timestamp',
            Configs::instance()->adminColumn,
        ]);

        $this->addColumn(
            '<?= $logTableName?>',
            'timestamp',
            "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Метка времени'"
        );

        $this->addColumn(
            '<?= $logTableName?>',
            Configs::instance()->adminColumn,
            Configs::instance()->adminColumnType
        );

        $this->addForeignKey(
            $this->generateKeyName('<?= $logTableName?>', 'fk-reference-by'),
            '<?= $logTableName?>',
            Configs::instance()->adminColumn,
            $this->getStorageDb()->getName() . '.' . Configs::instance()->adminTable,
            'id',
            static::SET_NULL
        );

        $this->addForeignKey(
            $this->generateKeyName('<?= $logTableName?>', 'fk-reference-to'),
            '<?= $logTableName?>',
            ['<?= join("','", $primaryKeys)?>'],
            $this->getStorageDb()->getName() . '.<?= $tableName?>',
            ['<?= join("','", $primaryKeys)?>'],
            static::CASCADE
        );
    }

    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
