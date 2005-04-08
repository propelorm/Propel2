<?php

/*
 *  $Id: Propel.php,v 1.50 2005/03/31 17:49:10 hlellelid Exp $
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
 * @version $Revision: 1.50 $
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
	private static $adapterMap;

	/**
	 * The logging category.
	 */
	private static $category;

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
	 * Store mapbuilder classnames for peers that have been referenced prior
	 * to Propel being initialized.  This can happen if the OM Peer classes are
	 * included before the Propel::init() method has been called.
	 */
	private static $mapBuilders = array();

	/**
	 * Cache of established connections (to eliminate overhead).
	 * @var array
	 */
	private static $connectionMap = array();
	
	/**
	 * initialize Propel
	 * @return void
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	public static function initialize() {
	
		if (self::$configuration === null) {
			throw new PropelException("Propel cannot be initialized without "
					. "a valid configuration. Please check the log files "
					. "for further details.");
		}

		self::configureLogging();

		// Now that we have dealt with processing the log properties
		// that may be contained in the configuration we will make the
		// configuration consist only of the remaining propel-specific
		// properties that are contained in the configuration. First
		// look for properties that are in the "propel" namespace.
		$originalConf = self::$configuration;
		self::$configuration = isset(self::$configuration['propel']) ? self::$configuration['propel'] : null;

		if (empty(self::$configuration)) {		
				// Assume the original configuration already had any
				// prefixes stripped.
				self::$configuration = $originalConf;			
		}

		self::initAdapters(self::$configuration);

		self::$isInit = true;
		
		// map builders may be registered w/ Propel before Propel has
		// been initialized; in this case they are stored in a static
		// var of this class & now can be propertly initialized.
		foreach(self::$mapBuilders as $mbClass) {
			BasePeer::getMapBuilder($mbClass);
		}
		
		// now that the pre-loaded map builders have been propertly initialized
		// empty the array.
		// any further mapBuilders will be build by the generated MapBuilder classes.
		self::$mapBuilders = array();
	}

	/**
	 * Setup the adapters needed.  An adapter must be defined for each database connection.
	 * Generally the adapter will be the same as the PEAR phpname; e.g. for MySQL, use the
	 * 'mysql' adapter.
	 * @param array $configuration the Configuration representing the properties file
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	private static function initAdapters($configuration) {
 
		self::$adapterMap = array();
 
		$c = isset($configuration['datasources']) ? $configuration['datasources'] : null;
		
		if (!empty($c)) {
			try {				
				foreach($c as $handle => $properties) {				
					if (is_array($properties) && isset($properties['adapter'])) {
						$db = DBAdapter::factory($properties['adapter']);
						// register the adapter for this name
						self::$adapterMap[$handle] = $db;
					}
				}
			} catch (Exception $e) {
				throw new PropelException("Unable to initialize adapters.", $e);
			}
		} else {
			self::log("There were no adapters in the configuration.", self::LOG_WARNING);
		}
	}

	/**
	 * configure propel
	 *
	 * @param string $config Path (absolute or relative to include_path) to config file.
	 * @return void
	 * @throws PropelException If configuration file cannot be opened. (E_WARNING probably will also be raised in PHP)
	 */
	public static function configure($configFile)
	{
		self::$configuration = include($configFile);
		if (self::$configuration === false) {
			throw new PropelException("Unable to open configuration file: " . var_export($configFile, true));
		}
	}
	
	/**
	 * Initialization of Propel with a properties file.
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
	 * Configure the logging for this subsystem.
	 * The logging system is only configured if there is a 'log'
	 * section in the passed-in runtime configuration.
	 * @return void
	 */
	protected static function configureLogging() {
		if (self::$logger === null) {
			if (isset(self::$configuration['log']) && is_array(self::$configuration['log']) && count(self::$configuration['log'])) {
				include_once 'Log.php'; // PEAR Log class
				$c = self::$configuration['log'];
				// array casting handles bug in PHP5b2 where the isset() checks
				// below may return true if $c is not an array (e.g. is a string)
				
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
		return self::$logger !== null;
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
		
		// CACHEHOOK - this would be a good place
		// to add shared memory caching options (database
		// maps should be a pretty safe candidate for shared mem caching)
		
		if (isset(self::$dbMaps[$name])) {
		    $map = self::$dbMaps[$name];
		} else {
			$map = self::initDatabaseMap($name);		
		}
		
		return $map;
	}

	/**
	 * Creates and initializes the mape for the named database.
	 *
	 * The database maps are "registered" by the generated map builder classes
	 * by calling this method and then adding the tables, etc. to teh DatabaseMap
	 * object returned from this method.
	 * 
	 * @param string $name The name of the database to map.
	 * @return DatabaseMap The desired map.
	 * @throws PropelException Any exceptions caught during processing will be
	 *		 rethrown wrapped into a PropelException.
	 */
	private static function initDatabaseMap($name)
	{	
		$map = new DatabaseMap($name);		
		self::$dbMaps[$name] = $map;
		return $map;
	}

	/**
	 * Register a MapBuilder
	 *
	 * @param string $className the MapBuilder
	 */
	public static function registerMapBuilder($className)
	{
		self::$mapBuilders[] = $className;
	}

	/**
	 * Returns the specified property of the given database, or the empty
	 * string if no value is set for the property.
	 *
	 * @param string $db   The name of the database whose property to get.
	 * @param string $prop The name of the property to get.
	 * @return mixed The property's value.
	 */
	private static function getDatabaseProperty($db, $prop)
	{
		return isset(self::$configuration['datasources'][$db][$prop]) ? self::$configuration['datasources'][$db][$prop] : null;
	}

	/**
	 *
	 * @param string $name The database name.
	 * @return Connection A database connection
	 * @throws PropelException - if no conneciton params, or SQLException caught when trying to connect.
	 */
	public static function getConnection($name = null) {
		
		if ($name === null) {
			$name = self::getDefaultDB();
		}
		
		$con = isset(self::$connectionMap[$name]) ? self::$connectionMap[$name] : null;
		
		if ($con === null) {		
			
			$dsn = isset(self::$configuration['datasources'][$name]['connection']) ? self::$configuration['datasources'][$name]['connection'] : null;
			if ($dsn === null) {
				throw new PropelException("No connection params set for " . $name);
			}
			
			include_once 'creole/Creole.php';
			
			// if specified, use custom driver
			if (isset(self::$configuration['datasources'][$name]['driver'])) {
				Creole::registerDriver($dsn['phptype'], self::$configuration['datasources'][$name]['driver']);
			}
			
			try {
				$con = Creole::getConnection($dsn);
			} catch (SQLException $e) {
				throw new PropelException($e);
			}
			self::$connectionMap[$name] = $con;
		}
		
		return $con;
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
			throw new PropelException("Unable to load DBAdapter for database '" . var_export($name, true) . "' (check your runtime properties file!)");
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
		if (self::$configuration === null) {
			return self::DEFAULT_NAME;
		} elseif (self::$defaultDBName === null) {
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
	public static function import($class) {
		if (!class_exists($class, false)) {
			$path = strtr($class, '.', DIRECTORY_SEPARATOR) . '.php';
			$ret = include_once($path);
			if ($ret === false) {
				throw new PropelException("Unable to import class: " . $class);
			}
			$pos = strrpos($class, '.');
			if ($pos !== false) { 
				$class = substr($class, $pos + 1);  // there is no '.' in the qualifed name
			}
		}
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
		foreach(self::$connectionMap as $conn) {
			$conn->close();
		}
	}
	
}
