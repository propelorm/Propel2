<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionManagerInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Runtime\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Propel's main resource pool and initialization & configuration class.
 *
 * This static class is used to handle Propel initialization and to maintain all of the
 * open database connections and instantiated database maps.
 *
 * @author Hans Lellelid <hans@xmpl.rg> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Magnús Þór Torfason <magnus@handtolvur.is> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Rafal Krzewski <Rafal.Krzewski@e-point.pl> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Kurt Schrader <kschrader@karmalab.org> (Torque)
 */
class Propel
{
    /**
     * The Propel version.
     *
     * @var string
     */
    public const VERSION = '2.0.0-dev';

    /**
     * A constant for <code>default</code>.
     *
     * @var string
     */
    public const DEFAULT_NAME = 'default';

    /**
     * A constant defining 'System is unusable' logging level
     *
     * @var int
     */
    public const LOG_EMERG = 550;

    /**
     * A constant defining 'Immediate action required' logging level
     *
     * @var int
     */
    public const LOG_ALERT = 550;

    /**
     * A constant defining 'Critical conditions' logging level
     *
     * @var int
     */
    public const LOG_CRIT = 500;

    /**
     * A constant defining 'Error conditions' logging level
     *
     * @var int
     */
    public const LOG_ERR = 400;

    /**
     * A constant defining 'Warning conditions' logging level
     *
     * @var int
     */
    public const LOG_WARNING = 300;

    /**
     * A constant defining 'Normal but significant' logging level
     *
     * @var int
     */
    public const LOG_NOTICE = 200;

    /**
     * A constant defining 'Informational' logging level
     *
     * @var int
     */
    public const LOG_INFO = 200;

    /**
     * A constant defining 'Debug-level messages' logging level
     *
     * @var int
     */
    public const LOG_DEBUG = 100;

    /**
     * @var \Propel\Runtime\ServiceContainer\ServiceContainerInterface
     */
    private static $serviceContainer;

    /**
     * @var bool Whether the object instance pooling is enabled
     */
    private static $isInstancePoolingEnabled = true;

    /**
     * Configure Propel using the given config file.
     *
     * @deprecated Why don't you just include the configuration file?
     *
     * @param string $configFile Path (absolute or relative to include_path) to config file.
     *
     * @return void
     */
    public static function init(string $configFile): void
    {
        $serviceContainer = self::getServiceContainer();
        $serviceContainer->closeConnections();

        include $configFile;
    }

    /**
     * Get the service container instance.
     *
     * @return \Propel\Runtime\ServiceContainer\ServiceContainerInterface
     */
    public static function getServiceContainer(): ServiceContainerInterface
    {
        if (self::$serviceContainer === null) {
            self::$serviceContainer = new StandardServiceContainer();
        }

        return self::$serviceContainer;
    }

    /**
     * Returns the service container if it is an instance of
     * StandardServiceContainer or throws an error if another service container
     * is used.
     *
     * This method allows type-safe access to methods of
     * StandardServiceContainer, it provides no other advantage over
     * {@link Propel::getServiceContainer()}
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\ServiceContainer\StandardServiceContainer
     */
    public static function getStandardServiceContainer(): StandardServiceContainer
    {
        $sc = self::getServiceContainer();
        if ($sc instanceof StandardServiceContainer) {
            return $sc;
        }

        throw new PropelException('Instance was configured to not use StandardServiceContainer. Use Propel::getServiceContainer()');
    }

    /**
     * Set the service container instance.
     *
     * @param \Propel\Runtime\ServiceContainer\ServiceContainerInterface $serviceContainer
     *
     * @return void
     */
    public static function setServiceContainer(ServiceContainerInterface $serviceContainer): void
    {
        self::$serviceContainer = $serviceContainer;
    }

    /**
     * @return string
     */
    public static function getDefaultDatasource(): string
    {
        return self::$serviceContainer->getDefaultDatasource();
    }

    /**
     * Get the adapter for a given datasource.
     *
     * If the adapter does not yet exist, build it using the related adapterClass.
     *
     * @param string|null $name The datasource name
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public static function getAdapter(?string $name = null): AdapterInterface
    {
        return self::$serviceContainer->getAdapter($name);
    }

    /**
     * Get the database map for a given datasource.
     *
     * The database maps are "registered" by the generated map builder classes.
     *
     * @param string|null $name The datasource name
     *
     * @return \Propel\Runtime\Map\DatabaseMap
     */
    public static function getDatabaseMap(?string $name = null): DatabaseMap
    {
        return self::$serviceContainer->getDatabaseMap($name);
    }

    /**
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Connection\ConnectionManagerInterface
     */
    public static function getConnectionManager(string $name): ConnectionManagerInterface
    {
        return self::$serviceContainer->getConnectionManager($name);
    }

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     *
     * @return void
     */
    public static function closeConnections(): void
    {
        self::$serviceContainer->closeConnections();
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
    public static function getConnection(?string $name = null, string $mode = ServiceContainerInterface::CONNECTION_WRITE): ConnectionInterface
    {
        return self::$serviceContainer->getConnection($name, $mode);
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
    public static function getWriteConnection(string $name): ConnectionInterface
    {
        return self::$serviceContainer->getWriteConnection($name);
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
    public static function getReadConnection(string $name): ConnectionInterface
    {
        return self::$serviceContainer->getReadConnection($name);
    }

    /**
     * Get a profiler instance.
     *
     * @return \Propel\Runtime\Util\Profiler
     */
    public static function getProfiler(): Profiler
    {
        return self::$serviceContainer->getProfiler();
    }

    /**
     * Get the configured logger.
     *
     * @return \Psr\Log\LoggerInterface Configured log class
     */
    public static function getLogger(): LoggerInterface
    {
        return self::$serviceContainer->getLogger();
    }

    /**
     * Logs a message
     * If a logger has been configured, the logger will be used, otherwise the
     * logging message will be discarded without any further action
     *
     * @param string $message The message that will be logged.
     * @param int $level The logging level.
     *
     * @return void
     */
    public static function log(string $message, int $level = self::LOG_DEBUG): void
    {
        $logger = self::$serviceContainer->getLogger();

        switch ($level) {
            case self::LOG_EMERG:
                $logger->emergency($message);

                break;
            case self::LOG_ALERT:
                $logger->alert($message);

                break;
            case self::LOG_CRIT:
                $logger->critical($message);

                break;
            case self::LOG_ERR:
                $logger->error($message);

                break;
            case self::LOG_WARNING:
                $logger->warning($message);

                break;
            case self::LOG_NOTICE:
                $logger->notice($message);

                break;
            case self::LOG_INFO:
                $logger->info($message);

                break;
            default:
                $logger->debug($message);
        }
    }

    /**
     * Disable instance pooling.
     *
     * @return bool true if the method changed the instance pooling state,
     * false if it was already disabled
     */
    public static function disableInstancePooling(): bool
    {
        if (!self::$isInstancePoolingEnabled) {
            return false;
        }
        self::$isInstancePoolingEnabled = false;

        return true;
    }

    /**
     * Enable instance pooling (enabled by default).
     *
     * @return bool true if the method changed the instance pooling state,
     * false if it was already enabled
     */
    public static function enableInstancePooling(): bool
    {
        if (self::$isInstancePoolingEnabled) {
            return false;
        }
        self::$isInstancePoolingEnabled = true;

        return true;
    }

    /**
     *  the instance pooling behaviour. True by default.
     *
     * @return bool Whether the pooling is enabled or not.
     */
    public static function isInstancePoolingEnabled(): bool
    {
        return self::$isInstancePoolingEnabled;
    }
}
