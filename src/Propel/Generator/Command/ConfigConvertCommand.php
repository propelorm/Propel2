<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Propel\Common\Config\ConfigurationManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Config\ArrayToPhpConverter;

class ConfigConvertCommand extends AbstractCommand
{
    const DEFAULT_CONFIG_DIRECTORY   = '.';
    const DEFAULT_OUTPUT_FILE       = 'config.php';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('config-dir',   null, InputOption::VALUE_REQUIRED,  'The directory where the configuration file is placed.',   self::DEFAULT_CONFIG_DIRECTORY)
            ->addOption('output-dir',  null, InputOption::VALUE_REQUIRED,  'The output directory')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED,  'The output file',       self::DEFAULT_OUTPUT_FILE)
            ->setName('config:convert')
            ->setAliases(array('convert-conf'))
            ->setDescription('Transform the configuration to PHP code leveraging the ServiceContainer')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configManager = new ConfigurationManager($input->getOption('config-dir'));

        if (!$input->getOption('output-dir')) {
            $input->setOption('output-dir', $configManager->getSection('paths')['phpConfDir']);
        }

        $this->createDirectory($input->getOption('output-dir'));

        $outputFilePath = $input->getOption('output-dir') . DIRECTORY_SEPARATOR .$input->getOption('output-file');
        if (!is_writable(dirname($outputFilePath))) {
            throw new \RuntimeException(sprintf('Unable to write the "%s" output file', $outputFilePath));
        }

        //Create the options array to pass to ArrayToPhpConverter
        $options['connections'] = $configManager->getConnectionParametersArray();
        $options['defaultConnection'] = $configManager->getSection('runtime')['defaultConnection'];
        $options['log'] = $configManager->getSection('runtime')['log'];
        $options['profiler'] = $configManager->getConfigProperty('runtime.profiler');

        $phpConf = ArrayToPhpConverter::convert($options);
        $phpConf = "<?php
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
