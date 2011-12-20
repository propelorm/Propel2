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

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('input-dir',    null, InputOption::VALUE_REQUIRED,  'The input directory', self::DEFAULT_INPUT_DIRECTORY),
                new InputOption('output-dir',   null, InputOption::VALUE_REQUIRED,  'The output directory', self::DEFAULT_OUTPUT_DIRECTORY),
                new InputOption('platform',     null, InputOption::VALUE_REQUIRED,  'The platform', self::DEFAULT_PLATFORM),
                new InputOption('validate',     null, InputOption::VALUE_NONE,      '')
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

        $buildProperties   = realpath($input->getOption('input-dir') . DIRECTORY_SEPARATOR . 'build.properties');
        $defaultProperties = realpath(__DIR__.'/../../../../tools/generator/default.properties');

        $generatorConfig = new GeneratorConfig(array_merge(
            $this->getBuildProperties($defaultProperties),
            $this->getBuildProperties($buildProperties),
            array(
                'propel.platform.class' => $input->getOption('platform'),
            )
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
        $manager->setIncludedFiles($files);
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
