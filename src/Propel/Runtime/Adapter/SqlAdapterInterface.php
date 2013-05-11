<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Adapter;

use Propel\Runtime\Connection\StatementInterface;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Interface for adapters.
 *
 */
interface SqlAdapterInterface extends AdapterInterface
{

    /**
     * This method is used to ignore case.
     *
     * @param  string $in The string to transform to upper case.
     * @return string The upper case string.
     */
    public function toUpperCase($in);

    /**
     * This method is used to ignore case.
     *
     * @param  string $in The string whose case to ignore.
     * @return string The string in a case that can be ignored.
     */
    public function ignoreCase($in);

    /**
     * Allows manipulation of the query string before StatementPdo is instantiated.
     *
     * @param string      $sql    The sql statement
     * @param array       $params array('column' => ..., 'table' => ..., 'value' => ...)
     * @param Criteria    $values
     * @param DatabaseMap $dbMap
     */
    public function cleanupSQL(&$sql, array &$params, Criteria $values, DatabaseMap $dbMap);

    /**
     * Modifies the passed-in SQL to add LIMIT and/or OFFSET.
     *
     * @param string  $sql
     * @param integer $offset
     * @param integer $limit
     */
    public function applyLimit(&$sql, $offset, $limit);

    /**
     * Gets the SQL string that this adapter uses for getting a random number.
     *
     * @param mixed $seed (optional) seed value for databases that support this
     */
    public function random($seed = null);

    /**
     * Returns the "DELETE FROM <table> [AS <alias>]" part of DELETE query.
     *
     * @param Criteria $criteria
     * @param string   $tableName
     *
     * @return string
     */
    public function getDeleteFromClause(Criteria $criteria, $tableName);

    /**
     * Builds the SELECT part of a SQL statement based on a Criteria
     * taking into account select columns and 'as' columns (i.e. columns aliases)
     *
     * @param Criteria $criteria
     * @param array    $fromClause
     * @param boolean  $aliasAll
     *
     * @return string
     */
    public function createSelectSqlPart(Criteria $criteria, &$fromClause, $aliasAll = false);

    /**
     * Ensures uniqueness of select column names by turning them all into aliases
     * This is necessary for queries on more than one table when the tables share a column name
     *
     * @see http://propel.phpdb.org/trac/ticket/795
     *
     * @param  Criteria $criteria
     * @return Criteria The input, with Select columns replaced by aliases
     */
    public function turnSelectColumnsToAliases(Criteria $criteria);

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
     * $params = array();
     * $adapter->populateStmtValues($stmt, $params, Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName()));
     * $stmt->execute();
     * </code>
     *
     * @param StatementInterface $stmt
     * @param array              $params array('column' => ..., 'table' => ..., 'value' => ...)
     * @param DatabaseMap        $dbMap
     */
    public function bindValues(StatementInterface $stmt, array $params, DatabaseMap $dbMap);

    /**
     * Binds a value to a positioned parameter in a statement,
     * given a ColumnMap object to infer the binding type.
     *
     * @param StatementInterface $stmt      The statement to bind
     * @param string             $parameter Parameter identifier
     * @param mixed              $value     The value to bind
     * @param ColumnMap          $cMap      The ColumnMap of the column to bind
     * @param null|integer       $position  The position of the parameter to bind
     *
     * @return boolean
     */
    public function bindValue(StatementInterface $stmt, $parameter, $value, ColumnMap $cMap, $position = null);
}
