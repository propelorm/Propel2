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
     * Array of configuration values
     *
     * @var array
     */
    private $config;

    /**
     * Load and validate configuration values from a file.
     *
     * @param string $filename Configuration file name
     */
    public function __construct($filename = 'propel')
    {
        $this->load($filename);
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
     * Find a configuration file and loads it.
     * Default configuration file is named 'propel' and is expected to be found in the current directory
     * or a subdirectory named '/conf' or '/config'. It can have one of the supported extensions (.ini, .properties,
     * .json, .yaml, .yml, .xml, .php, .inc).
     * Only one configuration file is supposed to be found.
     * This method also looks for a '.dist' configuration file and loads it.
     *
     * @param string $filename Configuration file name
     */
    private function load($fileName)
    {
        if ('propel' === $fileName) {
            $currentDir = getcwd();
            $dirs[] = $currentDir;
            if (is_dir($currentDir . '/conf')) {
                $dirs[] = $currentDir . '/conf';
            }
            if (is_dir($currentDir . '/config')) {
                $dirs[] = $currentDir . '/config';
            }

            $finder = new Finder();
            $finder->in($dirs)->files()->name($fileName . '.*')->notName('*.dist');
            $files = iterator_to_array($finder);

            $distfinder = new Finder();
            $distfinder->in($dirs)->files()->name($fileName . '.*.dist');
            $distfiles = iterator_to_array($distfinder);

            $numFiles = count($files);
            $numDistFiles = count($distfiles);

            //allow to load only .dist file
            if (0 === $numFiles && 1 === $numDistFiles) {
                $files = $distfiles;
                $numFiles = 1;
            }

            if ($numFiles !== 1) {
                if ($numFiles <= 0) {
                    throw new InvalidArgumentException('Propel configuration file not found');
                }

                throw new InvalidArgumentException('Propel expects only one configuration file');
            }

            $file = current($files);
            $fileName = $file->getFileName();
        }

        $delegatingLoader = new DelegatingLoader();

        $conf = $delegatingLoader->load($fileName);

        $distConf = array();
        if (file_exists($fileName . '.dist')) {
            $distDelegatingLoader = new DelegatingLoader();
            $distConf = $distDelegatingLoader->load($fileName . '.dist');
        }

        $this->config = array_merge($distConf, $conf);
    }

    /**
     * Validate the configuration array via Propel\Common\Config\PropelConfiguration class
     * @todo
     */
    private function process()
    {

    }
}
