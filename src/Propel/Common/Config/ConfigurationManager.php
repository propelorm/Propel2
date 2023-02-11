<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\InvalidConfigurationException;
use Propel\Common\Config\Loader\DelegatingLoader;
use RuntimeException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException as SymfonyInvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;

/**
 * Class ConfigurationManager
 *
 * Class to load and process configuration files. Supported formats are: php, ini (or .properties), yaml, xml, json.
 *
 * @author Cristiano Cinotti
 */
class ConfigurationManager
{
    /**
     * @var string
     */
    public const CONFIG_FILE_NAME = 'propel';

    /**
     * @var int
     */
    private const PRECEDENCE_DIST = 0;

    /**
     * @var int
     */
    private const PRECEDENCE_NORMAL = 1;

    /**
     * Array of configuration values
     *
     * @var array
     */
    private $config = [];

    /**
     * Load and validate configuration values from a file.
     *
     * @param string|null $path Configuration file name or directory in which resides the configuration file.
     * @param array|null $extraConf Array of configuration properties, to be merged with those loaded from file.
     *                              It's useful when passing configuration parameters from command line.
     *
     * @throws \RuntimeException
     */
    public function __construct(?string $path = null, ?array $extraConf = [])
    {
        if (!$path) {
            $path = getcwd();
            if ($path === false) {
                throw new RuntimeException('Cannot get the current working directory');
            }
        }
        if ($extraConf === null) {
            $extraConf = [];
        }
        $this->config = $this->loadConfig($path, $extraConf);
        $this->process();
    }

    /**
     * Return the whole configuration array
     *
     * @return array
     */
    public function get(): array
    {
        return $this->config;
    }

    /**
     * Return a specific section of the configuration array.
     * It ca be useful to get, in example, only 'generator' values.
     *
     * @param string $section the section to be returned
     *
     * @return array|null
     */
    public function getSection(string $section): ?array
    {
        if (!array_key_exists($section, $this->config)) {
            return null;
        }

        return $this->config[$section];
    }

    /**
     * Return a specific configuration property.
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['tableType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.tableType</code>
     *
     * @param string $name The name of property, expressed as a dot separated level hierarchy
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     *
     * @return mixed The configuration property
     */
    public function getConfigProperty(string $name)
    {
        if ($name === '') {
            throw new InvalidArgumentException('Invalid empty configuration property name.');
        }

        $keys = explode('.', $name);
        $output = $this->get();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $output)) {
                return null;
            }
            $output = $output[$key];
        }

        return $output;
    }

    /**
     * Return an array of parameters relative to configured connections, for `runtime` or `generator` section.
     * It's useful for \Propel\Generator\Command\ConfigConvertCommand class
     *
     * @param string $section `runtime` or `generator` section
     *
     * @return array|null
     */
    public function getConnectionParametersArray(string $section = 'runtime'): ?array
    {
        if (!in_array($section, ['runtime', 'generator'], true)) {
            return null;
        }

        $existingConnections = $this->config['database']['connections'];
        $output = [];
        foreach ($this->config[$section]['connections'] as $connection) {
            $output[$connection] = $existingConnections[$connection];
        }

        return $output;
    }

    /**
     * Find a configuration file and loads it into an array.
     * Default configuration file is named 'propel' and is expected to be found in the current directory
     * or a subdirectory named '/conf' or '/config'. It can have one of the supported extensions (.ini, .properties,
     * .json, .yaml, .yml, .xml, .php, .inc).
     * Only one configuration file is supposed to be found.
     * This method also looks for a '.dist' configuration file and loads it.
     *
     * @param string $path Configuration file name or directory in which resides the configuration file.
     * @param array $extraConf Array of configuration properties, to be merged with those loaded from file.
     *
     * @return array
     */
    protected function loadConfig(string $path, array $extraConf = []): array
    {
        if (!is_dir($path)) {
            $filesOrderedByPrecedence = [
                self::PRECEDENCE_DIST => $path . '.dist',
                self::PRECEDENCE_NORMAL => $path,
            ];
        } else {
            $filesOrderedByPrecedence = $this->getConfigFileNamesFromDirectory($path);
        }
        ksort($filesOrderedByPrecedence);

        $configs = [];
        foreach ($filesOrderedByPrecedence as $file) {
            $configs[] = $this->loadFile($file);
        }
        $configs[] = $extraConf;

        return array_replace_recursive(...$configs);
    }

    /**
     * Validate the configuration array via Propel\Common\Config\PropelConfiguration class
     * and add default values.
     *
     * @param array $extraConf Extra configuration to merge before processing. It's useful when a child class overwrite
     *                         the constructor to pass a built-in array of configuration, without load it from file. I.e.
     *                         Propel\Generator\Config\QuickGeneratorConfig class.
     *
     * @throws \Propel\Common\Config\Exception\InvalidConfigurationException
     *
     * @return void
     */
    protected function process(array $extraConf = []): void
    {
        if (!$extraConf && !$this->config) {
            return;
        }

        $processor = new Processor();
        $configuration = new PropelConfiguration();

        if ($extraConf) {
            $this->config = array_replace_recursive($this->config, $extraConf);
        }

        try {
            $this->config = $processor->processConfiguration($configuration, $this->config);
        } catch (SymfonyInvalidConfigurationException $e) {
            throw new InvalidConfigurationException('Could not process configuration. Please check the property in error message: ' . $e->getMessage());
        }
        $this->cleanupSlaveConnections();
        $this->cleanupConnections();
    }

    /**
     * Return an array of configuration files
     *
     * @param string $path The directories where to find the configuration files
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     *
     * @return array<int, string>
     */
    private function getConfigFileNamesFromDirectory(string $path): array
    {
        $dirs = [
            $path,
            $path . '/conf',
            $path . '/config',
        ];
        $dirs = array_filter($dirs, 'is_dir');

        $fileGlob = self::CONFIG_FILE_NAME . '.{php,inc,ini,properties,yaml,yml,xml,json}{,.dist}';
        $finder = new Finder();
        $finder->in($dirs)->depth(0)->files()->name($fileGlob);
        $orderedConfigFileNames = [];
        foreach ($finder as $file) {
            $precedence = ($file->getExtension() === 'dist') ? self::PRECEDENCE_DIST : self::PRECEDENCE_NORMAL;
            if (isset($orderedConfigFileNames[$precedence])) {
                $messageSplits = [
                    'Propel expects only one configuration file, but found two:',
                    $orderedConfigFileNames[$precedence],
                    $file->getPathname(),
                    'Please specify the correct folder using the --config-dir parameter.',
                ];
                $message = implode(PHP_EOL, $messageSplits);

                throw new InvalidArgumentException($message);
            }
            $orderedConfigFileNames[$precedence] = $file->getPathname();
        }

        return $orderedConfigFileNames;
    }

    /**
     * Return the configuration array, loaded from $fileName
     *
     * @param string $fileName The configuration file
     *
     * @return array
     */
    private function loadFile(string $fileName): array
    {
        if (!file_exists($fileName)) {
            return [];
        }

        $delegatingLoader = new DelegatingLoader();

        return $delegatingLoader->load($fileName);
    }

    /**
     * Remove empty `slaves` array from configured connections.
     *
     * @return void
     */
    private function cleanupSlaveConnections(): void
    {
        foreach ($this->config['database']['connections'] as $name => $connection) {
            if ($connection['slaves'] === []) {
                unset($this->config['database']['connections'][$name]['slaves']);
            }
        }
    }

    /**
     * If not defined, set `runtime` and `generator` connections, based on `database.connections` property.
     * Check if runtime and generator connections are correctly defined.
     *
     * @return void
     */
    private function cleanupConnections(): void
    {
        $databaseConnections = $this->config['database']['connections'];
        $assertConnectionExists = static function (string $connection, string $section, string $childSection) use ($databaseConnections): void {
            if (!array_key_exists($connection, $databaseConnections)) {
                throw new InvalidConfigurationException("`$connection` isn't a valid configured connection (Section: propel.$section.$childSection). " .
                    'Please, check your configured connections in `propel.database.connections` section of your configuration file.');
            }
        };
        foreach (['runtime', 'generator'] as $section) {
            $configSection = &$this->config[$section];

            if (empty($configSection['connections'])) {
                $configSection['connections'] = array_keys($databaseConnections);
            }

            if (!isset($configSection['defaultConnection'])) {
                $configSection['defaultConnection'] = array_key_first($databaseConnections);
            }

            foreach ($configSection['connections'] as $connection) {
                $assertConnectionExists($connection, $section, 'connections');
            }

            $assertConnectionExists($configSection['defaultConnection'], $section, 'defaultConnection');
        }
    }
}
