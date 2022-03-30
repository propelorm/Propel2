<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use IteratorAggregate;
use PDO;
use PDOStatement;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Traversable;

/**
 * Wraps a Statement class, providing logging.
 */
class StatementWrapper implements StatementInterface, IteratorAggregate
{
    /**
     * The wrapped statement class
     *
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * The connection wrapper generating this object
     *
     * @var \Propel\Runtime\Connection\ConnectionWrapper
     */
    protected $connection;

    /**
     * Hashmap for resolving the PDO::PARAM_* class constants to their human-readable names.
     * This is only used in logging the binding of variables.
     *
     * @see self::bindValue()
     *
     * @var array<string>
     */
    protected static $typeMap = [
        0 => 'PDO::PARAM_NULL',
        1 => 'PDO::PARAM_INT',
        2 => 'PDO::PARAM_STR',
        3 => 'PDO::PARAM_LOB',
        5 => 'PDO::PARAM_BOOL',
    ];

    /**
     * @var array The values that have been bound
     */
    protected $boundValues = [];

    /**
     * @var string
     */
    protected $sql;

    /**
     * Creates a Statement instance
     *
     * @param string $sql The SQL query for this statement
     * @param \Propel\Runtime\Connection\ConnectionWrapper $connection The parent connection
     */
    public function __construct($sql, ConnectionWrapper $connection)
    {
        $this->connection = $connection;
        $this->sql = $sql;
    }

    /**
     * @param array $options Optional driver options
     *
     * @return $this
     */
    public function prepare($options)
    {
        /** @var \PDOStatement $statement */
        $statement = $this->connection->getWrappedConnection()->prepare($this->sql, $options);
        $this->statement = $statement;

        return $this;
    }

    /**
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function query(): DataFetcherInterface
    {
        if ($this->connection->useDebug) {
            $callback = [$this->connection->getWrappedConnection(), 'query'];
            $statement = $this->connection->callUserFunctionWithLogging($callback, [$this->sql], $this->sql);
        } else {
            $statement = $this->connection->getWrappedConnection()->query($this->sql);
        }
        $this->statement = $statement;

        return $this->connection->getWrappedConnection()->getDataFetcher($this);
    }

    /**
     * Binds a PHP variable to a corresponding named or question mark placeholder in the SQL statement
     * that was use to prepare the statement. Unlike PDOStatement::bindValue(), the variable is bound
     * as a reference and will only be evaluated at the time that PDOStatement::execute() is called.
     * Returns a boolean value indicating success.
     *
     * @param int $parameter Parameter identifier (for determining what to replace in the query).
     * @param mixed $variable The value to bind to the parameter.
     * @param int $dataType Explicit data type for the parameter using the PDO::PARAM_* constants. Defaults to PDO::PARAM_STR.
     * @param int $length Length of the data type. To indicate that a parameter is an OUT parameter from a stored procedure, you must explicitly set the length.
     * @param mixed $driverOptions
     *
     * @return bool
     */
    public function bindParam($parameter, &$variable, $dataType = PDO::PARAM_STR, $length = 0, $driverOptions = null): bool
    {
        $return = $this->statement->bindParam($parameter, $variable, $dataType, $length, $driverOptions);
        if ($this->connection->useDebug) {
            $typestr = self::$typeMap[$dataType] ?? '(default)';
            $valuestr = $length > 100 ? '[Large value]' : var_export($variable, true);
            $this->boundValues[$parameter] = $valuestr;
            $msg = sprintf('Binding %s at position %s w/ PDO type %s', $valuestr, $parameter, $typestr);
            $this->connection->log($msg);
        }

        return $return;
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder in the SQL statement
     * that was use to prepare the statement. Returns a boolean value indicating success.
     *
     * @param int $parameter Parameter identifier (for determining what to replace in the query).
     * @param mixed $value The value to bind to the parameter.
     * @param int $dataType Explicit data type for the parameter using the PDO::PARAM_* constants. Defaults to PDO::PARAM_STR.
     *
     * @return bool
     */
    public function bindValue($parameter, $value, $dataType = PDO::PARAM_STR): bool
    {
        $return = $this->statement->bindValue($parameter, $value, $dataType);
        if ($this->connection->useDebug) {
            $typestr = self::$typeMap[$dataType] ?? '(default)';
            $valuestr = $dataType == PDO::PARAM_LOB ? '[LOB value]' : var_export($value, true);
            $this->boundValues[$parameter] = $valuestr;
            $msg = sprintf('Binding %s at position %s w/ PDO type %s', $valuestr, $parameter, $typestr);
            $this->connection->log($msg);
        }

        return $return;
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * closeCursor() frees up the connection to the server so that other SQL
     * statements may be issued, but leaves the statement in a state that enables
     * it to be executed again.
     *
     * This method is useful for database drivers that do not support executing
     * a PDOStatement object when a previously executed PDOStatement object still
     * has unfetched rows. If your database driver suffers from this limitation,
     * the problem may manifest itself in an out-of-sequence error.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function closeCursor(): bool
    {
        return $this->statement->closeCursor();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * Use columnCount() to return the number of columns in the result set
     * represented by the Statement object.
     *
     * If the Statement object was returned from PDO::query(), the column count
     * is immediately available.
     *
     * If the Statement object was returned from PDO::prepare(), an accurate
     * column count will not be available until you invoke Statement::execute().
     * Returns the number of columns in the result set
     *
     * @return int Returns the number of columns in the result set represented
     * by the PDOStatement object. If there is no result set,
     * this method should return 0.
     */
    public function columnCount(): int
    {
        return $this->statement->columnCount();
    }

    /**
     * Executes a prepared statement.
     *
     * Returns a boolean value indicating success.
     * Overridden for query counting and logging.
     *
     * @param array|null $inputParameters
     *
     * @return bool
     */
    public function execute($inputParameters = null): bool
    {
        if ($this->connection->useDebug) {
            $sql = $this->getExecutedQueryString($inputParameters);
            $args = ($inputParameters !== null) ? [$inputParameters] : [];

            return $this->connection->callUserFunctionWithLogging([$this->statement, 'execute'], $args, $sql);
        }

        return $this->statement->execute($inputParameters);
    }

    /**
     * @param array|null $inputParameters
     *
     * @return string
     */
    public function getExecutedQueryString(?array $inputParameters = null): string
    {
        return preg_replace_callback('/:p\d++\b/', function (array $matches) use ($inputParameters): string {
            $pos = $matches[0];

            return $this->boundValues[$pos]
                ?? $inputParameters[$pos]
                ?? $pos;
        }, $this->statement->queryString);
    }

    /**
     * Fetches the next row from a result set.
     *
     * Fetches a row from a result set associated with a Statement object.
     * The fetch_style parameter determines how the Connection returns the row.
     *
     * @param int $fetchStyle Controls how the next row will be returned to the caller.
     * @param int $cursorOrientation
     * @param int $cursorOffset
     *
     * @return mixed
     */
    public function fetch($fetchStyle = PDO::FETCH_BOTH, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->statement->fetch($fetchStyle);
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int|null $fetchStyle Controls the contents of the returned array as documented in fetch()
     * @param mixed|null $fetchArgument
     * @param array $ctorArgs
     *
     * @return array
     */
    public function fetchAll($fetchStyle = PDO::FETCH_BOTH, $fetchArgument = null, $ctorArgs = []): array
    {
        return $this->statement->fetchAll($fetchStyle);
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $columnIndex 0-indexed number of the column you wish to retrieve from the row. If no
     * value is supplied, PDOStatement->fetchColumn()
     * fetches the first column.
     *
     * @return string|null A single column in the next row of a result set.
     */
    public function fetchColumn($columnIndex = 0): ?string
    {
        $output = $this->statement->fetchColumn($columnIndex);

        return $output === null ? null : (string)$output;
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     *
     * rowCount() returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement
     * executed by the corresponding Statement object.
     *
     * If the last SQL statement executed by the associated Statement object was a SELECT statement,
     * some databases may return the number of rows returned by that statement. However,
     * this behaviour is not guaranteed for all databases and should not be
     * relied on for portable applications.
     *
     * @return int The number of rows.
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Return the internal statement, which is traversable
     *
     * @return \Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->statement;
    }

    /**
     * @return \Propel\Runtime\Connection\ConnectionWrapper
     */
    public function getConnection(): ConnectionWrapper
    {
        return $this->connection;
    }

    /**
     * @return \PDOStatement
     */
    public function getStatement(): PDOStatement
    {
        return $this->statement;
    }

    /**
     * @param \PDOStatement $statement
     *
     * @return void
     */
    public function setStatement(PDOStatement $statement): void
    {
        $this->statement = $statement;
    }

    /**
     * @return array
     */
    public function getBoundValues(): array
    {
        return $this->boundValues;
    }

    /**
     * @inheritDoc
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null): bool
    {
        return $this->statement->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

    /**
     * @inheritDoc
     */
    public function fetchObject($className = 'stdClass', array $ctorArgs = [])
    {
        return $this->statement->fetchObject($className, $ctorArgs);
    }

    /**
     * @inheritDoc
     */
    public function errorCode(): string
    {
        return $this->statement->errorCode();
    }

    /**
     * @inheritDoc
     */
    public function errorInfo(): array
    {
        return $this->statement->errorInfo();
    }

    /**
     * @inheritDoc
     */
    public function setAttribute($attribute, $value): bool
    {
        return $this->statement->setAttribute($attribute, $value);
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($attribute)
    {
        return $this->statement->getAttribute($attribute);
    }

    /**
     * @inheritDoc
     */
    public function getColumnMeta($column)
    {
        return $this->statement->getColumnMeta($column);
    }

    /**
     * @inheritDoc
     */
    public function setFetchMode($mode, $classNameObject = null, array $ctorarfg = []): bool
    {
        switch (func_num_args()) {
            case 1:
                return $this->statement->setFetchMode($mode);
            case 2:
                return $this->statement->setFetchMode($mode, $classNameObject);
            case 3:
                return $this->statement->setFetchMode($mode, $classNameObject, $ctorarfg);
            default:
                return $this->statement->setFetchMode(...func_get_args());
        }
    }

    /**
     * @inheritDoc
     */
    public function nextRowset(): bool
    {
        return $this->statement->nextRowset();
    }

    /**
     * @inheritDoc
     */
    public function debugDumpParams(): void
    {
        $this->statement->debugDumpParams();
    }

    /**
     * @param string $method
     * @param mixed $args
     *
     * @return mixed
     */
    public function __call(string $method, $args)
    {
        return $this->statement->$method(...$args);
    }
}
