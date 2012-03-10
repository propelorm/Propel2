<?php

namespace Propel\Generator\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\ConfigManager;
use Propel\Generator\Util\Filesystem;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class ConfigBuild extends AbstractCommand
{
    const DEFAULT_OUTPUT_FILE = 'generated-conf/config.php';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('input-dir',    null, InputOption::VALUE_REQUIRED,  'The input directory', self::DEFAULT_INPUT_DIRECTORY)
            ->addOption('output-file',  null, InputOption::VALUE_REQUIRED,  'The output file', self::DEFAULT_OUTPUT_FILE)
            ->setName('config:build')
            ->setDescription('Transform the configuration in PHP to increase performances')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = new Filesystem();
        $outputFile = $input->getOption('output-file');
        
        $parts = explode('/', $outputFile);
        array_pop($parts);
      
        if (0 < count($parts)) {
            $dirs  = implode('/', $parts);
        }

        $filesystem->mkdir($dirs);

        $manager = new ConfigManager();
        $manager->setLoggerClosure(function($message) use ($input, $output) {
            if ($input->getOption('verbose')) {
                $output->writeln($message);
            }
        });
        $manager->setWorkingDirectory($input->getOption('input-dir'));
        $manager->setOutputFile($outputFile);

        $manager->build();

        if (file_exists($file = $manager->getOutputFile()) && file_get_contents($manager->getOutputFile())) {
            $output->writeln(sprintf('Successfully wrote file <info>"%s"</info>.', $file));
        } else {
            $output->writeln('<error>Error while building the configuration in PHP</error>');
        }
    }
}
