<?= '<?php' ?>

use Propel\Generator\Manager\MigrationManager;

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version <?= $timestamp ?>.
 * Generated on <?= $timeInWords ?> <?= $migrationAuthor ?> 
 */
class <?= $migrationClassName ?> 
{
    public $comment = '<?= $commentString ?>';

    public function preUp(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    public function postUp(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    public function preDown(MigrationManager $manager)
    {
        // add the pre-migration code here
    }

    public function postDown(MigrationManager $manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
<?php foreach($migrationsUp as $connectionName => $sql): ?>
        <?= $connectionToVariableName[$connectionName] ?> = <<< 'EOT'
<?= $sql ?>
EOT;

<?php endforeach;?>
        return array(
<?php foreach($connectionToVariableName as $connectionName => $variableName): ?>
            '<?= $connectionName ?>' => <?= $variableName ?>,
<?php endforeach;?>
        );
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
<?php foreach($migrationsDown as $connectionName => $sql): ?>
        <?= $connectionToVariableName[$connectionName] ?> = <<< 'EOT'
<?= $sql ?>
EOT;

<?php endforeach;?>
        return array(
<?php foreach($connectionToVariableName as $connectionName => $variableName): ?>
            '<?= $connectionName ?>' => <?= $variableName ?>,
<?php endforeach;?>
        );
    }

}