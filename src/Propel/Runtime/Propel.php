<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Config\Configuration as Registry;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionManagerMasterSlave;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;

/**
 * Propel's main resource pool and initialization & configuration class.
 *
 * This static class is used to handle Propel initialization and to maintain all of the
 * open database connections and instantiated database maps.
 *
 * @author     Hans Lellelid <hans@xmpl.rg> (Propel)
 * @author     Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author     Magnús Þór Torfason <magnus@handtolvur.is> (Torque)
 * @author     Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author     Rafal Krzewski <Rafal.Krzewski@e-point.pl> (Torque)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author     Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author     Kurt Schrader <kschrader@karmalab.org> (Torque)
 */
class Propel
{
    /**
     * The Propel version.
     */
    const VERSION = '2.0.0-dev';

    /**
     * A constant for <code>default</code>.
     */
    const DEFAULT_NAME = "default";

    /**
     * A constant defining 'System is unusuable' logging level
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
     * @var        Configuration Propel-specific configuration.
     */
    private static $configuration;

    /**
     * @var \Propel\Runtime\ServiceContainer\ServiceContainerInterface
     */
    private static $serviceContainer;

    /**
     * @var        bool Whether the object instance pooling is enabled
     */
    private static $isInstancePoolingEnabled = true;

    /**
     * Configure Propel a PHP (array) config file.
     *
     * @param      string $configFile Path (absolute or relative to include_path) to config file.
     *
     * @throws     PropelException If configuration file cannot be opened.
     *                             (E_WARNING probably will also be raised by PHP)
     */
    static public function init($configFile)
    {
        $configuration = include($configFile);
        if ($configuration === false) {
            throw new PropelException(sprintf('Unable to open configuration file: "%s"', $configFile));
        }
        self::setConfiguration($configuration);
    }

    /**
     * Sets the configuration for Propel and all dependencies.
     *
     * @param      mixed The Configuration (array or Configuration)
     */
    static public function setConfiguration($c)
    {
        $serviceContainer = self::getServiceContainer();
        $serviceContainer->closeConnections();
        if (is_array($c)) {
            $c = new Registry($c);
        }
        // set datasources
        if (isset($c['datasources'])) {
            foreach ($c['datasources'] as $name => $params) {
                if (!is_array($params)) {
                    continue;
                }
                // set adapters
                if (isset($params['adapter'])) {
                    $serviceContainer->setAdapterClass($name, $params['adapter']);
                }
                // set connection settings
                if (isset($params['connection'])) {
                    $conParams = $params['connection'];
                    if (isset($conParams['slaves'])) {
                        $manager = new ConnectionManagerMasterSlave();
                        $manager->setName($name);
                        $manager->setReadConfiguration($conParams['slaves']);
                        unset($conParams['slaves']);
                        $manager->setWriteConfiguration($conParams);
                    } else {
                        $manager = new ConnectionManagerSingle();
                        $manager->setName($name);
                        $manager->setConfiguration($conParams);
                    }
                    $serviceContainer->setConnectionManager($name, $manager);
                }
            }
        }
        // set default datasource
        $defaultDatasource = isset($c['datasources']['default']) ? $c['datasources']['default'] : self::DEFAULT_NAME;
        $serviceContainer->setDefaultDatasource($defaultDatasource);
        // set profiler
        if (isset($c['profiler'])) {
            $profilerConf = $c['profiler'];
            if (isset($profilerConf['class'])) {
                $serviceContainer->setProfilerClass($profilerConf['class']);
                unset($profilerConf['class']);
            }
            if ($profilerConf) {
                $serviceContainer->setProfilerConfiguration($profilerConf);
            }
        }
        // set logger
        if (isset($c['log'])) {
            foreach ($c['log'] as $loggerConfiguration) {
                $name = $loggerConfiguration['name'];
                unset($loggerConfiguration['name']);
                $serviceContainer->setLoggerConfiguration($name, $loggerConfiguration);
            }
        }
        self::$configuration = $c;
    }

    /**
     * Get the configuration for this component.
     *
     * @param      int - Configuration::TYPE_ARRAY: return the configuration as an array
     *                   (for backward compatibility this is the default)
     *                 - Configuration::TYPE_ARRAY_FLAT: return the configuration as a flat array
     *                   ($config['name.space.item'])
     *                 - Configuration::TYPE_OBJECT: return the configuration as a PropelConfiguration instance
     * @return     mixed The Configuration (array or Configuration)
     */
    static public function getConfiguration($type = Registry::TYPE_ARRAY)
    {
        return self::$configuration->getParameters($type);
    }

    /**
     * Get the service container instance.
     *
     * @return \Propel\Runtime\ServiceContainer\ServiceContainerInterface
     */
    static public function getServiceContainer()
    {
        if (null === self::$serviceContainer) {
            self::$serviceContainer = new StandardServiceContainer();
        }

        return self::$serviceContainer;
    }

    /**
     * Set the service container instance.
     *
     * @param \Propel\Runtime\ServiceContainer\ServiceContainerInterface
     */
    static public function setServiceContainer(ServiceContainerInterface $serviceContainer)
    {
        self::$serviceContainer = $serviceContainer;
    }

    /**
     * @return string
     */
    static public function getDefaultDatasource()
    {
        return self::$serviceContainer->getDefaultDatasource();
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
    static public function getAdapter($name = null)
    {
        return self::$serviceContainer->getAdapter($name);
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
    static public function getDatabaseMap($name = null)
    {
        return self::$serviceContainer->getDatabaseMap($name);
    }

    /**
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Connection\ConnectionManagerInterface
     */
    static public function getConnectionManager($name)
    {
        return self::$serviceContainer->getConnectionManager($name);
    }

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     */
    static public function closeConnections()
    {
        return self::$serviceContainer->closeConnections();
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
    static public function getConnection($name = null, $mode = ServiceContainerInterface::CONNECTION_WRITE)
    {
        return self::$serviceContainer->getConnection($name, $mode);
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
    static public function getWriteConnection($name)
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
     * @param      string $name The datasource name that is used to look up the DSN
     *                          from the runtime configuration file. Empty name not allowed.
     *
     * @return     ConnectionInterface A database connection
     */
    static public function getReadConnection($name)
    {
        return self::$serviceContainer->getReadConnection($name);
    }

    /**
     * Get a profiler instance.
     *
     * @return \Propel\Runtime\Util\Profiler
     */
    static public function getProfiler()
    {
        return self::$serviceContainer->getProfiler();
    }

    /**
     * Returns true if a logger has been configured, otherwise false.
     *
     * @return     bool True if Propel uses logging
     */
    static public function hasLogger()
    {
        return self::$serviceContainer->hasLogger();
    }

    /**
     * Get the configured logger.
     *
     * @return     \Monolog\Logger Configured log class
     */
    static public function getLogger()
    {
        return self::$serviceContainer->getLogger();
    }

    /**
     * Logs a message
     * If a logger has been configured, the logger will be used, otherwrise the
     * logging message will be discarded without any further action
     *
     * @param      string The message that will be logged.
     * @param      string The logging level.
     *
     * @return     bool True if the message was logged successfully or no logger was used.
     */
    static public function log($message, $level = self::LOG_DEBUG)
    {
        if (self::$serviceContainer->hasLogger()) {
            $logger = self::$serviceContainer->getLogger();
            switch ($level) {
                case self::LOG_EMERG:
                case self::LOG_ALERT:
                    return $logger->addAlert($message);
                case self::LOG_CRIT:
                    return $logger->addCritical($message);
                case self::LOG_ERR:
                    return $logger->addError($message);
                case self::LOG_WARNING:
                    return $logger->addWarning($message);
                case self::LOG_NOTICE:
                case self::LOG_INFO:
                    return $logger->addInfo($message);
                default:
                    return $logger->addDebug($message);
            }
        }

        return true;
    }

    /**
     * Disable instance pooling.
     *
     * @return boolean true if the method changed the instance pooling state,
     *                 false if it was already disabled
     */
    static public function disableInstancePooling()
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
     * @return boolean true if the method changed the instance pooling state,
     *                 false if it was already enabled
     */
    static public function enableInstancePooling()
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
     * @return     boolean Whether the pooling is enabled or not.
     */
    static public function isInstancePoolingEnabled()
    {
        return self::$isInstancePoolingEnabled;
    }
}
