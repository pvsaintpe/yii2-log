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

/**
 * @author Veselov Pavel
 */
class <?= $className ?> extends Migration
{
    public function safeUp()
    {
        $this->db->createCommand("
            CREATE TABLE `<?= $logTableName?>`
            LIKE `". Yii::$app->db->getName() . "`.`<?= $tableName?>`

        ")->execute();

<?php
    foreach ($columns as $column) {
        if (!in_array($column['Field'], $primaryKeys)) {
            $nullExp = 'NULL default null';
        } else {
            $nullExp = 'NOT NULL';
        }
        echo "\t\t\$this->alterColumn(";
        echo "\n\t\t\t'{$logTableName}',";
        echo "\n\t\t\t'{$column['Field']}',";
        echo "\n\t\t\t\"{$column['Type']} {$nullExp} COMMENT '{$column['Comment']}'\"";
        echo "\n\t\t);\n\n";
    }
?>
        $this->db->createCommand("
            ALTER TABLE `<?= $logTableName?>`
            DROP PRIMARY KEY
        ")->execute();

<?php
    foreach ($uniqueKeys as $uniqueKey) {
        echo "\t\t\$this->dropIndex('{$uniqueKey}', '{$logTableName}');\n\n";
    }
?>
        $this->db->createCommand("
            ALTER TABLE `<?= $logTableName?>`
            ADD COLUMN `log_id` BIGINT UNSIGNED NOT NULL
            AUTO_INCREMENT
            FIRST,
            ADD PRIMARY KEY (`log_id`)
        ")->execute();

        $this->db->createCommand("ALTER TABLE `<?= $logTableName?>` DROP IF EXISTS `created_at`")->execute();
        $this->db->createCommand("ALTER TABLE `<?= $logTableName?>` DROP IF EXISTS `updated_at`")->execute();
        $this->db->createCommand("ALTER TABLE `<?= $logTableName?>` DROP IF EXISTS `timestamp`")->execute();

        $this->db->createCommand("
            ALTER TABLE `<?= $logTableName?>`
            ADD COLUMN `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            COMMENT 'Метка времени'
        ")->execute();

        $this->addForeignKey(
            'fk-reference-<?= $tableName?>',
            '<?= $logTableName?>',
            ['<?= join("','", $primaryKeys)?>'],
            Yii::$app->db->getName() . '.<?= $tableName?>',
            ['<?= join("','", $primaryKeys)?>']
        );
    }

    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
