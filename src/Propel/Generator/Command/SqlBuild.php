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


use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\SqlManager;
use Propel\Generator\Util\Filesystem;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class SqlBuild extends AbstractCommand
{
    const DEFAULT_OUTPUT_DIRECTORY  = 'generated-sql';

    const DEFAULT_MYSQL_ENGINE      = 'MyISAM';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(array(
                new InputOption('output-dir',   null, InputOption::VALUE_REQUIRED,  'The output directory', self::DEFAULT_OUTPUT_DIRECTORY),
                new InputOption('validate',     null, InputOption::VALUE_NONE,      ''),
                new InputOption('platform',     null, InputOption::VALUE_REQUIRED,  'The platform', self::DEFAULT_PLATFORM),
                new InputOption('schema-name',  null, InputOption::VALUE_REQUIRED,  'The schema name for RDBMS supporting them', ''),
                new InputOption('encoding',     null, InputOption::VALUE_REQUIRED,  'The encoding to use for the database', ''),
                new InputOption('table-prefix', null, InputOption::VALUE_REQUIRED,  'Add a prefix to all the table names in the database', ''),
                // MySQL specific
                new InputOption('mysql-engine', null, InputOption::VALUE_REQUIRED,  'MySQL engine (MyISAM, InnoDB, ...)', self::DEFAULT_MYSQL_ENGINE),
            ))
            ->setName('sql:build')
            ->setDescription('Build SQL files')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generatorConfig = new GeneratorConfig(array(
            'propel.platform.class'     => $input->getOption('platform'),
            'propel.database.schema'    => $input->getOption('schema-name'),
            'propel.database.encoding'  => $input->getOption('encoding'),
            'propel.tablePrefix'        => $input->getOption('table-prefix'),
            // MySQL specific
            'propel.mysql.tableType'    => $input->getOption('mysql-engine'),
        ));

        $filesystem = new Filesystem();
        $filesystem->mkdir($input->getOption('output-dir'));

        $manager = new SqlManager();
        $manager->setValidate($input->getOption('validate'));
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas());
        $manager->setLoggerClosure(function($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        $manager->buildSql();
    }
}
