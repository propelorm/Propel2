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
interface SqlConnectionInterface extends ConnectionInterface
{

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $statement The SQL statement to prepare and execute.
     *                          Data inside the query should be properly escaped.
     *
     * @return int The number of rows that were modified or deleted by the SQL
     *               statement you issued. If no rows were affected, returns 0.
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
     * @param string $statement This must be a valid SQL statement for the target
     *                          database server.
     * @param array $driver_options
     *
     * @return \Propel\Runtime\Connection\StatementInterface|bool A Statement object if the database server
     *                                 successfully prepares, FALSE otherwise.
     * @throws \Propel\Runtime\Connection\Exception\ConnectionException depending on error handling.
     */
    public function prepare($statement, $driver_options = array());

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
    public function quote($string, $parameter_type = 2);
}
