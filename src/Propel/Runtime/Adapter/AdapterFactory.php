<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\Exception\InvalidArgumentException;

/**
 * Factory for Adapter classes.
 */
class AdapterFactory
{
    /**
     * Creates a new instance of the database adapter associated
     * with the specified Propel driver.
     *
     * @param string $driver The name of the Propel driver to create a new adapter instance
     *                       for or a shorter form adapter key.
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException If the adapter could not be instantiated.
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface An instance of a Propel database adapter.
     */
    public static function create(string $driver): AdapterInterface
    {
        if (!$driver) {
            $adapterClass = '\Propel\Runtime\Adapter\NoneAdapter';
        } elseif (strpos($driver, '\\') === false) {
            $adapterClass = '\Propel\Runtime\Adapter\Pdo\\' . ucfirst($driver) . 'Adapter';
            if (!class_exists($adapterClass)) {
                $adapterClass = '\Propel\Runtime\Adapter\\' . ucfirst($driver) . 'Adapter';
            }
        } else {
            $adapterClass = $driver;
        }
        if (class_exists($adapterClass)) {
            /** @var \Propel\Runtime\Adapter\AdapterInterface $adapter */
            $adapter = new $adapterClass();

            return $adapter;
        }

        throw new InvalidArgumentException(sprintf('Unsupported Propel driver: "%s". Check your configuration file', $driver));
    }
}
