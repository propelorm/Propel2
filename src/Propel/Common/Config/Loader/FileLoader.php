<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Common\Config\Loader;

use Generator;
use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\RuntimeException;
use Propel\Common\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader as BaseFileLoader;

/**
 * Abstract class used by all file-based loaders.
 *
 * The resolve method and correlatives, with parameters between placeholders %name%, are heavily inspired to
 * Symfony\Component\DependencyInjection\ParameterBag class.
 *
 * @author Cristiano Cinotti
 */
abstract class FileLoader extends BaseFileLoader
{
    /**
     * If the configuration array with parameters is resolved.
     *
     * @var bool
     */
    private $resolved = false;

    /**
     * Configuration values array.
     * It contains the configuration values array to manipulate while resolving parameters.
     * It's useful, in particular, resolve() and get() method.
     *
     * @var array
     */
    private $config = [];

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Config\FileLocatorInterface|null $locator A FileLocator instance
     */
    public function __construct(?FileLocatorInterface $locator = null)
    {
        parent::__construct($locator ?? new FileLocator());
    }

    /**
     * Replaces parameter placeholders (%name%) by their values for all parameters.
     *
     * @param array $configuration The configuration array to resolve
     *
     * @return array
     */
    public function resolveParams(array $configuration): array
    {
        if ($this->resolved) {
            return [];
        }

        $this->config = $configuration;
        $parameters = [];
        foreach ($configuration as $key => $value) {
            $key = $this->resolveValue($key);
            $value = $this->resolveValue($value);
            $parameters[$key] = $this->unescapeValue($value);
        }

        $this->resolved = true;

        return $parameters;
    }

    /**
     * Get the path of a given resource
     *
     * @param string $file The resource
     *
     * @throws \Propel\Common\Config\Exception\InputOutputException If the path is not readable
     *
     * @return string
     */
    protected function getPath(string $file): string
    {
        $path = $this->locator->locate($file);
        if (!is_string($path)) {
            throw new InputOutputException("$file must return a single path.");
        }

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access configuration file $file.");
        }

        return $path;
    }

    /**
     * Check if a resource has a given extension
     *
     * @param array<string>|string $ext An extension or an array of extensions
     * @param mixed $resource A resource
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     *
     * @return bool
     */
    protected static function checkSupports($ext, $resource): bool
    {
        if (!is_string($resource)) {
            return false;
        }

        $pathParts = pathinfo($resource);
        $extension = $pathParts['extension'] ?? '';
        $filename = $pathParts['filename'];

        if ($extension === 'dist') {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
        }

        if (is_string($ext)) {
            return ($ext === $extension);
        }

        if (!is_array($ext)) {
            throw new InvalidArgumentException('$ext must be string or string[]');
        }

        return in_array($extension, $ext, true);
    }

    /**
     * Replaces parameter placeholders (%name%) by their values.
     *
     * @param mixed $value The value to be resolved
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     */
    private function resolveValue($value, array $resolving = [])
    {
        if (is_array($value)) {
            $args = [];
            foreach ($value as $k => $v) {
                $args[$this->resolveValue($k, $resolving)] = $this->resolveValue($v, $resolving);
            }

            return $args;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->resolveString($value, $resolving);
    }

    /**
     * Resolves parameters inside a string
     *
     * @param string $value The string to resolve
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     */
    private function resolveString(string $value, array $resolving = [])
    {
        /*
         * %%: to be unescaped
         * %[^%\s]++%: a parameter
         *         ^ backtracking is turned off
         * when it matches the entire $value, it can resolve to any value.
         * otherwise, it is replaced with the resolved string or number.
         */
        /** @phpstan-var string|null $onlyKey */
        $onlyKey = null;
        $replaced = preg_replace_callback('/%([^%\s]*+)%/', function ($match) use ($resolving, $value, &$onlyKey) {
            $key = $match[1];
            // skip %%
            if ($key === '') {
                return '%%';
            }

            $env = $this->parseEnvironmentParams($key);
            if ($env !== null) {
                return $env;
            }

            if (isset($resolving[$key])) {
                throw new RuntimeException(sprintf("Circular reference detected for parameter '%s'.", $key));
            }

            if ($value === $match[0]) {
                $onlyKey = $key;

                return $match[0];
            }

            $resolved = $this->get($key);

            if (!is_string($resolved) && !is_int($resolved) && !is_float($resolved)) {
                throw new RuntimeException(sprintf('A string value must be composed of strings and/or numbers, but found parameter "%s" of type %s inside string value "%s".', $key, gettype($resolved), $value));
            }

            $resolving[$key] = true;
            $resolved = (string)$resolved;

            return $this->resolveString($resolved, $resolving);
        }, $value);

        if ($onlyKey === null) {
            return $replaced;
        }

        $resolving[$onlyKey] = true;

        return $this->resolveValue($this->get($onlyKey), $resolving);
    }

    /**
     * Return unescaped variable.
     *
     * @param mixed $value The variable to unescape
     *
     * @return mixed|array
     */
    private function unescapeValue($value)
    {
        if (is_string($value)) {
            return str_replace('%%', '%', $value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->unescapeValue($v);
            }

            return $result;
        }

        return $value;
    }

    /**
     * Return the value correspondent to a given key.
     *
     * @param mixed $propertyKey The key, in the configuration values array, to return the respective value
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException when non-existent key in configuration array
     *
     * @return mixed
     */
    private function get($propertyKey)
    {
        $value = $this->findValue($propertyKey, $this->config);

        if (!$value->valid()) {
            throw new InvalidArgumentException("Parameter '$propertyKey' not found in configuration file.");
        }

        return $value->current();
    }

    /**
     * Scan recursively an array to find a value of a given key.
     *
     * @param string $propertyKey The array key
     * @param array $config The array to scan
     *
     * @return \Generator The value or null if not found
     */
    private function findValue(string $propertyKey, array $config): Generator
    {
        foreach ($config as $key => $value) {
            if ($key === $propertyKey) {
                yield $value;
            }
            if (is_array($value)) {
                yield from $this->findValue($propertyKey, $value);
            }
        }
    }

    /**
     * Check if the parameter contains an environment variable and parse it
     *
     * @param string $value The value to parse
     *
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException if the environment variable is not set
     *
     * @return string|null
     */
    private function parseEnvironmentParams(string $value): ?string
    {
        // env.variable is an environment variable
        if (strpos($value, 'env.') !== 0) {
            return null;
        }
        $env = substr($value, 4);

        $envParam = getenv($env);
        if ($envParam === false) {
            throw new InvalidArgumentException("Environment variable '$env' is not defined.");
        }

        return $envParam;
    }
}
