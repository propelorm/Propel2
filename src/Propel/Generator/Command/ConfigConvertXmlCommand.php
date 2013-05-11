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
use Propel\Generator\Config\XmlToArrayConverter;
use Propel\Generator\Config\ArrayToPhpConverter;

class ConfigConvertXmlCommand extends AbstractCommand
{
    const DEFAULT_INPUT_DIRECTORY   = '.';
    const DEFAULT_INPUT_FILE        = 'runtime-conf.xml';
    const DEFAULT_OUTPUT_DIRECTORY  = './generated-conf';
    const DEFAULT_OUTPUT_FILE       = 'config.php';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('input-dir',   null, InputOption::VALUE_REQUIRED,  'The input directory',   self::DEFAULT_INPUT_DIRECTORY)
            ->addOption('input-file',  null, InputOption::VALUE_REQUIRED,  'The input file',        self::DEFAULT_INPUT_FILE)
            ->addOption('output-dir',  null, InputOption::VALUE_REQUIRED,  'The output directory',  self::DEFAULT_OUTPUT_DIRECTORY)
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED,  'The output file',       self::DEFAULT_OUTPUT_FILE)
            ->setName('config:convert-xml')
            ->setAliases(array('convert-conf'))
            ->setDescription('Transform the XML configuration to PHP code leveraging the ServiceContainer')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFilePath = $input->getOption('input-dir') . DIRECTORY_SEPARATOR . $input->getOption('input-file');
        if (!file_exists($inputFilePath)) {
            throw new \RuntimeException(sprintf('Unable to find the "%s" configuration file', $inputFilePath));
        }

        $this->createDirectory($input->getOption('output-dir'));

        $outputFilePath = $input->getOption('output-dir') . DIRECTORY_SEPARATOR .$input->getOption('output-file');
        if (!is_writable(dirname($outputFilePath))) {
            throw new \RuntimeException(sprintf('Unable to write the "%s" output file', $outputFilePath));
        }

        $stringConf = file_get_contents($inputFilePath);
        $arrayConf  = XmlToArrayConverter::convert($stringConf);
        $phpConf    = ArrayToPhpConverter::convert($arrayConf);
        $phpConf    = "<?php
" . $phpConf;

        if (file_exists($outputFilePath)) {
            $currentContent = file_get_contents($outputFilePath);
            if ($currentContent == $phpConf) {
                $output->writeln(sprintf('No change required in the current configuration file <info>"%s"</info>.', $outputFilePath));

            } else {
                file_put_contents($outputFilePath, $phpConf);
                $output->writeln(sprintf('Successfully updated PHP configuration in file <info>"%s"</info>.', $outputFilePath));
            }
        } else {
            file_put_contents($outputFilePath, $phpConf);
            $output->writeln(sprintf('Successfully wrote PHP configuration in file <info>"%s"</info>.', $outputFilePath));
        }
    }
}
