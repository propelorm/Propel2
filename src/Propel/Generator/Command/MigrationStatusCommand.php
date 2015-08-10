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
use Propel\Generator\Manager\MigrationManager;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationStatusCommand extends AbstractCommand
{
    const DEFAULT_MIGRATION_TABLE   = 'propel_migration';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('output-dir',       null, InputOption::VALUE_REQUIRED,  'The output directory')
            ->addOption('migration-table',  null, InputOption::VALUE_REQUIRED,  'Migration table name', self::DEFAULT_MIGRATION_TABLE)
            ->addOption('connection',       null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use', array())
            ->setName('migration:status')
            ->setAliases(array('status'))
            ->setDescription('Get migration status')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configOptions = array();

        if ($this->hasInputOption('output-dir', $input)) {
            $configOptions['propel']['paths']['migrationDir'] = $input->getOption('output-dir');
        }

        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);

        $connections = array();
        $optionConnections = $input->getOption('connection');
        if (!$optionConnections) {
            $connections = $generatorConfig->getBuildConnections();
        } else {
            foreach ($optionConnections as $connection) {
                list($name, $dsn, $infos) = $this->parseConnection($connection);
                $connections[$name] = array_merge(array('dsn' => $dsn), $infos);
            }
        }

        $manager->setConnections($connections);
        $manager->setMigrationTable($input->getOption('migration-table'));
        $manager->setWorkingDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $output->writeln('Checking Database Versions...');
        foreach ($manager->getConnections() as $datasource => $params) {
            if ($input->getOption('verbose')) {
                $output->writeln(sprintf(
                    'Connecting to database "%s" using DSN "%s"',
                    $datasource,
                    $params['dsn']
                ));
            }

            if (!$manager->migrationTableExists($datasource)) {
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        'Migration table does not exist in datasource "%s"; creating it.',
                        $datasource
                    ));
                }
                $manager->createMigrationTable($datasource);
            }
        }

        $oldestMigrationTimestamp = $manager->getOldestDatabaseVersion();
        if ($input->getOption('verbose')) {
            if ($oldestMigrationTimestamp) {
                $output->writeln(sprintf(
                    'Latest migration was executed on %s (timestamp %d)',
                    date('Y-m-d H:i:s', $oldestMigrationTimestamp),
                    $oldestMigrationTimestamp
                ));
            } else {
                $output->writeln('No migration was ever executed on these connection settings.');
            }
        }

        $output->writeln('Listing Migration files...');
        $dir = $generatorConfig->getSection('paths')['migrationDir'];
        $migrationTimestamps  = $manager->getMigrationTimestamps();
        $nbExistingMigrations = count($migrationTimestamps);

        if ($migrationTimestamps) {
            $output->writeln(sprintf(
                '%d valid migration classes found in "%s"',
                $nbExistingMigrations,
                $dir
            ));

            if ($validTimestamps = $manager->getValidMigrationTimestamps()) {
                $countValidTimestamps = count($validTimestamps);

                if ($countValidTimestamps == 1) {
                    $output->writeln('1 migration needs to be executed:');
                } else {
                    $output->writeln(sprintf('%d migrations need to be executed:', $countValidTimestamps));
                }
            }
            foreach ($migrationTimestamps as $timestamp) {
                if ($timestamp > $oldestMigrationTimestamp || $input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        ' %s %s %s',
                        $timestamp == $oldestMigrationTimestamp ? '>' : ' ',
                        $manager->getMigrationClassName($timestamp),
                        !in_array($timestamp, $validTimestamps) ? '(executed)' : ''
                    ));
                }
            }
        } else {
            $output->writeln(sprintf('No migration file found in "%s".', $dir));

            return false;
        }

        $migrationTimestamps = $manager->getValidMigrationTimestamps();
        $nbNotYetExecutedMigrations = count($migrationTimestamps);

        if (!$nbNotYetExecutedMigrations) {
            $output->writeln('All migration files were already executed - Nothing to migrate.');

            return false;
        }

        $output->writeln(sprintf(
            'Call the "migrate" task to execute %s',
            $countValidTimestamps == 1 ? 'it' : 'them'
        ));
    }
}
