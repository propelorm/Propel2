<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Loader\DelegatingLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationManager
 *
 * Class to load and process configuration files. Supported formats are: php, ini (or .properties), yaml, xml, json.
 *
 * @author Cristiano Cinotti
 */
class ConfigurationManager
{
    const CONFIG_FILE_NAME = 'propel';

    /**
     * Array of configuration values
     *
     * @var array
     */
    private $config = array();

    /**
     * Load and validate configuration values from a file.
     *
     * @param string $filename  Configuration file name or directory in which resides the configuration file.
     * @param array  $extraConf Array of configuration properties, to be merged with those loaded from file.
     *                          It's useful when passing configuration parameters from command line.
     */
    public function __construct($filename = './', $extraConf = array())
    {
        if (null !== $filename) {
            $this->load($filename, $extraConf);
        }

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
     * It ca be useful to get, in example, only 'buildtime' values.
     *
     * @param  string $section the section to be returned
     * @return array
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
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
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
     * @param  string     $section `runtime` or `generator` section
     * @return array|null
     */
    public function getConnectionParametersArray($section = 'runtime')
    {
        if (!in_array($section, array('runtime', 'generator'))) {
            return null;
        }

        $output = array();
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
     * @param string $fileName  Configuration file name or directory in which resides the configuration file.
     * @param array  $extraConf Array of configuration properties, to be merged with those loaded from file.
     */
    protected function load($fileName, $extraConf)
    {
        $currentDir = getcwd();

        if (null === $fileName) {
            $fileName = self::CONFIG_FILE_NAME;
        }

        if (is_dir($fileName)) {
            $currentDir = $fileName;
            $fileName = 'propel';
        }

        if (null === $extraConf) {
            $extraConf = array();
        }

        if ('propel' === $fileName) {
            $dirs[] = $currentDir;
            if (is_dir($currentDir . '/conf')) {
                $dirs[] = $currentDir . '/conf';
            }
            if (is_dir($currentDir . '/config')) {
                $dirs[] = $currentDir . '/config';
            }

            $finder = new Finder();
            $finder->in($dirs)->depth(0)->files()->name($fileName . '.{php,inc,ini,properties,yaml,yml,xml,json}');
            $files = iterator_to_array($finder);

            $distFinder = new Finder();
            $distFinder->in($dirs)->depth(0)->files()->name($fileName . '.{php,inc,ini,properties,yaml,yml,xml,json}.dist');
            $distFiles = iterator_to_array($distFinder);

            $numFiles = count($files);
            $numDistFiles = count($distFiles);

            //allow to load only .dist file
            if (0 === $numFiles && 1 === $numDistFiles) {
                $files = $distFiles;
                $numFiles = 1;
            }

            if ($numFiles > 1) {
                $realPath = realpath($fileName);
                throw new InvalidArgumentException(
                    sprintf('Propel expects only one configuration file in %s. Found %s',
                    $realPath,
                    implode(', ', $files)
                ));
            } elseif ($numFiles === 0) {
                $this->config = $extraConf;
                return;
            } else {
                $file = current($files);
                $fileName = $file->getPathName();
            }
        }

        $delegatingLoader = new DelegatingLoader();

        $conf = $delegatingLoader->load($fileName);

        $distConf = array();
        if (file_exists($fileName . '.dist')) {
            $distDelegatingLoader = new DelegatingLoader();
            $distConf = $distDelegatingLoader->load($fileName . '.dist');
        }

        $this->config = array_replace_recursive($distConf, $conf, $extraConf);
    }

    /**
     * Validate the configuration array via Propel\Common\Config\PropelConfiguration class
     * and add default values.
     *
     * @param array $extraConf Extra configuration to merge before processing. It's useful when a child class overwrite
     *                         the constructor to pass a built-in array of configuration, without load it from file. I.e.
     *                         Propel\Generator\Config\QuickGeneratorConfig class.
     */
    protected function process($extraConf = null)
    {
        $processor = new Processor();
        $configuration = new PropelConfiguration();

        if (is_array($extraConf)) {
            $this->config = array_replace_recursive($this->config, $extraConf);
        }

        $this->config = $processor->processConfiguration($configuration, $this->config);

        //Workaround to remove empty `slaves` array from database.connections
        foreach ($this->config['database']['connections'] as $name => $connection) {
            if (count($connection['slaves'] <= 0)) {
                unset($this->config['database']['connections'][$name]['slaves']);
            }
        }

        foreach (['runtime', 'generator'] as $section) {
            if (!isset($this->config[$section]['connections']) || count($this->config[$section]['connections']) === 0) {
                $this->config[$section]['connections'] = array_keys($this->config['database']['connections']);
            }

            if (!isset($this->config[$section]['defaultConnection'])) {
                $this->config[$section]['defaultConnection'] = key($this->config['database']['connections']);
            }
        }
    }
}
