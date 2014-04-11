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
 * Based on the PDOStatement interface.
 * @see http://www.php.net/manual/en/class.pdostatement.php
 *
 * @author Francois Zaninotto
 */
interface StatementInterface
{
    /**
     * Binds a parameter to the specified variable name.
     *
     * Binds a PHP variable to a corresponding named or question mark placeholder in the
     * SQL statement that was use to prepare the statement. Unlike PDOStatement->bindValue(),
     * the variable is bound as a reference and will only be evaluated at the time
     * that PDOStatement->execute() is called.
     *
     * Most parameters are input parameters, that is, parameters that are
     * used in a read-only fashion to build up the query. Some drivers support the invocation
     * of stored procedures that return data as output parameters, and some also as input/output
     * parameters that both send in data and are updated to receive it.
     *
     * @param mixed $column Parameter identifier. For a prepared statement using named placeholders,
     *                      this will be a parameter name of the form :name. For a prepared statement
     *                      using question mark placeholders, this will be the 1-indexed position of the parameter
     *
     * @param mixed $variable Name of the PHP variable to bind to the SQL statement parameter.
     *
     * @param  integer $type Explicit data type for the parameter using the PDO::PARAM_* constants. To return
     *                       an INOUT parameter from a stored procedure, use the bitwise OR operator to set the
     *                       PDO::PARAM_INPUT_OUTPUT bits for the data_type parameter.
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function bindParam($column, &$variable, $type = null);

    /**
     * Binds a value to a parameter.
     *
     * Binds a value to a corresponding named or question mark placeholder
     * in the SQL statement that was used to prepare the statement.
     *
     * @param mixed $param Parameter identifier. For a prepared statement using named placeholders,
     *                     this will be a parameter name of the form :name. For a prepared statement
     *                     using question mark placeholders, this will be the 1-indexed position of the parameter
     *
     * @param mixed   $value The value to bind to the parameter.
     * @param integer $type  Explicit data type for the parameter using the PDO::PARAM_* constants.
     *
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function bindValue($param, $value, $type = null);

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
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function closeCursor();

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
     * @return integer Returns the number of columns in the result set represented
     *                 by the PDOStatement object. If there is no result set,
     *                 this method should return 0.
     */
    public function columnCount();

    /**
     * Executes a prepared statement.
     *
     * If the prepared statement included parameter markers, you must either:
     *  - call PDOStatement->bindParam() to bind PHP variables to the parameter markers:
     * bound variables pass their value as input and receive the output value,
     * if any, of their associated parameter markers
     * - or pass an array of input-only parameter values
     *
     *
     * @param  array   $parameters An array of values with as many elements as there are
     *                             bound parameters in the SQL statement being executed.
     * @return boolean Returns TRUE on success or FALSE on failure.
     */
    public function execute($parameters = null);

    /**
     * Fetches the next row from a result set.
     *
     * Fetches a row from a result set associated with a Statement object.
     * The fetch_style parameter determines how the Connection returns the row.
     *
     * @param integer $fetchStyle        Controls how the next row will be returned to the caller.
     * @param integer $cursorOrientation For a PDOStatement object representing a scrollable cursor,
     *                                   This value determines which row will be returned to the caller.
     * @param integer $cursorOffset      For a PDOStatement object representing a
     *                                   scrollable cursor for which the cursor_orientation
     *                                   parameter is set to PDO::FETCH_ORI_ABS, this value
     *                                   specifies the absolute number of the row in the
     *                                   result set that shall be fetched.
     *
     *                                   For a PDOStatement object representing a
     *                                   scrollable cursor for which the cursor_orientation
     *                                   parameter is set to PDO::FETCH_ORI_REL, this value
     *                                   specifies the row to fetch relative to the cursor
     *                                   position before PDOStatement::fetch() was called.
     *
     * @return mixed
     */
    public function fetch($fetchStyle = 4);

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param  integer $fetchStyle Controls the contents of the returned array as documented in fetch()
     * @return array
     */
    public function fetchAll($fetchStyle = 4);

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param integer $columnIndex 0-indexed number of the column you wish to retrieve from the row. If no
     *                             value is supplied, PDOStatement->fetchColumn()
     *                             fetches the first column.
     *
     * @return string A single column in the next row of a result set.
     */
    public function fetchColumn($columnIndex = 0);

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
     * @return integer The number of rows.
     */
    public function rowCount();
}
