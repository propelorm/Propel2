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
     * @phpstan-var non-empty-string
     *
     * @var string
     */
    private string $nestSeparator = '.';

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return static::checkSupports(['ini', 'properties'], $resource);
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
    public function load($resource, $type = null): array
    {
        /** @var array<array-key, string|array<array-key, string|array<array-key, string>>>|false $ini */
        $ini = parse_ini_file($this->getPath($resource), true, INI_SCANNER_RAW);

        if ($ini === false) {
            throw new InvalidArgumentException("The configuration file '$resource' has invalid content.");
        }

        $ini = $this->parse($ini); //Parse for nested sections

        return $this->resolveParams($ini); //Resolve parameter placeholders (%name%)
    }

    /**
     * Parse data from the configuration array, to transform nested sections into associative arrays
     * and to fix int/float/bool typing
     *
     * @param array<array-key, string|array<array-key, string|array<array-key, string>>> $data
     *
     * @return array
     */
    private function parse(array $data): array
    {
        $config = [];

        foreach ($data as $section => $value) {
            if (is_array($value)) {
                $sections = explode($this->nestSeparator, $section);
                $config = array_merge_recursive($config, $this->buildNestedSection($sections, $value));
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
     * @param array<array-key, string|array<array-key, string>> $value
     *
     * @return array
     */
    private function buildNestedSection(array $sections, array $value): array
    {
        $parsedSection = $this->parseSection($value);
        foreach (array_reverse($sections) as $section) {
            $parsedSection = [$section => $parsedSection];
        }

        return $parsedSection;
    }

    /**
     * Parse a section.
     *
     * @param array<array-key, string|array<array-key, string>> $section
     *
     * @return array
     */
    private function parseSection(array $section): array
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
     * @param array<array-key, string>|string $rawValue
     * @param array $config
     *
     * @throws \Propel\Common\Config\Exception\IniParseException
     *
     * @return void
     */
    private function parseKey(string $key, $rawValue, array &$config): void
    {
        $value = $rawValue;
        if (is_string($rawValue)) {
            if (strlen($rawValue) <= 5 && in_array(strtolower($rawValue), ['true', 'false'], true)) {
                $value = (strtolower($rawValue) === 'true');
            } elseif ($rawValue === (string)(int)$rawValue) {
                $value = (int)$rawValue;
            } elseif ($rawValue === (string)(float)$rawValue) {
                $value = (float)$rawValue;
            }
        }
        $subKeys = explode($this->nestSeparator, $key);
        $subConfig = &$config;
        $lastIndex = count($subKeys) - 1;
        foreach ($subKeys as $index => $subKey) {
            if ($subKey === '') {
                throw new IniParseException(sprintf('Invalid key "%s"', $key));
            }
            if ($index === $lastIndex) {
                $subConfig[$subKey] = $value;

                break;
            }
            if (!isset($subConfig[$subKey])) {
                if ($subKey === '0' && $subConfig) {
                    $subConfig = [$subKey => $subConfig];
                } else {
                    $subConfig[$subKey] = [];
                }
            } elseif (!is_array($subConfig[$subKey])) {
                throw new IniParseException(sprintf(
                    'Cannot create sub-key for "%s", as key already exists',
                    $subKey,
                ));
            }
            $subConfig = &$subConfig[$subKey];
        }
    }
}
