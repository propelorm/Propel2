<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

/**
 * Interface for Propel Connection object.
 * Based on the PDO interface.
 * @see http://php.net/manual/en/book.pdo.php
 *
 * @author Francois Zaninotto
 */
interface ConnectionInterface
{
    /**
     * @param string $name The datasource name associated to this connection
     */
    public function setName($name);

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName();

    /**
     * Turns off autocommit mode.
     *
     * While autocommit mode is turned off, changes made to the database via
     * the Connection object instance are not committed until you end the
     * transaction by calling Connection::commit().
     * Calling Connection::rollBack() will roll back all changes to the database
     * and return the connection to autocommit mode.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function beginTransaction();

    /**
     * Commits a transaction.
     *
     * commit() returns the database connection to autocommit mode until the
     * next call to connection::beginTransaction() starts a new transaction.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function commit();

    /**
     * Rolls back a transaction.
     *
     * Rolls back the current transaction, as initiated by beginTransaction().
     * It is an error to call this method if no transaction is active.
     * If the database was set to autocommit mode, this function will restore
     * autocommit mode after it has rolled back the transaction.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function rollBack();

    /**
     * Checks if inside a transaction.
     *
     * @return bool TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction();

    /**
     * Retrieve a database connection attribute.
     *
     * @param string $attribute The name of the attribute to retrieve,
     *                          e.g. PDO::ATTR_AUTOCOMMIT
     *
     * @return mixed A successful call returns the value of the requested attribute.
     *               An unsuccessful call returns null.
     */
    public function getAttribute($attribute);

    /**
     * Set an attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function setAttribute($attribute, $value);

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
    public function lastInsertId($name = null);

    /**
     * @param $data
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function getSingleDataFetcher($data);

    /**
     * @param $data
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public function getDataFetcher($data);

    /**
     * Executes the given callable within a transaction.
     * This helper method takes care to commit or rollback the transaction.
     *
     * In case you want the transaction to rollback just throw an Exception of any type.
     *
     * @param callable $callable A callable to be wrapped in a transaction
     *
     * @return mixed Returns the result of the callable.
     *
     * @throws \Exception Re-throws a possible <code>Exception</code> triggered by the callable.
     */
    public function transaction(callable $callable);

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *                          Data inside the query should be properly escaped.
     *
     * @return int The number of rows that were modified or deleted by the SQL
     *             statement you issued. If no rows were affected, returns 0.
     */
    public function exec($statement);

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
     * @param string $statement      This must be a valid SQL statement for the target
     *                               database server.
     * @param array  $driver_options
     *
     * @return \Propel\Runtime\Connection\StatementInterface|bool       A Statement object if the database server
     *                                                                  successfully prepares, FALSE otherwise.
     * @throws \Propel\Runtime\Connection\Exception\ConnectionException depending on error handling.
     */
    public function prepare($statement, $driver_options = null);

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *                          Data inside the query should be properly escaped.
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     * @throws \Propel\Runtime\Connection\Exception\ConnectionException depending on error handling.
     */
    public function query($statement);

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
    public function quote($string, $parameter_type = \PDO::PARAM_STR);
}
