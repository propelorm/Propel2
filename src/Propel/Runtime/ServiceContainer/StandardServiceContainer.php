<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ServiceContainer;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionManagerInterface;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @psalm-import-type \Propel\Runtime\Map\TableMapDump from \Propel\Runtime\Map\DatabaseMap
 */
class StandardServiceContainer implements ServiceContainerInterface
{
    /**
     * Expected version of the configuration file.
     *
     * @see StandardServiceContainer::checkVersion()
     *
     * @var int
     */
    public const CONFIGURATION_VERSION = 2;

    /**
     * Used in exception when the configuration is outdated.
     *
     * @see StandardServiceContainer::checkVersion()
     * @see StandardServiceContainer::getDatabaseMap()
     *
     * @var string
     */
    protected const HOWTO_FIX_MISSING_LOADER_SCRIPT_URL = 'https://github.com/propelorm/Propel2/wiki/Exception-Target:-Loading-the-database';

    /**
     * @var array<string, \Propel\Runtime\Adapter\AdapterInterface> List of database adapter instances
     */
    protected $adapters = [];

    /**
     * @phpstan-var array<string, class-string<\Propel\Runtime\Adapter\AdapterInterface>>
     *
     * @var array<string, string> List of database adapter classes
     */
    protected $adapterClasses = [];

    /**
     * @var string
     */
    protected $defaultDatasource = ServiceContainerInterface::DEFAULT_DATASOURCE_NAME;

    /**
     * @phpstan-var class-string<\Propel\Runtime\Map\DatabaseMap>
     *
     * @var string
     */
    protected $databaseMapClass = ServiceContainerInterface::DEFAULT_DATABASE_MAP_CLASS;

    /**
     * @var array<\Propel\Runtime\Map\DatabaseMap>|null List of database map instances. Is null if not initialized.
     * @see StandardServiceContainer::initDatabaseMaps();
     */
    protected $databaseMaps;

    /**
     * @var array<\Propel\Runtime\Connection\ConnectionManagerInterface> List of connection managers
     */
    protected $connectionManagers = [];

    /**
     * @phpstan-var class-string<\Propel\Runtime\Util\Profiler>
     *
     * @var string
     */
    protected $profilerClass = ServiceContainerInterface::DEFAULT_PROFILER_CLASS;

    /**
     * @var array
     */
    protected $profilerConfiguration = [];

    /**
     * @var \Propel\Runtime\Util\Profiler|null
     */
    protected $profiler;

    /**
     * @var array<\Psr\Log\LoggerInterface> List of loggers
     */
    protected $loggers = [];

    /**
     * @var array
     */
    protected $loggerConfigurations = [];

    /**
     * @return string
     */
    public function getDefaultDatasource(): string
    {
        return $this->defaultDatasource;
    }

    /**
     * @param string $defaultDatasource
     *
     * @return void
     */
    public function setDefaultDatasource(string $defaultDatasource): void
    {
        $this->defaultDatasource = $defaultDatasource;
    }

    /**
     * Get the adapter class for a given datasource.
     *
     * @param string|null $name The datasource name
     *
     * @return string
     */
    public function getAdapterClass(?string $name = null): string
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
     * @param class-string<\Propel\Runtime\Adapter\AdapterInterface> $adapterClass
     *
     * @return void
     */
    public function setAdapterClass(string $name, string $adapterClass): void
    {
        $this->adapterClasses[$name] = $adapterClass;
        unset($this->adapters[$name]);
    }

    /**
     * Reset existing adapters classes and set new classes for all datasources.
     *
     * @param array<string, class-string<\Propel\Runtime\Adapter\AdapterInterface>> $adapterClasses A list of adapters
     *
     * @return void
     */
    public function setAdapterClasses(array $adapterClasses): void
    {
        $this->adapterClasses = $adapterClasses;
        $this->adapters = [];
    }

    /**
     * Get the adapter for a given datasource.
     *
     * If the adapter does not yet exist, build it using the related adapterClass.
     *
     * @param string|null $name The datasource name
     *
     * @throws \Propel\Runtime\Adapter\Exception\AdapterException
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAdapter(?string $name = null): AdapterInterface
    {
        if ($name === null) {
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
     * @param string $name The datasource name
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return void
     */
    public function setAdapter(string $name, AdapterInterface $adapter): void
    {
        $this->adapters[$name] = $adapter;
        $this->adapterClasses[$name] = get_class($adapter);
    }

    /**
     * Reset existing adapters and set new adapters for all datasources.
     *
     * @param array<string, \Propel\Runtime\Adapter\AdapterInterface> $adapters A list of adapters
     *
     * @return void
     */
    public function setAdapters(array $adapters): void
    {
        $this->adapterClasses = [];
        $this->adapters = [];
        foreach ($adapters as $name => $adapter) {
            $this->setAdapter($name, $adapter);
        }
    }

    /**
     * Checks if the given propel generator version is outdated.
     *
     * @param string|int $generatorVersion
     *
     * @throws \Propel\Runtime\Exception\PropelException Thrown when the configuration is outdated.
     *
     * @return void
     */
    public function checkVersion($generatorVersion): void
    {
        if ($generatorVersion === static::CONFIGURATION_VERSION) {
            return;
        }

        $message = 'Your configuration is outdated. Please rebuild it with the config:convert command.';
        if (!is_int($generatorVersion) || $generatorVersion < 2) {
            $message .= sprintf(' Visit %s for information on how to fix this.', self::HOWTO_FIX_MISSING_LOADER_SCRIPT_URL);
        }

        throw new PropelException($message);
    }

    /**
     * @param array $databaseNameToTableMapClassNames
     *
     * @return void
     */
    public function initDatabaseMaps(array $databaseNameToTableMapClassNames = []): void
    {
        if ($this->databaseMaps === null) {
            $this->databaseMaps = [];
        }

        foreach ($databaseNameToTableMapClassNames as $databaseName => $tableMapClassNames) {
            $databaseMap = $this->getDatabaseMap($databaseName);
            $databaseMap->registerTableMapClasses($tableMapClassNames);
        }
    }

    /**
     * @psalm-param array<string, \Propel\Runtime\Map\TableMapDump> $databaseNameToTableMapDumps
     *
     * @param array<string, array<string, class-string<\Propel\Runtime\Map\TableMap>>> $databaseNameToTableMapDumps
     *
     * @return void
     */
    public function initDatabaseMapFromDumps(array $databaseNameToTableMapDumps = []): void
    {
        if ($this->databaseMaps === null) {
            $this->databaseMaps = [];
        }

        foreach ($databaseNameToTableMapDumps as $databaseName => $tableMapDumps) {
            $databaseMap = $this->getDatabaseMap($databaseName);
            $databaseMap->loadMapsFromDump($tableMapDumps);
        }
    }

    /**
     * @phpstan-param class-string<\Propel\Runtime\Map\DatabaseMap> $databaseMapClass
     *
     * @param string $databaseMapClass
     *
     * @return void
     */
    public function setDatabaseMapClass(string $databaseMapClass): void
    {
        $this->databaseMapClass = $databaseMapClass;
    }

    /**
     * Get the database map for a given datasource.
     *
     * The database maps are "registered" by the generated map builder classes.
     *
     * @param string|null $name The datasource name
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\Map\DatabaseMap
     */
    public function getDatabaseMap(?string $name = null): DatabaseMap
    {
        if (!$name) {
            $name = $this->getDefaultDatasource();
        }
        if ($this->databaseMaps === null) {
            $messageFormat = 'Database map was not initialized. Please check the database loader script included by your conf. '
                . 'Visit %s for information on how to fix this.';
            $message = sprintf($messageFormat, self::HOWTO_FIX_MISSING_LOADER_SCRIPT_URL);

            throw new PropelException($message);
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
     * @param string $name The datasource name
     * @param \Propel\Runtime\Map\DatabaseMap $databaseMap
     *
     * @return void
     */
    public function setDatabaseMap(string $name, DatabaseMap $databaseMap): void
    {
        $this->databaseMaps[$name] = $databaseMap;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionManagerInterface $manager
     *
     * @return void
     */
    public function setConnectionManager(ConnectionManagerInterface $manager): void
    {
        if (isset($this->connectionManagers[$manager->getName()])) {
            $this->connectionManagers[$manager->getName()]->closeConnections();
        }

        $this->connectionManagers[$manager->getName()] = $manager;
    }

    /**
     * @param string $name The datasource name
     *
     * @throws \Propel\Runtime\Exception\RuntimeException - if the datasource doesn't exist
     *
     * @return \Propel\Runtime\Connection\ConnectionManagerInterface
     */
    public function getConnectionManager(string $name): ConnectionManagerInterface
    {
        if (!isset($this->connectionManagers[$name])) {
            throw new RuntimeException(sprintf('No connection defined for database "%s". Did you forget to define a connection or is it wrong written?', $name));
        }

        return $this->connectionManagers[$name];
    }

    /**
     * @param string $name
     *
     * @return bool true if a connectionManager with $name has been registered
     */
    public function hasConnectionManager(string $name): bool
    {
        return isset($this->connectionManagers[$name]);
    }

    /**
     * @return array<\Propel\Runtime\Connection\ConnectionManagerInterface>
     */
    public function getConnectionManagers(): array
    {
        return $this->connectionManagers;
    }

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     *
     * @return void
     */
    public function closeConnections(): void
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
     * @param string|null $name The datasource name
     * @param string $mode The connection mode (this applies to replication systems).
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getConnection(?string $name = null, string $mode = ServiceContainerInterface::CONNECTION_WRITE): ConnectionInterface
    {
        if ($name === null) {
            $name = $this->getDefaultDatasource();
        }

        if ($mode === ServiceContainerInterface::CONNECTION_READ) {
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
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getWriteConnection(string $name): ConnectionInterface
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
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getReadConnection(string $name): ConnectionInterface
    {
        return $this->getConnectionManager($name)->getReadConnection($this->getAdapter($name));
    }

    /**
     * Shortcut to define a single connection for a datasource.
     *
     * @param string $name The datasource name
     * @param \Propel\Runtime\Connection\ConnectionInterface $connection A database connection
     *
     * @return void
     */
    public function setConnection(string $name, ConnectionInterface $connection): void
    {
        $manager = new ConnectionManagerSingle($name);
        $manager->setConnection($connection);
        $this->setConnectionManager($manager);
    }

    /**
     * Override the default profiler class.
     *
     * The service container uses this class to instantiate a new profiler when
     * getProfiler() is called.
     *
     * @phpstan-param class-string<\Propel\Runtime\Util\Profiler> $profilerClass
     *
     * @param string $profilerClass
     *
     * @return void
     */
    public function setProfilerClass(string $profilerClass): void
    {
        $this->profilerClass = $profilerClass;
        $this->profiler = null;
    }

    /**
     * Set the profiler configuration.
     *
     * @see \Propel\Runtime\Util\Profiler::setConfiguration()
     *
     * @param array $profilerConfiguration
     *
     * @return void
     */
    public function setProfilerConfiguration(array $profilerConfiguration): void
    {
        $this->profilerConfiguration = $profilerConfiguration;
        $this->profiler = null;
    }

    /**
     * Set the profiler instance.
     *
     * @param \Propel\Runtime\Util\Profiler $profiler
     *
     * @return void
     */
    public function setProfiler(Profiler $profiler): void
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
    public function getProfiler(): Profiler
    {
        if ($this->profiler === null) {
            $class = $this->profilerClass;
            /** @var \Propel\Runtime\Util\Profiler $profiler */
            $profiler = new $class();
            if ($this->profilerConfiguration) {
                $profiler->setConfiguration($this->profilerConfiguration);
            }
            $this->profiler = $profiler;
        }

        return $this->profiler;
    }

    /**
     * Get a logger instance
     *
     * @param string|null $name
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(?string $name = null): LoggerInterface
    {
        if ($name === null) {
            $name = 'defaultLogger';
        }

        if (!isset($this->loggers[$name])) {
            $this->loggers[$name] = $this->buildLogger($name);
        }

        return $this->loggers[$name];
    }

    /**
     * @param string $name the name of the logger to be set
     * @param \Psr\Log\LoggerInterface $logger A logger instance
     *
     * @return void
     */
    public function setLogger(string $name, LoggerInterface $logger): void
    {
        $this->loggers[$name] = $logger;
    }

    /**
     * @param string $name
     *
     * @throws \Propel\Runtime\Exception\UnexpectedValueException
     *
     * @return \Psr\Log\LoggerInterface
     */
    protected function buildLogger(string $name = 'defaultLogger'): LoggerInterface
    {
        if (!isset($this->loggerConfigurations[$name])) {
            return $name !== 'defaultLogger' ? $this->getLogger() : new NullLogger();
        }

        $logger = new Logger($name);
        $configuration = $this->loggerConfigurations[$name];
        switch ($configuration['type']) {
            case 'stream':
                $handler = new StreamHandler(
                    $configuration['path'],
                    $configuration['level'] ?? 500,
                    $configuration['bubble'] ?? true,
                );

                break;
            case 'rotating_file':
                $handler = new RotatingFileHandler(
                    $configuration['path'],
                    $configuration['max_files'] ?? 0,
                    $configuration['level'] ?? 100,
                    $configuration['bubble'] ?? true,
                );

                break;
            case 'syslog':
                $handler = new SyslogHandler(
                    $configuration['ident'],
                    $configuration['facility'] ?? LOG_USER,
                    $configuration['level'] ?? 100,
                    $configuration['bubble'] ?? true,
                );

                break;
            default:
                throw new UnexpectedValueException(sprintf(
                    'Handler type `%s` not supported by StandardServiceContainer. Try setting the Logger manually, or use another ServiceContainer.',
                    $configuration['type'],
                ));
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
     * @param array $loggerConfiguration
     *
     * @return void
     */
    public function setLoggerConfiguration(string $name, array $loggerConfiguration): void
    {
        $this->loggerConfigurations[$name] = $loggerConfiguration;
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Enable or disable debug output.
     *
     * Sets connections in debug mode. This only works when the default
     * ConnectionWrapper is used, and it does not override instance-specific
     * settings.
     *
     * @see \Propel\Runtime\Connection\ConnectionWrapper::useDebug()
     * @see \Propel\Runtime\Connection\ConnectionWrapper::isInDebugMode()
     * @see \Propel\Runtime\Connection\ProfilerConnectionWrapper
     *
     * @param bool $useDebug
     * @param bool|null $logStatementProfile If true, profile data of statement
     *              execution (execution time, used memory) is added to log
     *              output. Defaults to value of $useDebug.
     *
     * @return void
     */
    public function useDebugMode(bool $useDebug = true, ?bool $logStatementProfile = null): void
    {
        ConnectionWrapper::$useDebugMode = $useDebug;
        ConnectionFactory::$useProfilerConnection = $logStatementProfile ?? $useDebug;
        $this->closeConnections();
    }
}
