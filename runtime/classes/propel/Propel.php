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
 * @author Hans Lellelid <hans@xmpl.rg> (Propel)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Magnús Þór Torfason <magnus@handtolvur.is> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Rafal Krzewski <Rafal.Krzewski@e-point.pl> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Kurt Schrader <kschrader@karmalab.org> (Torque)
 * @version $Revision$
 * @package propel
 */
class Propel {

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
	 * The db name that is specified as the default in the property file
	 */
	private static $defaultDBName;

	/**
	 * The global cache of database maps
	 */
	private static $dbMaps = array();

	/**
	 * The cache of DB adapter keys
	 */
	private static $adapterMap = array();

	/**
	 * Cache of established connections (to eliminate overhead).
	 * @var array
	 */
	private static $connectionMap = array();
	
	/**
	 * Propel-specific configuration.
	 */
	private static $configuration;

	/**
	 * flag to set to true once this class has been initialized
	 */
	private static $isInit = false;

	/**
	 * @var Log
	 */
	private static $logger = null;

    /** 
	* You can set your own Database-Mapper - Class. Class DatabaseMap is
	* used by default.
	* @var string 
	*/ 
    private static $databaseMapClass = 'DatabaseMap'; 
	
	/**
	 * initialize Propel
	 * @return void
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function initialize()
	{
		if (self::$configuration === null) {
			throw new PropelException("Propel cannot be initialized without "
					. "a valid configuration. Please check the log files "
					. "for further details.");
		}

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
	 * @param string $config Path (absolute or relative to include_path) to config file.
	 * @return void
	 * @throws PropelException If configuration file cannot be opened. (E_WARNING probably will also be raised in PHP)
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
	 * Initialization of Propel with an INI or PHP (array) configuration file.
	 *
	 * @param string $c The Propel configuration file path.
	 * @return void
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function init($c)
	{
		self::configure($c);
		self::initialize();
	}

	/**
	 * Determine whether Propel has already been initialized.
	 *
	 * @return boolean True if Propel is already initialized.
	 */
	public static function isInit()
	{
		return self::$isInit;
	}

	/**
	 * Sets the configuration for Propel and all dependencies.
	 *
	 * @param array $c the Configuration
	 * @return void
	 */
	public static function setConfiguration($c)
	{
		self::$configuration = $c;
	}

	/**
	 * Get the configuration for this component.
	 *
	 * @return the Configuration
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
	 * @param object $logger The new logger to use. ([PEAR] Log or BasicLogger)
	 * @return void
	 */
	public static function setLogger($logger)
	{
		self::$logger = $logger;
	}

	/**
	 * Returns true if a logger, for example PEAR::Log, has been configured,
	 * otherwise false.
	 *
	 * @return boolean True if Propel uses logging
	 */
	public static function hasLogger()
	{
		return (self::$logger !== null);
	}

	/**
	 * Get the configured logger.
	 * @return object Configured log class ([PEAR] Log or BasicLogger).
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
	 * @param string $message The message that will be logged.
	 * @param string $level The logging level.
	 * @return boolean True if the message was logged successfully or no logger was used.
	 */
	public static function log($message, $level = self::LOG_DEBUG)
	{
		if(self::hasLogger())
		{
			$logger = self::logger();
			switch($level)
			{
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
	 * @param string $name The name of the database corresponding to the DatabaseMapto retrieve.
	 * @return DatabaseMap The named <code>DatabaseMap</code>.
	 * @throws PropelException - if database map is null or propel was not initialized properly.
	 */
	public static function getDatabaseMap($name = null) {

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
	 * @param string $name The name that is used to look up the DSN from the runtime properties file. 
	 * @return PDO A database connection
	 * @throws PropelException - if no conneciton params, or lower-level exception caught when trying to connect.
	 */
	public static function getConnection($name = null) {

		if ($name === null) {
			$name = self::getDefaultDB();
		}
		
		if (!isset(self::$connectionMap[$name])) {
					
			$key = $name.'.dsn';
			
			if (!isset(self::$configuration['datasources'][$key])) {
				throw new PropelException("Unable to find " . $key . ".dsn in the [datasources] section of your configuration file.");
			}
			
			if ( ! isset ( self::$configuration['datasources'][$key] ) ) {
				throw new PropelException("Unable to find " . $key . " in the [datasources] section of your configuration file.");
			}

			$user_key = $name . '.user';
			$password_key = $name . '.password';

			if ( isset(self::$configuration['datasources'][$user_key]) ) {
				$user = self::$configuration['datasources'][$user_key];
			} else {
				$user = null;
			}

			if ( isset(self::$configuration['datasources'][$password_key]) ) {
				$password = self::$configuration['datasources'][$password_key];
			} else {
				$password = null;
			}

			$dsn = self::$configuration['datasources'][$key];			
			
			// load any driver options from the INI file
			$driver_options = array();
			$options_key = $name . '_options';
			if ( isset( self::$configuration['global_options'] ) && is_array( self::$configuration['global_options'] ) ) {
				try {
					self::_processDriverOptions( self::$configuration['global_options'], $driver_options );
				} catch (PropelException $e) {
					throw new PropelException('Error processing driver options in [global_options]', $e);
				}
			}
			if ( isset( self::$configuration[$options_key] ) ) {
				try {
					self::_processDriverOptions( self::$configuration[$options_key], $driver_options );
				} catch (PropelException $e) {
					throw new PropelException('Error processing driver options in ['.$options_key.']', $e);
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
	 * @param array $source Where to find the list of constant flags and their new setting.
	 * @param array &$write_to Put the data into here
	 * @throws PropelException - if invalid options were specified.
	 */
	private static function _processDriverOptions( $source, &$write_to )
	{
		foreach ( $source as $option_key => $option_value ) {
			$option_key = 'PDO::'.$option_key;
			if ( defined ( $option_key ) && $option_key_value = constant ( $option_key ) ) {
				$write_to[$option_key_value] = $option_value;
			} else {
				throw new PropelException("Invalid PDO option specified: ".$option_key." = ".$option_value);
			}
		}
	}

	/**
	 * Returns database adapter for a specific connection pool.
	 *
	 * @param string $name A database name.
	 * @return DBAdapter The corresponding database adapter.
	 * @throws PropelException - if unable to find DBdapter for specified db.
	 */
	public static function getDB($name = null)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}
				
		if (!isset(self::$adapterMap[$name])) {
			$key = $name.'.adapter';
			if (!isset(self::$configuration['datasources'][$key])) {
				throw new PropelException("Unable to find " . $key . ".adapter in the [datasources] section of your configuration file.");
			}
			$db = DBAdapter::factory(self::$configuration['datasources'][$key]);
			// register the adapter for this name
			self::$adapterMap[$name] = $db;
		}
		
		return self::$adapterMap[$name];
	}

	/**
	 * Returns the name of the default database.
	 *
	 * @return string Name of the default DB
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
	 * @param string $class dot-path to clas (e.g. path.to.my.ClassName).
	 * @return string unqualified classname
	 */
	public static function import($path) {

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
	 *
	 * @return void
	 */
	public static function close()
	{
		foreach(self::$connectionMap as $con) {
			$con = null; // close for PDO
		}
	}

    /**
     *  Autoload function for loading propel dependencies.
     *
     *  spl_autoload() compatible.
     *
     *  @param string The class name needing loading.
     *  @param mixed Array of file extensions?
     *  @return boolean TRUE if the class was loaded, false otherwise.
     */
    public static function autoload($className, $fileExts = NULL)
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
     * @param string $name
     */ 
    public static function setDatabaseMapClass($name)
	{ 
        self::$databaseMapClass = $name; 
    } 

}
