<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ServiceContainer;

use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionManagerInterface;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Propel;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StandardServiceContainer implements ServiceContainerInterface
{
    /**
     * @var array[\Propel\Runtime\Adapter\AdapterInterface] List of database adapter instances
     */
    protected $adapters = array();

    /**
     * @var array[string] List of database adapter classes
     */
    protected $adapterClasses = array();

    /**
     * @var string
     */
    protected $defaultDatasource = ServiceContainerInterface::DEFAULT_DATASOURCE_NAME;

    /**
     * @var string
     */
    protected $databaseMapClass = ServiceContainerInterface::DEFAULT_DATABASE_MAP_CLASS;

    /**
     * @var array[\Propel\Runtime\Map\DatabaseMap] List of database map instances
     */
    protected $databaseMaps = array();

    /**
     * @var array[\Propel\Runtime\Connection\ConnectionManagerInterface] List of connection managers
     */
    protected $connectionManagers = array();

    /**
     * @var string
     */
    protected $profilerClass = ServiceContainerInterface::DEFAULT_PROFILER_CLASS;

    /**
     * @var array
     */
    protected $profilerConfiguration = array();

    /**
     * @var \Propel\Runtime\Util\Profiler
     */
    protected $profiler;

    /**
     * @var array[LoggerInterface] list of loggers
     */
    protected $loggers = array();

    /**
     * @var array
     */
    protected $loggerConfigurations = array();

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
        if (null === $name) {
            $name = $this->getDefaultDatasource();
        }

        return $this->adapterClasses[$name];
    }

    /**
     * Set the adapter class for a given datasource.
     *
     * This allows for lazy-loading adapter objects in getAdapter().
     *
     * @param string $name         The datasource name
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
     * @param array $adapterClasses A list of adapters
     */
    public function setAdapterClasses($adapterClasses)
    {
        $this->adapterClasses = $adapterClasses;
        $this->adapters = array();
    }

    /**
     * Get the adapter for a given datasource.
     *
     * If the adapter does not yet exist, build it using the related adapterClass.
     *
     * @param string $name The datasource name
     *
     * @return AdapterInterface
     *
     * @throws AdapterException
     */
    public function getAdapter($name = null)
    {
        if (null === $name) {
            $name = $this->getDefaultDatasource();
        }
        if (!isset($this->adapters[$name])) {
            if (!isset($this->adapterClasses[$name])) {
                throw new AdapterException(sprintf('No adapter class defined for datasource "%s"', $name));
            }
            $this->adapters[$name] = AdapterFactory::create($this->adapterClasses[$name]);
        }

        return $this->adapters[$name];
    }

    /**
     * Set the adapter for a given datasource.
     *
     * @param string                                   $name    The datasource name
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
        $this->adapterClasses = array();
        $this->adapters = array();
        foreach ($adapters as $name => $adapter) {
            $this->setAdapter($name, $adapter);
        }
    }

    /**
     * check whether the given propel generator version has the same version as
     * the propel runtime.
     *
     * @param string $generatorVersion
     */
    public function checkVersion($generatorVersion)
    {
        if ($generatorVersion != Propel::VERSION) {
            $warning  = "Version mismatch: The generated model was build using propel '" . $generatorVersion;
            $warning .= " while the current runtime is at version '" . Propel::VERSION . "'";

            $logger = $this->getLogger();
            if ($logger) {
                $logger->warning($warning);
            } else {
                trigger_error($warning, E_USER_WARNING);
            }
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
     * Get the database map for a given datasource.
     *
     * The database maps are "registered" by the generated map builder classes.
     *
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Map\DatabaseMap
     */
    public function getDatabaseMap($name = null)
    {
        if (null === $name) {
            $name = $this->getDefaultDatasource();
        }
        if (!isset($this->databaseMaps[$name])) {
            $class = $this->databaseMapClass;
            $this->databaseMaps[$name] = new $class($name);
        }

        return $this->databaseMaps[$name];
    }

    /**
     * Set the database map object to use for a given datasource.
     *
     * @param string                          $name        The datasource name
     * @param \Propel\Runtime\Map\DatabaseMap $databaseMap
     */
    public function setDatabaseMap($name, DatabaseMap $databaseMap)
    {
        $this->databaseMaps[$name] = $databaseMap;
    }

    /**
     * @param string                                                $name    The datasource name
     * @param \Propel\Runtime\Connection\ConnectionManagerInterface $manager
     */
    public function setConnectionManager($name, ConnectionManagerInterface $manager)
    {
        if (isset($this->connectionManagers[$name])) {
            $this->connectionManagers[$name]->closeConnections();
        }
        if (!$manager->getName()) {
            $manager->setName($name);
        }
        $this->connectionManagers[$name] = $manager;
    }

    /**
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Connection\ConnectionManagerInterface
     * @throws \Propel\Runtime\Exception\RuntimeException - if the datasource doesn't exist
     */
    public function getConnectionManager($name)
    {
        if (!isset($this->connectionManagers[$name])) {
            throw new RuntimeException(sprintf('No connection defined for database "%s". Did you forget to define a connection or is it wrong written?', $name));
        }

        return $this->connectionManagers[$name];
    }

    /**
     * @param string $name
     *
     * @return boolean true if a connectionManager with $name has been registered
     */
    public function hasConnectionManager($name)
    {
        return isset($this->connectionManagers[$name]);
    }

    /**
     * @return array[\Propel\Runtime\Connection\ConnectionManagerInterface]
     */
    public function getConnectionManagers()
    {
        return $this->connectionManagers;
    }

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     */
    public function closeConnections()
    {
        foreach ($this->connectionManagers as $manager) {
            $manager->closeConnections();
        }
    }

    /**
     * Get a connection for a given datasource.
     *
     * If the connection has not been opened, open it using the related
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param string $name The datasource name
     * @param string $mode The connection mode (this applies to replication systems).
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getConnection($name = null, $mode = ServiceContainerInterface::CONNECTION_WRITE)
    {
        if (null === $name) {
            $name = $this->getDefaultDatasource();
        }

        if (ServiceContainerInterface::CONNECTION_READ === $mode) {
            return $this->getReadConnection($name);
        }

        return $this->getWriteConnection($name);
    }

    /**
     * Get a write connection for a given datasource.
     *
     * If the connection has not been opened, open it using the related
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param string $name The datasource name that is used to look up the DSN
     *                     from the runtime configuration file. Empty name not allowed.
     *
     * @return ConnectionInterface A database connection
     *
     * @throws \Propel\Runtime\Adapter\Exception\AdapterException - if connection is not properly configured
     */
    public function getWriteConnection($name)
    {
        return $this->getConnectionManager($name)->getWriteConnection($this->getAdapter($name));
    }

    /**
     * Get a read connection for a given datasource.
     *
     * If the slave connection has not been opened, open it using a random read connection
     * setting for the related datasource. If no read connection setting exist, return the master
     * connection. If the slave connection has already been opened, return it.
     *
     * @param string $name The datasource name that is used to look up the DSN
     *                     from the runtime configuration file. Empty name not allowed.
     *
     * @return ConnectionInterface A database connection
     */
    public function getReadConnection($name)
    {
        return $this->getConnectionManager($name)->getReadConnection($this->getAdapter($name));
    }

    /**
     * Shortcut to define a single connection for a datasource.
     *
     * @param string $name The datasource name
     * @param \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function setConnection($name, ConnectionInterface $connection)
    {
        $manager = new ConnectionManagerSingle();
        $manager->setConnection($connection);
        $this->setConnectionManager($name, $manager);
    }

    /**
     * Override the default profiler class.
     *
     * The service container uses this class to instantiate a new profiler when
     * getProfiler() is called.
     *
     * @param string $profilerClass
     */
    public function setProfilerClass($profilerClass)
    {
        $this->profilerClass = $profilerClass;
        $this->profiler = null;
    }

    /**
     * Set the profiler configuration.
     * @see \Propel\Runtime\Util\Profiler::setConfiguration()
     *
     * @param array $profilerConfiguration
     */
    public function setProfilerConfiguration($profilerConfiguration)
    {
        $this->profilerConfiguration = $profilerConfiguration;
        $this->profiler = null;
    }

    /**
     * Set the profiler instance.
     *
     * @param \Propel\Runtime\Util\Profiler $profiler
     */
    public function setProfiler($profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * Get a profiler instance.
     *
     * If no profiler is set, create one using profilerClass and profilerConfiguration.
     *
     * @return \Propel\Runtime\Util\Profiler
     */
    public function getProfiler()
    {
        if (null === $this->profiler) {
            $class = $this->profilerClass;
            $profiler = new $class();
            if (!empty($this->profilerConfiguration)) {
                $profiler->setConfiguration($this->profilerConfiguration);
            }
            $this->profiler = $profiler;
        }

        return $this->profiler;
    }

    /**
     * Get a logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger($name = 'defaultLogger')
    {
        if (!isset($this->loggers[$name])) {
            $this->loggers[$name] = $this->buildLogger($name);
        }

        return $this->loggers[$name];
    }

    /**
     * @param string          $name   the name of the logger to be set
     * @param LoggerInterface $logger A logger instance
     */
    public function setLogger($name, LoggerInterface $logger)
    {
        $this->loggers[$name] = $logger;
    }

    protected function buildLogger($name = 'defaultLogger')
    {
        if (!isset($this->loggerConfigurations[$name])) {
            return 'defaultLogger' !== $name ? $this->getLogger() : new NullLogger();
        }

        $logger        = new Logger($name);
        $configuration = $this->loggerConfigurations[$name];
        switch ($configuration['type']) {
            case 'stream':
                $handler = new \Monolog\Handler\StreamHandler(
                    $configuration['path'],
                    isset($configuration['level']) ? $configuration['level'] : null,
                    isset($configuration['bubble']) ? $configuration['bubble'] : null
                );
                break;
            case 'rotating_file':
                $handler = new \Monolog\Handler\RotatingFileHandler(
                    $configuration['path'],
                    isset($configuration['max_files']) ? $configuration['max_files'] : null,
                    isset($configuration['level']) ? $configuration['level'] : null,
                    isset($configuration['bubble']) ? $configuration['bubble'] : null
                );
                break;
            case 'syslog':
                $handler = new \Monolog\Handler\SyslogHandler(
                    $configuration['ident'],
                    isset($configuration['facility']) ? $configuration['facility'] : null,
                    isset($configuration['level']) ? $configuration['level'] : null,
                    isset($configuration['bubble']) ? $configuration['bubble'] : null
                );
                break;
            default:
                throw new UnexpectedValueException(sprintf(
                    'Handler type "%s" not supported by StandardServiceContainer. Try setting the Logger manually, or use another ServiceContainer.',
                    $configuration['type']
                ));
                break;
        }
        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * Set the configuration for the logger of a given datasource.
     *
     * A logger configuration must contain a 'handlers' key defining one
     * or more handlers of type stream, rotating_file, or syslog.
     * You can also create more complex loggers by hand and set them directly
     * using setLogger().
     *
     * @example
     * <code>
     * $sc->setLoggerConfiguration('bookstore', array(
     *   'handlers' => array('stream' => array('path' => '/var/log/Propel.log'))
     *  ));
     * </code>
     *
     * @param string $name
     * @param array  $loggerConfiguration
     */
    public function setLoggerConfiguration($name, $loggerConfiguration)
    {
        $this->loggerConfigurations[$name] = $loggerConfiguration;
    }

    final private function __clone()
    {
    }
}
