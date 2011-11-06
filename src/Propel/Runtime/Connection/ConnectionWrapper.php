<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Propel;
use Propel\Runtime\Config\Configuration;
use Propel\Runtime\Exception\PropelException;

/**
 * Wraps a Connection class, providing nested transactions, statement cache, and logging.
 *
 * This class was designed to work around the limitation in PDO where attempting to begin
 * a transaction when one has already been begun will trigger a PDOException.  Propel
 * relies on the ability to create nested transactions, even if the underlying layer
 * simply ignores these (because it doesn't support nested transactions).
 *
 * The changes that this class makes to the underlying API include the addition of the
 * getNestedTransactionDepth() and isInTransaction() and the fact that beginTransaction()
 * will no longer throw a PDOException (or trigger an error) if a transaction is already
 * in-progress.
 *
 */
class ConnectionWrapper implements ConnectionInterface
{

    const DEFAULT_SLOW_THRESHOLD        = 0.1;
    const DEFAULT_ONLYSLOW_ENABLED      = false;

    /**
     * Attribute to use to set whether to cache prepared statements.
     */
    const PROPEL_ATTR_CACHE_PREPARES    = -1;
    
    /**
     * The wrapped connection class
     * @var ConnectionInterface
     */
    protected $connection;
    
    /**
     * The current transaction depth.
     * @var       integer
     */
    protected $nestedTransactionCount = 0;

    /**
     * Whether the final commit is possible
     * Is false if a nested transaction is rolled back
     */
    protected $isUncommitable = false;

    /**
     * Count of queries performed.
     *
     * @var       integer
     */
    protected $queryCount = 0;

    /**
     * SQL code of the latest performed query.
     *
     * @var       string
     */
    protected $lastExecutedQuery;

    /**
     * Cache of prepared statements (StatementWrapper) keyed by SQL.
     *
     * @var       array  [sql => StatementWrapper]
     */
    protected $cachedPreparedStatements = array();

    /**
     * Whether to cache prepared statements.
     *
     * @var       boolean
     */
    protected $isCachePreparedStatements = false;

    /**
     * Whether or not the debug is enabled
     *
     * @var       boolean
     */
    public $useDebug = false;

    /**
     * Configured BasicLogger (or compatible) logger.
     *
     * @var       BasicLogger
     */
    protected $logger;

    /**
     * The log level to use for logging.
     *
     * @var       integer
     */
    private $logLevel = Propel::LOG_DEBUG;

    /**
     * The runtime configuration
     *
     * @var       Configuration
     */
    protected $configuration;

    /**
     * The default value for runtime config item "debugpdo.logging.methods".
     *
     * @var       array
     */
    protected static $defaultLogMethods = array(
        'exec',
        'query',
        'statement_execute',
    );

    /**
     * Creates a Connection instance.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        if ($this->useDebug) {
            $debug = $this->getDebugSnapshot();
        }
        
        $this->connection = $connection;

        if ($this->useDebug) {
            $this->log('Opening connection', null, 'construct', $debug);
        }
    }

    /**
     * @return ConnectionInterface
     */
    public function getWrappedConnection()
    {
        return $this->connection;
    }
    
    /**
     * Inject the runtime configuration
     *
     * @param     Configuration  $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Get the runtime configuration
     *
     * @return    Configuration
     */
    public function getConfiguration()
    {
        if (null === $this->configuration) {
            $this->configuration = Propel::getConfiguration(Configuration::TYPE_OBJECT);
        }

        return $this->configuration;
    }

    /**
     * Gets the current transaction depth.
     *
     * @return    integer
     */
    public function getNestedTransactionCount()
    {
        return $this->nestedTransactionCount;
    }

    /**
     * Set the current transaction depth.
     * @param     int $v The new depth.
     */
    protected function setNestedTransactionCount($v)
    {
        $this->nestedTransactionCount = $v;
    }

    /**
     * Is this PDO connection currently in-transaction?
     * This is equivalent to asking whether the current nested transaction count is greater than 0.
     *
     * @return    boolean
     */
    public function isInTransaction()
    {
        return ($this->getNestedTransactionCount() > 0);
    }

    /**
     * Check whether the connection contains a transaction that can be committed.
     * To be used in an evironment where Propelexceptions are caught.
     *
     * @return    boolean  True if the connection is in a committable transaction
     */
    public function isCommitable()
    {
        return $this->isInTransaction() && !$this->isUncommitable;
    }

    /**
     * Overrides PDO::beginTransaction() to prevent errors due to already-in-progress transaction.
     *
     * @return    boolean
     */
    public function beginTransaction()
    {
        $return = true;
        if (!$this->nestedTransactionCount) {
            $return = $this->connection->beginTransaction();
            if ($this->useDebug) {
                $this->log('Begin transaction', null, 'beginTransaction');
            }
            $this->isUncommitable = false;
        }
        $this->nestedTransactionCount++;

        return $return;
    }

    /**
     * Overrides PDO::commit() to only commit the transaction if we are in the outermost
     * transaction nesting level.
     *
     * @return    boolean
     */
    public function commit()
    {
        $return = true;
        $opcount = $this->nestedTransactionCount;

        if ($opcount > 0) {
            if ($opcount === 1) {
                if ($this->isUncommitable) {
                    throw new PropelException('Cannot commit because a nested transaction was rolled back');
                } else {
                    $return = $this->connection->commit();
                    if ($this->useDebug) {
                        $this->log('Commit transaction', null, 'commit');
                    }
                }
            }

            $this->nestedTransactionCount--;
        }

        return $return;
    }

    /**
     * Overrides PDO::rollBack() to only rollback the transaction if we are in the outermost
     * transaction nesting level
     *
     * @return    boolean  Whether operation was successful.
     */
    public function rollBack()
    {
        $return = true;
        $opcount = $this->nestedTransactionCount;

        if ($opcount > 0) {
            if ($opcount === 1) {
                $return = $this->connection->rollBack();
                if ($this->useDebug) {
                    $this->log('Rollback transaction', null, 'rollBack');
                }
            } else {
                $this->isUncommitable = true;
            }

            $this->nestedTransactionCount--;
        }

        return $return;
    }

    /**
     * Rollback the whole transaction, even if this is a nested rollback
     * and reset the nested transaction count to 0.
     *
     * @return    boolean  Whether operation was successful.
     */
    public function forceRollBack()
    {
        $return = true;

        if ($this->nestedTransactionCount) {
            // If we're in a transaction, always roll it back
            // regardless of nesting level.
            $return = $this->connection->rollBack();

            // reset nested transaction count to 0 so that we don't
            // try to commit (or rollback) the transaction outside this scope.
            $this->nestedTransactionCount = 0;

            if ($this->useDebug) {
                $this->log('Rollback transaction', null, 'forceRollBack');
            }
        }

        return $return;
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }

    /**
     * Retrieve a database connection attribute.
     *
     * @param string $attribute The name of the attribute to retrieve,
     *                          e.g. PDO::ATTR_AUTOCOMMIT
     *
     * @return mixed A successful call returns the value of the requested attribute.
     *               An unsuccessful call returns null.
     */
    public function getAttribute($attribute)
    {
        switch($attribute) {
            case self::PROPEL_ATTR_CACHE_PREPARES:
                return $this->isCachePreparedStatements;
                break;
            default:
                return $this->connection->getAttribute($attribute);
        }
    }

    /**
     * Set an attribute.
     *
     * @param string $attribute The attribute name, or the constant name containing the attribute name (e.g. 'PDO::ATTR_CASE')
     * @param mixed $value
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute) && strpos($attribute, '::') !== false) {
            if (!defined($attribute)) {
                throw new PropelException(sprintf('Invalid connection option/attribute name specified: "%s"', $attribute));
            }
            $attribute = constant($attribute);
        }
        switch($attribute) {
            case self::PROPEL_ATTR_CACHE_PREPARES:
                $this->isCachePreparedStatements = $value;
                break;
            default:
                $this->connection->setAttribute($attribute, $value);
        }
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * Overrides PDO::prepare() in order to:
     *  - Add logging and query counting if logging is true.
     *  - Add query caching support if the PropelPDO::PROPEL_ATTR_CACHE_PREPARES was set to true.
     *
     * @param     string  $sql  This must be a valid SQL statement for the target database server.
     * @param     array   $driver_options  One $array or more key => value pairs to set attribute values
     *                                      for the PDOStatement object that this method returns.
     *
     * @return    PDOStatement
     */
    public function prepare($sql, $driver_options = array())
    {
        if ($this->useDebug) {
            $debug = $this->getDebugSnapshot();
        }

        if ($this->isCachePreparedStatements) {
            if (!isset($this->cachedPreparedStatements[$sql])) {
                $return = new StatementWrapper($sql, $this, $driver_options);
                $this->cachedPreparedStatements[$sql] = $return;
            } else {
                $return = $this->cachedPreparedStatements[$sql];
            }
        } else {
            $return = new StatementWrapper($sql, $this, $driver_options);
        }

        if ($this->useDebug) {
            $this->log($sql, null, 'prepare', $debug);
        }

        return $return;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     * Overrides PDO::exec() to log queries when required
     *
     * @param     string  $sql
     * @return    integer
     */
    public function exec($sql)
    {
        if ($this->useDebug) {
            $debug = $this->getDebugSnapshot();
        }

        $return = $this->connection->exec($sql);

        if ($this->useDebug) {
            $this->log($sql, null, 'exec', $debug);
            $this->setLastExecutedQuery($sql);
            $this->incrementQueryCount();
        }

        return $return;
    }
    
    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     * Despite its signature here, this method takes a variety of parameters.
     *
     * Overrides PDO::query() to log queries when required
     *
     * @see       http://php.net/manual/en/pdo.query.php for a description of the possible parameters.
     *
     * @return    PDOStatement
     */
    public function query()
    {
        if ($this->useDebug) {
            $debug = $this->getDebugSnapshot();
        }

        $args = func_get_args();
        $return = call_user_func_array(array($this->connection, 'query'), $args);

        if ($this->useDebug) {
            $sql = $args[0];
            $this->log($sql, null, 'query', $debug);
            $this->setLastExecutedQuery($sql);
            $this->incrementQueryCount();
        }

        return $return;
    }

    /**
     * Quotes a string for use in a query.
     *
     * Places quotes around the input string (if required) and escapes special
     * characters within the input string, using a quoting style appropriate to
     * the underlying driver.
     *
     * @param string $string The string to be quoted.
     * @param int    $parameter_type Provides a data type hint for drivers that
     *                               have alternate quoting styles.
     *
     * @return string A quoted string that is theoretically safe to pass into an
     *                SQL statement. Returns FALSE if the driver does not support
     *                quoting in this way.
     */
    public function quote($string, $parameter_type = 2)
    {
        return $this->connection->quote($string, $parameter_type);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * Returns the ID of the last inserted row, or the last value from a sequence
     * object, depending on the underlying driver. For example, PDO_PGSQL()
     * requires you to specify the name of a sequence object for the name parameter.
     *
     * @param string $name Name of the sequence object from which the ID should be
     *                     returned.
     *
     * @return string If a sequence name was not specified for the name parameter,
     *                returns a string representing the row ID of the last row that was
     *                inserted into the database.
     *                If a sequence name was specified for the name parameter, returns
     *                a string representing the last value retrieved from the specified
     *                sequence object.
     */
    public function lastInsertId($name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * Clears any stored prepared statements for this connection.
     */
    public function clearStatementCache()
    {
        $this->cachedPreparedStatements = array();
    }

    /**
     * Returns the number of queries this DebugPDO instance has performed on the database connection.
     *
     * When using DebugPDOStatement as the statement class, any queries by DebugPDOStatement instances
     * are counted as well.
     *
     * @throws     PropelException if persistent connection is used (since unable to override PDOStatement in that case).
     * @return     integer
     */
    public function getQueryCount()
    {
        return $this->queryCount;
    }

    /**
     * Increments the number of queries performed by this DebugPDO instance.
     *
     * Returns the original number of queries (ie the value of $this->queryCount before calling this method).
     *
     * @return    integer
     */
    public function incrementQueryCount()
    {
        $this->queryCount++;
    }

    /**
     * Get the SQL code for the latest query executed by Propel
     *
     * @return string Executable SQL code
     */
    public function getLastExecutedQuery()
    {
        return $this->lastExecutedQuery;
    }

    /**
     * Set the SQL code for the latest query executed by Propel
     *
     * @param     string  $query  Executable SQL code
     */
    public function setLastExecutedQuery($query)
    {
        $this->lastExecutedQuery = $query;
    }

    /**
     * Enable or disable the query debug features
     *
     * @param     boolean  $value  True to enable debug (default), false to disable it
     */
    public function useDebug($value = true)
    {
        if (!$value) {
            // reset query logging
            $this->setLastExecutedQuery('');
            $this->queryCount = 0;
        }
        $this->clearStatementCache();
        $this->useDebug = $value;
    }

    /**
     * Sets the logging level to use for logging method calls and SQL statements.
     *
     * @param     integer  $level  Value of one of the Propel::LOG_* class constants.
     */
    public function setLogLevel($level)
    {
        $this->logLevel = $level;
    }

    /**
     * Sets a logger to use.
     *
     * The logger will be used by this class to log various method calls and their properties.
     *
     * @param     BasicLogger  $logger  A Logger with an API compatible with BasicLogger (or PEAR Log).
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Gets the logger in use.
     *
     * @return    BasicLogger  A Logger with an API compatible with BasicLogger (or PEAR Log).
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Logs the method call or SQL using the Propel::log() method or a registered logger class.
     *
     * @uses      self::getLogPrefix()
     * @see       self::setLogger()
     *
     * @param     string   $msg  Message to log.
     * @param     integer  $level  Log level to use; will use self::setLogLevel() specified level by default.
     * @param     string   $methodName  Name of the method whose execution is being logged.
     * @param     array    $debugSnapshot  Previous return value from self::getDebugSnapshot().
     */
    public function log($msg, $level = null, $methodName = null, array $debugSnapshot = null)
    {
        // If logging has been specifically disabled, this method won't do anything
        if (!$this->getLoggingConfig('enabled', true)) {
            return;
        }

        // If the method being logged isn't one of the ones to be logged, bail
        if (!in_array($methodName, $this->getLoggingConfig('methods', static::$defaultLogMethods))) {
            return;
        }

        // If a logging level wasn't provided, use the default one
        if ($level === null) {
            $level = $this->logLevel;
        }

        // Determine if this query is slow enough to warrant logging
        if ($this->getLoggingConfig("onlyslow", self::DEFAULT_ONLYSLOW_ENABLED)) {
            $now = $this->getDebugSnapshot();
            if ($now['microtime'] - $debugSnapshot['microtime'] < $this->getLoggingConfig("details.slow.threshold", self::DEFAULT_SLOW_THRESHOLD)) {
                return;
            }
        }

        // If the necessary additional parameters were given, get the debug log prefix for the log line
        if ($methodName && $debugSnapshot) {
            $msg = $this->getLogPrefix($methodName, $debugSnapshot) . $msg;
        }

        // We won't log empty messages
        if (!$msg) {
            return;
        }

        // Delegate the actual logging forward
        if ($this->logger) {
            $this->logger->log($msg, $level);
        } else {
            Propel::log($msg, $level);
        }
    }

    /**
     * Returns a snapshot of the current values of some functions useful in debugging.
     *
     * @return    array
     */
    public function getDebugSnapshot()
    {
        if ($this->useDebug) {
            return array(
                'microtime'             => microtime(true),
                'memory_get_usage'      => memory_get_usage($this->getLoggingConfig('realmemoryusage', false)),
                'memory_get_peak_usage' => memory_get_peak_usage($this->getLoggingConfig('realmemoryusage', false)),
            );
        } else {
            throw new PropelException('Should not get debug snapshot when not debugging');
        }
    }

    /**
     * Returns a named configuration item from the Propel runtime configuration, from under the
     * 'debugpdo.logging' prefix.  If such a configuration setting hasn't been set, the given default
     * value will be returned.
     *
     * @param     string  $key  Key for which to return the value.
     * @param     mixed   $defaultValue  Default value to apply if config item hasn't been set.
     *
     * @return    mixed
     */
    protected function getLoggingConfig($key, $defaultValue)
    {
        return $this->getConfiguration()->getParameter("debugpdo.logging.$key", $defaultValue);
    }

    /**
     * Returns a prefix that may be prepended to a log line, containing debug information according
     * to the current configuration.
     *
     * Uses a given $debugSnapshot to calculate how much time has passed since the call to self::getDebugSnapshot(),
     * how much the memory consumption by PHP has changed etc.
     *
     * @see       self::getDebugSnapshot()
     *
     * @param     string  $methodName  Name of the method whose execution is being logged.
     * @param     array   $debugSnapshot  A previous return value from self::getDebugSnapshot().
     *
     * @return    string
     */
    protected function getLogPrefix($methodName, $debugSnapshot)
    {
        $config = $this->getConfiguration()->getParameters();
        if (!isset($config['debugpdo']['logging']['details'])) {
            return '';
        }
        $prefix     = '';
        $logDetails = $config['debugpdo']['logging']['details'];
        $now        = $this->getDebugSnapshot();
        $innerGlue  = $this->getLoggingConfig('innerglue', ': ');
        $outerGlue  = $this->getLoggingConfig('outerglue', ' | ');

        // Iterate through each detail that has been configured to be enabled
        foreach ($logDetails as $detailName => $details) {

            if (!$this->getLoggingConfig("details.$detailName.enabled", false)) {
                continue;
            }

            switch ($detailName) {

                case 'slow';
                $value = $now['microtime'] - $debugSnapshot['microtime'] >= $this->getLoggingConfig('details.slow.threshold', self::DEFAULT_SLOW_THRESHOLD) ? 'YES' : ' NO';
                break;

            case 'time':
                $value = number_format($now['microtime'] - $debugSnapshot['microtime'], $this->getLoggingConfig('details.time.precision', 3)) . ' sec';
                $value = str_pad($value, $this->getLoggingConfig('details.time.pad', 10), ' ', STR_PAD_LEFT);
                break;

            case 'mem':
                $value = self::getReadableBytes($now['memory_get_usage'], $this->getLoggingConfig('details.mem.precision', 1));
                $value = str_pad($value, $this->getLoggingConfig('details.mem.pad', 9), ' ', STR_PAD_LEFT);
                break;

            case 'memdelta':
                $value = $now['memory_get_usage'] - $debugSnapshot['memory_get_usage'];
                $value = ($value > 0 ? '+' : '') . self::getReadableBytes($value, $this->getLoggingConfig('details.memdelta.precision', 1));
                $value = str_pad($value, $this->getLoggingConfig('details.memdelta.pad', 10), ' ', STR_PAD_LEFT);
                break;

            case 'mempeak':
                $value = self::getReadableBytes($now['memory_get_peak_usage'], $this->getLoggingConfig('details.mempeak.precision', 1));
                $value = str_pad($value, $this->getLoggingConfig('details.mempeak.pad', 9), ' ', STR_PAD_LEFT);
                break;

            case 'querycount':
                $value = str_pad($this->getQueryCount(), $this->getLoggingConfig('details.querycount.pad', 2), ' ', STR_PAD_LEFT);
                break;

            case 'method':
                $value = str_pad($methodName, $this->getLoggingConfig('details.method.pad', 28), ' ', STR_PAD_RIGHT);
                break;

            default:
                $value = 'n/a';
                break;
            }

            $prefix .= $detailName . $innerGlue . $value . $outerGlue;

        }

        return $prefix;
    }

    /**
     * Returns a human-readable representation of the given byte count.
     *
     * @param     integer  $bytes  Byte count to convert.
     * @param     integer  $precision  How many decimals to include.
     *
     * @return    string
     */
    protected function getReadableBytes($bytes, $precision)
    {
        $suffix = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $total = count($suffix);

        for ($i = 0; $bytes > 1024 && $i < $total; $i++) {
            $bytes /= 1024;
        }

        return number_format($bytes, $precision) . ' ' . $suffix[$i];
    }
    
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->connection, $method), $args);
    }
    
    /**
     * If so configured, makes an entry to the log of the state of this object just prior to its destruction.
     * Add Connection::__destruct to $defaultLogMethods to see this message
     *
     * @see       self::log()
     */
    public function __destruct()
    {
        if ($this->useDebug) {
            $this->log('Closing connection', null, __METHOD__, $this->getDebugSnapshot());
        }
        $this->connection = null;
    }
}
