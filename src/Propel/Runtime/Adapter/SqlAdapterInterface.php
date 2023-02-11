<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Lock;
use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;

/**
 * Interface for adapters.
 */
interface SqlAdapterInterface extends AdapterInterface
{
    /**
     * This method is used to ignore case.
     *
     * @param string $in The string to transform to upper case.
     *
     * @return string The upper case string.
     */
    public function toUpperCase(string $in): string;

    /**
     * This method is used to ignore case.
     *
     * @param string $in The string whose case to ignore.
     *
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCase(string $in): string;

    /**
     * Allows manipulation of the query string before StatementPdo is instantiated.
     *
     * @param string $sql The sql statement
     * @param array $params array('column' => ..., 'table' => ..., 'value' => ...)
     * @param \Propel\Runtime\ActiveQuery\Criteria $values
     * @param \Propel\Runtime\Map\DatabaseMap $dbMap
     *
     * @return void
     */
    public function cleanupSQL(string &$sql, array &$params, Criteria $values, DatabaseMap $dbMap): void;

    /**
     * Modifies the passed-in SQL to add LIMIT and/or OFFSET.
     *
     * @param string $sql
     * @param int $offset
     * @param int $limit
     * @param \Propel\Runtime\ActiveQuery\Criteria|null $criteria
     *
     * @return void
     */
    public function applyLimit(string &$sql, int $offset, int $limit, ?Criteria $criteria = null): void;

    /**
     * Modifies the passed-in SQL to add locking capabilities
     *
     * @param string $sql
     * @param \Propel\Runtime\ActiveQuery\Lock $lock
     *
     * @return void
     */
    public function applyLock(string &$sql, Lock $lock): void;

    /**
     * Gets the SQL string that this adapter uses for getting a random number.
     *
     * @param string|null $seed (optional) seed value for databases that support this
     *
     * @return string
     */
    public function random(?string $seed = null): string;

    /**
     * Builds the SELECT part of a SQL statement based on a Criteria
     * taking into account select columns and 'as' columns (i.e. columns aliases)
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param array $fromClause
     * @param bool $aliasAll
     *
     * @return string
     */
    public function createSelectSqlPart(Criteria $criteria, array &$fromClause, bool $aliasAll = false): string;

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return \Propel\Runtime\ActiveQuery\Criteria The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria): Criteria;

    /**
     * Binds values in a prepared statement.
     *
     * This method is designed to work with the Criteria::createSelectSql() method, which creates
     * both the SELECT SQL statement and populates a passed-in array of parameter
     * values that should be substituted.
     *
     * <code>
     * $adapter = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
     * $sql = $criteria->createSelectSql($params);
     * $stmt = $con->prepare($sql);
     * $params = [];
     * $adapter->populateStmtValues($stmt, $params, Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName()));
     * $stmt->execute();
     * </code>
     *
     * @param \Propel\Runtime\Connection\StatementInterface $stmt
     * @param array $params array('column' => ..., 'table' => ..., 'value' => ...)
     * @param \Propel\Runtime\Map\DatabaseMap $dbMap
     *
     * @return void
     */
    public function bindValues(StatementInterface $stmt, array $params, DatabaseMap $dbMap): void;

    /**
     * Binds a value to a positioned parameter in a statement,
     * given a ColumnMap object to infer the binding type.
     *
     * @param \Propel\Runtime\Connection\StatementInterface $stmt The statement to bind
     * @param string $parameter Parameter identifier
     * @param mixed $value The value to bind
     * @param \Propel\Runtime\Map\ColumnMap $cMap The ColumnMap of the column to bind
     * @param int|null $position The position of the parameter to bind
     *
     * @return bool
     */
    public function bindValue(StatementInterface $stmt, string $parameter, $value, ColumnMap $cMap, ?int $position = null): bool;

    /**
     * Indicates if the database system can process DELETE statements with
     * aliases like 'DELETE t FROM my_table t JOIN my_other_table o ON ...'
     *
     * @return bool
     */
    public function supportsAliasesInDelete(): bool;
}
