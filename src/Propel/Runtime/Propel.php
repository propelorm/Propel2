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
use Propel\Runtime\Configuration;
use Propel\Runtime\Map\DatabaseMap;

use \PDO;
use \PDOException;

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
 * @version    $Revision$
 * @package    propel.runtime
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
    const LOG_EMERG = 0;

    /**
     * A constant defining 'Immediate action required' logging level
     */
    const LOG_ALERT = 1;

    /**
     * A constant defining 'Critical conditions' logging level
     */
    const LOG_CRIT = 2;

    /**
     * A constant defining 'Error conditions' logging level
     */
    const LOG_ERR = 3;

    /**
     * A constant defining 'Warning conditions' logging level
     */
    const LOG_WARNING = 4;

    /**
     * A constant defining 'Normal but significant' logging level
     */
    const LOG_NOTICE = 5;

    /**
     * A constant defining 'Informational' logging level
     */
    const LOG_INFO = 6;

    /**
     * A constant defining 'Debug-level messages' logging level
     */
    const LOG_DEBUG = 7;

    /**
     * The class name for a PDO object.
     */
    const CLASS_PDO = '\PDO';

    /**
     * The class name for a PropelPDO object.
     */
    const CLASS_PROPEL_PDO = '\Propel\Runtime\Connection\PropelPDO';

    /**
     * The class name for a DebugPDO object.
     */
    const CLASS_DEBUG_PDO = '\Propel\Runtime\Connection\DebugPDO';

    /**
     * Constant used to request a READ connection (applies to replication).
     */
    const CONNECTION_READ = 'read';

    /**
     * Constant used to request a WRITE connection (applies to replication).
     */
    const CONNECTION_WRITE = 'write';

    /**
     * @var        array Cache of established connections (to eliminate overhead).
     */
    private static $connectionMap = array();

    /**
     * @var        Configuration Propel-specific configuration.
     */
    private static $configuration;

    /**
     * @var        Log optional logger
     */
    private static $logger = null;

    /**
     * @var        bool Whether the object instance pooling is enabled
     */
    private static $isInstancePoolingEnabled = true;

    /**
     * @var        bool For replication, whether to force the use of master connection.
     */
    private static $isForceMasterConnection = false;

    /**
     * Configure Propel a PHP (array) config file.
     *
     * @param      string $configFile Path (absolute or relative to include_path) to config file.
     *
     * @throws     PropelException If configuration file cannot be opened.
     *                             (E_WARNING probably will also be raised by PHP)
     */
    public static function init($configFile)
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
    public static function setConfiguration($c)
    {
        self::$logger = null;
        self::closeConnections();
        if (is_array($c)) {
            $c = new Registry($c);
        }
        // set default datasource
        $defaultDatasource = isset($c['datasources']['default']) ? $c['datasources']['default'] : self::DEFAULT_NAME;
        Configuration::getInstance()->setDefaultDatasource($defaultDatasource);
        if (isset($c['datasources'])) {
            foreach ($c['datasources'] as $name => $params) {
                if (!is_array($params)) {
                    continue;
                }
                // set adapters
                if (isset($params['adapter'])) {
                    Configuration::getInstance()->setAdapterClass($name, $params['adapter']);
                }
                // set connection settings
                if (isset($params['connection'])) {
                    $connectionConfiguration = array();
                    $conParams = $params['connection'];
                    if (isset($conParams['slaves'])) {
                        $manager = new ConnectionManagerMasterSlave();
                        $manager->setReadConfiguration($conParams['slaves']);
                        unset($conParams['slaves']);
                        $manager->setWriteConfiguration($conParams);
                    } else {
                        $manager = new ConnectionManagerSingle();
                        $manager->setConfiguration($conParams);
                    }
                    Configuration::getInstance()->setConnectionManager($name, $manager);
                }
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
    public static function getConfiguration($type = Registry::TYPE_ARRAY)
    {
        return self::$configuration->getParameters($type);
    }

    /**
     * Returns true if a logger, for example PEAR::Log, has been configured,
     * otherwise false.
     *
     * @return     bool True if Propel uses logging
     */
    public static function hasLogger()
    {
        if (null === self::$logger) {
            self::configureLogging();
        }

        return self::$logger !== false;
    }

    /**
     * Get the configured logger.
     *
     * @return     object Configured log class ([PEAR] Log or BasicLogger).
     */
    public static function getLogger()
    {
        if (null === self::$logger) {
            self::configureLogging();
        }

        return self::$logger;
    }

    /**
     * Configure the logging system, if config is specified in the runtime configuration.
     */
    protected static function configureLogging()
    {
        if (isset(self::$configuration['log']) && is_array(self::$configuration['log']) && count(self::$configuration['log'])) {
            include_once 'Log.php'; // PEAR Log class
            $c = self::$configuration['log'];
            $type = isset($c['type']) ? $c['type'] : 'file';
            $name = isset($c['name']) ? $c['name'] : './propel.log';
            $ident = isset($c['ident']) ? $c['ident'] : 'propel';
            $conf = isset($c['conf']) ? $c['conf'] : array();
            $level = isset($c['level']) ? $c['level'] : PEAR_LOG_DEBUG;
            self::$logger = Log::singleton($type, $name, $ident, $conf, $level);
        } else {
            self::$logger = false;
        }
    }

    /**
     * Override the configured logger.
     *
     * This is primarily for things like unit tests / debugging where
     * you want to change the logger without altering the configuration file.
     *
     * You can use any logger class that implements the propel.logger.BasicLogger
     * interface.  This interface is based on PEAR::Log, so you can also simply pass
     * a PEAR::Log object to this method.
     *
     * @param      object The new logger to use. ([PEAR] Log or BasicLogger)
     */
    public static function setLogger($logger)
    {
        self::$logger = $logger;
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
    public static function log($message, $level = self::LOG_DEBUG)
    {
        if (self::hasLogger()) {
            $logger = self::getLogger();
            switch ($level) {
            case self::LOG_EMERG:
                return $logger->log($message, $level);
            case self::LOG_ALERT:
                return $logger->alert($message);
            case self::LOG_CRIT:
                return $logger->crit($message);
            case self::LOG_ERR:
                return $logger->err($message);
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

        return true;
    }

    /**
     * Define the name of the default datasource
     *
     * @return     string
     */
    public static function setDefaultDatasource($name = self::DEFAULT_NAME)
    {
        Configuration::getInstance()->setDefaultDatasource($name);
    }

    /**
     * Get the name of the default datasource
     *
     * @return     string
     */
    public static function getDefaultDatasource()
    {
        return Configuration::getInstance()->getDefaultDatasource();
    }

    /**
     * For replication, set whether to always force the use of a master connection.
     *
     * @param      boolean $bool
     */
    public static function setForceMasterConnection($bool)
    {
        foreach (Configuration::getInstance()->getConnectionManagers() as $manager) {
            $manager->setForceMasterConnection($bool);
        }
    }

    /**
     * For replication, whether to always force the use of a master connection.
     *
     * @return     boolean
     */
    public static function isForceMasterConnection()
    {
        return false;
    }

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     */
    public static function closeConnections()
    {
        foreach (Configuration::getInstance()->getConnectionManagers() as $manager) {
            $manager->closeConnections();
        }
    }

    /**
     * Include once a file specified in DOT notation and return unqualified classname.
     *
     * Typically, Propel uses autoload is used to load classes and expects that all classes
     * referenced within Propel are included in Propel's autoload map.  This method is only
     * called when a specific non-Propel classname was specified -- for example, the
     * classname of a validator in the schema.xml.  This method will attempt to include that
     * class via autoload and then relative to a location on the include_path.
     *
     * @param      string $class dot-path to clas (e.g. path.to.my.ClassName).
     * @return     string unqualified classname
     */
    public static function importClass($path) {

        // extract classname
        if (($pos = strrpos($path, '.')) === false) {
            $class = $path;
        } else {
            $class = substr($path, $pos + 1);
        }

        // check if class exists, using autoloader to attempt to load it.
        if (class_exists($class, $useAutoload=true)) {
            return $class;
        }

        // turn to filesystem path
        $path = strtr($path, '.', DIRECTORY_SEPARATOR) . '.php';

        // include class
        $ret = include_once($path);
        if ($ret === false) {
            throw new PropelException("Unable to import class: " . $class . " from " . $path);
        }

        // return qualified name
        return $class;
    }

    /**
     * Disable instance pooling.
     *
     * @return boolean true if the method changed the instance pooling state,
     *                 false if it was already disabled
     */
    public static function disableInstancePooling()
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
    public static function enableInstancePooling()
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
    public static function isInstancePoolingEnabled()
    {
        return self::$isInstancePoolingEnabled;
    }

}
