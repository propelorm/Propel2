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
 * Interface for Propel StatementWrapper object.
 * Based on the PDOStatement interface.
 * @see http://php.net/manual/en/book.pdo.php
 *
 * @author Aleksandr Bezpiatov
 */
interface StatementInterface
{
    /**
     * Executes a prepared statement
     * @param array $input_parameters [optional] <p>
     * An array of values with as many elements as there are bound
     * parameters in the SQL statement being executed.
     * All values are treated as <b>PDO::PARAM_STR</b>.
     * </p>
     * <p>
     * You cannot bind multiple values to a single parameter; for example,
     * you cannot bind two values to a single named parameter in an IN()
     * clause.
     * </p>
     * <p>
     * You cannot bind more values than specified; if more keys exist in
     * <i>input_parameters</i> than in the SQL specified
     * in the <b>PDO::prepare</b>, then the statement will
     * fail and an error is emitted.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * @throws \PDOException On error if PDO::ERRMODE_EXCEPTION option is true.
     */
    public function execute($input_parameters = null);

    /**
     * Fetches the next row from a result set
     * @param int $fetch_style [optional] <p>
     * Controls how the next row will be returned to the caller. This value
     * must be one of the PDO::FETCH_* constants,
     * defaulting to value of PDO::ATTR_DEFAULT_FETCH_MODE
     * (which defaults to PDO::FETCH_BOTH).
     * <p>
     * PDO::FETCH_ASSOC: returns an array indexed by column
     * name as returned in your result set
     * </p>
     * @param int $cursor_orientation [optional] <p>
     * For a PDOStatement object representing a scrollable cursor, this
     * value determines which row will be returned to the caller. This value
     * must be one of the PDO::FETCH_ORI_* constants,
     * defaulting to PDO::FETCH_ORI_NEXT. To request a
     * scrollable cursor for your PDOStatement object, you must set the
     * PDO::ATTR_CURSOR attribute to
     * PDO::CURSOR_SCROLL when you prepare the SQL
     * statement with <b>PDO::prepare</b>.
     * </p>
     * @param int $cursor_offset [optional]
     * @return mixed The return value of this function on success depends on the fetch type. In
     * all cases, <b>FALSE</b> is returned on failure.
     */
    public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0);

    /**
     * Binds a parameter to the specified variable name
     * @param mixed $parameter <p>
     * Parameter identifier. For a prepared statement using named
     * placeholders, this will be a parameter name of the form
     * :name. For a prepared statement using
     * question mark placeholders, this will be the 1-indexed position of
     * the parameter.
     * </p>
     * @param mixed $variable <p>
     * Name of the PHP variable to bind to the SQL statement parameter.
     * </p>
     * @param int $data_type [optional] <p>
     * Explicit data type for the parameter using the PDO::PARAM_*
     * constants.
     * To return an INOUT parameter from a stored procedure,
     * use the bitwise OR operator to set the PDO::PARAM_INPUT_OUTPUT bits
     * for the <i>data_type</i> parameter.
     * </p>
     * @param int $length [optional] <p>
     * Length of the data type. To indicate that a parameter is an OUT
     * parameter from a stored procedure, you must explicitly set the
     * length.
     * </p>
     * @param mixed $driver_options [optional] <p>
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null, $driver_options = null);

    /**
     * Bind a column to a PHP variable
     * @param mixed $column <p>
     * Number of the column (1-indexed) or name of the column in the result set.
     * If using the column name, be aware that the name should match the
     * case of the column, as returned by the driver.
     * </p>
     * @param mixed $param <p>
     * Name of the PHP variable to which the column will be bound.
     * </p>
     * @param int $type [optional] <p>
     * Data type of the parameter, specified by the PDO::PARAM_* constants.
     * </p>
     * @param int $maxlen [optional] <p>
     * A hint for pre-allocation.
     * </p>
     * @param mixed $driverdata [optional] <p>
     * Optional parameter(s) for the driver.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null);

    /**
     * Binds a value to a parameter
     * @param mixed $parameter <p>
     * Parameter identifier. For a prepared statement using named
     * placeholders, this will be a parameter name of the form
     * :name. For a prepared statement using
     * question mark placeholders, this will be the 1-indexed position of
     * the parameter.
     * </p>
     * @param mixed $value <p>
     * The value to bind to the parameter.
     * </p>
     * @param int $data_type [optional] <p>
     * Explicit data type for the parameter using the PDO::PARAM_*
     * constants.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR);

    /**
     * Returns the number of rows affected by the last SQL statement
     * @return int the number of rows.
     */
    public function rowCount();

    /**
     * Returns a single column from the next row of a result set
     * @param int $column_number [optional] <p>
     * 0-indexed number of the column you wish to retrieve from the row. If
     * no value is supplied, <b>PDOStatement::fetchColumn</b>
     * fetches the first column.
     * </p>
     * @return mixed Returns a single column from the next row of a result
     * set or FALSE if there are no more rows.
     * </p>
     * <p>
     * There is no way to return another column from the same row if you
     * use <b>PDOStatement::fetchColumn</b> to retrieve data.
     */
    public function fetchColumn($column_number = 0);

    /**
     * Returns an array containing all of the result set rows
     * @param int $fetch_style [optional] <p>
     * Controls the contents of the returned array as documented in
     * <b>PDOStatement::fetch</b>.
     * Defaults to value of <b>PDO::ATTR_DEFAULT_FETCH_MODE</b>
     * (which defaults to <b>PDO::FETCH_BOTH</b>)
     * </p>
     * <p>
     * To return an array consisting of all values of a single column from
     * the result set, specify <b>PDO::FETCH_COLUMN</b>. You
     * can specify which column you want with the
     * <i>column-index</i> parameter.
     * </p>
     * <p>
     * To fetch only the unique values of a single column from the result set,
     * bitwise-OR <b>PDO::FETCH_COLUMN</b> with
     * <b>PDO::FETCH_UNIQUE</b>.
     * </p>
     * <p>
     * To return an associative array grouped by the values of a specified
     * column, bitwise-OR <b>PDO::FETCH_COLUMN</b> with
     * <b>PDO::FETCH_GROUP</b>.
     * </p>
     * @param mixed $fetch_argument [optional] <p>
     * This argument have a different meaning depending on the value of
     * the <i>fetch_style</i> parameter:
     * <p>
     * <b>PDO::FETCH_COLUMN</b>: Returns the indicated 0-indexed
     * column.
     * </p>
     * @param array $ctor_args [optional] <p>
     * Arguments of custom class constructor when the <i>fetch_style</i>
     * parameter is <b>PDO::FETCH_CLASS</b>.
     * </p>
     * @return array <b>PDOStatement::fetchAll</b> returns an array containing
     * all of the remaining rows in the result set. The array represents each
     * row as either an array of column values or an object with properties
     * corresponding to each column name.
     * </p>
     * <p>
     * Using this method to fetch large result sets will result in a heavy
     * demand on system and possibly network resources. Rather than retrieving
     * all of the data and manipulating it in PHP, consider using the database
     * server to manipulate the result sets. For example, use the WHERE and
     * ORDER BY clauses in SQL to restrict results before retrieving and
     * processing them with PHP.
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, array $ctor_args = array());

    /**
     * Fetches the next row and returns it as an object.
     * @param string $class_name [optional] <p>
     * Name of the created class.
     * </p>
     * @param array $ctor_args [optional] <p>
     * Elements of this array are passed to the constructor.
     * </p>
     * @return mixed an instance of the required class with property names that
     * correspond to the column names or <b>FALSE</b> on failure.
     */
    public function fetchObject($class_name = "stdClass", array $ctor_args = array());

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle
     * @return string Identical to <b>PDO::errorCode</b>, except that
     * <b>PDOStatement::errorCode</b> only retrieves error codes
     * for operations performed with PDOStatement objects.
     */
    public function errorCode();

    /**
     * Fetch extended error information associated with the last operation on the statement handle
     * @return array <b>PDOStatement::errorInfo</b> returns an array of
     * error information about the last operation performed by this
     * statement handle. The array consists of the following fields:
     * <tr valign="top">
     * <td>Element</td>
     * <td>Information</td>
     * </tr>
     * <tr valign="top">
     * <td>0</td>
     * <td>SQLSTATE error code (a five characters alphanumeric identifier defined
     * in the ANSI SQL standard).</td>
     * </tr>
     * <tr valign="top">
     * <td>1</td>
     * <td>Driver specific error code.</td>
     * </tr>
     * <tr valign="top">
     * <td>2</td>
     * <td>Driver specific error message.</td>
     * </tr>
     */
    public function errorInfo();

    /**
     * Set a statement attribute
     * @param int $attribute
     * @param mixed $value
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function setAttribute($attribute, $value);

    /**
     * Retrieve a statement attribute
     * @param int $attribute
     * @return mixed the attribute value.
     */
    public function getAttribute($attribute);

    /**
     * Returns the number of columns in the result set
     * @return int the number of columns in the result set represented by the
     * StatementInterface object. If there is no result set,
     * <b>StatementInterface::columnCount</b> returns 0.
     */
    public function columnCount();

    /**
     * Returns metadata for a column in a result set
     * @param int $column <p>
     * The 0-indexed column in the result set.
     * </p>
     * @return array|false an associative array containing the following values representing
     * the metadata for a single column:
     * </p>
     * <table>
     * Column metadata
     * <tr valign="top">
     * <td>Name</td>
     * <td>Value</td>
     * </tr>
     * <tr valign="top">
     * <td>native_type</td>
     * <td>The PHP native type used to represent the column value.</td>
     * </tr>
     * <tr valign="top">
     * <td>driver:decl_type</td>
     * <td>The SQL type used to represent the column value in the database.
     * If the column in the result set is the result of a function, this value
     * is not returned by <b>PDOStatement::getColumnMeta</b>.
     * </td>
     * </tr>
     * <tr valign="top">
     * <td>flags</td>
     * <td>Any flags set for this column.</td>
     * </tr>
     * <tr valign="top">
     * <td>name</td>
     * <td>The name of this column as returned by the database.</td>
     * </tr>
     * <tr valign="top">
     * <td>table</td>
     * <td>The name of this column's table as returned by the database.</td>
     * </tr>
     * <tr valign="top">
     * <td>len</td>
     * <td>The length of this column. Normally -1 for
     * types other than floating point decimals.</td>
     * </tr>
     * <tr valign="top">
     * <td>precision</td>
     * <td>The numeric precision of this column. Normally
     * 0 for types other than floating point
     * decimals.</td>
     * </tr>
     * <tr valign="top">
     * <td>pdo_type</td>
     * <td>The type of this column as represented by the
     * PDO::PARAM_* constants.</td>
     * </tr>
     * </table>
     * <p>
     * Returns <b>FALSE</b> if the requested column does not exist in the result set,
     * or if no result set exists.
     */
    public function getColumnMeta($column);

    /**
     * Set the default fetch mode for this statement
     * @param int $mode <p>
     * The fetch mode must be one of the PDO::FETCH_* constants.
     * </p>
     * @param string|object $classNameObject [optional] <p>
     * Class name or object
     * </p>
     * @param array $ctorarfg [optional] <p> Constructor arguments. </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function setFetchMode($mode, $classNameObject = null, array $ctorarfg = array());

    /**
     * Advances to the next rowset in a multi-rowset statement handle
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function nextRowset();

    /**
     * Closes the cursor, enabling the statement to be executed again.
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function closeCursor();

    /**
     * Dump an SQL prepared command
     * @return bool No value is returned.
     */
    public function debugDumpParams();
}
