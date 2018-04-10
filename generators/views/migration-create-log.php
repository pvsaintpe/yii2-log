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
        $this->db->createCommand("
            CREATE TABLE `<?= $logTableName?>`
            LIKE `". $this->getDbOrigName() . "`.`<?= $tableName?>`

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
            $this->getDbOrigName() . '.<?= $tableName?>',
            ['<?= join("','", $primaryKeys)?>']
        );
    }

    public function safeDown()
    {
        echo "<?= $className ?> cannot be reverted.\n";
        return false;
    }
}
