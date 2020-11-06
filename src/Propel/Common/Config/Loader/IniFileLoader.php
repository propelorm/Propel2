<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\IniParseException;
use Propel\Common\Config\Exception\InvalidArgumentException;

/**
 * IniFileLoader loads parameters from INI files.
 *
 * This class is heavily inspired to Zend\Config component ini reader.
 * http://framework.zend.com/manual/2.1/en/modules/zend.config.reader.html
 *
 * @author Cristiano Cinotti
 */
class IniFileLoader extends FileLoader
{
    /**
     * Separator for nesting levels of configuration data identifiers.
     *
     * @var string
     */
    private $nestSeparator = '.';

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $this->checkSupports(['ini', 'properties'], $resource);
    }

    /**
     * Loads a resource, merge it with the default configuration array and resolve its parameters.
     *
     * @param string $resource The resource
     * @param string|null $type The resource type
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException When ini file is not valid
     * @throws \InvalidArgumentException if configuration file not found
     *
     * @return array The configuration array
     */
    public function load($resource, $type = null)
    {
        $ini = parse_ini_file($this->getPath($resource), true, INI_SCANNER_RAW);

        if ($ini === false) {
            throw new InvalidArgumentException("The configuration file '$resource' has invalid content.");
        }

        $ini = $this->parse($ini); //Parse for nested sections
        $ini = $this->resolveParams($ini); //Resolve parameter placeholders (%name%)

        return $ini;
    }

    /**
     * Parse data from the configuration array, to transform nested sections into associative arrays
     * and to fix int/float/bool typing
     *
     * @param array $data
     *
     * @return array
     */
    private function parse(array $data)
    {
        $config = [];

        foreach ($data as $section => $value) {
            if (is_array($value)) {
                if (strpos($section, $this->nestSeparator) !== false) {
                    $sections = explode($this->nestSeparator, $section);
                    $config = array_merge_recursive($config, $this->buildNestedSection($sections, $value));
                } else {
                    $config[$section] = $this->parseSection($value);
                }
            } else {
                $this->parseKey($section, $value, $config);
            }
        }

        return $config;
    }

    /**
     * Process a nested section
     *
     * @param array $sections
     * @param mixed $value
     *
     * @return array
     */
    private function buildNestedSection($sections, $value)
    {
        if (count($sections) == 0) {
            return $this->parseSection($value);
        }

        $nestedSection = [];

        $first = array_shift($sections);
        $nestedSection[$first] = $this->buildNestedSection($sections, $value);

        return $nestedSection;
    }

    /**
     * Parse a section.
     *
     * @param array $section
     *
     * @return array
     */
    private function parseSection(array $section)
    {
        $config = [];

        foreach ($section as $key => $value) {
            $this->parseKey($key, $value, $config);
        }

        return $config;
    }

    /**
     * Process a key.
     *
     * @param string $key
     * @param string $value
     * @param array $config
     *
     * @throws \Propel\Common\Config\Exception\IniParseException
     *
     * @return void
     */
    private function parseKey($key, $value, array &$config)
    {
        if (strpos($key, $this->nestSeparator) !== false) {
            $pieces = explode($this->nestSeparator, $key, 2);

            if (!strlen($pieces[0]) || !strlen($pieces[1])) {
                throw new IniParseException(sprintf('Invalid key "%s"', $key));
            }
            if (!isset($config[$pieces[0]])) {
                if ($pieces[0] === '0' && !empty($config)) {
                    $config = [$pieces[0] => $config];
                } else {
                    $config[$pieces[0]] = [];
                }
            } elseif (!is_array($config[$pieces[0]])) {
                throw new IniParseException(sprintf(
                    'Cannot create sub-key for "%s", as key already exists',
                    $pieces[0]
                ));
            }

            $this->parseKey($pieces[1], $value, $config[$pieces[0]]);
        } elseif (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
            $config[$key] = (strtolower($value) === 'true');
        } elseif ($value === (string)(int)$value) {
            $config[$key] = (int)$value;
        } elseif ($value === (string)(float)$value) {
            $config[$key] = (float)$value;
        } else {
            $config[$key] = $value;
        }
    }
}
