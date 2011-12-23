<?php

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Util\Filesystem;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationStatus extends AbstractCommand
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
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED,  'The output directory', self::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('migration-table', null, InputOption::VALUE_REQUIRED,  'Migration table name', self::DEFAULT_MIGRATION_TABLE)
            ->setName('migration:status')
            ->setDescription('Get migration status')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generatorConfig = new GeneratorConfig(array(
            'propel.platform.class' => $input->getOption('platform'),
        ));

        $filesystem = new Filesystem();
        $filesystem->mkdir($input->getOption('output-dir'));

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);

        // /!\
        // $manager->setConnections($this->getGeneratorConfig()->getBuildConnections());

        $manager->setMigrationTable($input->getOption('migration-table'));
        $manager->setWorkingDirectory($input->getOption('output-dir'));

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

        if ($input->getOption('verbose')) {
            if ($oldestMigrationTimestamp = $manager->getOldestDatabaseVersion()) {
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
        $dir = $input->getOption('output-dir');
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
                        $timestamp <= $oldestMigrationTimestamp ? '(executed)' : ''
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
