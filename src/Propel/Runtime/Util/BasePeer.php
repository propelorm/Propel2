<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Util;

use Propel\Runtime\Propel;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Validator\ValidationFailed;

use \Exception;

/**
 * This is a utility class for all generated Peer classes in the system.
 *
 * Peer classes are responsible for isolating all of the database access
 * for a specific business object.  They execute all of the SQL
 * against the database.  Over time this class has grown to include
 * utility methods which ease execution of cross-database queries and
 * the implementation of concrete Peers.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Kaspars Jaudzems <kaspars.jaudzems@inbox.lv> (Propel)
 * @author     Heltem <heltem@o2php.com> (Propel)
 * @author     Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author     Stephen Haberman <stephenh@chase3000.com> (Torque)
 */
class BasePeer
{
	/**
	 * Array (hash) that contains the cached mapBuilders.
	 */
    private static $mapBuilders = array();

	/**
	 * Array (hash) that contains cached validators
	 */
	private static $validatorMap = array();

    /**
     * phpname type
     * e.g. 'AuthorId'
     */
    const TYPE_PHPNAME = 'phpName';

    /**
     * studlyphpname type
     * e.g. 'authorId'
     */
    const TYPE_STUDLYPHPNAME = 'studlyPhpName';

    /**
     * column (peer) name type
     * e.g. 'book.AUTHOR_ID'
     */
    const TYPE_COLNAME = 'colName';

    /**
     * column part of the column peer name
     * e.g. 'AUTHOR_ID'
     */
    const TYPE_RAW_COLNAME = 'rawColName';

    /**
     * column fieldname type
     * e.g. 'author_id'
     */
    const TYPE_FIELDNAME = 'fieldName';

    /**
     * num type
     * simply the numerical array index, e.g. 4
     */
    const TYPE_NUM = 'num';

    static public function getFieldnames($classname, $type = self::TYPE_PHPNAME)
    {
        $peerclass  = $classname . 'Peer';
        $callable   = array($peerclass, 'getFieldnames');

        return call_user_func($callable, $type);
    }

    static public function translateFieldname($classname, $fieldname, $fromType, $toType)
    {
        $peerclass  = $classname . 'Peer';
        $callable   = array($peerclass, 'translateFieldname');
        $args       = array($fieldname, $fromType, $toType);

        return call_user_func_array($callable, $args);
    }

    /**
     * Method to perform deletes based on values and keys in a
     * Criteria.
     *
     * @param      Criteria $criteria The criteria to use.
     * @param      ConnectionInterface $con A ConnectionInterface connection object.
     * @return     int    The number of rows affected by last statement execution.  For most
     *                 uses there is only one delete statement executed, so this number
     *                 will correspond to the number of rows affected by the call to this
     *                 method.  Note that the return value does require that this information
     *                 is returned (supported) by the PDO driver.
     * @throws     PropelException
     */
    static public function doDelete(Criteria $criteria, ConnectionInterface $con)
    {
        $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());

        //join are not supported with DELETE statement
        if (count($criteria->getJoins())) {
            throw new PropelException('Delete does not support join');
        }

        // Set up a list of required tables (one DELETE statement will
        // be executed per table)
        $tables = $criteria->getTablesColumns();
        if (empty($tables)) {
            throw new PropelException("Cannot delete from an empty Criteria");
        }

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        foreach ($tables as $tableName => $columns) {

            $whereClause = array();
            $params = array();
            $stmt = null;
            try {
                $sql = $db->getDeleteFromClause($criteria, $tableName);

                foreach ($columns as $colName) {
                    $sb = "";
                    $criteria->getCriterion($colName)->appendPsTo($sb, $params);
                    $whereClause[] = $sb;
                }
                $sql .= " WHERE " .  implode(" AND ", $whereClause);

                $stmt = $con->prepare($sql);

                $db->bindValues($stmt, $params, $dbMap);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
            } catch (Exception $e) {
                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException(sprintf('Unable to execute DELETE statement [%s]', $sql), $e);
            }

        } // for each table

        return $affectedRows;
    }

    /**
     * Method to deletes all contents of specified table.
     *
     * This method is invoked from generated Peer classes like this:
     * <code>
     * static public function doDeleteAll($con = null)
     * {
     *   if ($con === null) $con = Propel::getServiceContainer()->getWriteConnection(self::DATABASE_NAME);
     *   BasePeer::doDeleteAll(self::TABLE_NAME, $con, self::DATABASE_NAME);
     * }
     * </code>
     *
     * @param      string $tableName The name of the table to empty.
     * @param      ConnectionInterface $con A ConnectionInterface connection object.
     * @param      string $databaseName the name of the database.
     * @return     int      The number of rows affected by the statement. Note
     *                      that the return value does require that this information
     *                      is returned (supported) by the Propel db driver.
     * @throws     PropelException - wrapping SQLException caught from statement execution.
     */
    static public function doDeleteAll($tableName, ConnectionInterface $con, $databaseName = null)
    {

        try {
            $db = Propel::getServiceContainer()->getAdapter($databaseName);
            if ($db->useQuoteIdentifier()) {
                $tableName = $db->quoteIdentifierTable($tableName);
            }
            $sql = "DELETE FROM " . $tableName;
            $stmt = $con->prepare($sql);

            $stmt->execute();

            return $stmt->rowCount();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute DELETE ALL statement [%s]', $sql), $e);
        }
    }

    /**
     * Method to perform inserts based on values and keys in a
     * Criteria.
     * <p>
     * If the primary key is auto incremented the data in Criteria
     * will be inserted and the auto increment value will be returned.
     * <p>
     * If the primary key is included in Criteria then that value will
     * be used to insert the row.
     * <p>
     * If no primary key is included in Criteria then we will try to
     * figure out the primary key from the database map and insert the
     * row with the next available id using util.db.IDBroker.
     * <p>
     * If no primary key is defined for the table the values will be
     * inserted as specified in Criteria and null will be returned.
     *
     * @param      Criteria $criteria Object containing values to insert.
     * @param      ConnectionInterface $con A ConnectionInterface connection.
     * @return     mixed The primary key for the new row if (and only if!) the primary key
     *                is auto-generated.  Otherwise will return <code>null</code>.
     * @throws     PropelException
     */
    static public function doInsert(Criteria $criteria, ConnectionInterface $con) {

        // the primary key
        $id = null;

        $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());

        // Get the table name and method for determining the primary
        // key value.
        $keys = $criteria->keys();
        if (!empty($keys)) {
            $tableName = $criteria->getTableName( $keys[0] );
        } else {
            throw new PropelException("Database insert attempted without anything specified to insert");
        }

        $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());
        $tableMap = $dbMap->getTable($tableName);
        $keyInfo = $tableMap->getPrimaryKeyMethodInfo();
        $useIdGen = $tableMap->isUseIdGenerator();
        //$keyGen = $con->getIdGenerator();

        $pk = self::getPrimaryKey($criteria);

        // only get a new key value if you need to
        // the reason is that a primary key might be defined
        // but you are still going to set its value. for example:
        // a join table where both keys are primary and you are
        // setting both columns with your own values

        // pk will be null if there is no primary key defined for the table
        // we're inserting into.
        if ($pk !== null && $useIdGen && !$criteria->keyContainsValue($pk->getFullyQualifiedName()) && $db->isGetIdBeforeInsert()) {
            try {
                $id = $db->getId($con, $keyInfo);
            } catch (Exception $e) {
                throw new PropelException("Unable to get sequence id.", $e);
            }
            $criteria->add($pk->getFullyQualifiedName(), $id);
        }

        try {
            $adapter = Propel::getServiceContainer()->getAdapter($criteria->getDBName());

            $qualifiedCols = $criteria->keys(); // we need table.column cols when populating values
            $columns = array(); // but just 'column' cols for the SQL
            foreach ($qualifiedCols as $qualifiedCol) {
                $columns[] = substr($qualifiedCol, strrpos($qualifiedCol, '.') + 1);
            }

            // add identifiers
            if ($adapter->useQuoteIdentifier()) {
                $columns = array_map(array($adapter, 'quoteIdentifier'), $columns);
                $tableName = $adapter->quoteIdentifierTable($tableName);
            }

            $sql = 'INSERT INTO ' . $tableName
                . ' (' . implode(',', $columns) . ')'
                . ' VALUES (';
            // . substr(str_repeat("?,", count($columns)), 0, -1) .
            for($p=1, $cnt=count($columns); $p <= $cnt; $p++) {
                $sql .= ':p'.$p;
                if ($p !== $cnt) $sql .= ',';
            }
            $sql .= ')';

            $params = self::buildParams($qualifiedCols, $criteria);

            $db->cleanupSQL($sql, $params, $criteria, $dbMap);

            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap, $db);
            $stmt->execute();

        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), $e);
        }

        // If the primary key column is auto-incremented, get the id now.
        if ($pk !== null && $useIdGen && $db->isGetIdAfterInsert()) {
            try {
                $id = $db->getId($con, $keyInfo);
            } catch (Exception $e) {
                throw new PropelException("Unable to get autoincrement id.", $e);
            }
        }

        return $id;
    }

    /**
     * Method used to update rows in the DB.  Rows are selected based
     * on selectCriteria and updated using values in updateValues.
     * <p>
     * Use this method for performing an update of the kind:
     * <p>
     * WHERE some_column = some value AND could_have_another_column =
     * another value AND so on.
     *
     * @param      $selectCriteria A Criteria object containing values used in where
     *        clause.
     * @param      $updateValues A Criteria object containing values used in set
     *        clause.
     * @param      ConnectionInterface $con The ConnectionInterface connection object to use.
     * @return     int    The number of rows affected by last update statement.  For most
     *                 uses there is only one update statement executed, so this number
     *                 will correspond to the number of rows affected by the call to this
     *                 method.  Note that the return value does require that this information
     *                 is returned (supported) by the Propel db driver.
     * @throws     PropelException
     */
    static public function doUpdate(Criteria $selectCriteria, Criteria $updateValues, ConnectionInterface $con) {

        $db = Propel::getServiceContainer()->getAdapter($selectCriteria->getDbName());
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($selectCriteria->getDbName());

        // Get list of required tables, containing all columns
        $tablesColumns = $selectCriteria->getTablesColumns();
        if (empty($tablesColumns)) {
            $tablesColumns = array($selectCriteria->getPrimaryTableName() => array());
        }

        // we also need the columns for the update SQL
        $updateTablesColumns = $updateValues->getTablesColumns();

        // If no columns are changing values, we may get here with
        // an empty array in $updateTablesColumns.  In that case,
        // there is nothing to do, so we return the rows affected,
        // which is 0.  Fixes a bug in which an UPDATE statement
        // would fail in this instance.

        if (empty($updateTablesColumns)) {
            return 0;
        }

        $affectedRows = 0; // initialize this in case the next loop has no iterations.

        foreach ($tablesColumns as $tableName => $columns) {

            $whereClause = array();
            $params = array();
            $stmt = null;
            try {
                $sql = 'UPDATE ';
                if ($queryComment = $selectCriteria->getComment()) {
                    $sql .= '/* ' . $queryComment . ' */ ';
                }
                // is it a table alias?
                if ($tableName2 = $selectCriteria->getTableForAlias($tableName)) {
                    $updateTable = $tableName2 . ' ' . $tableName;
                    $tableName = $tableName2;
                } else {
                    $updateTable = $tableName;
                }
                if ($db->useQuoteIdentifier()) {
                    $sql .= $db->quoteIdentifierTable($updateTable);
                } else {
                    $sql .= $updateTable;
                }
                $sql .= " SET ";
                $p = 1;
                foreach ($updateTablesColumns[$tableName] as $col) {
                    $updateColumnName = substr($col, strrpos($col, '.') + 1);
                    // add identifiers for the actual database?
                    if ($db->useQuoteIdentifier()) {
                        $updateColumnName = $db->quoteIdentifier($updateColumnName);
                    }
                    if ($updateValues->getComparison($col) != Criteria::CUSTOM_EQUAL) {
                        $sql .= $updateColumnName . '=:p'.$p++.', ';
                    } else {
                        $param = $updateValues->get($col);
                        $sql .= $updateColumnName . ' = ';
                        if (is_array($param)) {
                            if (isset($param['raw'])) {
                                $raw = $param['raw'];
                                $rawcvt = '';
                                // parse the $params['raw'] for ? chars
                                for($r=0,$len=strlen($raw); $r < $len; $r++) {
                                    if ($raw{$r} == '?') {
                                        $rawcvt .= ':p'.$p++;
                                    } else {
                                        $rawcvt .= $raw{$r};
                                    }
                                }
                                $sql .= $rawcvt . ', ';
                            } else {
                                $sql .= ':p'.$p++.', ';
                            }
                            if (isset($param['value'])) {
                                $updateValues->put($col, $param['value']);
                            }
                        } else {
                            $updateValues->remove($col);
                            $sql .= $param . ', ';
                        }
                    }
                }

                $params = self::buildParams($updateTablesColumns[$tableName], $updateValues);

                $sql = substr($sql, 0, -2);
                if (!empty($columns)) {
                    foreach ($columns as $colName) {
                        $sb = "";
                        $selectCriteria->getCriterion($colName)->appendPsTo($sb, $params);
                        $whereClause[] = $sb;
                    }
                    $sql .= " WHERE " .  implode(" AND ", $whereClause);
                }

                $db->cleanupSQL($sql, $params, $updateValues, $dbMap);

                $stmt = $con->prepare($sql);

                // Replace ':p?' with the actual values
                $db->bindValues($stmt, $params, $dbMap, $db);

                $stmt->execute();

                $affectedRows = $stmt->rowCount();

                $stmt = null; // close

            } catch (Exception $e) {
                if ($stmt) $stmt = null; // close
                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException(sprintf('Unable to execute UPDATE statement [%s]', $sql), $e);
            }

        } // foreach table in the criteria

        return $affectedRows;
    }

    /**
     * Executes query build by createSelectSql() and returns the resultset statement.
     *
     * @param      Criteria $criteria A Criteria.
     * @param      ConnectionInterface $con A ConnectionInterface connection to use.
     * @return     Propel\Runtime\Connection\StatementInterface The resultset.
     * @throws     PropelException
     * @see        createSelectSql()
     */
    static public function doSelect(Criteria $criteria, ConnectionInterface $con = null)
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());
        $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
        $stmt = null;

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($criteria->getDbName());
        }

        try {

            $params = array();
            $sql = self::createSelectSql($criteria, $params);

            $stmt = $con->prepare($sql);

            $db->bindValues($stmt, $params, $dbMap);

            $stmt->execute();

        } catch (Exception $e) {
            if ($stmt) {
                $stmt = null; // close
            }
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), $e);
        }

        return $stmt;
    }

    /**
     * Executes a COUNT query using either a simple SQL rewrite or, for more complex queries, a
     * sub-select of the SQL created by createSelectSql() and returns the statement.
     *
     * @param      Criteria $criteria A Criteria.
     * @param      ConnectionInterface $con A ConnectionInterface connection to use.
     * @return     Propel\Runtime\Connection\StatementInterface The resultset statement.
     * @throws     PropelException
     * @see        createSelectSql()
     */
    static public function doCount(Criteria $criteria, ConnectionInterface $con = null)
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());
        $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection($criteria->getDbName());
        }

        $stmt = null;

        $needsComplexCount = $criteria->getGroupByColumns()
            || $criteria->getOffset()
            || $criteria->getLimit()
            || $criteria->getHaving()
            || in_array(Criteria::DISTINCT, $criteria->getSelectModifiers());

        try {

            $params = array();

            if ($needsComplexCount) {
                if (self::needsSelectAliases($criteria)) {
                    if ($criteria->getHaving()) {
                        throw new PropelException('Propel cannot create a COUNT query when using HAVING and  duplicate column names in the SELECT part');
                    }
                    $db->turnSelectColumnsToAliases($criteria);
                }
                $selectSql = self::createSelectSql($criteria, $params);
                $sql = 'SELECT COUNT(*) FROM (' . $selectSql . ') propelmatch4cnt';
            } else {
                // Replace SELECT columns with COUNT(*)
                $criteria->clearSelectColumns()->addSelectColumn('COUNT(*)');
                $sql = self::createSelectSql($criteria, $params);
            }

            $stmt = $con->prepare($sql);
            $db->bindValues($stmt, $params, $dbMap);
            $stmt->execute();

        } catch (Exception $e) {
            if ($stmt !== null) {
                $stmt = null;
            }
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute COUNT statement [%s]', $sql), $e);
        }

        return $stmt;
    }

    /**
     * Applies any validators that were defined in the schema to the specified columns.
     *
     * @param      string $dbName The name of the database
     * @param      string $tableName The name of the table
     * @param      array $columns Array of column names as key and column values as value.
     */
    static public function doValidate($dbName, $tableName, $columns)
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($dbName);
        $tableMap = $dbMap->getTable($tableName);
        $failureMap = array(); // map of ValidationFailed objects
        foreach ($columns as $colName => $colValue) {
            if ($tableMap->hasColumn($colName)) {
                $col = $tableMap->getColumn($colName);
                foreach ($col->getValidators() as $validatorMap) {
                    $validator = BasePeer::getValidator($validatorMap->getClass());
                    if ($validator && ($col->isNotNull() || $colValue !== null) && $validator->isValid($validatorMap, $colValue) === false) {
                        if (!isset($failureMap[$colName])) { // for now we do one ValidationFailed per column, not per rule
                            $failureMap[$colName] = new ValidationFailed($colName, $validatorMap->getMessage(), $validator);
                        }
                    }
                }
            }
        }

        return (!empty($failureMap) ? $failureMap : true);
    }

    /**
     * Helper method which returns the primary key contained
     * in the given Criteria object.
     *
     * @param      Criteria $criteria A Criteria.
     * @return     ColumnMap If the Criteria object contains a primary
     *          key, or null if it doesn't.
     * @throws     PropelException
     */
	static private function getPrimaryKey(Criteria $criteria)
    {
        // Assume all the keys are for the same table.
        $keys = $criteria->keys();
        $key = $keys[0];
        $table = $criteria->getTableName($key);

        $pk = null;

        if (!empty($table)) {
            $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());

            $pks = $dbMap->getTable($table)->getPrimaryKeys();
            if (!empty($pks)) {
                $pk = array_shift($pks);
            }
        }

        return $pk;
    }

    /**
     * Checks whether the Criteria needs to use column aliasing
     * This is implemented in a service class rather than in Criteria itself
     * in order to avoid doing the tests when it's not necessary (e.g. for SELECTs)
     */
    static public function needsSelectAliases(Criteria $criteria)
    {
        $columnNames = array();
        foreach ($criteria->getSelectColumns() as $fullyQualifiedColumnName) {
            if ($pos = strrpos($fullyQualifiedColumnName, '.')) {
                $columnName = substr($fullyQualifiedColumnName, $pos);
                if (isset($columnNames[$columnName])) {
                    // more than one column with the same name, so aliasing is required
                    return true;
                }
                $columnNames[$columnName] = true;
            }
        }

        return false;
    }

    /**
     * Method to create an SQL query based on values in a Criteria.
     *
     * This method creates only prepared statement SQL (using ? where values
     * will go).  The second parameter ($params) stores the values that need
     * to be set before the statement is executed.  The reason we do it this way
     * is to let the PDO layer handle all escaping & value formatting.
     *
     * @param      Criteria $criteria Criteria for the SELECT query.
     * @param      array &$params Parameters that are to be replaced in prepared statement.
     * @return     string
     * @throws     PropelException Trouble creating the query string.
     */
    static public function createSelectSql(Criteria $criteria, &$params)
    {
        $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
        $dbMap = Propel::getServiceContainer()->getDatabaseMap($criteria->getDbName());

        $fromClause = array();
        $joinClause = array();
        $joinTables = array();
        $whereClause = array();
        $orderByClause = array();

        $orderBy = $criteria->getOrderByColumns();
        $groupBy = $criteria->getGroupByColumns();
        $ignoreCase = $criteria->isIgnoreCase();

        // get the first part of the SQL statement, the SELECT part
        $selectSql = $db->createSelectSqlPart($criteria, $fromClause);

        // Handle joins
        // joins with a null join type will be added to the FROM clause and the condition added to the WHERE clause.
        // joins of a specified type: the LEFT side will be added to the fromClause and the RIGHT to the joinClause
        foreach ($criteria->getJoins() as $join) {

            $join->setAdapter($db);

            // add 'em to the queues..
            if (!$fromClause) {
                $fromClause[] = $join->getLeftTableWithAlias();
            }
            $joinTables[] = $join->getRightTableWithAlias();
            $joinClause[] = $join->getClause($params);
        }

        // add the criteria to WHERE clause
        // this will also add the table names to the FROM clause if they are not already
        // included via a LEFT JOIN
        foreach ($criteria->keys() as $key) {

            $criterion = $criteria->getCriterion($key);
            $table = null;
            foreach ($criterion->getAttachedCriterion() as $attachedCriterion) {
                $tableName = $attachedCriterion->getTable();

                $table = $criteria->getTableForAlias($tableName);
                if ($table !== null) {
                    $fromClause[] = $table . ' ' . $tableName;
                } else {
                    $fromClause[] = $tableName;
                    $table = $tableName;
                }

                if (($criteria->isIgnoreCase() || $attachedCriterion->isIgnoreCase())
                    && $dbMap->getTable($table)->getColumn($attachedCriterion->getColumn())->isText()) {
                        $attachedCriterion->setIgnoreCase(true);
                    }
            }

            $criterion->setAdapter($db);

            $sb = '';
            $criterion->appendPsTo($sb, $params);
            $whereClause[] = $sb;
        }

        // Unique from clause elements
        $fromClause = array_unique($fromClause);
        $fromClause = array_diff($fromClause, array(''));

        // tables should not exist in both the from and join clauses
        if ($joinTables && $fromClause) {
            foreach ($fromClause as $fi => $ftable) {
                if (in_array($ftable, $joinTables)) {
                    unset($fromClause[$fi]);
                }
            }
        }

        // Add the GROUP BY columns
        $groupByClause = $groupBy;

        $having = $criteria->getHaving();
        $havingString = null;
        if ($having !== null) {
            $sb = '';
            $having->appendPsTo($sb, $params);
            $havingString = $sb;
        }

        if (!empty($orderBy)) {

            foreach ($orderBy as $orderByColumn) {

                // Add function expression as-is.

                if (strpos($orderByColumn, '(') !== false) {
                    $orderByClause[] = $orderByColumn;
                    continue;
                }

                // Split orderByColumn (i.e. "table.column DESC")

                $dotPos = strrpos($orderByColumn, '.');

                if ($dotPos !== false) {
                    $tableName = substr($orderByColumn, 0, $dotPos);
                    $columnName = substr($orderByColumn, $dotPos + 1);
                } else {
                    $tableName = '';
                    $columnName = $orderByColumn;
                }

                $spacePos = strpos($columnName, ' ');

                if ($spacePos !== false) {
                    $direction = substr($columnName, $spacePos);
                    $columnName = substr($columnName, 0, $spacePos);
                }    else {
                    $direction = '';
                }

                $tableAlias = $tableName;
                if ($aliasTableName = $criteria->getTableForAlias($tableName)) {
                    $tableName = $aliasTableName;
                }

                $columnAlias = $columnName;
                if ($asColumnName = $criteria->getColumnForAs($columnName)) {
                    $columnName = $asColumnName;
                }

                $column = $tableName ? $dbMap->getTable($tableName)->getColumn($columnName) : null;

                if ($criteria->isIgnoreCase() && $column && $column->isText()) {
                    $ignoreCaseColumn = $db->ignoreCaseInOrderBy("$tableAlias.$columnAlias");
                    $orderByClause[] =  $ignoreCaseColumn . $direction;
                    $selectSql .= ', ' . $ignoreCaseColumn;
                } else {
                    $orderByClause[] = $orderByColumn;
                }
            }
        }

        if (empty($fromClause) && $criteria->getPrimaryTableName()) {
            $fromClause[] = $criteria->getPrimaryTableName();
        }

        // tables should not exist as alias of subQuery
        if ($criteria->hasSelectQueries()) {
            foreach ($fromClause as $key => $ftable) {
                if (strpos($ftable, ' ') !== false) {
                    list($realtable, $tableName) = explode(' ', $ftable);
                } else {
                    $tableName = $ftable;
                }
                if ($criteria->hasSelectQuery($tableName)) {
                    unset($fromClause[$key]);
                }
            }
        }

        // from / join tables quoted if it is necessary
        if ($db->useQuoteIdentifier()) {
            $fromClause = array_map(array($db, 'quoteIdentifierTable'), $fromClause);
            $joinClause = $joinClause ? $joinClause : array_map(array($db, 'quoteIdentifierTable'), $joinClause);
        }

        // add subQuery to From after adding quotes
        foreach ($criteria->getSelectQueries() as $subQueryAlias => $subQueryCriteria) {
            $fromClause[] = '(' . BasePeer::createSelectSql($subQueryCriteria, $params) . ') AS ' . $subQueryAlias;
        }

        // build from-clause
        $from = '';
        if (!empty($joinClause) && count($fromClause) > 1) {
            $from .= implode(" CROSS JOIN ", $fromClause);
        } else {
            $from .= implode(", ", $fromClause);
        }

        $from .= $joinClause ? ' ' . implode(' ', $joinClause) : '';

        // Build the SQL from the arrays we compiled
        $sql =  $selectSql
            ." FROM "  . $from
            .($whereClause ? " WHERE ".implode(" AND ", $whereClause) : "")
            .($groupByClause ? " GROUP BY ".implode(",", $groupByClause) : "")
            .($havingString ? " HAVING ".$havingString : "")
            .($orderByClause ? " ORDER BY ".implode(",", $orderByClause) : "");

        // APPLY OFFSET & LIMIT to the query.
        if ($criteria->getLimit() || $criteria->getOffset()) {
            $db->applyLimit($sql, $criteria->getOffset(), $criteria->getLimit(), $criteria);
        }

        return $sql;
    }

    /**
     * Builds a params array, like the kind populated by Criterion::appendPsTo().
     * This is useful for building an array even when it is not using the appendPsTo() method.
     * @param      array $columns
     * @param      Criteria $values
     * @return     array params array('column' => ..., 'table' => ..., 'value' => ...)
     */
    static private function buildParams($columns, Criteria $values)
    {
		$params = array();
		foreach ($columns as $key) {
			if ($values->containsKey($key)) {
				$crit = $values->getCriterion($key);
				$params[] = array('column' => $crit->getColumn(), 'table' => $crit->getTable(), 'value' => $crit->getValue());
			}
		}

		return $params;
	}

    /**
     * This function searches for the given validator $name under propel/validator/$name.php,
     * imports and caches it.
     *
     * @param	string $classname	The name of class
     * @return	Validator object or null if not able to instantiate validator class (and error will be logged in this case)
     */
    static public function getValidator($classname)
    {
        try {
			$v = isset(self::$validatorMap[$classname]) ? self::$validatorMap[$classname] : null;
            if ($v === null) {
				$v = new $classname();
				self::$validatorMap[$classname] = $v;
			}

            return $v;
        } catch (Exception $e) {
            Propel::log("BasePeer::getValidator(): failed trying to instantiate " . $classname . ": ".$e->getMessage(), Propel::LOG_ERR);
        }
    }
}
