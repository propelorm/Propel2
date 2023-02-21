<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use PDO;

/**
 * Interface for Propel Statement object.
 * Based on the PDOStatement class.
 *
 * @see http://php.net/manual/en/book.pdo.php
 *
 * @author Aleksandr Bezpiatov
 */
interface StatementInterface
{
    /**
     * Executes a prepared statement.
     *
     * @param array|null $inputParameters
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function execute(?array $inputParameters = null): bool;

    /**
     * Fetches the next row from a result set.
     *
     * @param int $fetchStyle Controls how the next row will be returned to the caller.
     * @param int $cursorOrientation This value determines which row will be returned to the caller.
     * @param int $cursorOffset
     *
     * @return mixed
     */
    public function fetch(int $fetchStyle = PDO::FETCH_BOTH, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0);

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $parameter Parameter identifier.
     * @param mixed $variable Name of the PHP variable to bind to the SQL statement parameter.
     * @param int $dataType Explicit data type for the parameter using the PDO::PARAM_* constants.
     * @param int|null $length Length of the data type.
     * @param mixed $driverOptions
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function bindParam($parameter, &$variable, int $dataType = PDO::PARAM_STR, ?int $length = null, $driverOptions = null): bool;

    /**
     * Bind a column to a PHP variable.
     *
     * @param mixed $column Number of the column (1-indexed) or name of the column in the result set.
     * @param mixed $param Name of the PHP variable to which the column will be bound.
     * @param int|null $type Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @param int|null $maxlen A hint for pre-allocation.
     * @param mixed|null $driverdata Optional parameter(s) for the driver.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function bindColumn($column, &$param, ?int $type = null, ?int $maxlen = null, $driverdata = null): bool;

    /**
     * Binds a value to a parameter
     *
     * @param mixed $parameter Parameter identifier.
     * @param mixed $value The value to bind to the parameter.
     * @param int $dataType Explicit data type for the parameter using the PDO::PARAM_* constants.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function bindValue($parameter, $value, int $dataType = PDO::PARAM_STR): bool;

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int The number of rows.
     */
    public function rowCount(): int;

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param int $columnIndex 0-indexed number of the column you wish to retrieve from the row.
     *
     * @return string|null Returns a single column from the next row of a result set or FALSE if there are no more rows.
     */
    public function fetchColumn(int $columnIndex = 0): ?string;

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int|null $fetchStyle Controls the contents of the returned array as documented in PDOStatement::fetch.
     * @param mixed $fetchArgument This argument have a different meaning depending on the value of the fetch_style
     * @param array $ctorArgs Arguments of custom class constructor when the fetch_style parameter is PDO::FETCH_CLASS.
     *
     * @return array returns an array containing all of the remaining rows in the result set.
     */
    public function fetchAll(?int $fetchStyle = PDO::FETCH_BOTH, $fetchArgument = null, array $ctorArgs = []): array;

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param class-string $className Name of the created class.
     * @param array $ctorArgs Elements of this array are passed to the constructor.
     *
     * @return mixed
     */
    public function fetchObject(string $className, array $ctorArgs = []);

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle.
     *
     * @return string
     */
    public function errorCode(): string;

    /**
     * Fetch extended error information associated with the last operation on the statement handle.
     *
     * @return array returns an array of error information about the last operation performed by this statement handle.
     */
    public function errorInfo(): array;

    /**
     * Set a statement attribute.
     *
     * @param int $attribute
     * @param mixed $value
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setAttribute(int $attribute, $value): bool;

    /**
     * Retrieve a statement attribute.
     *
     * @param int $attribute
     *
     * @return mixed the attribute value.
     */
    public function getAttribute(int $attribute);

    /**
     * Returns the number of columns in the result set.
     *
     * @return int The number of columns in the result set represented by the StatementInterface object.
     */
    public function columnCount(): int;

    /**
     * Returns metadata for a column in a result set.
     *
     * @param int $column The 0-indexed column in the result set.
     *
     * @return array|false
     */
    public function getColumnMeta(int $column);

    /**
     * Set the default fetch mode for this statement.
     *
     * @param int $mode The fetch mode must be one of the PDO::FETCH_* constants.
     * @param object|string|null $classNameObject Class name or object.
     * @param array $ctorarfg Constructor arguments.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function setFetchMode(int $mode, $classNameObject = null, array $ctorarfg = []): bool;

    /**
     * Advances to the next rowset in a multi-rowset statement handle.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function nextRowset(): bool;

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function closeCursor(): bool;

    /**
     * Dump an SQL prepared command.
     *
     * @return void No value is returned.
     */
    public function debugDumpParams(): void;
}
