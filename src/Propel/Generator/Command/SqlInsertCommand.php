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
use Symfony\Component\Console\Output\Output;
use Propel\Generator\Manager\SqlManager;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class SqlInsertCommand extends AbstractCommand
{
    const DEFAULT_OUTPUT_DIRECTORY  = 'generated-sql';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('input-dir', null, InputOption::VALUE_REQUIRED,  'The input directory', self::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use. Example: bookstore=mysql:host=127.0.0.1;dbname=test;user=root;password=foobar')
            ->setName('sql:insert')
            ->setAliases(array('insert-sql'))
            ->setDescription('Insert SQL statements')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new SqlManager();

        $generatorConfig = $this->getGeneratorConfig(array(), $input);

        $connections = array();
        $optionConnections = $input->getOption('connection');
        if (!$optionConnections) {
            $connections = $generatorConfig->getBuildConnections($input->getOption('input-dir'));
        } else {
            foreach ($optionConnections as $connection) {
                list($name, $dsn, $infos) = $this->parseConnection($connection);
                $connections[$name] = array_merge(array('dsn' => $dsn), $infos);
            }
        }

        $manager->setConnections($connections);
        $manager->setLoggerClosure(function($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('input-dir'));

        $manager->insertSql();
    }
}
