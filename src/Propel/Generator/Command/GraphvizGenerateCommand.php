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
use Propel\Generator\Manager\GraphvizManager;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class GraphvizGenerateCommand extends AbstractCommand
{
    const DEFAULT_OUTPUT_DIRECTORY  = 'generated-graphviz';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('output-dir',   null, InputOption::VALUE_REQUIRED,  'The output directory', self::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('schema-dir',   null, InputOption::VALUE_REQUIRED,  'The directory where the schema files are placed')
            ->setName('graphviz:generate')
            ->setAliases(array('graphviz'))
            ->setDescription('Generate Graphviz files (.dot)')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configOptions = array();
        if ($this->hasInputOption('schema-dir', $input)){
            $configOptions['propel']['paths']['schemaDir'] = $input->getOption('schema-dir');
        }
        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($input->getOption('output-dir'));

        $manager = new GraphvizManager();
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas($generatorConfig->getSection('paths')['schemaDir'], $input->getOption('recursive')));
        $manager->setLoggerClosure(function ($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('output-dir'));

        $manager->build();
    }
}
