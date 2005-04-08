<?php
/*
 *  $Id: BasePeer.php,v 1.82 2005/03/25 16:15:43 dzuelke Exp $
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
 * Peer classes are responsible for isolating all of the database access
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
 * @version $Revision: 1.82 $
 * @package propel.util
 */
class BasePeer
{   

    /** Array (hash) that contains the cached mapBuilders. */
    private static $mapBuilders = array();  
	
    /** Array (hash) that contains cached validators */
    private static $validatorMap = array();
        
    /**
     * Method to perform deletes based on values and keys in a
     * Criteria.
     *
     * @param Criteria $criteria The criteria to use.
     * @param Connection $con A Connection.
	 * @return int	The number of rows affected by last statement execution.  For most
	 * 				uses there is only one delete statement executed, so this number
	 * 				will correspond to the number of rows affected by the call to this
	 * 				method.  Note that the return value does require that this information
	 * 				is returned (supported) by the Creole db driver.
     * @throws PropelException
     */
    public static function doDelete(Criteria $criteria, Connection $con) 
    {                
        $db = Propel::getDB($criteria->getDbName());
        $dbMap = Propel::getDatabaseMap($criteria->getDbName());

        // Set up a list of required tables (one DELETE statement will
        // be executed per table)

        $tables_keys = array();        
        foreach($criteria as $c) {
            foreach($c->getAllTables() as $tableName) {
                $tableName2 = $criteria->getTableForAlias($tableName);
                if ($tableName2 !== null) {
                    $tables_keys[$tableName2 . ' ' . $tableName] = true;
                } else {
                    $tables_keys[$tableName] = true;
                }
            }
        } // foreach criteria->keys()
		
		$affectedRows = 0; // initialize this in case the next loop has no iterations.
		
        $tables = array_keys($tables_keys);
        
        foreach($tables as $tableName) {
        
            $whereClause = array();            
            $selectParams = array();
            foreach($dbMap->getTable($tableName)->getColumns() as $colMap) {                
                $key = $tableName . '.' . $colMap->getColumnName();
                if ($criteria->containsKey($key)) {
                    $sb = "";
                    $criteria->getCriterion($key)->appendPsTo($sb, $selectParams);
                    $whereClause[] = $sb;                    
                }
            }
            
            if (empty($whereClause)) {
                throw new PropelException("Cowardly refusing to delete from table $tableName with empty WHERE clause.");
            }
            
            // Execute the statement.
            try {

                $sqlSnippet = implode(" AND ", $whereClause);
                                
                if ($criteria->isSingleRecord()) {                                                                        
                    $sql = "SELECT COUNT(*) FROM " . $tableName . " WHERE " . $sqlSnippet;
                    $stmt = $con->prepareStatement($sql);
                    self::populateStmtValues($stmt, $selectParams, $dbMap);                            
                    $rs = $stmt->executeQuery(ResultSet::FETCHMODE_NUM);
                    $rs->next();
                    if ($rs->getInt(1) > 1) {
                        $rs->close();
                        throw new PropelException("Expecting to delete 1 record, but criteria match multiple.");
                    }
                    $rs->close();
                }
                
                $sql = "DELETE FROM " . $tableName . " WHERE " .  $sqlSnippet;
                Propel::log($sql, Propel::LOG_DEBUG);
                $stmt = $con->prepareStatement($sql);
                self::populateStmtValues($stmt, $selectParams, $dbMap);
                $affectedRows = $stmt->executeUpdate();                
            } catch (Exception $e) {
                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException("Unable to execute DELETE statement.",$e);
            }
            
        } // for each table
		
		return $affectedRows;
    }    

    /**
     * Method to deletes all contents of specified table.
     * 
	 * This method is invoked from generated Peer classes like this:
	 * <code>
	 * public static function doDeleteAll($con = null)
	 * {
	 *   if ($con === null) $con = Propel::getConnection(self::DATABASE_NAME);
	 *   BasePeer::doDeleteAll(self::TABLE_NAME, $con);
	 * }
	 * </code>
	 *
     * @param string $tableName The name of the table to empty.
     * @param Connection $con A Connection.
	 * @return int	The number of rows affected by the statement.  Note 
	 * 				that the return value does require that this information
	 * 				is returned (supported) by the Creole db driver.
     * @throws PropelException - wrapping SQLException caught from statement execution.
     */
    public static function doDeleteAll($tableName, Connection $con) 
    {
		try {               
			$sql = "DELETE FROM " . $tableName;
			Propel::log($sql, Propel::LOG_DEBUG);
			$stmt = $con->prepareStatement($sql);
			return $stmt->executeUpdate(); 
		} catch (Exception $e) {
			Propel::log($e->getMessage(), Propel::LOG_ERR);
			throw new PropelException("Unable to perform DELETE ALL operation.", $e);
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
     * @param Criteria $criteria Object containing values to insert.
     * @param Connection $con A Connection.
     * @return mixed The primary key for the new row if (and only if!) the primary key
     *                is auto-generated.  Otherwise will return <code>null</code>.
     * @throws PropelException
     */
    public static function doInsert(Criteria $criteria, Connection $con) {
        
        // the primary key                
        $id = null;

        // Get the table name and method for determining the primary
        // key value.       
        $keys = $criteria->keys();
        if (!empty($keys)) {
            $tableName = $criteria->getTableName( $keys[0] );
        } else {
            throw new PropelException("Database insert attempted without anything specified to insert");
        }

        $dbMap = Propel::getDatabaseMap($criteria->getDbName());
        $tableMap = $dbMap->getTable($tableName);
        $keyInfo = $tableMap->getPrimaryKeyMethodInfo();
        $useIdGen = $tableMap->isUseIdGenerator();
        $keyGen = $con->getIdGenerator();

        $pk = self::getPrimaryKey($criteria);
        
        // only get a new key value if you need to
        // the reason is that a primary key might be defined
        // but you are still going to set its value. for example:
        // a join table where both keys are primary and you are
        // setting both columns with your own values

        // pk will be null if there is no primary key defined for the table
        // we're inserting into.
        if ($pk !== null && $useIdGen && !$criteria->containsKey($pk->getFullyQualifiedName())) {            

            // If the keyMethod is SEQUENCE get the id before the insert.
            if ($keyGen->isBeforeInsert()) {            
                try {
                    $id = $keyGen->getId($keyInfo);
                } catch (Exception $e) {
                    throw new PropelException("Unable to get sequence id.", $e);
                }                
                $criteria->add($pk->getFullyQualifiedName(), $id);
            }
        }

        try {
            
            $qualifiedCols = $criteria->keys(); // we need table.column cols when populating values
            $columns = array(); // but just 'column' cols for the SQL
            foreach($qualifiedCols as $qualifiedCol) {
                $columns[] = substr($qualifiedCol, strpos($qualifiedCol, '.') + 1);
            }
            
            $sql = "INSERT INTO " . $tableName 
                . " (" . implode(",", $columns) . ")"
                . " VALUES (" . substr(str_repeat("?,", count($columns)), 0, -1) . ")";
            
            Propel::log($sql, Propel::LOG_DEBUG);
            
            $stmt = $con->prepareStatement($sql);
            self::populateStmtValues($stmt, self::buildParams($qualifiedCols, $criteria), $dbMap);
            $stmt->executeUpdate();
            
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException("Unable to execute INSERT statement.", $e);
        }        

        // If the primary key column is auto-incremented, get the id
        // now.
        if ($pk !== null && $useIdGen && $keyGen->isAfterInsert()) {        
            try {
                $id = $keyGen->getId($keyInfo);
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
     * @param $selectCriteria A Criteria object containing values used in where
     *        clause.
     * @param $updateValues A Criteria object containing values used in set
     *        clause.
     * @param $con 	The Connection to use.
	 * @return int	The number of rows affected by last update statement.  For most
	 * 				uses there is only one update statement executed, so this number
	 * 				will correspond to the number of rows affected by the call to this
	 * 				method.  Note that the return value does require that this information
	 * 				is returned (supported) by the Creole db driver.
     * @throws PropelException
     */
    public static function doUpdate(Criteria $selectCriteria, Criteria $updateValues, Connection $con) {
            
        $db = Propel::getDB($selectCriteria->getDbName());        
        $dbMap = Propel::getDatabaseMap($selectCriteria->getDbName());
        
        // Get list of required tables, containing all columns
        $tablesColumns = $selectCriteria->getTablesColumns();
        
        // we also need the columns for the update SQL
        $updateTablesColumns = $updateValues->getTablesColumns();
        
		$affectedRows = 0; // initialize this in case the next loop has no iterations.
		
        foreach($tablesColumns as $tableName => $columns) {             
            
            $whereClause = array();
            
            $selectParams = array();
            foreach($columns as $colName) {                            
                $sb = "";
                $selectCriteria->getCriterion($colName)->appendPsTo($sb, $selectParams);
                $whereClause[] = $sb;                
            }
            
            $rs = null;
            $stmt = null;            
            try {
                                                         
                $sqlSnippet = implode(" AND ", $whereClause);                                            
                
                if ($selectCriteria->isSingleRecord()) {
                    // Get affected records.  
                    $sql = "SELECT COUNT(*) FROM " . $tableName . " WHERE " . $sqlSnippet;        
                    $stmt = $con->prepareStatement($sql);
                    self::populateStmtValues($stmt, $selectParams, $dbMap);
                    $rs = $stmt->executeQuery(ResultSet::FETCHMODE_NUM);                                                     
                    $rs->next();                                     
                    if ($rs->getInt(1) > 1) {
                        $rs->close();
                        throw new PropelException("Expected to update 1 record, multiple matched.");
                    }
                    $rs->close();                            
                }
                            
                $sql = "UPDATE " . $tableName . " SET ";
                foreach($updateTablesColumns[$tableName] as $col) {
                    $sql .= substr($col, strpos($col, '.') + 1) . " = ?,";
                }
                
                $sql = substr($sql, 0, -1) . " WHERE " . $sqlSnippet;
                                
                Propel::log($sql, Propel::LOG_DEBUG);
                
                $stmt = $con->prepareStatement($sql);                                                       
                
                // Replace '?' with the actual values
                self::populateStmtValues($stmt, array_merge(self::buildParams($updateTablesColumns[$tableName], $updateValues), $selectParams), $dbMap);
                
                $affectedRows = $stmt->executeUpdate();
                $stmt->close();
                         
            } catch (Exception $e) {
                if ($rs) $rs->close();
                if ($stmt) $stmt->close();
                Propel::log($e->getMessage(), Propel::LOG_ERR);
                throw new PropelException("Unable to execute UPDATE statement.", $e);
            }
            
        } // foreach table in the criteria
		
		return $affectedRows;
    }

    /**
     * Executes query build by createSelectSql() and returns ResultSet.
     *
     * @param Criteria $criteria A Criteria.
     * @param Connection $con A connection to use.
     * @return ResultSet The resultset.
     * @throws PropelException
     * @see createSelectSql()
     */
    public static function doSelect(Criteria $criteria, $con = null)
    {
        $dbMap = Propel::getDatabaseMap($criteria->getDbName());

        if ($con === null)
            $con = Propel::getConnection($criteria->getDbName());
        
        $stmt = null;            

        try {            
                    
            // Transaction support exists for (only?) Postgres, which must
            // have SELECT statements that include bytea columns wrapped w/ 
            // transactions.
            if ($criteria->isUseTransaction()) $con->begin();
            
            $params = array();
            $sql = self::createSelectSql($criteria, $params);        
                            
            $stmt = $con->prepareStatement($sql);
            $stmt->setLimit($criteria->getLimit());
            $stmt->setOffset($criteria->getOffset());
            
            self::populateStmtValues($stmt, $params, $dbMap);
            
            $rs = $stmt->executeQuery(ResultSet::FETCHMODE_NUM);            
            if ($criteria->isUseTransaction()) $con->commit();
        } catch (Exception $e) {
            if ($stmt) $stmt->close();
            if ($criteria->isUseTransaction()) $con->rollback();
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException($e);
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
    public static function doValidate($dbName, $tableName, $columns)
    {
        $dbMap = Propel::getDatabaseMap($dbName);
        $tableMap = $dbMap->getTable($tableName);
        $failureMap = array(); // map of ValidationFailed objects    
        foreach($columns as $colName => $colValue) {
            if ($tableMap->containsColumn($colName)) {
                foreach($tableMap->getColumn($colName)->getValidators() as $validatorMap) {
                    $validator = BasePeer::getValidator($validatorMap->getClass());                             
                    if ($validator->isValid($validatorMap, $colValue) === false) {
                        if (!isset($failureMap[$colName])) { // for now we do one ValidationFailed per column, not per rule
                            $failureMap[$colName] = new ValidationFailed($colName, $validatorMap->getMessage());
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
     */
    private static function getPrimaryKey(Criteria $criteria)
    {
        // Assume all the keys are for the same table.
        $keys = $criteria->keys();
        $key = $keys[0];
        $table = $criteria->getTableName($key);
        
        $pk = null;

        if (!empty($table)) {
            
            $dbMap = Propel::getDatabaseMap($criteria->getDbName());
            
            if ($dbMap === null) {
                throw new PropelException("\$dbMap is null");
            }
            
            if ($dbMap->getTable($table) === null) {
                throw new PropelException("\$dbMap->getTable() is null");
            }

            $columns = $dbMap->getTable($table)->getColumns();
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
    public static function createSelectSql(Criteria $criteria, &$params) {
                
        $db = Propel::getDB($criteria->getDbName());    
        $dbMap = Propel::getDatabaseMap($criteria->getDbName());

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
        foreach($select as $columnName) {
        
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
            if ($dotPos !== false) {

                if ($parenPos === false) { // table.column
                    $tableName = substr($columnName, 0, $dotPos);
                } else { // FUNC(table.column)
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
        }

        // set the aliases
        foreach($aliases as $alias => $col) {
            $selectClause[] = $col . " AS " . $alias;
        }        
                
        // add the criteria to WHERE clause
        // this will also add the table names to the FROM clause if they are not already
        // invluded via a LEFT JOIN
        foreach($criteria->keys() as $key) {
        
            $criterion = $criteria->getCriterion($key);
            $someCriteria = $criterion->getAttachedCriterion();
            $someCriteriaLength = count($someCriteria);
            $table = null;
            for ($i=0; $i < $someCriteriaLength; $i++) {
                $tableName = $someCriteria[$i]->getTable();
                
                $table = $criteria->getTableForAlias($tableName);
                if ($table !== null) {
                    $fromClause[] = $table . ' ' . $tableName;
                } else {
                    $fromClause[] = $tableName;
                    $table = $tableName;
                }

                $ignoreCase =
                    (($criteria->isIgnoreCase()
                        || $someCriteria[$i]->isIgnoreCase())
                        && ($dbMap->getTable($table)->getColumn($someCriteria[$i]->getColumn())->getType() == "string" )
                         );

                $someCriteria[$i]->setIgnoreCase($ignoreCase);
            }

            $criterion->setDB($db);
            
            $sb = "";
            $criterion->appendPsTo($sb, $params);
            $whereClause[] = $sb;
            
        }
        
        // handle RIGHT (straight) joins
        // This adds tables to the FROM clause and adds WHERE clauses.  Not sure if this shouldn't
        // be changed to use INNER JOIN
        $join = $criteria->getJoinL();
        if ($join !== null) {
            $joinR = $criteria->getJoinR();
            for ($i=0, $joinSize=count($join); $i < $joinSize; $i++) {
                $join1 = (string) $join[$i];
                $join2 = (string) $joinR[$i];
                
                $tableName = substr($join1, 0, strpos($join1,'.'));
                $table = $criteria->getTableForAlias($tableName);
                if ($table !== null) {
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

                $ignoreCase = ($criteria->isIgnoreCase()
                        && ($dbMap->getTable($table)->getColumn(substr($join2, $dot + 1))->getType() == "string")
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
            $having->appendPsTo($sb, $params);
            $havingString = $sb;          
        }
        
         if (!empty($orderBy)) {

            foreach($orderBy as $orderByColumn) {
                
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
				
                $column = $dbMap->getTable($table)->getColumn($actualColumn);
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
        $sql =  "SELECT "
				.($selectModifiers ? implode(" ", $selectModifiers) . " " : "")
				.implode(", ", $selectClause)
                ." FROM ".implode(", ", $fromClause)
                .($whereClause ? " WHERE ".implode(" AND ", $whereClause) : "")
                .($groupByClause ? " GROUP BY ".implode(",", $groupByClause) : "")
                .($havingString ? " HAVING ".$havingString : "")
                .($orderByClause ? " ORDER BY ".implode(",", $orderByClause) : "");
                                                
        Propel::log($sql . ' [LIMIT: ' . $criteria->getLimit() . ', OFFSET: ' . $criteria->getOffset() . ']', Propel::LOG_DEBUG);
        
        return $sql;

    }
    
    /**
     * Builds a params array, like the kind populated by Criterion::appendPsTo().
     * This is useful for building an array even when it is not using the appendPsTo() method.
     * @param array $columns
     * @param Criteria $values
     * @return array params array('column' => ..., 'table' => ..., 'value' => ...)
     */
    private static function buildParams($columns, Criteria $values) {
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
     */
    private static function populateStmtValues($stmt, $params, DatabaseMap $dbMap)
    {        
        $i = 1;
        foreach($params as $param) {
            $tableName = $param['table'];
            $columnName = $param['column'];
            $value = $param['value'];                                    
            
            if ($value === null) {
                $stmt->setNull($i++);
            } else {
                $cMap = $dbMap->getTable($tableName)->getColumn($columnName);            
                $setter = 'set' . CreoleTypes::getAffix($cMap->getCreoleType());
                $stmt->$setter($i++, $value);
            }            
        } // foreach
    }
    
    /**
    * This function searches for the given validator $name under propel/validator/$name.php,
    * imports and caches it.
    *
    * @param string $classname The dot-path name of class (e.g. myapp.propel.MyValidator)
    * @return Validator object or null if not able to instantiate validator class (and error will be logged in this case)
    */
    public static function getValidator($classname)
    {
        try {
            $v = @self::$validatorMap[$classname];
            if ($v === null) {
                $cls = Propel::import($classname);    
                $v = new $cls();
                self::$validatorMap[$classname] = $v;
            }
            return $v;
        } catch (Exception $e) {
            Propel::log("BasePeer::getValidator(): failed trying to instantiate " . $classname . ": ".$e->getMessage(), Propel::LOG_ERR);
        }
    }

    /**
     * This method returns the MapBuilder specified in the name
     * parameter.  You should pass in the full dot-path path to the class, ie:
     * myapp.propel.MyMapMapBuilder.  The MapBuilder instances are cached in 
     * this class for speed.
     * 
     * @param string $classname The dot-path name of class (e.g. myapp.propel.MyMapBuilder)
     * @return MapBuilder or null (and logs the error) if the MapBuilder was not found.
     * @todo -cBasePeer Consider adding app-level caching support for map builders.
     */
    public static function getMapBuilder($classname)
    {
        try {
            $mb = @self::$mapBuilders[$classname];
            if ($mb === null) {
                $cls = Propel::import($classname);
                $mb = new $cls();
                self::$mapBuilders[$classname] = $mb;                
            }            
            if (!$mb->isBuilt()) {
                $mb->doBuild();
            }
            return $mb;
        } catch (Exception $e) {
            // Have to catch possible exceptions because method is
            // used in initialization of Peers.  Log the exception and
            // return null.
            Propel::log("BasePeer::getMapBuilder() failed trying to instantiate " . $classname . ": " . $e->getMessage(), Propel::LOG_ERR);
        }
    }
    
}
