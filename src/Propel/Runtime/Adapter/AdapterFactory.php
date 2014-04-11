<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
     * @return \Propel\Runtime\Adapter\AdapterInterface           An instance of a Propel database adapter.
     */
    public static function create($driver)
    {
        if (!$driver) {
            $adapterClass = '\Propel\Runtime\Adapter\NoneAdapter';
        } elseif (false === strpos($driver, '\\')) {
            if (!class_exists($adapterClass = '\Propel\Runtime\Adapter\Pdo\\' . ucfirst($driver) . 'Adapter')) {
                $adapterClass = '\Propel\Runtime\Adapter\\' . ucfirst($driver) . 'Adapter';
            }
        } else {
            $adapterClass = $driver;
        }
        if (class_exists($adapterClass)) {
            return new $adapterClass();
        }
        throw new InvalidArgumentException(sprintf('Unsupported Propel driver: "%s". Check your configuration file', $driver));
    }
}
