<?php
/*
 *  $Id: BasePeer.php,v 1.25 2005/03/29 16:35:53 micha Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

include_once 'propel/adapter/DBAdapter.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/DatabaseMap.php';
include_once 'propel/map/MapBuilder.php';
include_once 'propel/map/TableMap.php';
include_once 'propel/map/ValidatorMap.php';
include_once 'propel/validator/ValidationFailed.php';
include_once 'propel/Propel.php';

/**
 * This is a utility class for all generated Peer classes in the system.  
 *
 * Peer
 * classes are responsible for isolating all of the database access
 * for a specific business object.  They execute all of the SQL
 * against the database.  Over time this class has grown to include
 * utility methods which ease execution of cross-database queries and
 * the implementation of concrete Peers.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Stephen Haberman <stephenh@chase3000.com> (Torque)
 * @version $Revision: 1.25 $
 * @package propel.util
 */
class BasePeer
{
  /** Array (hash) that contains the cached mapBuilders. */
  var $mapBuilders = array();
  var $validatorMap = array();

  /**
  * Method to perform deletes based on values and keys in a
  * Criteria.
  *
  * @param Criteria $criteria The criteria to use.
  * @param Connection $con A Connection.
  * @return mixed number of rows affected on success, PropelException on error
  * @access public
  * @static
  */
  function doDelete(/*Criteria*/ $criteria, /*Connection*/ &$con)
  {
    if (! is_a($criteria, 'Criteria'))
      return new PropelException (PROPEL_ERROR, "parameter 1 not of type 'Criteria' !");
    if (! is_a($con, 'Connection'))
      return new PropelException (PROPEL_ERROR, "parameter 2 not of type 'Connection' !");

    $db =& Propel::getDB($criteria->getDbName());
    $dbMap =& Propel::getDatabaseMap($criteria->getDbName());

    if (Propel::isError($dbMap)) {
      return $dbMap;
    }

    // Set up a list of required tables (one DELETE statement will
    // be executed per table)

    $tables_keys = array();

    for (($it =& $criteria->getIterator()); $it->valid(); $it->next())
    {
      $c =& $it->current();
      foreach($c->getAllTables() as $tableName)
      {
          $tableName2 = $criteria->getTableForAlias($tableName);
          if ($tableName2 !== null) {
              $tables_keys[$tableName2 . ' ' . $tableName] = true;
          } else {
              $tables_keys[$tableName] = true;
          }
      }
    } // foreach criteria->keys()

    $result = 0; // initialize this in case the next loop has no iterations.
    $tables = array_keys($tables_keys);

    foreach($tables as $tableName)
    {
      $whereClause = array();
      $selectParams = array();
      $t =& $dbMap->getTable($tableName);

      foreach($t->getColumns() as $colMap) {
          $key = $tableName . '.' . $colMap->getColumnName();
          if ($criteria->containsKey($key)) {
            $sb = "";
            $c =& $criteria->getCriterion($key);
            $e = $c->appendPsTo($sb, $selectParams);
            if (Propel::isError($e)) {
              return $e;
            }
            $whereClause[] = $sb;
          }
      }

      if (empty($whereClause)) {
        return new PropelException(PROPEL_ERROR, "Cowardly refusing to delete from table $tableName with empty WHERE clause !");
      }

      // Execute the statement.
      $sqlSnippet = implode(" AND ", $whereClause);

      if ($criteria->isSingleRecord())
      {
        $sql = "SELECT COUNT(*) FROM " . $tableName . " WHERE " . $sqlSnippet;
        $stmt = $con->prepareStatement($sql);

        if (Propel::isError($e = BasePeer::populateStmtValues($stmt, $selectParams, $dbMap)))
          return $e;

        $rs =& $stmt->executeQuery(ResultSet::FETCHMODE_NUM());
        if (Creole::isError($rs)) {
          Propel::log($rs->getMessage(), PROPEL_LOG_ERR);
          return new PropelException(PROPEL_ERROR_DB, "Unable to execute DELETE statement.", $rs);
        }
        $rs->next();
        if ($rs->getInt(1) > 1) {
          $rs->close();
          return new PropelException(PROPEL_ERROR, "Expecting to delete 1 record, but criteria match multiple.");
        }
        $rs->close();
      }

      $sql = "DELETE FROM " . $tableName . " WHERE " .  $sqlSnippet;
      Propel::log($sql, PROPEL_LOG_DEBUG);
      $stmt =& $con->prepareStatement($sql);

      if (Propel::isError($e = BasePeer::populateStmtValues($stmt, $selectParams, $dbMap)))
        return $e;

      $result = $stmt->executeUpdate();
      if (Creole::isError($result)) {
        Propel::log($result->getMessage(), PROPEL_LOG_ERR);
        return new PropelException(PROPEL_ERROR_DB, "Unable to execute DELETE statement.", $result);
      }
    } // for each table

    return $result;
  }

  /**
  * Method to deletes all contents of specified table.
  *
  * This method is invoked from generated Peer classes like this:
  * <code>
  * function doDeleteAll($con = null)
  * {
  *   $con = Param::get($con);
  *   if ($con === null) $con = Propel::getConnection(self::DATABASE_NAME());
  *   BasePeer::doDeleteAll(self::TABLE_NAME(), $con);
  * }
  * </code>
  *
  * @param string $tableName The name of the table to empty.
  * @param Connection $con A Connection.
  *
  * @return mixed Number of affected rows on success, PropelException on failure.
  */
  function doDeleteAll($tableName, /*Connection*/ &$con)
  {
    $sql = "DELETE FROM " . $tableName;
    Propel::log($sql, PROPEL_LOG_DEBUG);

    $stmt =& $con->prepareStatement($sql);
    $result = $stmt->executeUpdate();

    if (Creole::isError($result)) {
      Propel::log($result->getMessage(), PROPEL_LOG_ERR);
      return new PropelException(PROPEL_ERROR_DB, "Unable to perform DELETE ALL operation.", $result);
    }

    return $result;
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
  * @param Criteria $criteria Object containing values to insert.
  * @param Connection $con A Connection.
  * @return mixed The primary key for the new row if (and only if!) the primary key
  *               is auto-generated.  Otherwise will return <code>null</code> OR
  *               PropelException on failure. 
  */
  function doInsert(/*Criteria*/ $criteria, /*Connection*/ &$con)
  {
    // the primary key
    $id = null;
    // Get the table name and method for determining the primary
    // key value.
    $keys = $criteria->keys();

    if (empty($keys)) {
      return new PropelException(PROPEL_ERROR, "Database insert attempted without anything specified to insert");
    }

    $tableName = $criteria->getTableName($keys[0]);

    $dbMap =& Propel::getDatabaseMap($criteria->getDbName());

    if (Propel::isError($dbMap))
      return $dbMap;

    $tableMap =& $dbMap->getTable($tableName);
    $keyInfo =& $tableMap->getPrimaryKeyMethodInfo();
    $useIdGen = $tableMap->isUseIdGenerator();
    $keyGen =& $con->getIdGenerator();

    $pk = BasePeer::getPrimaryKey($criteria);

    // only get a new key value if you need to
    // the reason is that a primary key might be defined
    // but you are still going to set its value. for example:
    // a join table where both keys are primary and you are
    // setting both columns with your own values

    // pk will be null if there is no primary key defined for the table
    // we're inserting into.
    if ($pk !== null && $useIdGen && ! $criteria->containsKey($pk->getFullyQualifiedName()))
    {
      // If the keyMethod is SEQUENCE get the id before the insert.
      if ($keyGen->isBeforeInsert())
      {
        $id = $keyGen->getId($keyInfo);
        if (Creole::isError($id)) {
          return new PropelException(PROPEL_ERROR, "Unable to get sequence id.", $id);
        }
        $criteria->add($pk->getFullyQualifiedName(), $id);
      }
    }

    $qualifiedCols = $criteria->keys(); // we need table.column cols when populating values
    $columns = array(); // but just 'column' cols for the SQL

    foreach($qualifiedCols as $qualifiedCol) {
      $columns[] = substr($qualifiedCol, strpos($qualifiedCol, '.') + 1);
    }

    $sql = "INSERT INTO " . $tableName
            . " (" . implode(",", $columns) . ")"
            . " VALUES (" . substr(str_repeat("?,", count($columns)), 0, -1) . ")";

    Propel::log($sql, PROPEL_LOG_DEBUG);

    $stmt =& $con->prepareStatement($sql);
    $params =& BasePeer::buildParams($qualifiedCols, $criteria);

    if (Propel::isError($e = BasePeer::populateStmtValues($stmt, $params, $dbMap))) {
      Propel::log($e->getMessage(), PROPEL_LOG_ERR);
      return new PropelException("Unable to execute INSERT statement.", $e);
    }

    if (Creole::isError($e = $stmt->executeUpdate())) {
      Propel::log($e->getMessage(), PROPEL_LOG_ERR);
      return new PropelException(PROPEL_ERROR_DB, "Unable to execute INSERT statement.", $e);
    }

    // If the primary key column is auto-incremented, get the id
    // now.
    if ($pk !== null && $useIdGen && $keyGen->isAfterInsert())
    {
      $id = $keyGen->getId($keyInfo);
      if (Creole::isError($id)) {
        Propel::log("Unable to get autoincrement id: ", $id->getMessage(), PROPEL_LOG_ERR);
        return new PropelException(PROPEL_ERROR_DB, "Unable to get autoincrement id.", $id);
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
   * @param Criteria $selectCriteria A Criteria object containing values used in where clause.
   * @param Criteria $updateValues A Criteria object containing values used in set clause.
   * @param Connection $con A Connection.
   * @return mixed Number of affected rows on success, PropelException on failure.
   * @static public
   */
  function doUpdate(/*Criteria*/ &$selectCriteria, /*Criteria*/ &$updateValues, /*Connection*/ &$con)
  {
    Propel::typeHint($con, 'Connection', 'BasePeer', 'doUpdate', 3);

    $db =& Propel::getDB($selectCriteria->getDbName());
    $dbMap =& Propel::getDatabaseMap($selectCriteria->getDbName());

    if (Propel::isError($dbMap))
      return $dbMap;

    // Get list of required tables, containing all columns
    $tablesColumns = $selectCriteria->getTablesColumns();
    // we also need the columns for the update SQL
    $updateTablesColumns = $updateValues->getTablesColumns();
    // initialize this in case the next loop has no iterations.
    $result = 0;

    foreach($tablesColumns as $tableName => $columns)
    {
      $whereClause = array();
      $selectParams = array();

      foreach($columns as $colName)
      {
        $sb = "";
        $c =& $selectCriteria->getCriterion($colName);

        if (Propel::isError($e = $c->appendPsTo($sb, $selectParams)))
          return $e;

        $whereClause[] = $sb;
      }

      $rs = null;
      $stmt = null;

      $sqlSnippet = implode(" AND ", $whereClause);

      if ($selectCriteria->isSingleRecord())
      {
        // Get affected records.
        $sql = "SELECT COUNT(*) FROM " . $tableName . " WHERE " . $sqlSnippet;
        $stmt =& $con->prepareStatement($sql);

        if (Propel::isError($e = BasePeer::populateStmtValues($stmt, $selectParams, $dbMap)))
          return $e;

        $rs =& $stmt->executeQuery(ResultSet::FETCHMODE_NUM());
        if (Creole::isError($rs)) {
          return new PropelException(PROPEL_ERROR_DB, "Unable to execute UPDATE statement !", $rs);
        }
        if ($rs) {
          $rs->next();
          if ($rs->getInt(1) > 1) {
            $rs->close();
            return new PropelException(PROPEL_ERROR, "Expected to update 1 record, multiple matched.");
          }
          $rs->close();
        }
      }

      $sql = "UPDATE " . $tableName . " SET ";
      foreach($updateTablesColumns[$tableName] as $col) {
        $sql .= substr($col, strpos($col, '.') + 1) . " = ?,";
      }

      $sql = substr($sql, 0, -1) . " WHERE " . $sqlSnippet;

      Propel::log($sql, PROPEL_LOG_DEBUG);

      $stmt =& $con->prepareStatement($sql);

      // Replace '?' with the actual values
      $params =& BasePeer::buildParams($updateTablesColumns[$tableName], $updateValues);

      if (Propel::isError($e = BasePeer::populateStmtValues($stmt, array_merge($params, $selectParams), $dbMap)))
        return $e;

      if (Creole::isError($result = $stmt->executeUpdate())) {
        if ($rs) $rs->close();
        if ($stmt) $stmt->close();
        Propel::log($result->getMessage(), PROPEL_LOG_ERR);
        return new PropelException(PROPEL_ERROR_DB, "Unable to execute UPDATE statement.", $result);
      }

      $stmt->close();
    } // foreach table in the criteria

    return $result;
  }

  /**
  * Executes query build by createSelectSql() and returns a ResultSet.
  *
  * @param Criteria $criteria A Criteria.
  * @param Connection $con A connection to use.
  *
  * @return mixed A ResultSet object on success, PropelException on failure.
  *
  * @see createSelectSql()
  */
  function & doSelect(/*Criteria*/ &$criteria, $con = null)
  {
    /* [MA] temporarily check */
    Propel::assertParam($con, 'BasePeer', 'doSelect', 2);

    $con =& Param::get($con);

    if ($con === null) {
      $con =& Propel::getConnection($criteria->getDbName());
      if (Propel::isError($con)) { return $con; }
    }

    $dbMap = Propel::getDatabaseMap($criteria->getDbName());
    if (Propel::isError($dbMap)) { return $dbMap; }

    $stmt = null;

    // Transaction support exists for (only?) Postgres, which must
    // have SELECT statements that include bytea columns wrapped w/
    // transactions.
    if ($criteria->isUseTransaction())
    {
      $e = $con->begin();
      if (Creole::isError($e)) {
        return new PropelException(PROPEL_ERROR_DB, $e);
      }
    }

    $params = array();
    $sql = BasePeer::createSelectSql($criteria, $params);

    if (Propel::isError($sql)) {
      if ($criteria->isUseTransaction()) {
        $con->rollback();
      }
      return $sql;
    }

    $stmt =& $con->prepareStatement($sql);
    $stmt->setLimit($criteria->getLimit());
    $stmt->setOffset($criteria->getOffset());

    $e = BasePeer::populateStmtValues($stmt, $params, $dbMap);

    if (Propel::isError($e)) {
      if ($criteria->isUseTransaction()) {
        $con->rollback();
      }
      return $e;
    }

    $rs =& $stmt->executeQuery(ResultSet::FETCHMODE_NUM());

    if (Creole::isError($rs)) {
      $stmt->close();
      Propel::log($rs->getMessage(), PROPEL_LOG_ERR);

      if ($criteria->isUseTransaction()) {
        $con->rollback();
      }

      return new PropelException(PROPEL_ERROR_DB, "Unable to execute SELECT statement !", $rs);
    }

    if ($criteria->isUseTransaction())
    {
      $e = $con->commit();
      if (Creole::isError($e))
      {
        $stmt->close();
        $con->rollback();
        Propel::log($e->getMessage(), PROPEL_LOG_ERR);
        return new PropelException(PROPEL_ERROR_DB, $e);
      }
    }

    return $rs;
  }

    /**
     * Applies any validators that were defined in the schema to the specified columns.
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param array $columns Array of column names as key and column values as value.
     */
    function doValidate($dbName, $tableName, $columns)
    {
      $dbMap = Propel::getDatabaseMap($dbName);
      $tableMap = $dbMap->getTable($tableName);
      $failureMap = array(); // map of ValidationFailed objects
      foreach($columns as $colName => $colValue) {
        if ($tableMap->containsColumn($colName)) {
          $col =& $tableMap->getColumn($colName);
          $vals =& $col->getValidators();
          for($i = 0, $j = count($vals); $i < $j; $i++) {
            $validatorMap =& $vals[$i];
            $validator =& BasePeer::getValidator($validatorMap->getClass());
            if ($validator->isValid($validatorMap, $colValue) === false) {
              if (!isset($failureMap[$colName])) { // for now we do one ValidationFailed per column, not per rule
                $failureMap[$colName] =& new ValidationFailed($colName, $validatorMap->getMessage());
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
     * @param Criteria $criteria A Criteria.
     * @return ColumnMap If the Criteria object contains a primary
     *          key, or null if it doesn't.
     * @throws PropelException
     * @private static
     */
  function getPrimaryKey(/*Criteria*/ $criteria)
  {
    // Assume all the keys are for the same table.
    $keys = $criteria->keys();
    $key = $keys[0];
    $table = $criteria->getTableName($key);

    $pk = null;

    if (!empty($table))
    {
      $dbMap = Propel::getDatabaseMap($criteria->getDbName());

      if (Propel::isError($dbMap))
        return $dbMap;


      if ($dbMap->getTable($table) == null)
        return new PropelException(PROPEL_ERROR, "\$dbMap->getTable() is null");

      $t =& $dbMap->getTable($table);
      $columns = $t->getColumns();
      foreach(array_keys($columns) as $key) {
        if ($columns[$key]->isPrimaryKey()) {
          $pk = $columns[$key];
          break;
        }
      }
    }

    return $pk;
  }

  /**
   * Method to create an SQL query based on values in a Criteria.
   *
   * This method creates only prepared statement SQL (using ? where values
   * will go).  The second parameter ($params) stores the values that need
   * to be set before the statement is executed.  The reason we do it this way
   * is to let the Creole layer handle all escaping & value formatting.
   *
   * @param Criteria $criteria Criteria for the SELECT query.
   * @param array &$params Parameters that are to be replaced in prepared statement.
   * @return string
   * @throws PropelException Trouble creating the query string.
   */
  function createSelectSql(/*Criteria*/ &$criteria, &$params)
  {
    $db =& Propel::getDB($criteria->getDbName());
    $dbMap =& Propel::getDatabaseMap($criteria->getDbName());

    if (Propel::isError($dbMap))
      return $dbMap;

    // redundant definition $selectModifiers = array();
    $selectClause = array();
    $fromClause = array();
    $whereClause = array();
    $orderByClause = array();
    // redundant definition $groupByClause = array();

    $orderBy = $criteria->getOrderByColumns();
    $groupBy = $criteria->getGroupByColumns();
    $ignoreCase = $criteria->isIgnoreCase();
    $select = $criteria->getSelectColumns();
    $aliases = $criteria->getAsColumns();

    // simple copy
    $selectModifiers = $criteria->getSelectModifiers();

    // get selected columns
    foreach($select as $columnName)
    {
      // expect every column to be of "table.column" formation
      // it could be a function:  e.g. MAX(books.price)

      $tableName = null;
      $selectClause[] = $columnName; // the full column name: e.g. MAX(books.price)
      $parenPos = strpos($columnName, '(');
      $dotPos = strpos($columnName, '.');

      // [HL] I think we really only want to worry about adding stuff to
      // the fromClause if this function has a TABLE.COLUMN in it at all.
      // e.g. COUNT(*) should not need this treatment -- or there needs to
      // be special treatment for '*'
      if ($dotPos !== false)
      {
        if ($parenPos === false) { // table.column
          $tableName = substr($columnName, 0, $dotPos);
        }
        else
        { // FUNC(table.column)
          // FIXED: see Issue #21
          $tableName = substr($columnName, $parenPos + 1, $dotPos - ($parenPos + 1));
          // functions may contain qualifiers so only take the last
          // word as the table name.
          // COUNT(DISTINCT books.price)
          $lastSpace = strpos($tableName, ' ');
          if ($lastSpace !== false) { // COUNT(DISTINCT books.price)
            $tableName = substr($tableName, $lastSpace + 1);
          }
        }

        $tableName2 = $criteria->getTableForAlias($tableName);
        if ($tableName2 !== null) {
                $fromClause[] = $tableName2 . ' ' . $tableName;
        } else {
                $fromClause[] = $tableName;
        }

      } // if $dotPost !== null
    } // foreach

    // set the aliases
    foreach($aliases as $alias => $col) {
      $selectClause[] = $col . " AS " . $alias;
    }

    // add the criteria to WHERE clause
    // this will also add the table names to the FROM clause if they are not already
    // invluded via a LEFT JOIN
    foreach($criteria->keys() as $key)
    {
      $criterion =& $criteria->getCriterion($key);
      $someCriteria =& $criterion->getAttachedCriterion();
      $someCriteriaLength = count($someCriteria);
      $table = null;

      for ($i=0; $i < $someCriteriaLength; $i++)
      {
        $tableName = $someCriteria[$i]->getTable();

        $table = $criteria->getTableForAlias($tableName);
        if ($table !== null) {
            $fromClause[] = $table . ' ' . $tableName;
        } else {
            $fromClause[] = $tableName;
            $table = $tableName;
        }

        $t =& $dbMap->getTable($table);

        /* fix for CriteriaTest.php*/
        if ($t) {
          $col =& $t->getColumn($someCriteria[$i]->getColumn());
          $type = $col->getType();
        } else {
          $type = null;
        }

        $ignoreCase = (
          ($criteria->isIgnoreCase() || $someCriteria[$i]->isIgnoreCase())
          && ($type == "string" )
        );

        $someCriteria[$i]->setIgnoreCase($ignoreCase);
      }

      $criterion->setDB($db);

      $sb = "";

      if (Propel::isError($e = $criterion->appendPsTo($sb, $params)))
        return $e;

      $whereClause[] = $sb;
    }

    // handle RIGHT (straight) joins
    // This adds tables to the FROM clause and adds WHERE clauses.  Not sure if this shouldn't
    // be changed to use INNER JOIN
    $join = $criteria->getJoinL();
    if ($join !== null)
    {
      $joinR = $criteria->getJoinR();
      for ($i=0, $joinSize=count($join); $i < $joinSize; $i++)
      {
        $join1 = (string) $join[$i];
        $join2 = (string) $joinR[$i];

        $tableName = substr($join1, 0, strpos($join1,'.'));
        $table = $criteria->getTableForAlias($tableName);
        if ($table != null) {
            $fromClause[] = $table . ' ' . $tableName;
        } else {
            $fromClause[] = $tableName;
        }

        $dot = strpos($join2, '.');
        $tableName = substr($join2, 0, $dot);
        $table = $criteria->getTableForAlias($tableName);
        if ($table !== null) {
            $fromClause[] = $table . ' ' . $tableName;
        } else {
            $fromClause[] = $tableName;
            $table = $tableName;
        }

        $t =& $dbMap->getTable($table);
        $col =& $t->getColumn(substr($join2, $dot + 1));
        $type =& $col->getType();

        $ignoreCase = ($criteria->isIgnoreCase()
                && ($type == "string")
                                        );
        if ($ignoreCase) {
            $whereClause[] = $db->ignoreCase($join1) . '=' . $db->ignoreCase($join2);
        } else {
            $whereClause[] = $join1 . '=' . $join2;
        }
      }
    }

    // Unique from clause elements
    $fromClause = array_unique($fromClause);

    // Add the GROUP BY columns
    $groupByClause = $groupBy;

    $having = $criteria->getHaving();
    $havingString = null;

    if ($having !== null) {
      $sb = "";

      if (Propel::isError($e = $having->appendPsTo($sb, $params)))
        return $e;

      $havingString = $sb;
    }

    if (!empty($orderBy))
    {
      foreach($orderBy as $orderByColumn)
      {
        $dotPos = strpos($orderByColumn, '.');
        $tableName = substr($orderByColumn,0, $dotPos);

        $table = $criteria->getTableForAlias($tableName);
        if ($table === null) $table = $tableName;
                
        // See if there's a space (between the column list and sort
        // order in ORDER BY table.column DESC).
        $spacePos = strpos($orderByColumn, ' ');

        if ($spacePos === false) {
          $columnName = substr($orderByColumn, $dotPos + 1);
        } else {
          $columnName = substr($orderByColumn, $dotPos + 1, $spacePos - ($dotPos + 1));
        }

        $actualColumn = $criteria->getColumnForAs($columnName);

        if ($actualColumn === null) {
          $actualColumn = $columnName;
        }
        
        $t =& $dbMap->getTable($table);
        $column = $t->getColumn($actualColumn);

        if ($column->getType() == "string") {
            if ($spacePos === false) {
                $orderByClause[] = $db->ignoreCaseInOrderBy($orderByColumn);
            } else {
                $orderByClause[] = $db->ignoreCaseInOrderBy(substr($orderByColumn, 0, $spacePos)) . substr($orderByColumn, $spacePos);
            }
            $selectClause[] = $db->ignoreCaseInOrderBy($tableName . '.' . $columnName);
        } else {
            $orderByClause[] = $orderByColumn;
        }
      }
    }

    // Build the SQL from the arrays we compiled
    $sql = "SELECT "
         . ($selectModifiers ? implode(" ", $selectModifiers) . " " : "")
         .implode(", ", $selectClause)
         ." FROM ".implode(", ", $fromClause)
         .($whereClause   ? " WHERE ".implode(" AND ", $whereClause) : "")
         .($groupByClause ? " GROUP BY ".implode(",", $groupByClause) : "")
         .($havingString  ? " HAVING ".$havingString : "")
         .($orderByClause ? " ORDER BY ".implode(",", $orderByClause) : "");

     Propel::log($sql . ' [LIMIT: ' . $criteria->getLimit() . ', OFFSET: ' . $criteria->getOffset() . ']', PROPEL_LOG_DEBUG);

     return $sql;
  }

  /**
   * Builds a params array, like the kind populated by Criterion::appendPsTo().
   * This is useful for building an array even when it is not using the appendPsTo() method.
   * @param array $columns
   * @param Criteria $values
   * @return array params array('column' => ..., 'table' => ..., 'value' => ...)
   * @private static
   */
  function buildParams($columns, /*Criteria*/ $values)
  {
    $params = array();
    foreach($columns as $key) {
      if ($values->containsKey($key)) {
        $crit = $values->getCriterion($key);
        $params[] = array('column' => $crit->getColumn(), 'table' => $crit->getTable(), 'value' => $crit->getValue());
      }
    }
    return $params;
  }

  /**
   * Populates values in a prepared statement.
   *
   * @param PreparedStatement $stmt
   * @param array $params array('column' => ..., 'table' => ..., 'value' => ...)
   * @param DatabaseMap $dbMap
   * @return int The number of params replaced.
   * @private static
   */
  function populateStmtValues(&$stmt, &$params, /*DatabaseMap*/ &$dbMap)
  {
    $i = 1;
    foreach($params as $param)
    {
      $tableName = $param['table'];
      $columnName = $param['column'];
      $value = $param['value'];

      if ($value === null) {
        $stmt->setNull($i++);
      } else {
        $t =& $dbMap->getTable($tableName);
        $cMap = $t->getColumn($columnName);
        $setter = 'set' . CreoleTypes::getAffix($cMap->getCreoleType());

        if (Creole::isError($setter)) {
          return new PropelException(PROPEL_ERROR_DB, $setter);
        }

        $stmt->$setter($i++, $value);
      }
    } // foreach
  }

  /**
  * @param string $classname The dot-path name of class (e.g. myapp.propel.MyValidator)
  * @return mixed Validator object or PropelException if not able to instantiate validator class.
  */
  function & getValidator($classname)
  {
    $self =& BasePeer::getInstance();
    $v = null;

    if (isset($self->validatorMap[$classname])) {
      $v =& $self->validatorMap[$classname];
    }

    if ($v === null)
    {
      $cls = Propel::import($classname);
      if (Propel::isError($cls)) {
        Propel::log("BasePeer::getMapBuilder() failed trying to instantiate: " . $classname . ": " . $cls->getMessage(), PROPEL_LOG_ERR);
        return $cls;
      }

      $v =& new $cls();
      $self->validatorMap[$classname] =& $v;
    }

    return $v;
  }

  /**
   * This method returns the MapBuilder specified in the name
   * parameter.  You should pass in the full dot-path path to the class, ie:
   * myapp.propel.MyMapMapBuilder.  The MapBuilder instances are cached in
   * this class for speed.
   *
   * @return MapBuilder or PropelException (and logs the error) if the MapBuilder was not found.
   * @todo -cBasePeer Consider adding app-level caching support for map builders.
   */
  function & getMapBuilder($classname)
  {
    $self =& BasePeer::getInstance();
    $mb = null;

    if (isset($self->mapBuilders[$classname])) {
      $mb =& $self->mapBuilders[$classname];
    }

    if ($mb === null)
    {
      $cls = Propel::import($classname);
      if (Propel::isError($cls)) {
        // Have to catch possible exceptions because method is
        // used in initialization of Peers.  Log the exception and
        // return null.
        Propel::log("BasePeer::getMapBuilder() failed trying to instantiate: " . $classname . ": " . $cls->getMessage(), PROPEL_LOG_ERR);
        return $cls;
      }

      $mb =& new $cls();
      $self->mapBuilders[$classname] =& $mb;
    }

    if (! $mb->isBuilt()) {
      $mb->doBuild();
    }

    return $mb;
  }

  /*
  * @private
  */
  function & getInstance()
  {
    static $instance;

    if ($instance === null) {
      $instance = new BasePeer();
    }

    return $instance;
  }

};

