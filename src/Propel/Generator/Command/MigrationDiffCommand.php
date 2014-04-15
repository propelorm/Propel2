<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Propel\Generator\Exception\RuntimeException;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Schema;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationDiffCommand extends AbstractCommand
{
    const DEFAULT_OUTPUT_DIRECTORY  = 'generated-migrations';

    const DEFAULT_MIGRATION_TABLE   = 'propel_migration';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('output-dir',       null, InputOption::VALUE_REQUIRED,  'The output directory', self::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('migration-table',  null, InputOption::VALUE_REQUIRED,  'Migration table name', self::DEFAULT_MIGRATION_TABLE)
            ->addOption('connection',       null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use', array())
            ->addOption('table-renaming',   null, InputOption::VALUE_NONE,  'Detect table renaming', null)
            ->addOption('editor',           null, InputOption::VALUE_OPTIONAL,  'The text editor to use to open diff files', null)
            ->setName('migration:diff')
            ->setAliases(array('diff'))
            ->setDescription('Generate diff classes')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generatorConfig = $this->getGeneratorConfig(array(
            'propel.platform.class'       => $input->getOption('platform'),
            'propel.reverse.parser.class' => $this->getReverseClass($input),
            'propel.migration.table'      => $input->getOption('migration-table')
        ), $input);

        $this->createDirectory($input->getOption('output-dir'));

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas($input->getOption('input-dir'), $input->getOption('recursive')));

        $connections = array();
        $optionConnections = $input->getOption('connection');
        if (!$optionConnections) {
            $connections = $generatorConfig->getBuildConnections($input->getOption('input-dir'));
        } else {
            foreach ($optionConnections as $connection) {
                list($name, $dsn, $infos) = $this->parseConnection($connection);
                $connections[$name] = array_merge(array('dsn' => $dsn), $infos);
            }
        }

        $manager->setConnections($connections);
        $manager->setMigrationTable($input->getOption('migration-table'));
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        if ($manager->hasPendingMigrations()) {
            throw new RuntimeException('Uncommitted migrations have been found ; you should either execute or delete them before rerunning the \'diff\' task');
        }

        $totalNbTables = 0;
        $reversedSchema = new Schema();

        foreach ($manager->getDatabases() as $appDatabase) {

            $name = $appDatabase->getName();
            if (!$params = @$connections[$name]) {
                $output->writeln(sprintf('<info>No connection configured for database "%s"</info>', $name));
            }

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Connecting to database "%s" using DSN "%s"', $name, $params['dsn']));
            }

            $conn     = $manager->getAdapterConnection($name);
            $platform = $generatorConfig->getConfiguredPlatform($conn, $name);

            if (!$platform->supportsMigrations()) {
                $output->writeln(sprintf('Skipping database "%s" since vendor "%s" does not support migrations', $name, $platform->getDatabaseType()));
                continue;
            }

            $additionalTables = [];
            foreach ($appDatabase->getTables() as $table) {
                if ($table->getSchema() && $table->getSchema() != $appDatabase->getSchema()) {
                    $additionalTables[] = $table;
                }
            }

            $database = new Database($name);
            $database->setPlatform($platform);
            $database->setSchema($appDatabase->getSchema());
            $database->setDefaultIdMethod(IdMethod::NATIVE);

            $parser   = $generatorConfig->getConfiguredSchemaParser($conn);
            $nbTables = $parser->parse($database, $additionalTables);

            $reversedSchema->addDatabase($database);
            $totalNbTables += $nbTables;

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('%d tables found in database "%s"', $nbTables, $name), Output::VERBOSITY_VERBOSE);
            }
        }

        if ($totalNbTables) {
            $output->writeln(sprintf('%d tables found in all databases.', $totalNbTables));
        } else {
            $output->writeln('No table found in all databases');
        }

        // comparing models
        $output->writeln('Comparing models...');
        $tableRenaming = $input->getOption('table-renaming');

        $migrationsUp   = array();
        $migrationsDown = array();
        foreach ($reversedSchema->getDatabases() as $database) {
            $name = $database->getName();

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Comparing database "%s"', $name));
            }

            if (!$appDataDatabase = $manager->getDatabase($name)) {
                continue;
            }

            $databaseDiff = DatabaseComparator::computeDiff($database, $appDataDatabase, false, $tableRenaming);

            if (!$databaseDiff) {
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf('Same XML and database structures for datasource "%s" - no diff to generate', $name));
                }
                continue;
            }

            $output->writeln(sprintf('Structure of database was modified in datasource "%s": %s', $name, $databaseDiff->getDescription()));

            foreach ($databaseDiff->getPossibleRenamedTables() as $fromTableName => $toTableName) {
                $output->writeln(sprintf(
                    '<info>Possible table renaming detected: "%s" to "%s". It will be deleted and recreated. Use --table-renaming to only rename it.</info>',
                        $fromTableName, $toTableName
                ));
            }

            $platform               = $generatorConfig->getConfiguredPlatform(null, $name);
            $migrationsUp[$name]    = $platform->getModifyDatabaseDDL($databaseDiff);
            $migrationsDown[$name]  = $platform->getModifyDatabaseDDL($databaseDiff->getReverseDiff());
        }

        if (!$migrationsUp) {
            $output->writeln('Same XML and database structures for all datasource - no diff to generate');

            return;
        }

        $timestamp = time();
        $migrationFileName  = $manager->getMigrationFileName($timestamp);
        $migrationClassBody = $manager->getMigrationClassBody($migrationsUp, $migrationsDown, $timestamp);

        $file = $input->getOption('output-dir') . DIRECTORY_SEPARATOR . $migrationFileName;
        file_put_contents($file, $migrationClassBody);

        $output->writeln(sprintf('"%s" file successfully created.', $file));

        if (null !== $editorCmd = $input->getOption('editor')) {
            $output->writeln(sprintf('Using "%s" as text editor', $editorCmd));
            shell_exec($editorCmd . ' ' . escapeshellarg($file));
        } else {
            $output->writeln('Please review the generated SQL statements, and add data migration code if necessary.');
            $output->writeln('Once the migration class is valid, call the "migrate" task to execute it.');
        }
    }

    /**
     * Return the name of the reverse parser class
     */
    protected function getReverseClass(InputInterface $input)
    {
        $reverse = $input->getOption('platform');
        if (false !== strpos($reverse, 'Platform')) {
            $reverse = strstr($input->getOption('platform'), 'Platform', true);
        }
        $reverse = sprintf('Propel\\Generator\\Reverse\\%sSchemaParser', ucfirst($reverse));

        return $reverse;
    }
}
