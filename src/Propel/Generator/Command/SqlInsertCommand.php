<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Manager\SqlManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class SqlInsertCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('sql-dir', null, InputOption::VALUE_REQUIRED, 'The SQL files directory')
            ->addOption('connection', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Connection to use. Example: \'bookstore=mysql:host=127.0.0.1;dbname=test;user=root;password=foobar\' where "bookstore" is your propel database name (used in your schema.xml)')
            ->setName('sql:insert')
            ->setAliases(['insert-sql'])
            ->setDescription('Insert SQL statements');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = new SqlManager();

        $configOptions = [];
        if ($sqlDir = $input->getOption('sql-dir')) {
            $configOptions['propel']['paths']['sqlDir'] = $sqlDir;
        }

        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

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
        $manager->setLoggerClosure(function ($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($generatorConfig->getSection('paths')['sqlDir']);

        $manager->insertSql();

        return static::CODE_SUCCESS;
    }
}
