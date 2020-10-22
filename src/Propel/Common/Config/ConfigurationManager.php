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
    public const CONFIG_FILE_NAME = 'propel';

    /**
     * Array of configuration values
     *
     * @var array
     */
    private $config = [];

    /**
     * Load and validate configuration values from a file.
     *
     * @param string|null $filename Configuration file name or directory in which resides the configuration file.
     * @param array $extraConf Array of configuration properties, to be merged with those loaded from file.
     *                          It's useful when passing configuration parameters from command line.
     */
    public function __construct($filename = null, $extraConf = [])
    {
        $this->load($filename, $extraConf);
        $this->process();
    }

    /**
     * Return the whole configuration array
     *
     * @return array
     */
    public function get()
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
    public function getSection($section)
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
    public function getConfigProperty($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("Invalid configuration property name '$name'.");
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
    public function getConnectionParametersArray($section = 'runtime')
    {
        if (!in_array($section, ['runtime', 'generator'], true)) {
            return null;
        }

        $output = [];
        foreach ($this->config[$section]['connections'] as $connection) {
            $output[$connection] = $this->config['database']['connections'][$connection];
        }

        return $output;
    }

    /**
     * Find a configuration file and loads it.
     * Default configuration file is named 'propel' and is expected to be found in the current directory
     * or a subdirectory named '/conf' or '/config'. It can have one of the supported extensions (.ini, .properties,
     * .json, .yaml, .yml, .xml, .php, .inc).
     * Only one configuration file is supposed to be found.
     * This method also looks for a '.dist' configuration file and loads it.
     *
     * @param string|null $fileName Configuration file name or directory in which resides the configuration file.
     * @param array|null $extraConf Array of configuration properties, to be merged with those loaded from file.
     *
     * @return void
     */
    protected function load($fileName, $extraConf)
    {
        $dirs = $this->getDirs($fileName);

        if (!$fileName || is_dir($fileName)) {
            $fileName = self::CONFIG_FILE_NAME;
        }

        if ($extraConf === null) {
            $extraConf = [];
        }

        if ($fileName === static::CONFIG_FILE_NAME) {
            $files = $this->getFiles($dirs, $fileName);
            $distFiles = $this->getFiles($dirs, $fileName, true);

            $numFiles = count($files);
            $numDistFiles = count($distFiles);

            //allow to load only .dist file
            if ($numFiles === 0 && $numDistFiles === 1) {
                $files = $distFiles;
                $numFiles = 1;
            }

            if ($numFiles === 0) {
                $this->config = $extraConf;

                return;
            }

            $file = current($files);
            $fileName = $file->getPathName();
        }

        $this->config = array_replace_recursive($this->loadFile($fileName . '.dist'), $this->loadFile($fileName), $extraConf);
    }

    /**
     * Validate the configuration array via Propel\Common\Config\PropelConfiguration class
     * and add default values.
     *
     * @param array|null $extraConf Extra configuration to merge before processing. It's useful when a child class overwrite
     *                         the constructor to pass a built-in array of configuration, without load it from file. I.e.
     *                         Propel\Generator\Config\QuickGeneratorConfig class.
     *
     * @return void
     */
    protected function process($extraConf = null)
    {
        if ($extraConf === null && count($this->config) <= 0) {
            return;
        }

        $processor = new Processor();
        $configuration = new PropelConfiguration();

        if (is_array($extraConf)) {
            $this->config = array_replace_recursive($this->config, $extraConf);
        }

        $this->config = $processor->processConfiguration($configuration, $this->config);

        $this->cleanupSlaveConnections();
        $this->cleanupConnections();
    }

    /**
     * Return an array of configuration files in the $dirs directories
     *
     * @param array $dirs The directories where to find the configuration files
     * @param string $fileName The name of the file
     * @param bool $dist If search .dist files
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     *
     * @return array
     */
    private function getFiles($dirs, $fileName, $dist = false)
    {
        $finder = new Finder();
        $fileName .= '.{php,inc,ini,properties,yaml,yml,xml,json}';

        if ($dist === true) {
            $fileName .= '.dist';
        }

        $finder->in($dirs)->depth(0)->files()->name($fileName);
        $files = iterator_to_array($finder);

        if (count($files) > 1) {
            throw new InvalidArgumentException('Propel expects only one configuration file');
        }

        return $files;
    }

    /**
     * Return the configuration array, loaded from $fileName
     *
     * @param string $fileName The configuration file
     *
     * @return array|mixed
     */
    private function loadFile($fileName)
    {
        if (!file_exists($fileName)) {
            return [];
        }

        $delegatingLoader = new DelegatingLoader();

        return $delegatingLoader->load($fileName);
    }

    /**
     * Return the directories where to find the configuration file.
     *
     * @param string|null $fileName
     *
     * @return array
     */
    private function getDirs($fileName)
    {
        if ($fileName && is_file($fileName)) {
            return [];
        }

        $currentDir = getcwd();

        if ($fileName && is_dir($fileName)) {
            $currentDir = $fileName;
        }

        $dirs[] = $currentDir;
        if (is_dir($currentDir . '/conf')) {
            $dirs[] = $currentDir . '/conf';
        }
        if (is_dir($currentDir . '/config')) {
            $dirs[] = $currentDir . '/config';
        }

        return $dirs;
    }

    /**
     * Remove empty `slaves` array from configured connections.
     *
     * @return void
     */
    private function cleanupSlaveConnections()
    {
        foreach ($this->config['database']['connections'] as $name => $connection) {
            if (count($connection['slaves']) <= 0) {
                unset($this->config['database']['connections'][$name]['slaves']);
            }
        }
    }

    /**
     * If not defined, set `runtime` and `generator` connections, based on `database.connections` property.
     * Check if runtime and generator connections are correctly defined.
     *
     * @throws \Propel\Common\Config\Exception\InvalidConfigurationException
     *
     * @return void
     */
    private function cleanupConnections()
    {
        foreach (['runtime', 'generator'] as $section) {
            if (!isset($this->config[$section]['connections']) || count($this->config[$section]['connections']) === 0) {
                $this->config[$section]['connections'] = array_keys($this->config['database']['connections']);
            }

            if (!isset($this->config[$section]['defaultConnection'])) {
                //The previous `array_keys` instruction has moved the pointer to the last element of the array, so the last
                //connection is set as default, instead of the first one. To avoid this, we reset the array.
                reset($this->config['database']['connections']);
                $this->config[$section]['defaultConnection'] = key($this->config['database']['connections']);
            }

            foreach ($this->config[$section]['connections'] as $connection) {
                if (!array_key_exists($connection, $this->config['database']['connections'])) {
                    throw new InvalidConfigurationException("`$connection` isn't a valid configured connection (Section: propel.$section.connections). " .
                        'Please, check your configured connections in `propel.database.connections` section of your configuration file.');
                }
            }

            if (!array_key_exists($defaultConnection = $this->config[$section]['defaultConnection'], $this->config['database']['connections'])) {
                throw new InvalidConfigurationException("`$defaultConnection` isn't a valid configured connection (Section: propel.$section.defaultConnection). " .
                    'Please, check your configured connections in `propel.database.connections` section of your configuration file.');
            }
        }
    }
}
