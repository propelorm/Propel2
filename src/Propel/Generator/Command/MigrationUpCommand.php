<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Exception;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Util\SqlParser;
use Propel\Runtime\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationUpCommand extends AbstractCommand
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
            ->addOption('fake', null, InputOption::VALUE_NONE, 'Does not touch the actual schema, but marks next migration as executed.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Continues with the migration even when errors occur.')
            ->setName('migration:up')
            ->setAliases(['up'])
            ->setDescription('Execute migrations up');
    }

    /**
     * @inheritDoc
     *
     * @throws \Propel\Runtime\Exception\RuntimeException
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

        $nextMigrationTimestamp = $manager->getFirstUpMigrationTimestamp();
        if (!$nextMigrationTimestamp) {
            $output->writeln('All migrations were already executed - nothing to migrate.');

            return static::CODE_ERROR;
        }

        if ($input->getOption('fake')) {
            $output->writeln(
                sprintf(
                    'Faking migration %s up',
                    $manager->getMigrationClassName($nextMigrationTimestamp),
                ),
            );
        } else {
            $output->writeln(
                sprintf(
                    'Executing migration %s up',
                    $manager->getMigrationClassName($nextMigrationTimestamp),
                ),
            );
        }

        $migration = $manager->getMigrationObject($nextMigrationTimestamp);

        if (!$input->getOption('fake')) {
            if ($migration->preUp($manager) === false) {
                if ($input->getOption('force')) {
                    $output->writeln('<error>preUp() returned false. Continue migration.</error>');
                } else {
                    $output->writeln('<error>preUp() returned false. Aborting migration.</error>');

                    return static::CODE_ERROR;
                }
            }
        }

        foreach ($migration->getUpSQL() as $datasource => $sql) {
            $connection = $manager->getConnection($datasource);

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf(
                    'Connecting to database "%s" using DSN "%s"',
                    $datasource,
                    $connection['dsn'],
                ));
            }

            $conn = $manager->getAdapterConnection($datasource);
            $res = 0;
            $statements = SqlParser::parseString($sql);

            if (!$input->getOption('fake')) {
                foreach ($statements as $statement) {
                    try {
                        if ($input->getOption('verbose')) {
                            $output->writeln(sprintf('Executing statement "%s"', $statement));
                        }

                        $conn->exec($statement);
                        $res++;
                    } catch (Exception $e) {
                        if ($input->getOption('force')) {
                            //continue, but print error message
                            $output->writeln(
                                sprintf('<error>Failed to execute SQL "%s". Continue migration.</error>', $statement),
                            );
                        } else {
                            throw new RuntimeException(
                                sprintf('<error>Failed to execute SQL "%s". Aborting migration.</error>', $statement),
                                0,
                                $e,
                            );
                        }
                    }
                }

                //make sure foreign_keys are activated again in mysql

                $output->writeln(
                    sprintf(
                        '%d of %d SQL statements executed successfully on datasource "%s"',
                        $res,
                        count($statements),
                        $datasource,
                    ),
                );
            }

            $manager->updateLatestMigrationTimestamp($datasource, $nextMigrationTimestamp);

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf(
                    'Updated latest migration date to %d for datasource "%s"',
                    $nextMigrationTimestamp,
                    $datasource,
                ));
            }
        }

        if (!$input->getOption('fake')) {
            $migration->postUp($manager);
        }

        $timestamps = $manager->getValidMigrationTimestamps();
        if ($timestamps) {
            $output->writeln(sprintf(
                'Migration complete. %d migrations left to execute.',
                count($timestamps),
            ));
        } else {
            $output->writeln('Migration complete. No further migration to execute.');
        }

        return static::CODE_SUCCESS;
    }
}
