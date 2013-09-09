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
use Symfony\Component\Console\Output\Output;
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
            ->addOption('database-name', null, InputOption::VALUE_REQUIRED, 'The database name to reverse', self::DEFAULT_DATABASE_NAME)
            ->addOption('schema-name',   null, InputOption::VALUE_REQUIRED, 'The schema name to generate', self::DEFAULT_SCHEMA_NAME)
            ->addArgument('connection',  null, InputArgument::REQUIRED,     'Connection to use')
            ->setName('database:reverse')
            ->setAliases(array('reverse'))
            ->setDescription('Reverse-engineer a XML schema file based on given database')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vendor = $input->getArgument('connection');
        $vendor = preg_split('{:}', $vendor);
        $vendor = ucfirst($vendor[0]);

        $generatorConfig = $this->getGeneratorConfig(array(
            'propel.platform.class'         => $input->getOption('platform'),
            'propel.reverse.parser.class'   => sprintf('\\Propel\\Generator\\Reverse\\%sSchemaParser', $vendor),
        ), $input);

        $this->createDirectory($input->getOption('output-dir'));

        $manager = new ReverseManager(new XmlDumper());
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setLoggerClosure(function($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        list(, $dsn, $infos) = $this->parseConnection('connection=' . $input->getArgument('connection'));
        
        $manager->setConnection(array_merge(array('dsn' => $dsn), $infos));

        $manager->setDatabaseName($input->getOption('database-name'));
        $manager->setSchemaName($input->getOption('schema-name'));

        if (true === $manager->reverse()) {
            $output->writeln('<info>Schema reverse engineering finished.</info>');
        } else {
            $more = $input->getOption('verbose') ? '' : ' You can use the --verbose option to get more information.';

            $output->writeln(sprintf('<error>Schema reverse engineering failed.%s</error>', $more));
        }
    }
}
