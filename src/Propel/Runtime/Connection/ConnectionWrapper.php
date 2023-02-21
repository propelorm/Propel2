<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use PDOException;
use Propel\Runtime\Connection\Exception\RollbackException;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\InvalidArgumentException;
use Propel\Runtime\Propel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Wraps a Connection class, providing nested transactions, statement cache, and logging.
 *
 * This class was designed to work around the limitation in PDO where attempting to begin
 * a transaction when one has already been begun will trigger a PDOException. Propel
 * relies on the ability to create nested transactions, even if the underlying layer
 * simply ignores these (because it doesn't support nested transactions).
 *
 * The changes that this class makes to the underlying API include the addition of the
 * getNestedTransactionDepth() and isInTransaction() and the fact that beginTransaction()
 * will no longer throw a PDOException (or trigger an error) if a transaction is already
 * in-progress.
 */
class ConnectionWrapper implements ConnectionInterface, LoggerAwareInterface
{
    use TransactionTrait;

    /**
     * Attribute to use to set whether to cache prepared statements.
     */
    public const PROPEL_ATTR_CACHE_PREPARES = -1;

    /**
     * Set debug mode for all instances without instance-specific configuration.
     *
     * @var bool
     */
    public static $useDebugMode = false;

    /**
     * Instance-specific debug mode setting.
     *
     * @var bool|null
     */
    protected $useDebugModeOnInstance;

    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * The wrapped connection class
     *
     * @var \Propel\Runtime\Connection\ConnectionInterface|null
     */
    protected $connection;

    /**
     * The current transaction depth.
     *
     * @var int
     */
    protected $nestedTransactionCount = 0;

    /**
     * @var bool
     * Whether the final commit is possible
     * Is false if a nested transaction is rolled back
     */
    protected $isUncommitable = false;

    /**
     * Count of queries performed.
     *
     * @var int
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
     * @var array [sql => StatementWrapper]
     */
    protected $cachedPreparedStatements = [];

    /**
     * Whether to cache prepared statements.
     *
     * @var bool
     */
    protected $isCachePreparedStatements = false;

    /**
     * The list of methods that trigger logging.
     *
     * @var array
     */
    protected $logMethods = [
        'exec',
        'query',
        'execute',
    ];

    /**
     * Configured logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Determines if debug mode is used on this connection instance.
     *
     * @return bool
     */
    public function isInDebugMode(): bool
    {
        return $this->useDebugModeOnInstance ?? static::$useDebugMode;
    }

    /**
     * Creates a Connection instance.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $name The datasource name associated to this connection
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null The datasource name associated to this connection
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return \Propel\Runtime\Connection\ConnectionInterface|null
     */
    public function getWrappedConnection(): ?ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Gets the current transaction depth.
     *
     * @return int
     */
    public function getNestedTransactionCount(): int
    {
        return $this->nestedTransactionCount;
    }

    /**
     * Set the current transaction depth.
     *
     * @param int $v The new depth.
     *
     * @return void
     */
    protected function setNestedTransactionCount(int $v): void
    {
        $this->nestedTransactionCount = $v;
    }

    /**
     * Is this PDO connection currently in-transaction?
     * This is equivalent to asking whether the current nested transaction count is greater than 0.
     *
     * @return bool
     */
    public function isInTransaction(): bool
    {
        return ($this->getNestedTransactionCount() > 0);
    }

    /**
     * Check whether the connection contains a transaction that can be committed.
     * To be used in an environment where Propelexceptions are caught.
     *
     * @return bool True if the connection is in a committable transaction
     */
    public function isCommitable(): bool
    {
        return $this->isInTransaction() && !$this->isUncommitable;
    }

    /**
     * Overrides PDO::beginTransaction() to prevent errors due to already-in-progress transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        $return = true;
        if (!$this->nestedTransactionCount) {
            $return = $this->connection->beginTransaction();
            if ($this->isInDebugMode()) {
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
     * @throws \Propel\Runtime\Connection\Exception\RollbackException
     *
     * @return bool
     */
    public function commit(): bool
    {
        $return = true;
        $opcount = $this->nestedTransactionCount;

        if ($opcount > 0 && $this->inTransaction()) {
            if ($opcount === 1) {
                if ($this->isUncommitable) {
                    throw new RollbackException('Cannot commit because a nested transaction was rolled back');
                }

                $return = $this->connection->commit();
                if ($this->isInDebugMode()) {
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
     * @return bool Whether operation was successful.
     */
    public function rollBack(): bool
    {
        $return = true;
        $opcount = $this->nestedTransactionCount;

        if ($opcount > 0 && $this->inTransaction()) {
            if ($opcount === 1) {
                $return = $this->connection->rollBack();
                if ($this->isInDebugMode()) {
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
     * @return bool Whether operation was successful.
     */
    public function forceRollBack(): bool
    {
        $return = true;

        if ($this->nestedTransactionCount) {
            // If we're in a transaction, always roll it back
            // regardless of nesting level.
            $return = $this->connection->rollBack();

            // reset nested transaction count to 0 so that we don't
            // try to commit (or rollback) the transaction outside this scope.
            $this->nestedTransactionCount = 0;

            if ($this->isInDebugMode()) {
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
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Retrieve a database connection attribute.
     *
     * @param int $attribute The name of the attribute to retrieve,
     *                          e.g. PDO::ATTR_AUTOCOMMIT
     *
     * @return mixed A successful call returns the value of the requested attribute.
     *               An unsuccessful call returns null.
     */
    public function getAttribute(int $attribute)
    {
        switch ($attribute) {
            case self::PROPEL_ATTR_CACHE_PREPARES:
                return $this->isCachePreparedStatements;
            default:
                return $this->connection->getAttribute($attribute);
        }
    }

    /**
     * Set an attribute.
     *
     * @param string|int $attribute The attribute name, or the constant name containing the attribute name (e.g. 'PDO::ATTR_CASE')
     * @param mixed $value
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     *
     * @return bool
     */
    public function setAttribute($attribute, $value): bool
    {
        if (is_string($attribute)) {
            if (strpos($attribute, '::') === false) {
                if (defined('\PDO::' . $attribute)) {
                    $attribute = '\PDO::' . $attribute;
                } else {
                    $attribute = self::class . '::' . $attribute;
                }
            }
            if (!defined($attribute)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid connection option/attribute name specified: "%s"',
                    $attribute,
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

        return true;
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * Overrides PDO::prepare() in order to:
     *  - Add logging and query counting if logging is true.
     *  - Add query caching support if the PropelPDO::PROPEL_ATTR_CACHE_PREPARES was set to true.
     *
     * @param string $statement This must be a valid SQL statement for the target database server.
     * @param array $driverOptions One $array or more key => value pairs to set attribute values
     *                               for the PDOStatement object that this method returns.
     *
     * @return \Propel\Runtime\Connection\StatementInterface|false
     */
    public function prepare(string $statement, array $driverOptions = [])
    {
        if ($this->isCachePreparedStatements && isset($this->cachedPreparedStatements[$statement])) {
            $statementWrapper = $this->cachedPreparedStatements[$statement];
        } else {
            $statementWrapper = $this->createStatementWrapper($statement);
            $statementWrapper->prepare($driverOptions);
            if ($this->isCachePreparedStatements) {
                $this->cachedPreparedStatements[$statement] = $statementWrapper;
            }
        }

        if ($this->isInDebugMode()) {
            $this->log($statement);
        }

        return $statementWrapper;
    }

    /**
     * @inheritDoc
     */
    public function exec($statement): int
    {
        if ($this->isInDebugMode()) {
            /** @var callable $callback */
            $callback = [$this->connection, 'exec'];

            return $this->callUserFunctionWithLogging($callback, [$statement], $statement);
        }

        return $this->connection->exec($statement);
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
     * @param mixed ...$args
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function query(string $statement, ...$args): DataFetcherInterface
    {
        $statementWrapper = $this->createStatementWrapper($statement);

        return $statementWrapper->query(...$args);
    }

    /**
     * Run a query callback and log the SQL statement.
     *
     * This method ensures, that the statement is logged, even if an error occures, and that the
     * query is logged after it was run. The latter is necessary for profiling to work.
     *
     * @param callable $callback
     * @param array|null $args
     * @param string $sqlForLog Logged SQL query
     *
     * @throws \PDOException
     *
     * @return mixed
     */
    public function callUserFunctionWithLogging(callable $callback, ?array $args, string $sqlForLog)
    {
        if ($args === null) {
            $args = [];
        }
        $pdoException = null;
        $return = null;

        try {
            $return = $callback(...$args);
        } catch (PDOException $e) {
            $pdoException = $e;
        }

        // For profiling to work, $this->log() needs to be run after the query was executed
        $this->log($sqlForLog);
        $this->setLastExecutedQuery($sqlForLog);
        $this->incrementQueryCount();

        if ($pdoException !== null) {
            throw $pdoException;
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
     * @param int $parameterType Provides a data type hint for drivers that
     *                               have alternate quoting styles.
     *
     * @return string A quoted string that is theoretically safe to pass into an
     *                SQL statement. Returns FALSE if the driver does not support
     *                quoting in this way.
     */
    public function quote(string $string, int $parameterType = 2): string
    {
        return $this->connection->quote($string, $parameterType);
    }

    /**
     * @inheritDoc
     */
    public function getSingleDataFetcher($data): DataFetcherInterface
    {
        return $this->connection->getSingleDataFetcher($data);
    }

    /**
     * @inheritDoc
     */
    public function getDataFetcher($data): DataFetcherInterface
    {
        return $this->connection->getDataFetcher($data);
    }

    /**
     * Creates a wrapper for the Statement object.
     *
     * @param string $sql A valid SQL statement
     *
     * @return \Propel\Runtime\Connection\StatementWrapper
     */
    protected function createStatementWrapper(string $sql): StatementWrapper
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
     * @param string|null $name Name of the sequence object from which the ID should be
     *                     returned.
     *
     * @return string|int If a sequence name was not specified for the name parameter,
     *                returns a string representing the row ID of the last row that was
     *                inserted into the database.
     *                If a sequence name was specified for the name parameter, returns
     *                a string representing the last value retrieved from the specified
     *                sequence object.
     */
    public function lastInsertId(?string $name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * Clears any stored prepared statements for this connection.
     *
     * @return void
     */
    public function clearStatementCache(): void
    {
        $this->cachedPreparedStatements = [];
    }

    /**
     * Returns the number of queries this DebugPDO instance has performed on the database connection.
     *
     * When using DebugPDOStatement as the statement class, any queries by DebugPDOStatement instances
     * are counted as well.
     *
     * @return int
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    /**
     * Increments the number of queries performed by this DebugPDO instance.
     *
     * Returns the original number of queries (ie the value of $this->queryCount before calling this method).
     *
     * @return void
     */
    public function incrementQueryCount(): void
    {
        $this->queryCount++;
    }

    /**
     * Get the SQL code for the latest query executed by Propel
     *
     * @return string Executable SQL code
     */
    public function getLastExecutedQuery(): string
    {
        return $this->lastExecutedQuery;
    }

    /**
     * Set the SQL code for the latest query executed by Propel
     *
     * @param string $query Executable SQL code
     *
     * @return void
     */
    public function setLastExecutedQuery(string $query): void
    {
        $this->lastExecutedQuery = $query;
    }

    /**
     * Enable or disable the query debug features
     *
     * @param bool|null $value True to enable debug (default), false to disable it, null to use mode from class
     *
     * @return void
     */
    public function useDebug(?bool $value = true): void
    {
        if (!$value) {
            // reset query logging
            $this->setLastExecutedQuery('');
            $this->queryCount = 0;
        }
        $this->clearStatementCache();
        $this->useDebugModeOnInstance = $value;
    }

    /**
     * @param array $logMethods
     *
     * @return void
     */
    public function setLogMethods(array $logMethods): void
    {
        $this->logMethods = $logMethods;
    }

    /**
     * @return array
     */
    public function getLogMethods(): array
    {
        return $this->logMethods;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    protected function isLogEnabledForMethod(string $methodName): bool
    {
        return in_array($methodName, $this->getLogMethods(), true);
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Gets the logger to use for this connection.
     * If no logger was set, returns the default logger from the Service Container.
     *
     * @return \Psr\Log\LoggerInterface A logger.
     */
    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            return Propel::getServiceContainer()->getLogger($this->getName());
        }

        return $this->logger;
    }

    /**
     * Logs the method call or the executed SQL statement.
     *
     * @param string $msg Message to log.
     *
     * @return void
     */
    public function log(string $msg): void
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
        } while (in_array($callingMethod, ['log', 'callUserFunctionWithLogging'], true) && $i < $stackSize);

        if (!$msg || !$this->isLogEnabledForMethod($callingMethod)) {
            return;
        }

        $this->getLogger()->info($msg);
    }

    /**
     * Forward any call to a method not found to the wrapped connection.
     *
     * @param string $method
     * @param mixed $args
     *
     * @return mixed
     */
    public function __call(string $method, $args)
    {
        return $this->connection->$method(...$args);
    }
}
