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
     * @return bool|mixed Returns the result of the callable on success, or <code>true</code> when the callable doesn't return anything.
     *
     * @throws Exception Re-throws a possible <code>Exception</code> triggered by the callable.
     */
    public function transaction(callable $callable);
}
