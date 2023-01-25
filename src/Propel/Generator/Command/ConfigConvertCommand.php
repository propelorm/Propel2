<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Common\Config\ConfigurationManager;
use Propel\Generator\Builder\Om\TableMapLoaderScriptBuilder;
use Propel\Generator\Config\ArrayToPhpConverter;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ConfigConvertCommand extends AbstractCommand
{
    /**
     * @var string
     */
    public const DEFAULT_CONFIG_DIRECTORY = '.';

    /**
     * @var string
     */
    public const DEFAULT_OUTPUT_FILE = 'config.php';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->addOption('config-dir', null, InputOption::VALUE_REQUIRED, 'The directory where the configuration file is placed.', self::DEFAULT_CONFIG_DIRECTORY)
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'The output directory')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'The output file', self::DEFAULT_OUTPUT_FILE)
            ->addOption('loader-script-dir', null, InputOption::VALUE_REQUIRED, 'Target folder of the database table map loader script. Defaults to paths.loaderScriptDir', null)
            ->setName('config:convert')
            ->setAliases(['convert-conf'])
            ->setDescription('Transform the configuration to PHP code leveraging the ServiceContainer');
    }

    /**
     * @inheritDoc
     *
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configManager = new ConfigurationManager($input->getOption('config-dir'));

        $outputDir = $input->getOption('output-dir') ?? $configManager->getConfigProperty('paths.phpConfDir');

        $this->createDirectory($outputDir);

        $outputFilePath = $outputDir . DIRECTORY_SEPARATOR . $input->getOption('output-file');
        if (!is_writable(dirname($outputFilePath))) {
            throw new RuntimeException(sprintf('Unable to write the "%s" output file', $outputFilePath));
        }

        $loaderDir = $input->getOption('loader-script-dir') ?? $configManager->getConfigProperty('paths.loaderScriptDir') ?? $configManager->getConfigProperty('paths.phpConfDir');
        $fileName = $this->createLoadDatabaseDummyScript($loaderDir, $output);
        $relativeLoaderScriptLocation = DIRECTORY_SEPARATOR . $this->getRelativePathToLoaderScript($loaderDir, $outputDir) . $fileName;

        $phpConf = $this->buildScript($configManager, $relativeLoaderScriptLocation);

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

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Propel\Common\Config\ConfigurationManager $configManager
     * @param string $loaderScriptLocation
     *
     * @return string
     */
    protected function buildScript(ConfigurationManager $configManager, string $loaderScriptLocation): string
    {
        $options = [];
        $options['connections'] = $configManager->getConnectionParametersArray();
        $options['defaultConnection'] = $configManager->getSection('runtime')['defaultConnection'];
        $options['log'] = $configManager->getSection('runtime')['log'];
        $options['profiler'] = $configManager->getConfigProperty('runtime.profiler');

        $stringifiedOptions = ArrayToPhpConverter::convert($options);
        $runtimeVersion = StandardServiceContainer::CONFIGURATION_VERSION;
        $phpConf = "<?php
\$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
\$serviceContainer->checkVersion($runtimeVersion);
$stringifiedOptions
require_once __DIR__ . '$loaderScriptLocation';
";

        return $phpConf;
    }

    /**
     * @param string $loaderDir
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \RuntimeException
     *
     * @return string Name of the generated file
     */
    protected function createLoadDatabaseDummyScript(string $loaderDir, OutputInterface $output): string
    {
        $fileName = TableMapLoaderScriptBuilder::FILENAME;
        $scriptLocation = $loaderDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($scriptLocation)) {
            return $fileName;
        }
        $this->createDirectory($loaderDir);

        if (!is_writable($loaderDir)) {
            throw new RuntimeException('Cannot write database loader file at ' . $scriptLocation);
        }

        $dummyContent = '<?php

/**
 * Dummy file.
 *
 * The actual script will be created when running model:build.
 */
';
        file_put_contents($scriptLocation, $dummyContent);

        return $fileName;
    }

    /**
     * Get the relative path from the config dir to the loader file script.
     *
     * @param string $loaderDir
     * @param string $outputDir
     *
     * @return string
     */
    protected function getRelativePathToLoaderScript(string $loaderDir, string $outputDir): string
    {
        $absoluteLoaderDir = (string)realpath($loaderDir);
        $absoluteOutputDir = (string)realpath($outputDir);
        $fs = new Filesystem();

        return $fs->makePathRelative($absoluteLoaderDir, $absoluteOutputDir);
    }
}
