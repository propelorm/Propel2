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
use Propel\Runtime\Connection\Exception\RollbackException;
use Propel\Runtime\Exception\InvalidArgumentException;
use Monolog\Logger;

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
    /**
     * Attribute to use to set whether to cache prepared statements.
     */
    const PROPEL_ATTR_CACHE_PREPARES    = -1;

    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * Whether or not the debug is enabled
     *
     * @var       Boolean
     */
    public $useDebug = false;

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
     * @var       Boolean
     */
    protected $isCachePreparedStatements = false;

    /**
     * The list of methods that trigger logging.
     *
     * @var array
     */
    protected $logMethods = array(
        'exec',
        'query',
        'execute',
    );

    /**
     * Configured logger.
     *
     * @var       \Monolog\Logger
     */
    protected $logger;

    /**
     * Creates a Connection instance.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        if ($this->useDebug) {
            $this->log('Opening connection');
        }
    }

    /**
     * @param string $name The datasource name associated to this connection
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWrappedConnection()
    {
        return $this->connection;
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
     * @param int $v The new depth.
     */
    protected function setNestedTransactionCount($v)
    {
        $this->nestedTransactionCount = $v;
    }

    /**
     * Is this PDO connection currently in-transaction?
     * This is equivalent to asking whether the current nested transaction count is greater than 0.
     *
     * @return    Boolean
     */
    public function isInTransaction()
    {
        return ($this->getNestedTransactionCount() > 0);
    }

    /**
     * Check whether the connection contains a transaction that can be committed.
     * To be used in an evironment where Propelexceptions are caught.
     *
     * @return    Boolean  True if the connection is in a committable transaction
     */
    public function isCommitable()
    {
        return $this->isInTransaction() && !$this->isUncommitable;
    }

    /**
     * Overrides PDO::beginTransaction() to prevent errors due to already-in-progress transaction.
     *
     * @return    Boolean
     */
    public function beginTransaction()
    {
        $return = true;
        if (!$this->nestedTransactionCount) {
            $return = $this->connection->beginTransaction();
            if ($this->useDebug) {
                $this->log('Begin transaction');
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
     * @return    Boolean
     */
    public function commit()
    {
        $return = true;
        $opcount = $this->nestedTransactionCount;

        if ($opcount > 0) {
            if (1 === $opcount) {
                if ($this->isUncommitable) {
                    throw new RollbackException('Cannot commit because a nested transaction was rolled back');
                }

                $return = $this->connection->commit();
                if ($this->useDebug) {
                    $this->log('Commit transaction');
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
     * @return    Boolean  Whether operation was successful.
     */
    public function rollBack()
    {
        $return = true;
        $opcount = $this->nestedTransactionCount;

        if ($opcount > 0) {
            if (1 === $opcount) {
                $return = $this->connection->rollBack();
                if ($this->useDebug) {
                    $this->log('Rollback transaction');
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
     * @return    Boolean  Whether operation was successful.
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
                $this->log('Rollback transaction');
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
        switch ($attribute) {
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
     * @param mixed  $value
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute) && false !== strpos($attribute, '::')) {
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf('Invalid connection option/attribute name specified: "%s"', $attribute));
            }
            $attribute = constant($attribute);
        }
        switch ($attribute) {
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
     * @param string $sql            This must be a valid SQL statement for the target database server.
     * @param array  $driver_options One $array or more key => value pairs to set attribute values
     *                                      for the PDOStatement object that this method returns.
     *
     * @return    \Propel\Runtime\Connection\StatementInterface
     */
    public function prepare($sql, $driver_options = array())
    {
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
            $this->log($sql);
        }

        return $return;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     * Overrides PDO::exec() to log queries when required
     *
     * @param string $sql
     * @return    integer
     */
    public function exec($sql)
    {
        $return = $this->connection->exec($sql);

        if ($this->useDebug) {
            $this->log($sql);
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
        $args = func_get_args();
        $return = call_user_func_array(array($this->connection, 'query'), $args);

        if ($this->useDebug) {
            $sql = $args[0];
            $this->log($sql);
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
     * @param string $string         The string to be quoted.
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
     * @param string $query Executable SQL code
     */
    public function setLastExecutedQuery($query)
    {
        $this->lastExecutedQuery = $query;
    }

    /**
     * Enable or disable the query debug features
     *
     * @param Boolean $value True to enable debug (default), false to disable it
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
     * @param array $logMethods
     */
    public function setLogMethods($logMethods)
    {
        $this->logMethods = $logMethods;
    }

    /**
     * @return array
     */
    public function getLogMethods()
    {
        return $this->logMethods;
    }

    protected function isLogEnabledForMethod($methodName)
    {
        return in_array($methodName, $this->getLogMethods());
    }

    /**
     * Set a logger to use for this connection.
     *
     * @param     \Monolog\Logger  A Monolog logger
     */
    public function setLogger(Logger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Gets the logger to use for this connection.
     *
     * If no logger was set, returns the default logger from the Service Container.
     *
     * @return    \Monolog\Logger  A Monolog logger, or null.
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            return Propel::getServiceContainer()->getLogger($this->getName());
        }

        return $this->logger;
    }

    /**
     * Check if this connection has a configured logger.
     *
     * @return Boolean
     */
    public function hasLogger()
    {
        return null !== $this->logger || Propel::getServiceContainer()->hasLogger($this->getName());
    }

    /**
     * Logs the method call or the executed SQL statement.
     *
     * @param string $msg Message to log.
     */
    public function log($msg)
    {
        $backtrace = debug_backtrace();
        if (!isset($backtrace[1]['function'])) {
            return;
        }
        $callingMethod = $backtrace[1]['function'];
        if (!$msg || !$this->hasLogger() || !$this->isLogEnabledForMethod($callingMethod)) {
            return;
        }

        $this->getLogger()->addInfo($msg);
    }

    /**
     * Forward any call to a method not found to the wrapped connection.
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->connection, $method), $args);
    }

    /**
     * If so configured, makes an entry to the log of the state of this object just prior to its destruction.
     *
     * @see       self::log()
     */
    public function __destruct()
    {
        if ($this->useDebug) {
            $this->log('Closing connection');
        }
        $this->connection = null;
    }
}
