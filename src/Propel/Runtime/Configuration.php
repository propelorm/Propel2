<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime;

use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Exception\PropelException;

class Configuration
{
    /**
     * The unique instance for this singleton.
     * @var \Propel\Runtime\Configuration
     */
    private static $instance;

    /**
     * List of database adapter instances.
     * @var array[\Propel\Runtime\Adapter\AdapterInterface]
     */
    private $adapters = array();

    /**
     * List of database adapter classes.
     * @var array[string]
     */
    private $adapterClasses = array();

    /**
     * @var string
     */
    private $defaultDatasource = 'default';

    /**
     * @var string
     */
    private $databaseMapClass = '\Propel\Runtime\Map\DatabaseMap';
    
    /**
     * List of database map instances.
     * @var array[\Propel\Runtime\Map\DatabaseMap]
     */
    private $databaseMaps = array();

    /**
     * Get the singleton instance for this class.
     *
     * @return \Propel\Runtime\Configuration
     */
    final public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getDefaultDatasource()
    {
        return $this->defaultDatasource;
    }

    /**
     * @param string $defaultDatasource
     */
    public function setDefaultDatasource($defaultDatasource)
    {
        $this->defaultDatasource = $defaultDatasource;
    }

    /**
     * Get the adapter class for a given datasource.
     *
     * @param string $name The datasource name
     *
     * @return string
     */
    public function getAdapterClass($name = null)
    {
        if ($name === null) {
            $name = $this->getDefaultDatasource();
        }

        return $this->adapterClasses[$name];
    }

    /**
     * Set the adapter class for a given datasource.
     *
     * This allows for lazy-loading adapter objects in getAdapter().
     *
     * @param string $name The datasource name
     * @param string $adapterClass
     */
    public function setAdapterClass($name, $adapterClass)
    {
        $this->adapterClasses[$name] = $adapterClass;
        unset($this->adapters[$name]);
    }

    /**
     * Reset existing adapters classes and set new classes for all datasources.
     *
     * @param array $adapters A list of adapters
     */
    public function setAdapterClasses($adapterClasses)
    {
        $this->adapterClasses = $adapterClasses;
        $this->adapters = null;
    }

    /**
     * Get the adapter for a given datasource.
     *
     * If the adapter does not yet exist, build it using the related adapterClass.
     *
     * @param string $name The datasource name
     *
     * @return Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAdapter($name = null)
    {
        if ($name === null) {
            $name = $this->getDefaultDatasource();
        }
        if (!isset($this->adapters[$name])) {
            if (!isset($this->adapterClasses[$name])) {
                throw new PropelException(sprintf('No adapter class defined for datasource "%s"', $name));
            }
            $this->adapters[$name] = AdapterFactory::create($this->adapterClasses[$name]);
        }

        return $this->adapters[$name];
    }
    
    /**
     * Set the adapter for a given datasource.
     *
     * @param string $name The datasource name
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     */
    public function setAdapter($name, AdapterInterface $adapter)
    {
        $this->adapters[$name] = $adapter;
        $this->adapterClasses[$name] = get_class($adapter);
    }

    /**
     * Reset existing adapters and set new adapters for all datasources.
     *
     * @param array $adapters A list of adapters
     */
    public function setAdapters($adapters)
    {
        foreach ($adapters as $name => $adapter) {
            $this->setAdapter($name, $adapter);
        }
    }

    /**
     * @param string $databaseMapClass
     */
    public function setDatabaseMapClass($databaseMapClass)
    {
        $this->databaseMapClass = $databaseMapClass;
    }

    /**
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Map\DatabaseMap
     */
    public function getDatabaseMap($name = null)
    {
        if ($name === null) {
            $name = $this->getDefaultDatasource();
        }
        if (!isset($this->databaseMaps[$name])) {
            $class = $this->databaseMapClass;
            $this->databaseMaps[$name] = new $class($name);
        }
        
        return $this->databaseMaps[$name];
    }

    /**
     * @param string $name The datasource name
     * @param \Propel\Runtime\Map\DatabaseMap $databaseMap
     */
    public function setDatabaseMap($name, DatabaseMap $databaseMap)
    {
        $this->databaseMaps[$name] = $databaseMap;
    }
    
    final private function __clone()
    {
    }
}
