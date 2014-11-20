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
class MigrationUpCommand extends AbstractCommand
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
            ->setName('migration:up')
            ->setAliases(array('up'))
            ->setDescription('Execute migrations up')
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

        if (!$migrationTimestamps = $manager->getFirstUpMigrationTimestamp()) {
            $output->writeln('All migrations were already executed - nothing to migrate.');

            return false;
        }


        foreach($migrationTimestamps as $datasource => $nextMigrationTimestamp){

            $output->writeln(sprintf(
                'Executing migration %s up',
                $manager->getMigrationClassName($nextMigrationTimestamp)
            ));

            $migration = $manager->getMigrationObject($nextMigrationTimestamp);
            if (false === $migration->preUp($manager)) {
                $output->writeln('<error>preUp() returned false. Aborting migration.</error>');

                return false;
            }

            foreach ($migration->getUpSQL() as $migrationDatasource => $sql) {

                if ($migrationDatasource != $datasource){
                    continue;
                }

                $connection = $manager->getConnection($datasource);

                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        'Connecting to database "%s" using DSN "%s"',
                        $datasource,
                        $connection['dsn']
                    ));
                }

                $conn = $manager->getAdapterConnection($datasource);
                $res = 0;
                $statements = SqlParser::parseString($sql);
                foreach ($statements as $statement) {
                    try {
                        if ($input->getOption('verbose')) {
                            $output->writeln(sprintf('Executing statement "%s"', $statement));
                        }

                        $stmt = $conn->prepare($statement);
                        $stmt->execute();
                        $res++;
                    } catch (\PDOException $e) {
                        throw new RuntimeException(sprintf('<error>Failed to execute SQL "%s". Aborting migration.</error>', $statement), 0, $e);
                    }
                }
                if (!$res) {
                    $output->writeln('No statement was executed. The version was not updated.');
                    $output->writeln(sprintf(
                        'Please review the code in "%s"',
                        $manager->getMigrationDir() . DIRECTORY_SEPARATOR . $manager->getMigrationClassName($nextMigrationTimestamp)
                    ));
                    $output->writeln('<error>Migration aborted</error>', Project::MSG_ERR);

                    return false;
                }
                $output->writeln(sprintf(
                    '%d of %d SQL statements executed successfully on datasource "%s"',
                    $res,
                    count($statements),
                    $datasource
                ));
                $manager->updateLatestMigrationTimestamp($datasource, $nextMigrationTimestamp);
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        'Updated latest migration date to %d for datasource "%s"',
                        $nextMigrationTimestamp,
                        $datasource
                    ));
                }
            }

            $migration->postUp($manager);

            if ($timestamps = $manager->getValidMigrationTimestamps()) {
                $output->writeln(sprintf(
                    'Migration complete. %d migrations left to execute.',
                    count($timestamps)
                ));
            } else {
                $output->writeln('Migration complete. No further migration to execute.');
            }
        }
    }
}
