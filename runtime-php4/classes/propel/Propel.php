<?php

/*
 *  $Id: Propel.php,v 1.22 2005/03/29 16:35:52 micha Exp $
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
include_once 'creole/util/Param.php';

define('PROPEL_LOG_EMERG',   0);
define('PROPEL_LOG_ALERT',   1);
define('PROPEL_LOG_CRIT',    2);
define('PROPEL_LOG_ERR',     3);
define('PROPEL_LOG_WARNING', 4);
define('PROPEL_LOG_NOTICE',  5);
define('PROPEL_LOG_INFO',    6);
define('PROPEL_LOG_DEBUG',   7);

define('PROPEL_ERROR', -1);
define('PROPEL_ERROR_NOT_FOUND', -2);
define('PROPEL_ERROR_CONFIGURATION', -3);
define('PROPEL_ERROR_DB', -4);
define('PROPEL_ERROR_SYNTAX', -5);
define('PROPEL_ERROR_INVALID', -6);

/**
 * Propel's main resource pool and initialization & configuration class.
 * 
 * This static class is used to handle Propel initialization and to maintain all of the 
 * open database connections and instantiated database maps.
 *
 * @author Kaspars Jaudzems <kasparsj@navigators.lv> (Propel)
 * @author Hans Lellelid <hans@xmpl.rg> (Propel)
 * @author Michael Aichler <aichler@mediacluster.de>
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Magns �r Torfason <magnus@handtolvur.is> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Rafal Krzewski <Rafal.Krzewski@e-point.pl> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Kurt Schrader <kschrader@karmalab.org> (Torque)
 * @version $Revision: 1.22 $
 * @package propel
 */
class Propel
{
  /**
   * A constant for <code>default</code>.
   */
  function DEFAULT_NAME() { return "default"; }

  /**
   * The db name that is specified as the default in the property file
   */
  var $defaultDBName = null;

  /**
   * The global cache of database maps
   */
  var $dbMaps = array();

  /**
   * The cache of DB adapter keys
   */
  var $adapterMap = null;

  /**
   * The logging category.
   */
  var $category = null;

  /**
   * Propel-specific configuration.
   */
  var $configuration = null;

  /**
   * flag to set to true once this class has been initialized
   */
  var $isInit = false;

  /**
   * @var Log
   */
  var $logger = null;

  /**
   * Store mapbuilder classnames for peers that have been referenced prior
   * to Propel being initialized.  This can happen if the OM Peer classes are
   * included before the Propel::init() method has been called.
   */
  var $mapBuilders = array();

  /**
   * Cache of established connections (to eliminate overhead).
   * @var array
   */
  var $connectionMap = array();

  /*
  * PHP4 extension to Propel to get static access to the memeber functions.
  */
  function & getInstance ()
  {
    static $instance;

    if ($instance === null) {
      // can't use reference with static data
      $instance = new Propel();
    }

    return $instance;
  }

  /**
   * initialize Propel
   * @return mixed TRUE on success, PropelException Any exceptions caught during processing will be
   *         rethrown wrapped into a PropelException.
   */
  function initialize()
  {
    $self =& Propel::getInstance();

    if ($self->configuration === null) {
      /* [MA]: trigger_error should be ok here, shouldn't it ? */
      trigger_error(
          "Propel cannot be initialized without "
        . "a valid configuration. Please check the log files "
        . "for further details.",
        E_USER_ERROR
      );
    }

    // Setup PEAR Log
    $self->configureLogging();

    // Now that we have dealt with processing the log properties
    // that may be contained in the configuration we will make the
    // configuration consist only of the remaining propel-specific
    // properties that are contained in the configuration. First
    // look for properties that are in the "propel" namespace.
    $originalConf = $self->configuration;
    $self->configuration = isset($self->configuration['propel']) ? $self->configuration['propel'] : null;

    if (empty($self->configuration)) {
      // Assume the original configuration already had any
      // prefixes stripped.
      $self->configuration = $originalConf;
    }

    $e = $self->initAdapters($self->configuration);
    if (Propel::isError($e)) { return $e; }

    $self->isInit = true;

    foreach($self->mapBuilders as $mbClass)
    {
      //this will add any maps in this builder to the proper database map
      $e = BasePeer::getMapBuilder($mbClass);
      if (Propel::isError($e)) { return $e; }
    }

    // now that the pre-loaded map builders have been propertly initialized
    // empty the array.
    // any further mapBuilders will be called/built on demand
    $self->mapBuilders = array();
    return true;
  }

  /**
  * Setup the adapters needed.  An adapter must be defined for each database connection.
  * Generally the adapter will be the same as the PEAR phpname; e.g. for MySQL, use the
  * 'mysql' adapter.
  * @param array $configuration the Configuration representing the properties file
  * @return TRUE on success, PropelException Any exceptions caught during processing will be
  *         rethrown wrapped into a PropelException.
  */
  function initAdapters($configuration)
  {
    $self =& Propel::getInstance();

    // a bit excessive ...
    // self::$logger->log("Starting initAdapters", PEAR_LOG_DEBUG);

    $self->adapterMap = array();

    $c = isset($configuration['datasources']) ? $configuration['datasources'] : null;

    if (! empty($c))
    {
      foreach($c as $handle => $properties)
      {
        if (is_array($properties) && isset($properties['adapter']))
        {
          $db =& DBAdapter::factory($properties['adapter']);
          if (Propel::isError($db)) {
            return new PropelException(PROPEL_ERROR_NOT_FOUND, "Unable to initialize adapters.", $e);
          }
          // register the adapter for this name
          $self->adapterMap["$handle"] =& $db;
        }
      }
    }
    else
    {
      $self->log("There were no adapters in the configuration.", PROPEL_LOG_WARNING);
      return new PropelException(PROPEL_ERROR_NOT_FOUND, "There were no adapters in the configuration.");
    }

    return true;
  }

  /**
  * Configure propel.
  * This function triggers an error of type E_USER_ERROR, if the configuration file cannot be read.
  *
  * @param string $config Path (absolute or relative to include_path) to config file.
  * @return void
  */
  function configure($configFile)
  {
    $self =& Propel::getInstance();
    $self->configuration = include($configFile);

    if ($self->configuration === false) {
      trigger_error("Unable to open configuration file: " . var_export($configFile, true), E_USER_ERROR);
    }
  }

  /**
  * Initialization of Propel with a properties file.
  *
  * @param string $c The Propel configuration file path.
  * @return mixed boolean TRUE on success, PropelException on error: Any exceptions caught during processing will be
  *         rethrown wrapped into a PropelException.
  */
  function init($c)
  {
    // get a new instance (all the properties are static)
    $p =& Propel::getInstance();
    $p->configure($c);

    if (Propel::isError($e = $p->initialize())) {
      return $e;
    }

    return true;
  }

  /**
  * Determine whether Propel has already been initialized.
  *
  * @return boolean True if Propel is already initialized.
  */
  function isInit()
  {
    $self =& Propel::getInstance();
    return $self->isInit;
  }

  /**
  * Sets the configuration for Propel and all dependencies.
  *
  * @param array $c the Configuration
  * @return void
  */
  function setConfiguration($c)
  {
    $self =& Propel::getInstance();
    $self->configuration = $c;
  }

  /**
  * Get the configuration for this component.
  *
  * @return the Configuration
  */
  function getConfiguration()
  {
    $self =& Propel::getInstance();
    return $self->configuration;
  }

  /**
  * Configure the logging for this subsystem.
  * The logging system is only configured if there is a 'log'
  * section in the passed-in runtime configuration.
  * @return void
  */
  function configureLogging()
  {
    $self =& Propel::getInstance();

    if ($self->logger === null)
    {
      if (isset($self->configuration['log']) && is_array($self->configuration['log']) && count($self->configuration['log'])) 
      {
        include_once 'Log.php'; // PEAR Log class

        $c =& $self->configuration['log'];
  
        $type  = isset($c['type'])  ? $c['type']  : 'file';
        $name  = isset($c['name'])  ? $c['name']  : './propel.log';
        $ident = isset($c['ident']) ? $c['ident'] : 'propel';
        $conf  = isset($c['conf'])  ? $c['conf']  : array();
        $level = isset($c['level']) ? $c['level'] : PEAR_LOG_DEBUG;
  
        $self->logger = Log::singleton($type, $name, $ident, $conf, $level);
      }
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
  function setLogger(&$logger)
  {
    $self =& Propel::getInstance();
    $self->logger =& $logger;
  }

  /**
  * Get the configured logger.
  * @return object Configured log class ([PEAR] Log or BasicLogger).
  */
  function & logger()
  {
    $self =& Propel::getInstance();
    return $self->logger;
  }

  /**
  * Logs a message.
  *
  * If a logger has been configured, the logger will be used, otherwrise the
  * logging message will be discarded without any further action
  *
  * @param string $message The message that will be logged.
  * @param string $level The logging level.
  * @return boolean True if the message was logged successfully or no logger was used.
  */
  function log($message, $level = PROPEL_LOG_DEBUG)
  {
    $self =& Propel::getInstance();

    if($self->logger != null)
    {
      switch($level)
      {
        case PROPEL_LOG_EMERG:
          return $self->logger->log($message, $level);
        case PROPEL_LOG_ALERT:
          return $self->logger->alert($message);
        case PROPEL_LOG_CRIT:
          return $self->logger->crit($message);
        case PROPEL_LOG_ERR:
          return $self->logger->err($mesage);
        case PROPEL_LOG_WARNING:
          return $self->logger->warning($message);
        case PROPEL_LOG_NOTICE:
          return $self->logger->notice($message);
        case PROPEL_LOG_INFO:
          return $self->logger->info($message);
        default:
          return $self->logger->debug($message);
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
  * @return DatabaseMap The named <code>DatabaseMap</code> or
  *         PropelException - if database map is null or propel was not initialized properly.
  */
  function & getDatabaseMap($name = null)
  {
    $self =& Propel::getInstance();

    if ($name === null || $name == 'default')
    {
      $name = $self->getDefaultDB();

      if ($name === null) {
        return new PropelException (PROPEL_ERROR_CONFIGURATION, "DatabaseMap name was null!");
      }
    }

    // CACHEHOOK - this would be a good place
    // to add shared memory caching options (database
    // maps should be a pretty safe candidate for shared mem caching)
    $map = null;

    if (isset($self->dbMaps["$name"])) {
      $map =& $self->dbMaps["$name"];
    }

    if ($map === null) {
      $self->initDatabaseMap($name);
      // Still not there.  Create and add.
      $map =& $self->dbMaps["$name"];
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
  *         rethrown wrapped into a PropelException.
  */
  function initDatabaseMap($name)
  {
    $self =& Propel::getInstance();
    //$self->logger->debug("Initializing database map $name");
    $map = new DatabaseMap($name);
    $self->dbMaps["$name"] =& $map;
    return $map;
  }

  /**
  * Register a MapBuilder
  *
  * @param string $className the MapBuilder
  */
  function registerMapBuilder($className)
  {
    $self =& Propel::getInstance();
    $self->mapBuilders[] = $className;
  }

  /**
  * Returns the specified property of the given database, or the empty
  * string if no value is set for the property.
  *
  * @param string $db   The name of the database whose property to get.
  * @param string $prop The name of the property to get.
  * @return mixed The property's value.
  */
  function getDatabaseProperty($db, $prop)
  {
    if ($db === null || $db == 'default')
      $db = Propel::getDefaultDB();

    $self =& Propel::getInstance();
    return isset($self->configuration['datasources'][$db][$prop]) ? $self->configuration['datasources'][$db][$prop] : null;
  }

  /**
  *
  * @param string $name The database name.
  * @return Connection A database connection or
  *         PropelException - if no conneciton params, or SQLException caught when trying to connect.
  */
  function & getConnection($name = null)
  {
    $self =& Propel::getInstance();
    $con = null;

    if ($name === null || $name == 'default') {
      $name = $self->getDefaultDB();
    }

    if (isset($self->connectionMap["$name"]))
      $con =& $self->connectionMap["$name"];

    if ($con === null)
    {
      $dsn = isset($self->configuration['datasources']["$name"]['connection']) ? $self->configuration['datasources']["$name"]['connection'] : null;

      if ($dsn === null) {
        return new PropelException(PROPEL_ERROR_CONFIGURATION, "No connection params set for " . $name);
      }

      require_once 'creole/Creole.php';

      // if specified, use custom driver
      if (isset($self->configuration['datasources']["$name"]['driver'])) {
        Creole::registerDriver($dsn['phptype'], $self->configuration['datasources']["$name"]['driver']);
      }

      $con =& Creole::getConnection($dsn);
      if (Creole::isError($con)) {
        return new PropelException(PROPEL_ERROR_DB, $con);
      }

      $self->connectionMap[$name] =& $con;
    }

    return $con;
  }

  /**
  * Returns database adapter for a specific connection pool.
  * This function triggers an error of type E_USER_ERROR, if DBAdapter cannot be loaded.
  *
  * @param string $name A database name.
  * @return DBAdapter The corresponding database adapter.
  */
  function & getDB($name = null)
  {
    $self =& Propel::getInstance();

    if ($name === null || $name == 'default') {
      $name = $self->getDefaultDB();
    }

    if (! isset($self->adapterMap["$name"])) {
      trigger_error("Unable to load DBAdapter for database: " . var_export($name, true), E_USER_ERROR);
    }

    return @$self->adapterMap["$name"];
  }

  /**
  * Returns the name of the default database.
  *
  * @return string Name of the default DB
  */
  function getDefaultDB()
  {
    $self =& Propel::getInstance();

    if ($self->configuration === null) {
      trigger_error(
        'Propel::getDefaultDB(): Propel configuration not initialized !',
        E_USER_ERROR
      );
    }

    if ($self->defaultDBName === null && ! isset($self->configuration['datasources']['default'])) {
      trigger_error(
        'Propel::getDefaultDB(): No default database specified in configuration !',
        E_USER_ERROR
      );
    }


      // Determine default database name.
    $self->defaultDBName = $self->configuration['datasources']['default'];
/*
    if ($self->configuration === null) {
      return $self->DEFAULT_NAME();
    }
    elseif ($self->defaultDBName === null) {
      // Determine default database name.
      $self->defaultDBName = isset($self->configuration['database']['default'])
                             ? $self->configuration['database']['default']
                             : $self->DEFAULT_NAME;
    }
*/
    return $self->defaultDBName;
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
  * @return string unqualified classname or PropelExecption on error.
  */
  function import($class)
  {
    if (!class_exists($class))
    {
      $path = strtr($class, '.', DIRECTORY_SEPARATOR) . '.php';
      $ret = include_once($path);
      if ($ret === false) {
        return new PropelException(PROPEL_ERROR_NOT_FOUND, "Unable to import class: " . $class);
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
  function close()
  {
    $self =& Propel::getInstance();

    foreach($self->$connectionMap as $conn) {
      $conn->close();
    }
  }

  /**
  * Tell whether a result code of a Propel method is an error.
  *
  * @param mixed
  * @return bool Whether $value is an error.
  * @access public
  */
  function isError($value)
  {
    return is_a($value, 'Exception');
  }
  
  /**
  * Verifies that @c $value is of type @c $type.
  *
  * @param object $value The object in question.
  * @param string $type The type to check agains.
  * @param string $class
  * @param string $func
  * @param int $param
  *
  * @return void
  */
  function typeHint(&$value, $type, $class, $func, $param = 1)
  {
    if (! is_a($value, "$type")) {
      trigger_error (
        "$class::$func(): parameter '$param' not of type '$type' !",
        E_USER_ERROR
      );
    }
  }

  /**
  * Temporarily function that is used to check that e.g. optional
  * Connection parameters are passed wrapped inside a Param object.
  *
  * @param mixed $param The optional parameter in question.
  * @param string $class The current class name.
  * @param string $func The current function name.
  * @param int $pos Position of the paramter in the functions argument list.
  *
  * @return void
  */
  function assertParam(&$param, $class, $func, $pos = 2)
  {
    if (! is_null($param) && ! is_a($param, 'Param'))
    {
      $type = is_object($param) ? get_class($param) : gettype($param);

      trigger_error (
        "$class::$func(): Optional parameter '$pos' of type '$type' is not wrapped inside a Param object ! " .
        "Use Param::set() to pass in an optional parameter as reference, e.g.: " .
        "FooPeer::doDelete(\$criteria, Param::set(\$con)); ",
        E_USER_ERROR
      );
    }
  }

  /**
  * Returns a reference to null.
  */
  function & null()
  {
    return null;
  }

}
