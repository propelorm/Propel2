<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use PDO;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

/**
 * Interface for Propel Connection class.
 * Based on the PDO interface.
 *
 * @see http://php.net/manual/en/book.pdo.php
 *
 * @author Francois Zaninotto
 */
interface ConnectionInterface
{
    /**
     * @param string $name The datasource name associated to this connection.
     *
     * @return void
     */
    public function setName(string $name): void;

    /**
     * @return string|null The datasource name associated to this connection.
     */
    public function getName(): ?string;

    /**
     * Turns off autocommit mode.
     *
     * While autocommit mode is turned off, changes made to the database via
     * the Connection object instance are not committed until you end the
     * transaction by calling Connection::commit().
     * Calling Connection::rollBack() will roll back all changes to the database
     * and return the connection to autocommit mode.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction(): bool;

    /**
     * Commits a transaction.
     *
     * commit() returns the database connection to autocommit mode until the
     * next call to connection::beginTransaction() starts a new transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit(): bool;

    /**
     * Rolls back a transaction.
     *
     * Rolls back the current transaction, as initiated by beginTransaction().
     * It is an error to call this method if no transaction is active.
     * If the database was set to autocommit mode, this function will restore
     * autocommit mode after it has rolled back the transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBack(): bool;

    /**
     * Checks if inside a transaction.
     *
     * @return bool TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction(): bool;

    /**
     * Retrieve a database connection attribute.
     *
     * @param int $attribute The name of the attribute to retrieve,
     *                          e.g. PDO::ATTR_AUTOCOMMIT.
     *
     * @return mixed A successful call returns the value of the requested attribute.
     *               An unsuccessful call returns null.
     */
    public function getAttribute(int $attribute);

    /**
     * Set an attribute.
     *
     * @param string|int $attribute
     * @param mixed $value
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setAttribute($attribute, $value): bool;

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
    public function lastInsertId(?string $name = null);

    /**
     * @param mixed $data
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function getSingleDataFetcher($data): DataFetcherInterface;

    /**
     * @param mixed $data
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function getDataFetcher($data): DataFetcherInterface;

    /**
     * Executes the given callable within a transaction.
     * This helper method takes care to commit or rollback the transaction.
     *
     * In case you want the transaction to rollback just throw an Exception of any type.
     *
     * @param callable $callable A callable to be wrapped in a transaction.
     *
     * @throws \Throwable Re-throws a possible <code>Throwable</code> triggered by the callable.
     *
     * @return mixed Returns the result of the callable.
     */
    public function transaction(callable $callable);

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *                          Data inside the query should be properly escaped.
     *
     * @return int The number of rows that were modified or deleted.
     */
    public function exec(string $statement): int;

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * Prepares an SQL statement to be executed by the Statement::execute() method.
     * The SQL statement can contain zero or more named (:name) or question mark (?)
     * parameter markers for which real values will be substituted when the statement
     * is executed. You cannot use both named and question mark parameter markers
     * within the same SQL statement; pick one or the other parameter style. Use
     * these parameters to bind any user-input, do not include the user-input
     * directly in the query.
     *
     * @param string $statement This must be a valid SQL statement for the target database server.
     * @param array $driverOptions
     *
     * @throws \Propel\Runtime\Connection\Exception\ConnectionException depending on error handling.
     *
     * @return \Propel\Runtime\Connection\StatementInterface|\PDOStatement|false
     */
    public function prepare(string $statement, array $driverOptions = []);

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *                          Data inside the query should be properly escaped.
     *
     * @throws \Propel\Runtime\Connection\Exception\ConnectionException depending on error handling.
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface|\PDOStatement|false
     */
    public function query(string $statement);

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
    public function quote(string $string, int $parameterType = PDO::PARAM_STR): string;
}
