<?php

/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

include_once 'propel/PropelException.php';
include_once 'propel/adapter/DBAdapter.php';
include_once 'propel/util/PropelPDO.php';

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
 * @package    propel
 */
class Propel
{
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
	 * @var        string The db name that is specified as the default in the property file
	 */
	private static $defaultDBName;

	/**
	 * @var        array The global cache of database maps
	 */
	private static $dbMaps = array();

	/**
	 * @var        array The cache of DB adapter keys
	 */
	private static $adapterMap = array();

	/**
	 * @var        array Cache of established connections (to eliminate overhead).
	 */
	private static $connectionMap = array();
	
	/**
	 * @var        array Propel-specific configuration.
	 */
	private static $configuration;

	/**
	 * @var        bool flag to set to true once this class has been initialized
	 */
	private static $isInit = false;

	/**
	 * @var        Log optional logger
	 */
	private static $logger = null;

 /** 
	* @var         string The name of the database mapper class
	*/ 
	private static $databaseMapClass = 'DatabaseMap'; 
	
	/**
	 * Initializes Propel
	 *
	 * @throws     PropelException Any exceptions caught during processing will be
	 *                             rethrown wrapped into a PropelException.
	 */
	public static function initialize()
	{
		if (self::$configuration === null) {
			throw new PropelException("Propel cannot be initialized without "
				. "a valid configuration. Please check the log files "
				. "for further details.");
		}
		
		self::configureLogging();
		
		// Support having the configuration stored within a 'propel' sub-section or at the top-level
		if (isset(self::$configuration['propel']) && is_array(self::$configuration['propel'])) {
			self::$configuration = self::$configuration['propel'];
		}
		
		// reset the connection map (this should enable runtime changes of connection params)
		self::$connectionMap = array();
		
		self::$isInit = true;
	}

	/**
	 * Configure Propel using an INI or PHP (array) config file.
	 *
	 * @param      string Path (absolute or relative to include_path) to config file.
	 *
	 * @throws     PropelException If configuration file cannot be opened. 
	 *                             (E_WARNING probably will also be raised by PHP)
	 */
	public static function configure($configFile)
	{
		if (substr($configFile, strrpos($configFile, '.') + 1) === "ini") {
			ini_set('track_errors', true);
			self::$configuration = parse_ini_file($configFile, true);
			if (!empty($php_errormsg)) {
				throw new PropelException("Error reading ini file: " . $php_errormsg);
			}
			ini_restore('track_errors');
		} else {
			self::$configuration = include($configFile);
			if (self::$configuration === false) {
				throw new PropelException("Unable to open configuration file: " . var_export($configFile, true));
			}
		}		
	}
	
	/**
	 * Configure the logging system, if config is specified in the runtime configuration.
	 */
	protected static function configureLogging()
	{
		if (self::$logger === null) {
			if (isset(self::$configuration['log']) && is_array(self::$configuration['log']) && count(self::$configuration['log'])) {
				include_once 'Log.php'; // PEAR Log class
				$c = self::$configuration['log'];
				$type = isset($c['type']) ? $c['type'] : 'file';
				$name = isset($c['name']) ? $c['name'] : './propel.log';
				$ident = isset($c['ident']) ? $c['ident'] : 'propel';
				$conf = isset($c['conf']) ? $c['conf'] : array();
				$level = isset($c['level']) ? $c['level'] : PEAR_LOG_DEBUG;
				self::$logger = Log::singleton($type, $name, $ident, $conf, $level);
			} // if isset()
		}
	}
	
	/**
	 * Initialization of Propel with an INI or PHP (array) configuration file.
	 *
	 * @param      string $c The Propel configuration file path.
	 *
	 * @throws     PropelException Any exceptions caught during processing will be
	 *                             rethrown wrapped into a PropelException.
	 */
	public static function init($c)
	{
		self::configure($c);
		self::initialize();
	}

	/**
	 * Determine whether Propel has already been initialized.
	 *
	 * @return     bool True if Propel is already initialized.
	 */
	public static function isInit()
	{
		return self::$isInit;
	}

	/**
	 * Sets the configuration for Propel and all dependencies.
	 *
	 * @param      array The Configuration
	 */
	public static function setConfiguration($c)
	{
		self::$configuration = $c;
	}

	/**
	 * Get the configuration for this component.
	 *
	 * @return     array The Configuration
	 */
	public static function getConfiguration()
	{
		return self::$configuration;
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
	 * Returns true if a logger, for example PEAR::Log, has been configured,
	 * otherwise false.
	 *
	 * @return     bool True if Propel uses logging
	 */
	public static function hasLogger()
	{
		return (self::$logger !== null);
	}

	/**
	 * Get the configured logger.
	 *
	 * @return     object Configured log class ([PEAR] Log or BasicLogger).
	 */
	public static function logger()
	{
		return self::$logger;
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
			$logger = self::logger();
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
	 * Returns the database map information. Name relates to the name
	 * of the connection pool to associate with the map.
	 *
	 * The database maps are "registered" by the generated map builder classes.
	 *
	 * @param      string The name of the database corresponding to the DatabaseMap to retrieve.
	 *
	 * @return     DatabaseMap The named <code>DatabaseMap</code>.
	 *
	 * @throws     PropelException - if database map is null or propel was not initialized properly.
	 */
	public static function getDatabaseMap($name = null)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
			if ($name === null) {
				throw new PropelException("DatabaseMap name was null!");
			}
		}

		if (!isset(self::$dbMaps[$name])) {
			$clazz = self::$databaseMapClass;
			self::$dbMaps[$name] = new $clazz($name);
		}

		return self::$dbMaps[$name];
	}
	
	/**
	 * Gets an already-opened PDO connection or opens a new one for passed-in db name.
	 * 
	 * @param      string The name that is used to look up the DSN from the runtime properties file. 
	 *
	 * @return     PDO A database connection
	 *
	 * @throws     PropelException - if no conneciton params, or lower-level exception caught when trying to connect.
	 */
	public static function getConnection($name = null)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}
		
		if (!isset(self::$connectionMap[$name])) {		
			
			$conparams = isset(self::$configuration['datasources'][$name]['connection']) ? self::$configuration['datasources'][$name]['connection'] : null; 		 
			if ($conparams === null) {
				throw new PropelException('No connection information in your runtime configuration file for datasource ['.$name.']');
			}
			
			$dsn = $conparams['dsn'];
			if ($dsn === null) {
				throw new PropelException('No dsn specified in your connection parameters for datasource ['.$name.']');
			} 
			
			$user = isset($conparams['user']) ? $conparams['user'] : null;
			$password = isset($conparams['password']) ? $conparams['password'] : null;			
			
			// load any driver options from the INI file
			$driver_options = array();
			
			if ( isset(self::$configuration['datasources']['options']) && is_array(self::$configuration['datasources']['options']) ) {
				try {
					self::processDriverOptions( self::$configuration['datasources']['options'], $driver_options );
				} catch (PropelException $e) {
					throw new PropelException('Error processing driver options in global [options]', $e);
				}
			}
			if ( isset(self::$configuration['datasources'][$name]['options']) && is_array(self::$configuration['datasources'][$name]['options']) ) {
				try {
					self::processDriverOptions( self::$configuration['datasources'][$name]['options'], $driver_options );
				} catch (PropelException $e) {
					throw new PropelException('Error processing driver options for datasource ['.$name.']', $e);
				}
			}
			
			try {
				$con = new PropelPDO($dsn, $user, $password, $driver_options);
				$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$connectionMap[$name] = $con; 
			} catch (PDOException $e) {
				throw new PropelException("Unable to open PDO connection", $e);
			}
		}
		
		return self::$connectionMap[$name];
	}
	
	/**
	 * Internal function to handle driver_options in PDO
	 *
	 * Process the INI file flags to be passed to each connection.
	 *
	 * @param      array Where to find the list of constant flags and their new setting.
	 * @param      array Put the data into here
	 *
	 * @throws     PropelException If invalid options were specified.
	 */
	private static function processDriverOptions($source, &$write_to)
	{
		foreach ($source as $option => $optiondata) {
			$constant = 'PDO::'.$option;
			$option_value = $optiondata['value'];
			if ( defined ($constant) ) {
				$constant_value = constant($constant);
				$write_to[$constant_value] = $option_value;
			} else {
				throw new PropelException("Invalid PDO option specified: ".$option." = ".$option_value);
			}
		}
	}

	/**
	 * Returns database adapter for a specific connection pool.
	 *
	 * @param      string A database name.
	 *
	 * @return     DBAdapter The corresponding database adapter.
	 *
	 * @throws     PropelException If unable to find DBdapter for specified db.
	 */
	public static function getDB($name = null)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}
		
		if (!isset(self::$adapterMap[$name])) {
			if (!isset(self::$configuration['datasources'][$name]['adapter'])) {
				throw new PropelException("Unable to find adapter for datasource [" . $name . "].");
			}
			$db = DBAdapter::factory(self::$configuration['datasources'][$name]['adapter']);
			// register the adapter for this name
			self::$adapterMap[$name] = $db;
		}
		
		return self::$adapterMap[$name];
	}

	/**
	 * Returns the name of the default database.
	 *
	 * @return     string Name of the default DB
	 */
	public static function getDefaultDB()
	{
		if (self::$defaultDBName === null) {
			// Determine default database name.
			self::$defaultDBName = isset(self::$configuration['datasources']['default']) ? self::$configuration['datasources']['default'] : self::DEFAULT_NAME;
		}
		return self::$defaultDBName;
	}

	/**
	 * Include once a file specified in DOT notation and reutrn unqualified clasname.
	 *
	 * Package notation is expected to be relative to a location
	 * on the PHP include_path.  The dot-path classes are used as a way
	 * to represent both classname and filesystem location; there is
	 * an inherent assumption about filenaming.  To get around these
	 * naming requirements you can include the class yourself
	 * and then just use the classname instead of dot-path.
	 *
	 * @param      string dot-path to clas (e.g. path.to.my.ClassName).
	 *
	 * @return     string unqualified classname
	 */
	public static function import($path)
	{
		// extract classname
		if (($pos = strrpos($path, '.')) === false) {
			$class = $path;
		} else {
			$class = substr($path, $pos + 1);
		}

		// check if class exists
		if (class_exists($class, false)) {
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
	 * Closes any associated resource handles.
	 *
	 * This method frees any database connection handles that have been
	 * opened by the getConnection() method.
	 */
	public static function close()
	{
		foreach (self::$connectionMap as $con) {
			$con = null; // close for PDO
		}
	}

	/**
	 * Autoload function for loading propel dependencies.
	 *
	 * @param     string The class name needing loading.
	 *
	 * @return    boolean TRUE if the class was loaded, false otherwise.
	 */
	public static function autoload($className)
	{
		switch ($className) {
			case 'CreoleTypes':
				require('creole/CreoleTypes.php');
				return true;
			case 'MapBuilder':
				require 'propel/map/MapBuilder.php';
				return true;
			case 'BaseObject':
			case 'Persistent':
				require("propel/om/{$className}.php");
				return true;
			case 'BasePeer':
			case 'Criteria':
				require("propel/util/{$className}.php");
				return true;
		}
		return false;
	}

	/** 
	 * Set your own class-name for Database-Mapping. Then
	 * you can change the whole TableMap-Model, but keep its
	 * functionality for Criteria.
	 * 
	 * @param      string The name of the class.
	 */ 
	public static function setDatabaseMapClass($name)
	{ 
		self::$databaseMapClass = $name; 
	}
}
