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

require 'propel/PropelException.php';
require 'propel/adapter/DBAdapter.php';
require 'propel/util/PropelPDO.php';

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
	 * The Propel version.
	 */
	const VERSION = '1.3.0-dev';

	/**
	 * The class name for a PDO object.
	 */
	const CLASS_PDO = 'PDO';

	/**
	 * The class name for a PropelPDO object.
	 */
	const CLASS_PROPEL_PDO = 'PropelPDO';

	/**
	 * The class name for a DebugPDO object.
	 */
	const CLASS_DEBUG_PDO = 'DebugPDO';

	/**
	 * Constant used to request a READ connection (applies to replication).
	 */
	const CONNECTION_READ = 'read';

	/**
	 * Constant used to request a WRITE connection (applies to replication).
	 */
	const CONNECTION_WRITE = 'write';

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
	 * @var        string The name of the database mapper class
	 */
	private static $databaseMapClass = 'DatabaseMap';

	/**
	 * @var        bool Whether the object instance pooling is enabled
	 */
	private static $instancePoolingEnabled = true;

	/**
	 * @var        bool For replication, whether to force the use of master connection.
	 */
	private static $forceMasterConnection = false;

	/**
	 * @var        array A map of class names and their file paths for autoloading
	 */
	private static $autoloadMap = array(
		'PropelException' => 'propel/PropelException.php',

		'DBAdapter' => 'propel/adapter/DBAdapter.php',
		'DBMSSQL' => 'propel/adapter/DBMSSQL.php',
		'DBMySQL' => 'propel/adapter/DBMySQL.php',
		'DBMySQLi' => 'propel/adapter/DBMySQLi.php',
		'DBNone' => 'propel/adapter/DBNone.php',
		'DBOracle' => 'propel/adapter/DBOracle.php',
		'DBPostgres' => 'propel/adapter/DBPostgres.php',
		'DBSQLite' => 'propel/adapter/DBSQLite.php',
		'DBSybase' => 'propel/adapter/DBSybase.php',

		'BasicLogger' => 'propel/logger/BasicLogger.php',
		'MojaviLogAdapter' => 'propel/logger/MojaviLogAdapter.php',

		'ColumnMap' => 'propel/map/ColumnMap.php',
		'DatabaseMap' => 'propel/map/DatabaseMap.php',
		'MapBuilder' => 'propel/map/MapBuilder.php',
		'TableMap' => 'propel/map/TableMap.php',
		'ValidatorMap' => 'propel/map/ValidatorMap.php',

		'BaseObject' => 'propel/om/BaseObject.php',
		'NodeObject' => 'propel/om/NodeObject.php',
		'Persistent' => 'propel/om/Persistent.php',
		'PreOrderNodeIterator' => 'propel/om/PreOrderNodeIterator.php',
		'NestedSetPreOrderNodeIterator' => 'propel/om/NestedSetPreOrderNodeIterator.php',
		'NestedSetRecursiveIterator' => 'propel/om/NestedSetRecursiveIterator.php',

		'BasePeer' => 'propel/util/BasePeer.php',
		'NodePeer' => 'propel/util/NodePeer.php',
		'Criteria' => 'propel/util/Criteria.php',
		'PeerInfo' => 'propel/util/PeerInfo.php',
		'PropelColumnTypes' => 'propel/util/PropelColumnTypes.php',
		'PropelPDO' => 'propel/util/PropelPDO.php',
		'PropelPager' => 'propel/util/PropelPager.php',
		'PropelDateTime' => 'propel/util/PropelDateTime.php',
		'DebugPDO' => 'propel/util/DebugPDO.php',
		'DebugPDOStatement' => 'propel/util/DebugPDOStatement.php',

		'BasicValidator' => 'propel/validator/BasicValidator.php',
		'MatchValidator' => 'propel/validator/MatchValidator.php',
		'MaxLengthValidator' => 'propel/validator/MaxLengthValidator.php',
		'MaxValueValidator' => 'propel/validator/MaxValueValidator.php',
		'MinLengthValidator' => 'propel/validator/MinLengthValidator.php',
		'MinValueValidator' => 'propel/validator/MinValueValidator.php',
		'NotMatchValidator' => 'propel/validator/NotMatchValidator.php',
		'RequiredValidator' => 'propel/validator/RequiredValidator.php',
		'UniqueValidator' => 'propel/validator/UniqueValidator.php',
		'ValidValuesValidator' => 'propel/validator/ValidValuesValidator.php',
		'ValidationFailed' => 'propel/validator/ValidationFailed.php',
	);

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

		foreach (self::$configuration['datasources'] as $key => $datasource) {
			if ($key != 'default' && isset($datasource['classes'])) {
				// merge the classes to the autoload map
				self::$autoloadMap = array_merge($datasource['classes'], self::$autoloadMap);
			}
		}

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
		self::$configuration = include($configFile);
		if (self::$configuration === false) {
			throw new PropelException("Unable to open configuration file: " . var_export($configFile, true));
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
	 * Sets the database map object to use for specified datasource.
	 *
	 * @param      string $name The datasource name.
	 * @param      DatabaseMap $map The database map object to use for specified datasource.
	 */
	public static function setDatabaseMap($name, DatabaseMap $map)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}
		self::$dbMaps[$name] = $map;
	}

	/**
	 * For replication, set whether to always force the use of a master connection.
	 *
	 * @param      boolean $bit True or False
	 */
	public static function setForceMasterConnection($bit)
	{
		self::$forceMasterConnection = (bool) $bit;
	}

	/**
	 * For replication, whether to always force the use of a master connection.
	 *
	 * @return     boolean
	 */
	public static function getForceMasterConnection()
	{
		return self::$forceMasterConnection;
	}

	/**
	 * Sets a Connection for specified datasource name.
	 *
	 * @param      string $name The datasource name for the connection being set.
	 * @param      PropelPDO $con The PDO connection.
	 * @param      string $mode Whether this is a READ or WRITE connection (Propel::CONNECTION_READ, Propel::CONNECTION_WRITE)
	 */
	public static function setConnection($name, PropelPDO $con, $mode = Propel::CONNECTION_WRITE)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}
		if ($mode == Propel::CONNECTION_READ) {
			self::$connectionMap[$name]['slave'] = $con;
		} else {
			self::$connectionMap[$name]['master'] = $con;
		}
	}

	/**
	 * Gets an already-opened PDO connection or opens a new one for passed-in db name.
	 *
	 * @param      string $name The datasource name that is used to look up the DSN from the runtime configuation file.
	 * @param      string $mode The connection mode (this applies to replication systems).
	 *
	 * @return     PDO A database connection
	 *
	 * @throws     PropelException - if connection cannot be configured or initialized.
	 */
	public static function getConnection($name = null, $mode = Propel::CONNECTION_WRITE)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}

		// IF a WRITE-mode connection was requested
		// or Propel is configured to always use the master connection
		// or the slave for this connection has already been set to FALSE (indicating no slave)
		// THEN return the master connection.
		if ($mode != Propel::CONNECTION_READ || self::$forceMasterConnection || (isset(self::$connectionMap[$name]['slave']) && self::$connectionMap[$name]['slave'] === false)) {
			if (!isset(self::$connectionMap[$name]['master'])) {
				// load connection parameter for master connection
				$conparams = isset(self::$configuration['datasources'][$name]['connection']) ? self::$configuration['datasources'][$name]['connection'] : null;
				if (empty($conparams)) {
					throw new PropelException('No connection information in your runtime configuration file for datasource ['.$name.']');
				}
				// initialize master connection
				$con = Propel::initConnection($conparams, $name);
				self::$connectionMap[$name]['master'] = $con;
			}

			return self::$connectionMap[$name]['master'];

		} else {

			if (!isset(self::$connectionMap[$name]['slave'])) {

				// we've already ensured that the configuration exists, in previous if-statement
				$slaveconfigs = isset(self::$configuration['datasources'][$name]['slaves']) ? self::$configuration['datasources'][$name]['slaves'] : null;

				if (empty($slaveconfigs)) { // no slaves configured for this datasource
					self::$connectionMap[$name]['slave'] = false;
					return self::getConnection($name, Propel::CONNECTION_WRITE); // Recurse to get the WRITE connection
				} else { // Initialize a new slave
					if (isset($slaveconfigs['connection']['dsn'])) { // only one slave connection configured
						$conparams = $slaveconfigs['connection'];
					} else {
						$randkey = array_rand($slaveconfigs['connection']);
						$conparams = $slaveconfigs['connection'][$randkey];
						if (empty($conparams)) {
							throw new PropelException('No connection information in your runtime configuration file for SLAVE ['.$randkey.'] to datasource ['.$name.']');
						}
					}

					// initialize master connection
					$con = Propel::initConnection($conparams, $name);
					self::$connectionMap[$name]['slave'] = $con;
				}

			} // if datasource slave not set

			return self::$connectionMap[$name]['slave'];

		} // if mode == CONNECTION_WRITE

	} // getConnection()

	/**
	 * Opens a new PDO connection for passed-in db name.
	 *
	 * @param      array $conparams Connection paramters.
	 * @param      string $name Datasource name.
	 * @param      string $defaultClass The PDO subclass to instantiate if there is no explicit classname
	 * 									specified in the connection params (default is Propel::CLASS_PROPEL_PDO)
	 *
	 * @return     PDO A database connection of the given class (PDO, PropelPDO, SlavePDO or user-defined)
	 *
	 * @throws     PropelException - if lower-level exception caught when trying to connect.
	 */
	public static function initConnection($conparams, $name, $defaultClass = Propel::CLASS_PROPEL_PDO)
	{

		$dsn = $conparams['dsn'];
		if ($dsn === null) {
			throw new PropelException('No dsn specified in your connection parameters for datasource ['.$name.']');
		}

		if (isset($conparams['classname']) && !empty($conparams['classname'])) {
			$classname = $conparams['classname'];
			if (!class_exists($classname)) {
				throw new PropelException('Unable to load specified PDO subclass: ' . $classname);
			}
		} else {
			$classname = $defaultClass;
		}

		$user = isset($conparams['user']) ? $conparams['user'] : null;
		$password = isset($conparams['password']) ? $conparams['password'] : null;

		// load any driver options from the config file
		// driver options are those PDO settings that have to be passed during the connection construction
		$driver_options = array();
		if ( isset($conparams['options']) && is_array($conparams['options']) ) {
			try {
				self::processDriverOptions( $conparams['options'], $driver_options );
			} catch (PropelException $e) {
				throw new PropelException('Error processing driver options for datasource ['.$name.']', $e);
			}
		}

		try {
			$con = new $classname($dsn, $user, $password, $driver_options);
			$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new PropelException("Unable to open PDO connection", $e);
		}

		// load any connection options from the config file
		// connection attributes are those PDO flags that have to be set on the initialized connection
		if (isset($conparams['attributes']) && is_array($conparams['attributes'])) {
			$attributes = array();
			try {
				self::processDriverOptions( $conparams['attributes'], $attributes );
			} catch (PropelException $e) {
				throw new PropelException('Error processing connection attributes for datasource ['.$name.']', $e);
			}
			foreach ($attributes as $key => $value) {
				$con->setAttribute($key, $value);
			}
		}

		// initialize the connection using the settings provided in the config file. this could be a "SET NAMES <charset>" query for MySQL, for instance
		$adapter = self::getDB($name);
		$adapter->initConnection($con, isset($conparams['settings']) && is_array($conparams['settings']) ? $conparams['settings'] : array());

		return $con;
	}

	/**
	 * Internal function to handle driver options or conneciton attributes in PDO.
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
			if (is_string($option) && strpos($option, '::') !== false) {
				$key = $option;
			} elseif (is_string($option)) {
				$key = 'PropelPDO::' . $option;
			}
			if (!defined($key)) {
				throw new PropelException("Invalid PDO option/attribute name specified: ".$key);
			}
			$key = constant($key);

			$value = $optiondata['value'];
			if (is_string($value) && strpos($value, '::') !== false) {
				if (!defined($value)) {
					throw new PropelException("Invalid PDO option/attribute value specified: ".$value);
				}
				$value = constant($value);
			}

			$write_to[$key] = $value;
		}
	}

	/**
	 * Returns database adapter for a specific datasource.
	 *
	 * @param      string The datasource name.
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
	 * Sets a database adapter for specified datasource.
	 *
	 * @param      string $name The datasource name.
	 * @param      DBAdapter $adapter The DBAdapter implementation to use.
	 */
	public static function setDB($name, DBAdapter $adapter)
	{
		if ($name === null) {
			$name = self::getDefaultDB();
		}
		self::$adapterMap[$name] = $adapter;
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
	 * Closes any associated resource handles.
	 *
	 * This method frees any database connection handles that have been
	 * opened by the getConnection() method.
	 */
	public static function close()
	{
		foreach (self::$connectionMap as $idx => $cons) {
			// Propel::log("Closing connections for " . $idx, Propel::LOG_DEBUG);
			unset(self::$connectionMap[$idx]);
		}
	}

	/**
	 * Autoload function for loading propel dependencies.
	 *
	 * @param      string The class name needing loading.
	 *
	 * @return     boolean TRUE if the class was loaded, false otherwise.
	 */
	public static function autoload($className)
	{
		if (isset(self::$autoloadMap[$className])) {
			require(self::$autoloadMap[$className]);
			return true;
		}
		return false;
	}

	/**
	 * Include once a file specified in DOT notation and reutrn unqualified clasname.
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

	/**
	 * Disable instance pooling.
	 */
	public static function disableInstancePooling()
	{
		self::$instancePoolingEnabled = false;
	}

	/**
	 * Enable instance pooling (enabled by default).
	 */
	public static function enableInstancePooling()
	{
		self::$instancePoolingEnabled = true;
	}

	/**
	 *  the instance pooling behaviour. True by default.
	 *
	 * @return     boolean Whether the pooling is enabled or not.
	 */
	public static function isInstancePoolingEnabled()
	{
		return self::$instancePoolingEnabled;
	}
}

spl_autoload_register(array('Propel', 'autoload'));
