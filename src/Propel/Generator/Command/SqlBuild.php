<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Finder\Finder;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\SqlManager;
use Propel\Generator\Util\Filesystem;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class SqlBuild extends Command
{
    const DEFAULT_INPUT_DIRECTORY   = '.';

    const DEFAULT_OUTPUT_DIRECTORY  = 'generated-sql';

    const DEFAULT_PLATFORM          = 'MysqlPlatform';

    const DEFAULT_MYSQL_ENGINE      = 'MyISAM';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('input-dir',    null, InputOption::VALUE_REQUIRED,  'The input directory', self::DEFAULT_INPUT_DIRECTORY),
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
        $manager = new SqlManager();
        $generatorConfig = new GeneratorConfig(array(
            'propel.platform.class'     => $input->getOption('platform'),
            'propel.database.schema'    => $input->getOption('schema-name'),
            'propel.database.encoding'  => $input->getOption('encoding'),
            'propel.tablePrefix'        => $input->getOption('table-prefix'),
            // MySQL specific
            'propel.mysql.tableType'    => $input->getOption('mysql-engine'),
        ));

        $finder = new Finder();
        $files  = $finder
            ->name('*schema.xml')
            ->in($input->getOption('input-dir'))
            ->depth(0)
            ->files()
            ;

        $filesystem = new Filesystem();
        $filesystem->mkdir($input->getOption('output-dir'));

        $manager->setValidate($input->getOption('validate'));
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($files);
        $manager->setLoggerClosure(function($message) use ($output) {
            $output->writeln($message);
        });
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        $manager->buildSql();
    }

    protected function getBuildProperties($file)
    {
        $properties = array();

        if (false === $lines = @file($file)) {
            throw new \Exception(sprintf('Unable to parse contents of "%s".', $file));
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' == $line || in_array($line[0], array('#', ';'))) {
                continue;
            }

            $pos = strpos($line, '=');
            $properties[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
        }

        return $properties;
    }
}
