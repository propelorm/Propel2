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
class MigrationMigrate extends AbstractCommand
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
            ->setName('migration:migrate')
            ->setDescription('Execute all pending migrations')
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

        $connections = array();
        foreach ($input->getOption('connection') as $connection) {
            list($name, $dsn, $infos) = $this->parseConnection($connection);
            $connections[$name] = array_merge(array('dsn' => $dsn), $infos);
        }

        $manager->setConnections($connections);
        $manager->setMigrationTable($input->getOption('migration-table'));
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        if (!$nextMigrationTimestamp = $manager->getFirstUpMigrationTimestamp()) {
            $output->writeln('All migrations were already executed - nothing to migrate.');

            return false;
        }

        $timestamps = $manager->getValidMigrationTimestamps();
        if (count($timestamps) > 1) {
            $output->writeln(sprintf('%d migrations to execute', count($timestamps)));
        }

        foreach ($timestamps as $timestamp) {
            $output->writeln(sprintf(
                'Executing migration %s up',
                $manager->getMigrationClassName($timestamp)
            ));

            $migration = $manager->getMigrationObject($timestamp);
            if (false === $migration->preUp($manager)) {
                $output->writeln('<error>preUp() returned false. Aborting migration.</error>');

                return false;
            }

            foreach ($migration->getUpSQL() as $datasource => $sql) {
                $connection = $manager->getConnection($datasource);
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        'Connecting to database "%s" using DSN "%s"',
                        $datasource,
                        $connection['dsn']
                    ));
                }

                $pdo = $manager->getPdoConnection($datasource);
                $res = 0;
                $statements = SqlParser::parseString($sql);
                foreach ($statements as $statement) {
                    try {
                        if ($input->getOption('verbose')) {
                            $output->writeln(sprintf('Executing statement "%s"', $statement));
                        }
                        $stmt = $pdo->prepare($statement);
                        $stmt->execute();
                        $res++;
                    } catch (\PDOException $e) {
                        $output->writeln(sprintf('<error>Failed to execute SQL "%s"</error>', $statement));
                        // continue
                    }
                }
                if (!$res) {
                    $output->writeln('No statement was executed. The version was not updated.');
                    $output->writeln(sprintf(
                        'Please review the code in "%s"',
                        $manager->getMigrationDir() . DIRECTORY_SEPARATOR . $manager->getMigrationClassName($timestamp)
                    ));
                    $output->writeln('<error>Migration aborted</error>');

                    return false;
                }
                $output->writeln(sprintf(
                    '%d of %d SQL statements executed successfully on datasource "%s"',
                    $res,
                    count($statements),
                    $datasource
                ));
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
            $migration->postUp($manager);
        }

        $output->writeln('Migration complete. No further migration to execute.');
    }
}
