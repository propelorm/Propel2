<?php

namespace Propel\Runtime;

use Monolog\Logger;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Connection\ConnectionManagerInterface;
use Propel\Runtime\Exception\UnexpectedValueException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Persister\PersisterInterface;
use Propel\Runtime\Repository\Repository;
use Propel\Runtime\Session\Session;
use Propel\Runtime\Session\SessionFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This static class is used to handle Propel initialization and to maintain all of the
 * open database connections and instantiated database maps.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 * @author Hans Lellelid <hans@xmpl.rg> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Magnús Þór Torfason <magnus@handtolvur.is> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Rafal Krzewski <Rafal.Krzewski@e-point.pl> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Kurt Schrader <kschrader@karmalab.org> (Torque)
 */
class Configuration extends GeneratorConfig
{
    /**
     * A constant defining 'System is unusable' logging level
     */
    const LOG_EMERG = 550;

    /**
     * A constant defining 'Immediate action required' logging level
     */
    const LOG_ALERT = 550;

    /**
     * A constant defining 'Critical conditions' logging level
     */
    const LOG_CRIT = 500;

    /**
     * A constant defining 'Error conditions' logging level
     */
    const LOG_ERR = 400;

    /**
     * A constant defining 'Warning conditions' logging level
     */
    const LOG_WARNING = 300;

    /**
     * A constant defining 'Normal but significant' logging level
     */
    const LOG_NOTICE = 200;

    /**
     * A constant defining 'Informational' logging level
     */
    const LOG_INFO = 200;

    /**
     * A constant defining 'Debug-level messages' logging level
     */
    const LOG_DEBUG = 100;

    /**
     * Connections configured in the `runtime` section of the configuration file
     *
     * @var array
     */
    protected $runtimeConnections = null;

    /**
     * @var ConnectionManagerInterface[]
     */
    public $connectionManager = [];

    /**
     * @var string[]
     */
    protected $databaseToAdapter = [];

    /**
     * @var AdapterInterface[]
     */
    protected $adapters = [];

    /**
     * @var Session\SessionFactory
     */
    protected $sessionFactory;

    /**
     * Map from full entity class name to entity map.
     *
     * @var EntityMap[]
     */
    protected $entityMaps = [];

    /**
     * @var string[]
     */
    protected $entityToDatabaseMap = [];
    protected $databaseToEntitiesMap = [];

    /**
     * @var DatabaseMap[]
     */
    protected $databaseMaps = [];

    /**
     * @var string
     */
    protected $defaultDatasource = 'default';

    /**
     * @var Configuration
     */
    public static $globalConfiguration;

    /**
     * @var LoggerInterface[]
     */
    protected $loggers = [];

    /**
     * @var array
     */
    protected $loggerConfigurations = [];

    /**
     * @var string
     */
    protected $profilerClass = '\Propel\Runtime\Util\Profiler';

    /**
     * @var \Propel\Runtime\Util\Profiler
     */
    protected $profiler;

    /**
     * @var array
     */
    protected $profilerConfiguration = array();

    /**
     * @var Repository[]
     */
    protected $repositories = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    protected $typeMaps = [];

    /**
     * @param string $filename
     * @param array  $extraConf
     */
    public function __construct($filename = null, $extraConf = array())
    {
        if ($filename || $extraConf) {
            parent::__construct($filename, $extraConf);

            foreach ($this->getRuntimeConnections() as $name => $connection) {
                $this->databaseToAdapter[$name] = $connection['adapter'];
                $connectionManager = $this->buildConnectionManager($name, $connection);
                $this->connectionManager[$name] = $connectionManager;
            }
        }

        if (!static::$globalConfiguration) {
            static::$globalConfiguration = $this;
        }
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        if (null === $this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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
            $warning = "Version mismatch: The generated configuration was build using propel '" . $generatorVersion;
            $warning .= " while the current runtime is at version '" . Propel::VERSION . "'.\n";
            $warning .= "Please consider a new build of your php configuration file using build:config command.";

            $logger = $this->getLogger();
            if ($logger) {
                $logger->warning($warning);
            } else {
                trigger_error($warning, E_USER_WARNING);
            }
        }
    }

    /**
     * Returns the current configuration used by ActiveRecord entities.
     *
     * @return Configuration
     */
    public static function getCurrentConfiguration()
    {
        if (!static::$globalConfiguration) {
            throw new RuntimeException('There is no propel configuration instantiated which could be used for active record entities.');
        }

        return static::$globalConfiguration;
    }

    /**
     * Sets the current configuration for the global calls, like ActiveRecord entities.
     *
     * @param Configuration $configuration
     */
    public static function registerConfiguration(Configuration $configuration)
    {
        static::$globalConfiguration = $configuration;
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
     * Registers and associates a entity class with a database and thus known by this configuration and
     * can be used to retrieve table map, repository etc.
     *
     * This is usually called during QuickBuilder or the converted configuration script.
     *
     * @param string          $databaseName
     * @param string|string[] $fullEntityClassName
     */
    public function registerEntity($databaseName, $fullEntityClassName)
    {
        foreach ((array)$fullEntityClassName as $fullEntityClassName) {
            $this->entityToDatabaseMap[$fullEntityClassName] = $databaseName;
            $this->databaseToEntitiesMap[$databaseName][] = $fullEntityClassName;
        }
    }

    /**
     * Returns a list of full class names of all known entities associated with $databaseName.
     *
     * @param string $databaseName
     *
     * @return string[]
     */
    public function getEntitiesForDatabase($databaseName)
    {
        return $this->databaseToEntitiesMap[$databaseName];
    }

    /**
     * Returns all EntityMap instances for all known entities in this database.
     * Note this can be extremely SLOW since all EntityMaps and it's requirements Query, Repository and
     * Entity classes will be loaded by the auto loader. Use getEntitiesForDatabase if you only want to
     * see if a entity name list.
     *
     * @param string $databaseName
     *
     * @return EntityMap[]
     */
    public function getEntityMapsForDatabase($databaseName)
    {
        $entities = [];
        foreach ($this->databaseToEntitiesMap[$databaseName] as $entityClass) {
            $entities[$entityClass] = $this->getEntityMap($entityClass);
        }

        return $entities;
    }

    /**
     * @param string $fullEntityClassName fqcn
     *
     * @return DatabaseMap
     */
    public function getDatabaseForEntityClass($fullEntityClassName)
    {
        $databaseName = $this->entityToDatabaseMap[$fullEntityClassName];

        return $this->getDatabase($databaseName);
    }

    /**
     * @param string $databaseName
     *
     * @return DatabaseMap
     */
    public function getDatabase($databaseName = 'default')
    {
        if (!isset($this->databaseMaps[$databaseName])) {
            $this->databaseMaps[$databaseName] = new DatabaseMap($databaseName);

            $this->getEntityMapsForDatabase($databaseName); //this registers all entityMaps to $this->databaseMaps[$databaseName]
        }

        return $this->databaseMaps[$databaseName];
    }

    /**
     * @param string $fullEntityClassName
     *
     * @return bool
     */
    public function hasEntityMap($fullEntityClassName)
    {
        return isset($this->entityToDatabaseMap[$fullEntityClassName]);
    }

    /**
     * @param string $fullEntityClassName
     *
     * @return EntityMap
     */
    public function getEntityMap($fullEntityClassName)
    {
        $fullEntityClassName = trim($fullEntityClassName, '\\');
        if (!isset($this->entityToDatabaseMap[$fullEntityClassName])) {
            throw new RuntimeException(sprintf('Entity `%s` not assigned to any database', $fullEntityClassName));
        }

        if (isset($this->entityMaps[$fullEntityClassName])) {
            return $this->entityMaps[$fullEntityClassName];
        }

        $databaseName = $this->entityToDatabaseMap[$fullEntityClassName];

        $namespaces = explode('\\', $fullEntityClassName);
        $className = array_pop($namespaces);

        $entityMapClass = implode('\\', $namespaces) . '\\Map\\' . $className . 'EntityMap';

        if (!class_exists($entityMapClass)) {
            throw new RuntimeException(
                sprintf('EntityMap class `%s` for entity `%s` not found', $entityMapClass, $fullEntityClassName)
            );
        }

        $database = $this->getDatabase($databaseName);
        /** @var EntityMap $map */
        $map = new $entityMapClass($fullEntityClassName, $database, $this);
        $database->addEntityMap($map);
        $this->entityMaps[$fullEntityClassName] = $map;

        return $map;
    }

    public function reset()
    {
        $this->databaseMaps = [];
        $this->entityMaps = [];
    }

    /**
     * @param Session $session
     * @param object $entity
     *
     * @return PersisterInterface
     */
    public function getEntityPersisterForEntity(Session $session, $entity)
    {
        if ($entity instanceof EntityProxyInterface) {
            $entityName = get_parent_class($entity);
        } else {
            $entityName = get_class($entity);
        }

        return $this->getEntityPersister($session, $entityName);
    }

    /**
     * @param string $entityName
     * @param Session $session
     *
     * @return PersisterInterface
     */
    public function getEntityPersister(Session $session, $entityName)
    {
        $entityMap = $this->getEntityMap($entityName);
        $database = $this->getDatabaseForEntityClass($entityName);
        $adapter = $this->getAdapter($database->getName());
        return $adapter->getPersister($session, $entityMap);
    }

    /**
     * @param string $entityName
     * @param string $alias
     *
     * @return ModelCriteria
     */
    public function createQuery($entityName, $alias = '')
    {
        return $this->getRepository($entityName)->createQuery($alias);
    }

    public function getFieldType($type)
    {
        $type = strtolower($type);

        //@todo, move this to PropelConfiguration tree
        $typeMap = [
            'varchar' => 'Propel\Common\Types\SQL\VarcharType',
            'integer' => 'Propel\Common\Types\SQL\IntegerType',
            'double' => 'Propel\Common\Types\SQL\DoubleType',
            'float' => 'Propel\Common\Types\SQL\DoubleType',
            'datetime' => 'Propel\Common\Types\SQL\DateTimeType',
            'date' => 'Propel\Common\Types\SQL\DateTimeType',
            'timestamp' => 'Propel\Common\Types\SQL\DateTimeType',
            'lob' => 'Propel\Common\Types\SQL\LobType',
            'clob' => 'Propel\Common\Types\SQL\LobType',
            'blob' => 'Propel\Common\Types\SQL\LobType',
        ];

        if (!isset($typeMap[$type])) {
            $class = 'Propel\Common\Types\SQL\VarcharType';
        } else {
            $class = $typeMap[$type];
        }

        if (!isset($this->typeMaps[$class])) {
            $this->typeMaps[$class] = new $class;
        }

        return $this->typeMaps[$class];

    }

    /**
     * @param string $entityName
     *
     * @return Repository
     */
    public function getRepository($entityName)
    {
        $entityMap = $this->getEntityMap($entityName);
        $class = $entityMap->getRepositoryClass();

        if (!isset($this->repositories[$class])) {
            $this->repositories[$class] = new $class($entityMap, $this);
        }

        return $this->repositories[$class];
    }

    /**
     * @param object $entity
     *
     * @return Repository
     */
    public function getRepositoryForEntity($entity)
    {
        if ($entity instanceof EntityProxyInterface) {
            $entityName = get_parent_class($entity);
        } else {
            $entityName = get_class($entity);
        }

        return $this->getRepository($entityName);
    }

    /**
     * @param object $entity
     *
     * @return EntityMap
     */
    public function getEntityMapForEntity($entity)
    {
        if ($entity instanceof EntityProxyInterface) {
            $entityName = get_parent_class($entity);
        } else {
            $entityName = get_class($entity);
        }

        return $this->getEntityMap($entityName);
    }

    /**
     * @return SessionFactory
     */
    public function getSessionFactory()
    {
        if (null === $this->sessionFactory) {
            $this->sessionFactory = new SessionFactory($this);
        }

        return $this->sessionFactory;
    }

    /**
     * Returns the current session, if not exists, we create one and set this to the new current..
     *
     * @return Session
     */
    public function getSession()
    {
        $sessionFactory = $this->getSessionFactory();

        return $sessionFactory->getCurrentSession();
    }

    /**
     * Returns always a new session.
     *
     * Shortcut for $configuration->getSessionFactory()->build();
     *
     * @return Session
     */
    public function createSession()
    {
        return $this->getSessionFactory()->build();
    }

    /**
     * Return an array of all configured connection properties, from `runtime`
     * sections of the configuration.
     *
     * @return array
     */
    protected function getRuntimeConnections()
    {
        if (null === $this->runtimeConnections) {
            $connectionNames = $this->get()['runtime']['connections'];

            foreach ($connectionNames as $name) {
                if ($definition = $this->getConfigProperty('database.connections.' . $name)) {
                    $this->runtimeConnections[$name] = $definition;
                }
            }
        }

        return $this->runtimeConnections;
    }

    /**
     * @param string $databaseName
     *
     * @return ConnectionManagerInterface
     */
    public function getConnectionManager($databaseName = 'default')
    {
        if (!isset($this->connectionManager[$databaseName])) {
            throw new InvalidArgumentException(sprintf('ConnectionManager for %s database not found. [%s]', $databaseName, implode(', ', array_keys($this->connectionManager))));
        }

        return $this->connectionManager[$databaseName];
    }

    /**
     * @param string                     $databaseName
     * @param ConnectionManagerInterface $connectionManager
     */
    public function setConnectionManager($databaseName, ConnectionManagerInterface $connectionManager)
    {
        if ($connectionManager instanceof LoggerAwareInterface) {
            $connectionManager->setLogger($this->getLogger());
        }

        $this->connectionManager[$databaseName] = $connectionManager;
    }

    /*
     * @return ConnectionManagerInterface
     */
    public function buildConnectionManager($name, array $connection)
    {
        $manager = new \Propel\Runtime\Connection\ConnectionManagerSingle($this->getAdapter($name));
        $manager->setName($name);
        $manager->setConfiguration($connection);
        $manager->setLogger($this->getLogger());

        return $manager;
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
    public function getAdapter($name)
    {
        if (!isset($this->adapters[$name])) {
            $this->adapters[$name] = AdapterFactory::create($this->databaseToAdapter[$name]);
        }

        return $this->adapters[$name];
    }

    /**
     * @param string $name
     * @param AdapterInterface $adapter
     */
    public function setAdapter($name, AdapterInterface $adapter)
    {
        $this->adapters[$name] = $adapter;
    }

    /**
     * @param string $databaseName
     * @param string $adapterClass
     */
    public function setAdapterClass($databaseName, $adapterClass)
    {
        $this->databaseToAdapter[$databaseName] = $adapterClass;
    }

    /**
     * Logs a message
     * If a logger has been configured, the logger will be used, otherwise the
     * logging message will be discarded without any further action
     *
     * @param string $message The message that will be logged.
     * @param int    $level   The logging level.
     *
     * @return boolean True if the message was logged successfully or no logger was used.
     */
    public function log($message, $level = self::LOG_DEBUG)
    {
        $logger = $this->getLogger();

        switch ($level) {
            case self::LOG_EMERG:
                return $logger->emergency($message);
            case self::LOG_ALERT:
                return $logger->alert($message);
            case self::LOG_CRIT:
                return $logger->critical($message);
            case self::LOG_ERR:
                return $logger->error($message);
            case self::LOG_WARNING:
                return $logger->warning($message);
            case self::LOG_NOTICE:
                return $logger->notice($message);
            case self::LOG_INFO:
                return $logger->info($message);
            default:
                return $logger->debug($message);
        }
    }

    /**
     * @param string $message
     */
    public function debug($message)
    {
        list(, $caller) = debug_backtrace();
        $class = $caller['class'];
        $additional = '';
        if ('Propel\Runtime\Session\SessionRound' === $class) {
            $additional = sprintf(', round=%d', $caller['object']->getIdx());
        }
        $this->log(sprintf('[%s:%d%s] %s', $class, $caller['line'], $additional, $message));
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

    public function isDebug()
    {
        return getenv('DEBUG');
    }

    /**
     * @param string $name
     *
     * @return Logger|LoggerInterface|NullLogger
     * @throws UnexpectedValueException
     */
    protected function buildLogger($name = 'defaultLogger')
    {
        if ($this->isDebug()) {
            $logger = new Logger($name);
            $handler = new \Monolog\Handler\StreamHandler(
                'php://output'
            );
            $logger->pushHandler($handler);
            return $logger;
        }

        if (!isset($this->loggerConfigurations[$name])) {
            //no configuration found, return default Logger
            return new NullLogger();
        }

        $logger = new Logger($name);
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
                throw new UnexpectedValueException(
                    sprintf(
                        'Handler type "%s" not supported by StandardServiceContainer. Try setting the Logger manually, ' .
                        'or use another ServiceContainer.',
                        $configuration['type']
                    )
                );
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
     *
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
}
