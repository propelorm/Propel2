<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Propel\Generator\Schema\Dumper\XmlDumper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Manager\ReverseManager;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class DatabaseReverseCommand extends AbstractCommand
{
    const DEFAULT_OUTPUT_DIRECTORY  = 'generated-reversed-database';
    const DEFAULT_DATABASE_NAME     = 'default';
    const DEFAULT_SCHEMA_NAME       = 'schema';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('output-dir',    null, InputOption::VALUE_REQUIRED, 'The output directory', self::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'The database name used in the created schema.xml. If not defined we use `connection`.')
            ->addOption('schema-name',   null, InputOption::VALUE_REQUIRED, 'The schema name to generate', self::DEFAULT_SCHEMA_NAME)
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'Connection name or dsn to use. Example: \'mysql:host=127.0.0.1;dbname=test;user=root;password=foobar\' (don\'t forget the quote for dsn)',
                'default'
            )
            ->setName('database:reverse')
            ->setAliases(array('reverse'))
            ->setDescription('Reverse-engineer a XML schema file based on given database. Uses given `connection` as name, as dsn or your `reverse.connection` configuration in propel config as connection.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configOptions = array();

        $connection = $input->getArgument('connection');
        if (false === strpos($connection, ':')) {
            //treat it as connection name
            $configOptions['propel']['reverse']['connection'] = $connection;
            if (!$input->getOption('database-name')) {
                $input->setOption('database-name', $connection);
            }
        } else {
            //probably a dsn
            $configOptions += $this->connectionToProperties('reverseconnection=' . $connection, 'reverse');
            $configOptions['propel']['reverse']['parserClass'] = sprintf(
                '\\Propel\\Generator\\Reverse\\%sSchemaParser',
                ucfirst($configOptions['propel']['database']['connections']['reverseconnection']['adapter'])
            );

            if (!$input->getOption('database-name')) {
                $input->setOption('database-name', self::DEFAULT_DATABASE_NAME);
            }
        }
        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($input->getOption('output-dir'));

        $manager = new ReverseManager(new XmlDumper());
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setLoggerClosure(function ($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('output-dir'));
        $manager->setDatabaseName($input->getOption('database-name'));
        $manager->setSchemaName($input->getOption('schema-name'));

        if (true === $manager->reverse()) {
            $output->writeln('<info>Schema reverse engineering finished.</info>');
        }
    }
}
