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
use Propel\Runtime\Adapter\AdapterException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Exception\PropelException;

class Configuration
{
    /**
     * Constant used to request a READ connection (applies to replication).
     */
    const CONNECTION_READ = 'read';

    /**
     * Constant used to request a WRITE connection (applies to replication).
     */
    const CONNECTION_WRITE = 'write';

    /**
     * The default DatabaseMap class created by getDatabaseMap()
     */
    const DEFAULT_DATABASE_MAP_CLASS = '\Propel\Runtime\Map\DatabaseMap';

    /**
     * The default connection class created by initConnection()
     */
    const DEFAULT_CONNECTION_CLASS = '\Propel\Runtime\Connection\ConnectionWrapper';
    
    /**
     * @var \Propel\Runtime\Configuration The unique instance for this singleton
     */
    private static $instance;

    /**
     * @var array[\Propel\Runtime\Adapter\AdapterInterface] List of database adapter instances
     */
    private $adapters = array();

    /**
     * @var array[string] List of database adapter classes
     */
    private $adapterClasses = array();

    /**
     * @var string
     */
    private $defaultDatasource = 'default';

    /**
     * @var string
     */
    private $databaseMapClass = self::DEFAULT_DATABASE_MAP_CLASS;
    
    /**
     * @var array[\Propel\Runtime\Map\DatabaseMap] List of database map instances
     */
    private $databaseMaps = array();

    /**
     * @var bool For replication, whether to force the use of master connection.
     */
    private $isForceMasterConnection = false;

    /**
     * @var array[\Propel\Runtime\Connection\ConnectionInterface] List of established connections
     */
    private $connections = array();

    /**
     * @var array List of the database connection settings, required to establish actual connections
     */
    private $connectionConfigurations = array();
    
    /**
     * @var string
     */
    private $connectionClass = self::DEFAULT_CONNECTION_CLASS;
    
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

    /**
     * Get the connection settings for a given datasource.
     *
     * @param string $name The datasource name
     * @param string $mode Whether this is for a READ or WRITE connection (self::CONNECTION_READ or self::CONNECTION_WRITE)
     *
     * @return array
     */
    public function getConnectionConfiguration($name = null, $mode = self::CONNECTION_WRITE)
    {
        if ($name === null) {
            $name = $this->getDefaultDatasource();
        }
        
        return $this->connectionConfigurations[$name][$mode];
    }

    /**
     * Chack whether the connection settings are defined for a given datasource.
     *
     * @param string $name The datasource name
     * @param string $mode Whether this is for a READ or WRITE connection (self::CONNECTION_READ or self::CONNECTION_WRITE)
     *
     * @return bool
     */
    public function hasConnectionConfiguration($name = null, $mode = self::CONNECTION_WRITE)
    {
        if ($name === null) {
            $name = $this->getDefaultDatasource();
        }
        
        return isset($this->connectionConfigurations[$name][$mode]);
    }

    /**
     * Set the connection settings for a given datasource.
     *
     * Example connection settings:
     * array(
     *   'write' => array(
     *     'dsn'      => 'mysql:dbname=test_master',
     *     'user'     => 'mywriteuser',
     *     'password' => 'S3cr3t'
     *   ),
     *   'read' => array(
     *     array(
     *       'dsn'      => 'mysql:dbname=test_slave1',
     *       'user'     => 'myreaduser',
     *       'password' => 'F00baR'
     *     ),
     *     array(
     *       'dsn'      => 'mysql:dbname=test_slave2',
     *       'user'     => 'myreaduser',
     *       'password' => 'F00baR'
     *     )
     *   )
     * )
     *
     * @param string $name The datasource name
     * @param array $connectionSettings
     */
    public function setConnectionConfiguration($name, $connectionConfiguration)
    {
        $this->connectionConfigurations[$name] = $connectionConfiguration;
        $this->closeConnection($name);
    }

    /**
     * Set connection settings for all datasources.
     *
     * Also close existing connections.
     *
     * @param array $connectionConfigurations A list of connection configurations
     */
    public function setConnectionConfigurations($connectionConfigurations)
    {
        foreach ($connectionConfigurations as $name => $connectionConfiguration) {
            $this->setConnectionConfiguration($name, $connectionConfiguration);
        }
    }

    /**
     * For replication, whether to always force the use of a master connection.
     *
     * @return     boolean
     */
    public function isForceMasterConnection()
    {
        return $this->isForceMasterConnection;
    }

    /**
     * For replication, set whether to always force the use of a master connection.
     *
     * @param      boolean $isForceMasterConnection
     */
    public function setForceMasterConnection($isForceMasterConnection)
    {
        $this->isForceMasterConnection = (bool) $isForceMasterConnection;
    }

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     */
    public function closeConnections()
    {
        foreach ($this->connections as $name => $connection) {
            $this->closeConnection($name);
        }
    }

    /**
     * Close a connection for a given datasource.
     *
     * This method frees the database connection handles that have been
     * opened by the getConnection() method.
     *
     * @param string $name The datasource name
     */
    public function closeConnection($name)
    {
        unset($this->connections[$name][self::CONNECTION_WRITE]);
        unset($this->connections[$name][self::CONNECTION_READ]);
    }

    /**
     * Get a connection for a given datasource.
     * 
     * If the connection has not been opened, open it using the related 
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param      string $name The datasource name
     * @param      string $mode The connection mode (this applies to replication systems).
     *
     * @return     \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getConnection($name = null, $mode = self::CONNECTION_WRITE)
    {
        if (null === $name) {
            $name = $this->getDefaultDatasource();
        }
        if (self::CONNECTION_WRITE == $mode || $this->isForceMasterConnection()) {
            return $this->getMasterConnection($name);
        }
        return $this->getSlaveConnection($name);
    }

    /**
     * Get a write connection for a given datasource.
     * 
     * If the connection has not been opened, open it using the related 
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param      string $name The datasource name that is used to look up the DSN
     *                          from the runtime configuration file. Empty name not allowed.
     *
     * @return     ConnectionInterface A database connection
     *
     * @throws     PropelException - if connection is not properly configured
     */
    public function getMasterConnection($name)
    {
        $mode = self::CONNECTION_WRITE;
        if (!isset($this->connections[$name][$mode])) {
            if (!$this->hasConnectionConfiguration($name, $mode)) {
                throw new PropelException(sprintf('No connection settings defined for datasource "%s"', $name));
            }
            $con = $this->initConnection($name, $this->getConnectionConfiguration($name, $mode));
            $this->connections[$name][$mode] = $con;
        }

        return $this->connections[$name][$mode];
    }

    /**
     * Get a read connection for a given datasource.
     *
     * If the slave connection has not been opened, open it using a random read connection
     * setting for the related datasource. If no read connection setting exist, return the master
     * connection. If the slave connection has already been opened, return it.
     *
     * @param      string $name The datasource name that is used to look up the DSN
     *                          from the runtime configuration file. Empty name not allowed.
     *
     * @return     ConnectionInterface A database connection
     */
    public function getSlaveConnection($name)
    {
        $mode = self::CONNECTION_READ;
        if (!isset($this->connections[$name][$mode])) {
            if (!$this->hasConnectionConfiguration($name, $mode)) {
                // fallback to master
                return $this->getMasterConnection($name);
            }
            $slaveConnectionConfigurations = $this->getConnectionConfiguration($name, $mode);
            $key = mt_rand(0, count($slaveConnectionConfigurations) - 1);
            $con = $this->initConnection($name, $slaveConnectionConfigurations[$key]);
            $this->connections[$name][$mode] = $con;
        }

        return $this->connections[$name][$mode];
    }

    /**
     * Set a connection for a given datasource.
     *
     * @param      string $name The datasource name
     * @param      \Propel\Runtime\Connection\ConnectionInterface $con The database connection.
     * @param      string $mode Whether this is a READ or WRITE connection (self::CONNECTION_READ or self::CONNECTION_WRITE)
     */
    public function setConnection($name, ConnectionInterface $con, $mode = self::CONNECTION_WRITE)
    {
        if ($mode == self::CONNECTION_READ) {
            $this->setSlaveConnection($name, $con);
        } else {
            $this->setMasterConnection($name, $con);
        }
    }

    /**
     * Sets the master connection for a given datasource name
     *
     * @param      string $name The datasource name
     * @param      \Propel\Runtime\Connection\ConnectionInterface $con The database connection.
     */
    public function setMasterConnection($name, ConnectionInterface $con)
    {
        $this->connections[$name][self::CONNECTION_WRITE] = $con;
    }

    /**
     * Sets the slave connection a given datasource
     *
     * @param      string $name The datasource name
     * @param      \Propel\Runtime\Connection\ConnectionInterface $con The database connection.
     */
    public function addSlaveConnection($name, ConnectionInterface $con)
    {
        $this->connections[$name][self::CONNECTION_READ][] = $con;
    }

    /**
     * Opens a new connection for a given datasource.
     *
     * Use the connection configuration to build a connection.
     *
     * @param      string $name Datasource name.
     * @param      array $connectionConfiguration Connection paramters.
     * @param      string $connectionClass  The name of a class implementing
     *                                      \Propel\Runtime\Connection\ConnectionInterface,
     *                                     (default is ConnectionWrapper)
     *
     * @return     \Propel\Runtime\Connection\ConnectionWrapper A database connection
     *
     * @throws     PropelException - if lower-level exception caught when trying to connect.
     */
    public function initConnection($name, $connectionConfiguration)
    {
        if (isset($connectionConfiguration['classname'])) {
            $connectionClass = $connectionConfiguration['classname'];
        } else {
            $connectionClass = $this->connectionClass;
        }
        $adapter = $this->getAdapter($name);
        try {
            $adapterConnection = $adapter->getConnection($connectionConfiguration);
        } catch (AdapterException $e) {
            throw new PropelException("Unable to open PDO connection", $e);
        }
        $connection = new $connectionClass($adapterConnection);

        // load any connection options from the config file
        // connection attributes are those PDO flags that have to be set on the initialized connection
        if (isset($connectionConfiguration['attributes']) && is_array($connectionConfiguration['attributes'])) {
            foreach ($connectionConfiguration['attributes'] as $option => $value) {
                if (is_string($value) && false !== strpos($value, '::')) {
                    if (!defined($value)) {
                        throw new PropelException(sprintf('Invalid class constant specified "%s" while processing connection attributes for datasource "%s"'), $value, $name);
                    }
                    $value = constant($value);
                }
                $connection->setAttribute($option, $value);
            }
        }

        return $connection;
    }

    /**
     * @param string $connectionClass
     */
    public function setConnectionClass($connectionClass)
    {
        $this->connectionClass = $connectionClass;
    }

    final private function __clone()
    {
    }
}
