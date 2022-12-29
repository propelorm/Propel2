<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Command\Executor\RollbackExecutor;
use Propel\Generator\Manager\MigrationManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationDownCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'The output directory')
            ->addOption('migration-table', null, InputOption::VALUE_REQUIRED, 'Migration table name')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use', [])
            ->addOption('fake', null, InputOption::VALUE_NONE, 'Does not touch the actual schema, but marks previous migration as executed.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Continues with the migration even when errors occur.')
            ->setName('migration:down')
            ->setAliases(['down'])
            ->setDescription('Execute migrations down');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configOptions = [];

        if ($this->hasInputOption('output-dir', $input)) {
            $configOptions['propel']['paths']['migrationDir'] = $input->getOption('output-dir');
        }

        if ($this->hasInputOption('migration-table', $input)) {
            $configOptions['propel']['migrations']['tableName'] = $input->getOption('migration-table');
        }

        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);

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
        $manager->setMigrationTable($generatorConfig->getSection('migrations')['tableName']);
        $manager->setWorkingDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $alreadyExecutedMigrations = $manager->getAlreadyExecutedMigrationTimestamps();
        if ($alreadyExecutedMigrations === []) {
            $output->writeln('No migrations were ever executed on this database - nothing to reverse.');

            return static::CODE_ERROR;
        }

        $rollbackExecutor = new RollbackExecutor($input, $output, $manager);

        $currentMigrationVersion = array_pop($alreadyExecutedMigrations);

        $leftMigrationsCount = count($alreadyExecutedMigrations);
        $previousMigrationVersion = array_pop($alreadyExecutedMigrations);

        if (!$rollbackExecutor->executeRollbackToPreviousVersion($currentMigrationVersion, $previousMigrationVersion)) {
            return static::CODE_ERROR;
        }

        if ($leftMigrationsCount) {
            $output->writeln(sprintf('Reverse migration complete. %d more migrations available for reverse.', $leftMigrationsCount));
        } else {
            $output->writeln('Reverse migration complete. No more migration available for reverse');
        }

        return static::CODE_SUCCESS;
    }
}
