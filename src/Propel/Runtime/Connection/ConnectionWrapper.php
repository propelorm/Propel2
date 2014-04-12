<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Propel;
use Propel\Runtime\Connection\Exception\RollbackException;
use Propel\Runtime\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

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
class ConnectionWrapper implements ConnectionInterface, LoggerAwareInterface
{
    use TransactionTrait;

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
     * @var boolean
     */
    public $useDebug = false;

    /**
     * The wrapped connection class
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * The current transaction depth.
     * @var integer
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
     * @var integer
     */
    protected $queryCount = 0;

    /**
     * SQL code of the latest performed query.
     *
     * @var string
     */
    protected $lastExecutedQuery;

    /**
     * Cache of prepared statements (StatementWrapper) keyed by SQL.
     *
     * @var array  [sql => StatementWrapper]
     */
    protected $cachedPreparedStatements = array();

    /**
     * Whether to cache prepared statements.
     *
     * @var boolean
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
     * @var LoggerInterface
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
     * @return integer
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
     * @return boolean
     */
    public function isInTransaction()
    {
        return ($this->getNestedTransactionCount() > 0);
    }

    /**
     * Check whether the connection contains a transaction that can be committed.
     * To be used in an environment where Propelexceptions are caught.
     *
     * @return boolean True if the connection is in a committable transaction
     */
    public function isCommitable()
    {
        return $this->isInTransaction() && !$this->isUncommitable;
    }

    /**
     * Overrides PDO::beginTransaction() to prevent errors due to already-in-progress transaction.
     *
     * @return boolean
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
     * @return boolean
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
     * @return boolean Whether operation was successful.
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
     * @return boolean Whether operation was successful.
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
     * @return boolean TRUE if a transaction is currently active, and FALSE if not.
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
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute)) {
            if (false === strpos($attribute, '::')) {
                if (defined('\PDO::' . $attribute)) {
                    $attribute = '\PDO::' . $attribute;
                } else {
                    $attribute = __CLASS__ . '::' . $attribute;
                }
            }
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid connection option/attribute name specified: "%s"',
                    $attribute
                ));
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
     * @param string $statement      This must be a valid SQL statement for the target database server.
     * @param array  $driver_options One $array or more key => value pairs to set attribute values
     *                               for the PDOStatement object that this method returns.
     *
     * @return StatementInterface
     */
    public function prepare($statement, $driver_options = null)
    {
        $statementWrapper = null;

        if ($this->isCachePreparedStatements && isset($this->cachedPreparedStatements[$statement])) {
            $statementWrapper = $this->cachedPreparedStatements[$statement];
        } else {
            $statementWrapper = $this->createStatementWrapper($statement);
            $statementWrapper->prepare($driver_options);
            if ($this->isCachePreparedStatements) {
                $this->cachedPreparedStatements[$statement] = $statementWrapper;
            }
        }

        if ($this->useDebug) {
            $this->log($statement);
        }

        return $statementWrapper;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     * Overrides PDO::exec() to log queries when required
     *
     * @param  string  $sql
     * @return integer
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
     * @see http://php.net/manual/en/pdo.query.php for a description of the possible parameters.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *                          Data inside the query should be properly escaped.
     *
     * @return StatementInterface
     */
    public function query($statement)
    {
        $args = func_get_args();
        $sql = array_shift($args);
        $statementWrapper = $this->createStatementWrapper($sql);
        $return = call_user_func_array(array($statementWrapper, 'query'), $args);

        if ($this->useDebug) {
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
     * {@inheritDoc}
     */
    public function getSingleDataFetcher($data)
    {
        return $this->connection->getSingleDataFetcher($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataFetcher($data)
    {
        return $this->connection->getDataFetcher($data);
    }

    /**
     * Creates a wrapper for the Statement object.
     *
     * @param string $sql A valid SQL statement
     *
     * @return StatementWrapper
     */
    protected function createStatementWrapper($sql)
    {
        return new StatementWrapper($sql, $this);
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
     * @return integer
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
     * @return integer
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
     * @param boolean $value True to enable debug (default), false to disable it
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
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Gets the logger to use for this connection.
     * If no logger was set, returns the default logger from the Service Container.
     *
     * @return LoggerInterface A logger.
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            return Propel::getServiceContainer()->getLogger($this->getName());
        }

        return $this->logger;
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

        $i = 1;
        $stackSize = count($backtrace);
        do {
            $callingMethod = $backtrace[$i]['function'];
            $i++;
        } while ($callingMethod == "log" && $i < $stackSize);

        if (!$msg || !$this->isLogEnabledForMethod($callingMethod)) {
            return;
        }

        $this->getLogger()->info($msg);
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
     * @see self::log()
     */
    public function __destruct()
    {
        if ($this->useDebug) {
            $this->log('Closing connection');
        }
        $this->connection = null;
    }
}
