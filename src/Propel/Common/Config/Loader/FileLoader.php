<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use Propel\Common\Config\Exception\InputOutputException;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Exception\RuntimeException;
use Propel\Common\Config\FileLocator;
use Symfony\Component\Config\Loader\FileLoader as BaseFileLoader;
use Symfony\Component\Config\FileLocatorInterface;

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
    private $config = array();

    /**
     * Constructor.
     *
     * @param FileLocatorInterface $locator A FileLocator instance
     */
    public function __construct(FileLocatorInterface $locator = null)
    {
        if (null === $locator) {
            $locator = new FileLocator();
        }

        parent::__construct($locator);
    }

    /**
     * Replaces parameter placeholders (%name%) by their values for all parameters.
     *
     * @param array $configuration The configuration array to resolve
     */
    public function resolveParams(array $configuration)
    {
        if ($this->resolved) {
            return;
        }

        $this->config = $configuration;
        $parameters = array();
        foreach ($configuration as $key => $value) {
            $key = $this->resolveValue($key);
            $value = $this->resolveValue($value);
            $parameters[$key] = $this->unescapeValue($value);
        }

        $this->resolved = true;

        return $parameters;
    }

    /**
     * Get the pathof a given resource
     *
     * @param mixed $file The resource
     *
     * @return array|string
     * @throws \InvalidArgumentException                            If the file is not found
     * @throws \Propel\Common\Config\Exception\InputOutputException If the path isnot readable
     */
    protected function getPath($file)
    {
        $path = $this->locator->locate($file);

        if (!is_readable($path)) {
            throw new InputOutputException("You don't have permissions to access configuration file $file.");
        }

        return $path;
    }

    /**
     * Check if a resource has a given extension
     *
     * @param $ext mixed  An extension or an arrayof extensions
     * @param $resource  string A resource
     */
    protected function checkSupports($ext, $resource)
    {
        if (!is_string($resource)) {
            return false;
        }

        $info = pathinfo($resource);
        $extension = $info['extension'];

        if ('dist' === $extension) {
            $extension = pathinfo($info['filename'], PATHINFO_EXTENSION);
        }

        if (is_string($ext)) {
            return ($ext === $extension);
        }

        if (is_array($ext)) {
            $supported = false;

            foreach ($ext as $value) {
                if ($value === $extension) {
                    $supported = true;
                    break;
                }
            }

            return $supported;
        }

        return false;
    }

    private function isResolved()
    {
        return ($this->resolved);
    }

    /**
     * Replaces parameter placeholders (%name%) by their values.
     *
     * @param mixed $value     The value to be resolved
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     *
     */
    private function resolveValue($value, array $resolving = array())
    {
        if (is_array($value)) {
            $args = array();
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
     * @param string $value     The string to resolve
     * @param array  $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return string                                                   The resolved string
     * @throws \Propel\Common\Config\Exception\RuntimeException         if a problem occurs
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException if a parameter is non-existent
     */
    private function resolveString($value, array $resolving = array())
    {
        if (preg_match('/^%([^%\s]+)%$/', $value, $match)) {
            if (null !== $ret = $this->parseEnvironmentParams($match[1])) {
                return $ret;
            }

            $key = $match[1];

            if (isset($resolving[$key])) {
                throw new RuntimeException("Circular reference detected for parameter '$key'.");
            }

            $resolving[$key] = true;

            return $this->resolved ? $this->get($key) : $this->resolveValue($this->get($key), $resolving);
        }

        $self = $this;

        return preg_replace_callback('/%%|%([^%\s]+)%/', function ($match) use ($self, $resolving, $value) {
            // skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            if (null !== $ret = $this->parseEnvironmentParams($match[1])) {
                return $ret;
            }

            $key = $match[1];
            if (isset($resolving[$key])) {
                throw new RuntimeException(sprintf("Circular reference detected for parameter '$key'."));
            }

            $resolved = $this->get($key);

            if (!is_string($resolved) && !is_numeric($resolved)) {
                throw new RuntimeException(sprintf('A string value must be composed of strings and/or numbers, but found parameter "%s" of type %s inside string value "%s".', $key, gettype($resolved), $value));
            }

            $resolved = (string) $resolved;
            $resolving[$key] = true;

            return $self->isResolved() ? $resolved : $self->resolveString($resolved, $resolving);
        }, $value);
    }

    /**
     * Return unescaped variable.
     *
     * @param  mixed       $value The variable to unescape
     * @return array|mixed
     */
    private function unescapeValue($value)
    {
        if (is_string($value)) {
            return str_replace('%%', '%', $value);
        }

        if (is_array($value)) {
            $result = array();
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
     * @param mixed $property_key The key, in the configuration values array, to return the respective value
     *
     * @return mixed
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException when non-existent key in configuration array
     */
    private function get($property_key)
    {
        $found = false;

        $ret = $this->getValue($property_key, null, $found);

        if (false === $found) {
            throw new InvalidArgumentException("Parameter '$property_key' not found in configuration file.");
        }

        return $ret;
    }

    /**
     * Scan recursively an array to find a value of a given key.
     *
     * @param string  $property_key The array key
     * @param array   $config       The array to scan
     * @param boolean $found        if the key was found
     *
     * @return mixed The value or null if not found
     */
    private function getValue($property_key, $config = null, &$found)
    {
        if (null === $config) {
            $config = $this->config;
        }

        foreach ($config as $key => $value) {
            if ($key === $property_key) {
                $found = true;

                return $value;
            }
            if (is_array($value)) {
                $ret = $this->getValue($property_key, $value, $found);

                if (null !== $ret) {
                    return $ret;
                }
            }
        }
    }

    /**
     * Check if the parameter contains an environment variable and parse it
     *
     * @param string $value The value to parse
     *
     * @return string|null
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException if the environment variable is not set
     */
    private function parseEnvironmentParams($value)
    {
        // env.variable is an environment variable
        $env = explode('.', $value);
        if ('env' === $env[0]) {
            if (!$envParam = getenv($env[1])) {
                throw new InvalidArgumentException("Environment variable '$env[1]' is not defined.");
            }

            return $envParam;
        }

        return null;
    }
}
