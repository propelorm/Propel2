<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\ConfigStore;

use Propel\Generator\Exception\SchemaException;

class ConfigurationStore
{
    /**
     * @var \Propel\Generator\Behavior\ConfigStore\ConfigurationStore|null
     */
    protected static $instance = null;

    /**
     * @var array<\Propel\Generator\Behavior\ConfigStore\ConfigurationItem>
     */
    private static $preconfigurations = [];

    /**
     * @return \Propel\Generator\Behavior\ConfigStore\ConfigurationStore|self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $key
     * @param array<string> $behaviorAttributes
     * @param array<string> $params
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return void
     */
    public function storePreconfiguration(string $key, array $behaviorAttributes, array $params): void
    {
        if ($this->hasPreconfiguration($key)) {
            throw new SchemaException("preconfigure behavior: $key '%s' is already in use.");
        }

        self::$preconfigurations[$key] = new ConfigurationItem($behaviorAttributes, $params);
    }

    /**
     * @param string $key
     *
     * @throws \Propel\Generator\Exception\SchemaException
     *
     * @return \Propel\Generator\Behavior\ConfigStore\ConfigurationItem
     */
    public function loadPreconfiguration(string $key): ConfigurationItem
    {
        if (!array_key_exists($key, self::$preconfigurations)) {
            throw new SchemaException("preconfigure behavior: No preconfigured behavior with key '$key'.");
        }

        return self::$preconfigurations[$key];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasPreconfiguration(string $key): bool
    {
        return array_key_exists($key, self::$preconfigurations);
    }
}
