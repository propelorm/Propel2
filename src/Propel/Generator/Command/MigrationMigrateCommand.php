<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Propel\Runtime\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Util\SqlParser;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class MigrationMigrateCommand extends AbstractCommand
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
            ->addOption('fake',             null, InputOption::VALUE_NONE,  'Does not touch the actual schema, but marks all migration as executed.')
            ->addOption('force',            null, InputOption::VALUE_NONE,  'Continues with the migration even when errors occur.')
            ->setName('migration:migrate')
            ->setAliases(array('migrate'))
            ->setDescription('Execute all pending migrations')
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

        if (!$manager->getFirstUpMigrationTimestamp()) {
            $output->writeln('All migrations were already executed - nothing to migrate.');

            return false;
        }

        $timestamps = $manager->getValidMigrationTimestamps();
        if (count($timestamps) > 1) {
            $output->writeln(sprintf('%d migrations to execute', count($timestamps)));
        }

        foreach ($timestamps as $timestamp) {

            if ($input->getOption('fake')) {
                $output->writeln(
                    sprintf('Faking migration %s up', $manager->getMigrationClassName($timestamp))
                );
            } else {
                $output->writeln(
                    sprintf('Executing migration %s up', $manager->getMigrationClassName($timestamp))
                );
            }

            if (!$input->getOption('fake')) {
                $migration = $manager->getMigrationObject($timestamp);
                if (property_exists($migration, 'comment') && $migration->comment) {
                    $output->writeln(sprintf('<info>%s</info>', $migration->comment));
                }

                if (false === $migration->preUp($manager)) {
                    if ($input->getOption('force')) {
                        $output->writeln('<error>preUp() returned false. Continue migration.</error>');
                    } else {
                        $output->writeln('<error>preUp() returned false. Aborting migration.</error>');

                        return false;
                    }
                }

                foreach ($migration->getUpSQL() as $datasource => $sql) {
                    $connection = $manager->getConnection($datasource);
                    if ($input->getOption('verbose')) {
                        $output->writeln(
                            sprintf(
                                'Connecting to database "%s" using DSN "%s"',
                                $datasource,
                                $connection['dsn']
                            )
                        );
                    }

                    $conn = $manager->getAdapterConnection($datasource);
                    $res = 0;
                    $statements = SqlParser::parseString($sql);

                    foreach ($statements as $statement) {
                        try {
                            if ($input->getOption('verbose')) {
                                $output->writeln(sprintf('Executing statement "%s"', $statement));
                            }
                            $conn->exec($statement);
                            $res++;
                        } catch (\Exception $e) {
                            if ($input->getOption('force')) {
                                //continue, but print error message
                                $output->writeln(
                                    sprintf('<error>Failed to execute SQL "%s". Continue migration.</error>', $statement)
                                );
                            } else {
                                throw new RuntimeException(
                                    sprintf('<error>Failed to execute SQL "%s". Aborting migration.</error>', $statement),
                                    0,
                                    $e
                                );
                            }
                        }
                    }

                    $output->writeln(
                        sprintf(
                            '%d of %d SQL statements executed successfully on datasource "%s"',
                            $res,
                            count($statements),
                            $datasource
                        )
                    );
                }
            }

            // migrations for datasources have passed - update the timestamp
            // for all datasources
            foreach ($manager->getConnections() as $datasource => $connection) {
                $manager->updateLatestMigrationTimestamp($datasource, $timestamp);
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        'Updated latest migration date to %d for datasource "%s"',
                        $timestamp,
                        $datasource
                    ));
                }
            }

            if (!$input->getOption('fake')) {
                $migration->postUp($manager);
            }
        }

        $output->writeln('Migration complete. No further migration to execute.');
    }
}
