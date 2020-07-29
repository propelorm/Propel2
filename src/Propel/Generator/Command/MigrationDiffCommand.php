<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Common\Config\ConfigurationManager;
use Propel\Generator\Exception\RuntimeException;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Schema;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationDiffCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('schema-dir', null, InputOption::VALUE_REQUIRED, 'The directory where the schema files are placed')
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'The output directory where the migration files are located')
            ->addOption('migration-table', null, InputOption::VALUE_REQUIRED, 'Migration table name', null)
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use. Example: \'bookstore=mysql:host=127.0.0.1;dbname=test;user=root;password=foobar\' where "bookstore" is your propel database name (used in your schema.xml)', [])
            ->addOption('table-renaming', null, InputOption::VALUE_NONE, 'Detect table renaming', null)
            ->addOption('editor', null, InputOption::VALUE_OPTIONAL, 'The text editor to use to open diff files', null)
            ->addOption('skip-removed-table', null, InputOption::VALUE_NONE, 'Option to skip removed table from the migration')
            ->addOption('skip-tables', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of excluded tables', [])
            ->addOption('disable-identifier-quoting', null, InputOption::VALUE_NONE, 'Disable identifier quoting in SQL queries for reversed database tables.')
            ->addOption('comment', 'm', InputOption::VALUE_OPTIONAL, 'A comment for the migration', '')
            ->addOption('suffix', null, InputOption::VALUE_OPTIONAL, 'A suffix for the migration class', '')
            ->setName('migration:diff')
            ->setAliases(['diff'])
            ->setDescription('Generate diff classes');
    }

    /**
     * @inheritDoc
     *
     * @throws \Propel\Generator\Exception\RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configOptions = [];

        if ($this->hasInputOption('connection', $input)) {
            foreach ($input->getOption('connection') as $conn) {
                $configOptions += $this->connectionToProperties($conn);
            }
        }

        if ($this->hasInputOption('migration-table', $input)) {
            $configOptions['propel']['migrations']['tableName'] = $input->getOption('migration-table');
        }

        if ($this->hasInputOption('schema-dir', $input)) {
            $configOptions['propel']['paths']['schemaDir'] = $input->getOption('schema-dir');
        }

        if ($this->hasInputOption('output-dir', $input)) {
            $configOptions['propel']['paths']['migrationDir'] = $input->getOption('output-dir');
        }

        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas($generatorConfig->getSection('paths')['schemaDir'], $generatorConfig->getSection('generator')['recursive']));

        $connections = [];
        $optionConnections = $input->getOption('connection');
        if (!$optionConnections) {
            $connections = $generatorConfig->getBuildConnections();
        } else {
            foreach ($optionConnections as $connection) {
                [$name, $dsn, $infos] = $this->parseConnection($connection);
                $connections[$name] = array_merge(['dsn' => $dsn], $infos);
            }
        }

        $manager->setConnections($connections);
        $manager->setMigrationTable($generatorConfig->getConfigProperty('migrations.tableName'));
        $manager->setWorkingDirectory($generatorConfig->getSection('paths')['migrationDir']);

        if ($manager->hasPendingMigrations()) {
            throw new RuntimeException('Uncommitted migrations have been found ; you should either execute or delete them before rerunning the \'diff\' task');
        }

        $totalNbTables = 0;
        $reversedSchema = new Schema();

        foreach ($manager->getDatabases() as $appDatabase) {
            $name = $appDatabase->getName();
            $params = $connections[$name] ?? [];
            if (!$params) {
                $output->writeln(sprintf('<info>No connection configured for database "%s"</info>', $name));
            }

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Connecting to database "%s" using DSN "%s"', $name, $params['dsn']));
            }

            $conn = $manager->getAdapterConnection($name);
            $platform = $generatorConfig->getConfiguredPlatform($conn, $name);

            if ($platform && !$platform->supportsMigrations()) {
                $output->writeln(sprintf('Skipping database "%s" since vendor "%s" does not support migrations', $name, $platform->getDatabaseType()));

                continue;
            }

            $additionalTables = [];
            foreach ($appDatabase->getTables() as $table) {
                if ($table->getSchema() && $table->getSchema() != $appDatabase->getSchema()) {
                    $additionalTables[] = $table;
                }
            }

            if ($input->getOption('disable-identifier-quoting')) {
                $platform->setIdentifierQuoting(false);
            }

            $database = new Database($name);
            $database->setPlatform($platform);
            $database->setSchema($appDatabase->getSchema());
            $database->setDefaultIdMethod(IdMethod::NATIVE);

            $parser = $generatorConfig->getConfiguredSchemaParser($conn, $name);
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

        $migrationsUp = [];
        $migrationsDown = [];
        $removeTable = !$input->getOption('skip-removed-table');
        $excludedTables = $input->getOption('skip-tables');
        $configManager = new ConfigurationManager($input->getOption('config-dir'));
        $excludedTables = array_merge((array)$excludedTables, (array)$configManager->getSection('exclude_tables'));

        foreach ($reversedSchema->getDatabases() as $database) {
            $name = $database->getName();

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Comparing database "%s"', $name));
            }

            if (!$appDataDatabase = $manager->getDatabase($name)) {
                $output->writeln(sprintf('<error>Database "%s" does not exist in schema.xml. Skipped.</error>', $name));

                continue;
            }

            $databaseDiff = DatabaseComparator::computeDiff($database, $appDataDatabase, false, $tableRenaming, $removeTable, $excludedTables);

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
                    $fromTableName,
                    $toTableName
                ));
            }

            $conn = $manager->getAdapterConnection($name);
            /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
            $platform = $generatorConfig->getConfiguredPlatform($conn, $name);
            if ($input->getOption('disable-identifier-quoting')) {
                $platform->setIdentifierQuoting(false);
            }
            $migrationsUp[$name] = $platform->getModifyDatabaseDDL($databaseDiff);
            $migrationsDown[$name] = $platform->getModifyDatabaseDDL($databaseDiff->getReverseDiff());
        }

        if (!$migrationsUp) {
            $output->writeln('Same XML and database structures for all datasource - no diff to generate');

            return static::CODE_SUCCESS;
        }

        $timestamp = time();
        $migrationFileName = $manager->getMigrationFileName($timestamp, $input->getOption('suffix'));
        $migrationClassBody = $manager->getMigrationClassBody($migrationsUp, $migrationsDown, $timestamp, $input->getOption('comment'), $input->getOption('suffix'));

        $file = $generatorConfig->getSection('paths')['migrationDir'] . DIRECTORY_SEPARATOR . $migrationFileName;
        file_put_contents($file, $migrationClassBody);

        $output->writeln(sprintf('"%s" file successfully created.', $file));

        if (null !== $editorCmd = $input->getOption('editor')) {
            $output->writeln(sprintf('Using "%s" as text editor', $editorCmd));
            shell_exec($editorCmd . ' ' . escapeshellarg($file));
        } else {
            $output->writeln('Please review the generated SQL statements, and add data migration code if necessary.');
            $output->writeln('Once the migration class is valid, call the "migrate" task to execute it.');
        }

        return static::CODE_SUCCESS;
    }
}
