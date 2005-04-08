<?php
/*
 *  $Id: Criteria.php,v 1.16 2005/03/31 09:21:15 micha Exp $
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

include_once 'Log.php';

/**
 * This is a utility class for holding criteria information for a query.
 *
 * BasePeer constructs SQL statements based on the values in this class.
 *
 * @author Kaspars Jaudzems <kasparsj@navigators.lv> (Propel)
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Michael Aichler <aichler@mediacluster.de> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Eric Dobbs <eric@dobbse.net> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Sam Joseph <sam@neurogrid.com> (Torque)
 * @version $Revision: 1.16 $
 * @package propel.util
 */
class Criteria /*implements IteratorAggregate */
{
  /** Comparison type. */
  function EQUAL() { return "="; }             
  
  /** Comparison type. */
  function NOT_EQUAL() { return "<>"; }            
  
  /** Comparison type. */
  function ALT_NOT_EQUAL() { return "!="; }            
  
  /** Comparison type. */
  function GREATER_THAN() { return ">"; }             
  
  /** Comparison type. */
  function LESS_THAN() { return "<"; }             
  
  /** Comparison type. */
  function GREATER_EQUAL() { return ">="; }            
  
  /** Comparison type. */
  function LESS_EQUAL() { return "<="; }            
  
  /** Comparison type. */
  function LIKE() { return " LIKE "; }        
  
  /** Comparison type. */
  function NOT_LIKE() { return " NOT LIKE "; }    
  
  /** PostgreSQL comparison type */
  function ILIKE() { return " ILIKE "; }       
  
  /** PostgreSQL comparison type */
  function NOT_ILIKE() { return " NOT ILIKE "; }   
  
  /** Comparison type. */
  function CUSTOM() { return "CUSTOM"; }        
  
  /** Comparison type. */
  function DISTINCT() { return "DISTINCT "; }     
  
  /** Comparison type. */
  function IN () { return " IN "; }          
  
  /** Comparison type. */
  function NOT_IN() { return " NOT IN "; }      
  
  /** Comparison type. */
  function ALL() { return "ALL "; }          
  
  /** Comparison type. */
  function JOIN() { return "JOIN"; }          
  
  /** "Order by" qualifier - ascending */
  function ASC() { return "ASC"; }           
  
  /** "Order by" qualifier - descending */
  function DESC() { return "DESC"; }          
  
  /** "IS NULL" null comparison */
  function ISNULL() { return " IS NULL "; }     
  
  /** "IS NOT NULL" null comparison */
  function ISNOTNULL() { return " IS NOT NULL "; } 
  
  /** "CURRENT_DATE" ANSI SQL function */
  function CURRENT_DATE() { return "CURRENT_DATE"; }  
  
  /** "CURRENT_TIME" ANSI SQL function */
  function CURRENT_TIME() { return "CURRENT_TIME"; }  
  
  /** "CURRENT_TIMESTAMP" ANSI SQL function */
  function CURRENT_TIMESTAMP() { return "CURRENT_TIMESTAMP"; } 

  var $ignoreCase = false;
  var $singleRecord = false;
  var $selectModifiers = array();
  var $asColumns = array();
  var $selectColumns = array();
  var $orderByColumns = array();
  var $groupByColumns = array();
  var $having = null;
  var $joinL = null;
  var $joinR = null;

  var $leftJoinL = null;

  /** The name of the database. */
  var $dbName;

  /** The name of the database as given in the contructor. */
  var $originalDbName;

  /**
  * To limit the number of rows to return.  <code>0</code> means return all
  * rows.
  */
  var $limit = 0;

  /** To start the results at a row other than the first one. */
  var $offset = 0;

  // flag to note that the criteria involves a blob.
  var $blobFlag = null;

  var $aliases = null;

  var $useTransaction = false;

  /**
  * Primary storage of criteria data.
  * @var array
  */
  var $map = array();

  /**
  * Creates a new instance with the default capacity which corresponds to
  * the specified database.
  *
  * @param dbName The dabase name.
  */
  function Criteria($dbName = null)
  {
    $this->setDbName($dbName);
    $this->originalDbName = $dbName;
  }

  /**
  * Implementing SPL IteratorAggregate interface.  This allows
  * you to foreach() over a Criteria object.
  */
  function & getIterator() 
  {
    return new CriterionIterator($this);
  }

  /**
  * Get the criteria map.
  *
  * @return array
  */
  function & getMap()
  {
    return $this->map;
  }

  /**
  * Brings this criteria back to its initial state, so that it
  * can be reused as if it was new. Except if the criteria has grown in
  * capacity, it is left at the current capacity.
  *
  * @return void
  */
  function clear()
  {
    $this->map = array();
    $this->ignoreCase = false;
    $this->singleRecord = false;
    $this->selectModifiers = array();
    $this->selectColumns = array();
    $this->orderByColumns = array();
    $this->groupByColumns = array();
    $this->having = null;
    $this->asColumns = array();
    $this->joinL = null;
    $this->joinR = null;
    $this->dbName = $this->originalDbName;
    $this->offset = 0;
    $this->limit = -1;
    $this->blobFlag = null;
    $this->aliases = null;
    $this->useTransaction = false;
  }

  /**
  * Add an AS clause to the select columns. Usage:
  *
  * <code>
  * Criteria myCrit = new Criteria();
  * myCrit.addAsColumn("alias", "ALIAS(" . MyPeer::ID() . ")");
  * </code>
  *
  * @param string $name  wanted Name of the column (alias)
  * @param string $clause SQL clause to select from the table
  *
  * If the name already exists, it is replaced by the new clause.
  *
  * @return Criteria A modified Criteria object.
  */
  function & addAsColumn($name, $clause)
  {
    $this->asColumns[$name] = $clause;
    return $this;
  }

  /**
  * Get the column aliases.
  *
  * @return array A Hashtable which map the column alias names
  * to the alias clauses.
  */
  function & getAsColumns()
  {
    return $this->asColumns;
  }

  /**
  * Returns the column name associated with an alias (AS-column).
  *
  * @param string $alias
  * @return string
  */
  function & getColumnForAs($as)
  {
    if(isset($this->asColumns[$as])) {
      return $this->asColumns[$as];
    }
  }

  /**
  * Allows one to specify an alias for a table that can
  * be used in various parts of the SQL.
  *
  * @param alias a <code>String</code> value
  * @param table a <code>String</code> value
  * @return void
  */
  function addAlias($alias, $table)
  {
    if ($this->aliases === null) {
      $this->aliases = array();
    }

    $this->aliases[$alias] = $table;
  }

  /**
  * Returns the table name associated with an alias.
  *
  * @param alias a <code>String</code> value
  * @return string A <code>String</code> value
  */
  function & getTableForAlias($alias)
  {
    $table = null;

    if (isset($this->aliases[$alias])) {
      $table = $this->aliases[$alias];
    }

    return $table;
  }

  /**
  * Get the keys for the criteria map.
  * @return array
  */
  function keys()
  {
    return array_keys($this->map);
  }

  /**
  * Does this Criteria object contain the specified key?
  *
  * @param string $column [table.]column
  * @return boolean True if this Criteria object contain the specified key.
  */
  function containsKey($column)
  {
    // must use array_key_exists() because the key could
    // exist but have a NULL value (that'd be valid).
    return array_key_exists($column, $this->map);
  }

  /**
  * Will force the sql represented by this criteria to be executed within
  * a transaction.  This is here primarily to support the oid type in
  * postgresql.  Though it can be used to require any single sql statement
  * to use a transaction.
  * @return void
  */
  function setUseTransaction($v)
  {
    $this->useTransaction = (boolean) $v;
  }

  /**
  * called by BasePeer to determine whether the sql command specified by
  * this criteria must be wrapped in a transaction.
  *
  * @return a <code>boolean</code> value
  */
  function isUseTransaction()
  {
    return $this->useTransaction;
  }

  /**
  * Method to return criteria related to columns in a table.
  *
  * @return A Criterion.
  */
  function & getCriterion($column)
  {
    if (isset($this->map[$column])) {
      return $this->map[$column];
    }

    return null;
  }

  /**
  * Method to return criterion that is not added automatically
  * to this Criteria.  This can be used to chain the
  * Criterions to form a more complex where clause.
  *
  * @param column String full name of column (for example TABLE.COLUMN).
  * @param mixed $value
  * @param string $comparison
  * @return A Criterion.
  */
  function & getNewCriterion($column, $value, $comparison = null)
  {
    $nc =& new Criterion($this, $column, $value, $comparison);
    return $nc;
  }

  /**
  * Method to return a String table name.
  *
  * @param name A String with the name of the key.
  * @return A String with the value of the object at key.
  */
  function & getColumnName($name)
  {
    $val = null;

    if(isset($this->map[$name])) {
      $val = $this->map[$name]->getColumn();
    }

    return $val;
  }

  /**
  * Shortcut method to get an array of columns indexed by table.
  * @return array array(table => array(table.column1, table.column2))
  */
  function & getTablesColumns()
  {
    $tables = array();
    $keys = array_keys($this->map);
    foreach($keys as $key) {
      $t = substr($key, 0, strpos($key, '.'));
      // this happens automatically, so if no notices
      // are raised, then leave it out:
      // if (!isset($tables[$t])) $tables[$t] = array();
      $tables[$t][] = $key;
    }
    return $tables;
  }

  /**
  * Method to return a comparison String.
  *
  * @param string $key String name of the key.
  * @return string A String with the value of the object at key.
  */
  function & getComparison($key)
  {
    $val = null;

    if (isset($this->map[$key])) {
      $val = $this->map[$key]->getComparison();
    }

    return $val;
  }

  /**
  * Get the Database(Map) name.
  *
  * @return string A String with the Database(Map) name.
  */
  function getDbName()
  {
    return $this->dbName;
  }

  /**
  * Set the DatabaseMap name.  If <code>null</code> is supplied, uses value
  * provided by <code>Propel::getDefaultDB()</code>.
  *
  * @param $dbName A String with the Database(Map) name.
  * @return void
  */
  function setDbName($dbName = null)
  {
    $this->dbName = ($dbName === null ? Propel::getDefaultDB() : $dbName);
  }

  /**
  * Method to return a String table name.
  *
  * @param $name A String with the name of the key.
  * @return string A String with the value of table for criterion at key.
  */
  function & getTableName($name)
  {
    $val = null;

    if (isset($this->map[$name])) {
      $val = $this->map[$name]->getTable();
    }

    return $val;
  }

  /**
  * Method to return the value that was added to Criteria.
  *
  * @param string $name A String with the name of the key.
  * @return mixed The value of object at key.
  */
  function & getValue($name)
  {
    $val = null;

    if (isset($this->map[$name])) {
      $val = $this->map[$name]->getValue();
    }

    return $val;
  }

  /**
  * An alias to getValue() -- exposing a Hashtable-like interface.
  *
  * @param string $key An Object.
  * @return mixed The value within the Criterion (not the Criterion object).
  */
  function & get($key)
  {
    return $this->getValue($key);
  }

  /**
  * Overrides Hashtable put, so that this object is returned
  * instead of the value previously in the Criteria object.
  * The reason is so that it more closely matches the behavior
  * of the add() methods. If you want to get the previous value
  * then you should first Criteria.get() it yourself. Note, if
  * you attempt to pass in an Object that is not a String, it will
  * throw a NPE. The reason for this is that none of the add()
  * methods support adding anything other than a String as a key.
  *
  * @param string $key
  * @param string $value
  * @return Instance of self.
  */
  function & put($key, $value)
  {
    return $this->add($key, $value);
  }

  /**
  * Copies all of the mappings from the specified Map to this Criteria
  * These mappings will replace any mappings that this Criteria had for any
  * of the keys currently in the specified Map.
  *
  * if the map was another Criteria, its attributes are copied to this
  * Criteria, overwriting previous settings.
  *
  * @param mixed $t Mappings to be stored in this map.
  */
  function putAll($t)
  {
    if (is_array($t))
    {
      $keys = array_keys($t);
      foreach ($keys as $key)
      {
        $val =& $t[$key];
        if (is_a($val, 'Criterion')) {//$val instanceof Criterion) {
          $this->map[$key] =& $val;
        } else {
          // put throws an exception ... right?
          // otherwise there's no difference ... not sure why this is here
          // %%%
          $this->put($key, $val);
        }
      }
    }
    else if (is_a($t, 'Criteria')) //($t instanceof Criteria)
    {
      $this->joinL = $t->joinL;
      $this->joinR = $t->joinR;
    }
  }

  /**
  * This method adds a new criterion to the list of criterias.
  * If a criterion for the requested column already exists, it is
  * replaced. If is used as follow:
  *
  * <p>
  * <code>
  * Criteria crit = new Criteria();
  * $crit->add(&quot;column&quot;,
  *            &quot;value&quot;
  *            &quot;Criterion.GREATER_THAN()&quot;);
  * </code>
  *
  * Any comparison can be used.
  *
  * The name of the table must be used implicitly in the column name,
  * so the Column name must be something like 'TABLE.id'. If you
  * don't like this, you can use the add(table, column, value) method.
  *
  * @param mixed $p1 The column to run the comparison on, or a Criterion object.
  * @param mixed $value
  * @param string $comparison A String.
  *
  * @return A modified Criteria object.
  */
  function & add($p1, $value = null, $comparison = null)
  {
    if (is_a($p1, 'Criterion')) {
      $c =& $p1;
      $this->map[$c->getTable() . '.' . $c->getColumn()] =& $c;
    } else {
      $column = $p1;
      $this->map[$column] =& new Criterion($this, $column, $value, $comparison);
    }

    return $this;
  }

  /**
  * This is the way that you should add a straight (inner) join of two tables.  For
  * example:
  *
  * <p>
  * AND PROJECT.PROJECT_ID=FOO.PROJECT_ID
  * <p>
  *
  * left = PROJECT.PROJECT_ID
  * right = FOO.PROJECT_ID
  *
  * @param string $left A String with the left side of the join.
  * @param string $right A String with the right side of the join.
  * @return Criteria A modified Criteria object.
  */
  function & addJoin($left, $right)
  {
    if ($this->joinL === null) {
      $this->joinL = array();
      $this->joinR = array();
    }

    $this->joinL[] = $left;
    $this->joinR[] = $right;

    return $this;
  }

  /**
  * get one side of the set of possible joins.  This method is meant to
  * be called by BasePeer.
  * @return array
  */
  function & getJoinL()
  {
    return $this->joinL;
  }

  /**
  * get one side of the set of possible joins.  This method is meant to
  * be called by BasePeer.
  * @return array
  */
  function & getJoinR()
  {
    return $this->joinR;
  }

  /**
  * Adds "ALL " to the SQL statement.
  * @return void
  */
  function setAll()
  {
    $this->selectModifiers[] = Criteria::ALL();
  }

  /**
  * Adds "DISTINCT " to the SQL statement.
  * @return void
  */
  function setDistinct()
  {
    $this->selectModifiers[] = Criteria::DISTINCT();
  }

  /**
  * Sets ignore case.
  *
  * @param boolean $b True if case should be ignored.
  * @return A modified Criteria object.
  */
  function & setIgnoreCase($b)
  {
    $this->ignoreCase = (boolean) $b;
    return $this;
  }

  /**
  * Is ignore case on or off?
  *
  * @return boolean True if case is ignored.
  */
  function isIgnoreCase()
  {
    return $this->ignoreCase;
  }

  /**
  * Set single record?  Set this to <code>true</code> if you expect the query
  * to result in only a single result record (the default behaviour is to
  * throw a PropelException if multiple records are returned when the query
  * is executed).  This should be used in situations where returning multiple
  * rows would indicate an error of some sort.  If your query might return
  * multiple records but you are only interested in the first one then you
  * should be using setLimit(1).
  *
  * @param bool $b Set to <code>true</code> if you expect the query to select just
  * one record.
  * @return A modified Criteria object.
  */
  function & setSingleRecord($b)
  {
    $this->singleRecord = (boolean) $b;
    return $this;
  }

  /**
  * Is single record?
  *
  * @return boolean True if a single record is being returned.
  */
  function isSingleRecord()
  {
    return $this->singleRecord;
  }

  /**
  * Set limit.
  *
  * @param int $limit An int with the value for limit.
  * @return A modified Criteria object.
  */
  function & setLimit($limit)
  {
    $this->limit = $limit;
    return $this;
  }

  /**
  * Get limit.
  *
  * @return int An int with the value for limit.
  */
  function getLimit()
  {
    return $this->limit;
  }

  /**
  * Set offset.
  *
  * @param int $offset An int with the value for offset.
  * @return A modified Criteria object.
  */
  function & setOffset($offset)
  {
    $this->offset = $offset;
    return $this;
  }

  /**
  * Get offset.
  *
  * @return An int with the value for offset.
  */
  function getOffset()
  {
    return $this->offset;
  }

  /**
  * Add select column.
  *
  * @param string $name A String with the name of the select column.
  * @return A modified Criteria object.
  */
  function & addSelectColumn($name)
  {
    $this->selectColumns[] = $name;
    return $this;
  }

  /**
  * Get select columns.
  *
  * @return array An array with the name of the select
  * columns.
  */
  function getSelectColumns()
  {
    return $this->selectColumns;
  }

  /**
  * Clears current select columns.
  *
  * @return Criteria A modified Criteria object.
  */
  function & clearSelectColumns() 
  {
    $this->selectColumns = array();
    $this->asColumns = array();
    return $this;
  }

  /**
  * Get select modifiers.
  *
  * @return An array with the select modifiers.
  */
  function getSelectModifiers()
  {
    return $this->selectModifiers;
  }

  /**
  * Add group by column name.
  *
  * @param string $groupBy The name of the column to group by.
  * @return A modified Criteria object.
  */
  function & addGroupByColumn($groupBy)
  {
    $this->groupByColumns[] = $groupBy;
    return $this;
  }

  /**
  * Add order by column name, explicitly specifying ascending.
  *
  * @param string $name The name of the column to order by.
  * @return The modified Criteria object.
  */
  function & addAscendingOrderByColumn($name)
  {
    $this->orderByColumns[] = $name . ' ' . Criteria::ASC();
    return $this;
  }

  /**
  * Add order by column name, explicitly specifying descending.
  *
  * @param string $name The name of the column to order by.
  * @return The modified Criteria object.
  */
  function & addDescendingOrderByColumn($name)
  {
    $this->orderByColumns[] = $name . ' ' . Criteria::DESC();
    return $this;
  }

  /**
  * Get order by columns.
  *
  * @return A StringStack with the name of the order columns.
  */
  function getOrderByColumns()
  {
    return $this->orderByColumns;
  }

  /**
  * Clear the order-by columns.
  *
  * @return The modified Criteria object.
  */
  function & clearOrderByColumns()
  {
    $this->orderByColumns = array();
    return $this;
  }
  
  /**
  * Clear the group-by columns.
  *
  * @return The modified Criteria object.
  */
  function & clearGroupByColumns()
  {
    $this->groupByColumns = array();
    return $this;
  }
  
  
  /**
  * Get group by columns.
  *
  * @return array.
  */
  function getGroupByColumns()
  {
    return $this->groupByColumns;
  }

  /**
  * Get Having Criterion.
  *
  * @return A Criterion that is the having clause.
  */
  function & getHaving()
  {
    return $this->having;
  }

  /**
  * Remove an object from the criteria.
  *
  * @param string $key A String with the key to be removed.
  * @return mixed The removed value.
  */
  function & remove($key)
  {
    $c = null;

    if(isset($this->map[$key]))
    {
      $c = $this->map[$key];
      unset($this->map[$key]);

      if (is_a($c, 'Criterion')) {
        return $c->getValue();
      }
    }

    return $c;
  }

  /**
  * Build a string representation of the Criteria.
  *
  * @return string A String with the representation of the Criteria.
  */
  function toString()
  {
    $sb  = "Criteria:: ";        
    $sb .= "\nCurrent Query SQL (may not be complete or applicable): ";
    $params = array();

    $sql = BasePeer::createSelectSql($this, $params);

    if (Propel::isError($sql)) {
      $sb .= "(Error: " . $sql->getMessage() . ")";
    }
         
    $sb .= "\nParameters to replace: " . var_export($params, true);

    return $sb;
  }

  /**
  * Returns the size (count) of this criteria.
  * @return int
  */
  function size()
  {
    return count($this->map);
  }

  /**
  * This method checks another Criteria to see if they contain
  * the same attributes and hashtable entries.
  * @return boolean
  */
  function equals(&$crit)
  {
    $isEquiv = false;
    if ($crit === null || ! is_a($crit, 'Criteria')) {
      $isEquiv = false;
    } 
    elseif ($this === $crit) {
      $isEquiv = true;
    } 
    elseif ($this->size() === $crit->size()) {
      // Important: nested criterion objects are checked
      $criteria =& $crit; // alias
      if ($this->offset === $criteria->getOffset()
          && $this->limit === $criteria->getLimit()
          && $this->ignoreCase === $criteria->isIgnoreCase()
          && $this->singleRecord === $criteria->isSingleRecord()
          && $this->dbName === $criteria->getDbName()
          && $this->selectModifiers === $criteria->getSelectModifiers()
          && $this->selectColumns === $criteria->getSelectColumns()
          && $this->orderByColumns === $criteria->getOrderByColumns()
         )
      {
        $isEquiv = true;
        foreach($criteria->keys() as $key) {
          if ($this->containsKey($key)) {
            $a = $this->getCriterion($key);
            $b = $criteria->getCriterion($key);
            if (!$a->equals($b)) {
              $isEquiv = false;
              break;
            }
          } else {
            $isEquiv = false;
            break;
          }
        }
      }
    }

    return $isEquiv;
  }

  /**
  * This method adds a prepared Criterion object to the Criteria as a having clause.
  * You can get a new, empty Criterion object with the
  * getNewCriterion() method.
  *
  * <p>
  * <code>
  * $crit = new Criteria();
  * $c = $crit->getNewCriterion(BasePeer::ID(), 5, Criteria::LESS_THAN());
  * $crit->addHaving($c);
  * </code>
  *
  * @param having A Criterion object
  *
  * @return A modified Criteria object.
  */
  function & addHaving(/*Criterion*/ &$having)
  {
    $this->having =& $having;
    return $this;
  }

  /**
  * This method adds a new criterion to the list of criterias.
  * If a criterion for the requested column already exists, it is
  * "AND"ed to the existing criterion.
  *
  * addAnd(column, value, comparison)
  * <code>
  * $crit = $orig_crit->addAnd(&quot;column&quot;,
  *                                      &quot;value&quot;
  *                                      &quot;Criterion::GREATER_THAN&quot;);
  * </code>
  *
  * addAnd(column, value)
  * <code>
  * $crit = $orig_crit->addAnd(&quot;column&quot;, &quot;value&quot;);
  * </code>
  *
  * addAnd(Criterion)
  * <code>
  * $crit = new Criteria();
  * $c = $crit->getNewCriterion(BasePeer::ID, 5, Criteria::LESS_THAN);
  * $crit->addAnd($c);
  * </code>
  *
  * Any comparison can be used, of course.
  *
  * @note As a Criterion object will be passed by value all function calls to that
  *       Criterion must be done before adding it to the Criteria !
  *
  * @param mixed $p1 Column name or Criterion object.
  * @param mixed $p2
  * @param string $p3
  *
  * @return Criteria A modified Criteria object.
  */
  function & addAnd($p1, $p2 = null, $p3 = null)
  {
    if ($p3 !== null)
    {
      // addAnd(column, value, comparison)
      $oc =& $this->getCriterion($p1);
      $nc =& new Criterion($this, $p1, $p2, $p3);

      if ($oc === null) {
        $this->map[$p1] =& $nc;
      } else {
        $oc->addAnd($nc);
      }
    }
    else if ($p2 !== null)
    {
      // addAnd(column, value)
      $this->addAnd($p1, $p2, Criteria::EQUAL());
    }
    else if (is_a($p1, 'Criterion'))
    {
      // addAnd(Criterion)
      $c =& $p1;
      $oc =& $this->getCriterion($c->getTable() . '.' . $c->getColumn());

      if ($oc === null) {
        $this->add($c);
      } else {
        $oc->addAnd($c);
      }
    }

    return $this;
  }

  /**
  * This method adds a new criterion to the list of criterias.
  * If a criterion for the requested column already exists, it is
  * "OR"ed to the existing criterion.
  *
  * Any comparison can be used.
  *
  * Supports a number of different signatures:
  *
  * addOr(column, value, comparison)
  * <code>
  * $crit = $orig_crit->addOr(&quot;column&quot;,
  *                           &quot;value&quot;
  *                           &quot;Criterion::GREATER_THAN&quot;);
  * </code>
  *
  * addOr(column, value)
  * <code>
  * $crit = $orig_crit->addOr(&quot;column&quot;, &quot;value&quot;);
  * </code>
  *
  * addOr(Criterion)
  *
  * @note As a Criterion object will be passed by value all function calls to that
  *       Criterion must be done before adding it to the Criteria !
  *
  * @param mixed $p1 Column name or Criterion object.
  * @param mixed $p2
  * @param string $p3
  *
  * @return Criteria A modified Criteria object.
  */
  function & addOr($p1, $p2 = null, $p3 = null)
  {
    if ($p3 !== null)
    {
      // addOr(column, value, comparison)
      $oc =& $this->getCriterion($p1);
      $nc =& new Criterion($this, $p1, $p2, $p3);

      if ($oc === null) {
        $this->map[$p1] =& $nc;
      } else {
        $oc->addOr($nc);
      }
    }
    else if ($p2 !== null)
    {
      // addOr(column, value)
      $this->addOr($p1, $p2, Criteria::EQUAL());
    }
    else if (is_a($p1, 'Criterion'))
    {
      // addOr(Criterion)
      $c =& $p1;
      $oc =& $this->getCriterion($c->getTable() . '.' . $c->getColumn());
      if ($oc === null) {
        $this->add($c);
      } else {
        $oc->addOr($c);
      }
    }

    return $this;
  }

};

// --------------------------------------------------------------------
// Criterion Iterator class -- allows foreach($criteria as $criterion)
// --------------------------------------------------------------------

/**
 * Class that implements SPL Iterator interface.  This allows foreach() to
 * be used w/ Criteria objects.  Probably there is no performance advantage
 * to doing it this way, but it makes sense -- and simpler code.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class CriterionIterator 
{

  var $idx = 0;
  var $criteria;
  var $criteriaKeys;
  var $criteriaSize;

  function CriterionIterator(&$criteria) 
  {
    $this->criteria =& $criteria;
    $this->criteriaKeys = $criteria->keys();
    $this->criteriaSize = count($this->criteriaKeys);
  }

  function rewind() 
  {
    $this->idx = 0;
  }

  function valid() 
  {
    return $this->idx < $this->criteriaSize;
  }

  function key() 
  {
    return $this->criteriaKeys[$this->idx];
  }

  function & current() 
  {
    return $this->criteria->getCriterion($this->criteriaKeys[$this->idx]);
  }

  function next() 
  {
    $this->idx++;
  }

};

// --------------------------------------------------------------------
// Criterion "inner" class
// --------------------------------------------------------------------

define('Criterion_UND', " AND ");
define('Criterion_ODER'," OR ");

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * In Torque this is an inner class of the Criteria class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
class Criterion
{
  /** Value of the CO. */
  var $value;

  /** Comparison value.
  * @var SqlEnum
  */
  var $comparison;

  /** Table name. */
  var $table;

  /** Column name. */
  var $column;

  /** flag to ignore case in comparision */
  var $ignoreStringCase = false;

  /**
  * The DBAdapter adaptor which might be used to get db specific
  * variations of sql.
  */
  var $db;

  /**
  * other connected criteria and their conjunctions.
  */
  var $clauses = array();

  var $conjunctions = array();

  /** "Parent" Criteria class */
  var $parent;

  function UND() { return ' AND '; }
  
  function ODER() { return ' OR '; }

  /**
  * Create a new instance.
  *
  * @param Criteria $parent The outer class (this is an "inner" class).
  * @param string $column TABLE.COLUMN format.
  * @param mixed $value
  * @param string $comparison
  */
  function Criterion(/*Criteria*/ &$outer, $column, $value, $comparison = null)
  {
    $this->outer =& $outer;
    list($this->table, $this->column) = explode('.', $column);
    $this->value = $value;
    $this->comparison = ($comparison === null ? Criteria::EQUAL() : $comparison);
  }

  /**
  * Get the column name.
  *
  * @return string A String with the column name.
  */
  function & getColumn()
  {
    return $this->column;
  }

  /**
  * Set the table name.
  *
  * @param name A String with the table name.
  * @return void
  */
  function setTable($name)
  {
    $this->table = $name;
  }

  /**
  * Get the table name.
  *
  * @return string A String with the table name.
  */
  function & getTable()
  {
    return $this->table;
  }

  /**
  * Get the comparison.
  *
  * @return string A String with the comparison.
  */
  function & getComparison()
  {
    return $this->comparison;
  }

  /**
  * Get the value.
  *
  * @return mixed An Object with the value.
  */
  function getValue()
  {
    return $this->value;
  }

  /**
  * Get the value of db.
  * The DBAdapter which might be used to get db specific
  * variations of sql.
  * @return DBAdapter value of db.
  */
  function & getDB()
  {
    $db = null;
    if ($this->db === null) {
      // db may not be set if generating preliminary sql for
      // debugging.
      if (($db =& Propel::getDB($this->outer->getDbName())) === null) {
          // we are only doing this to allow easier debugging, so
          // no need to throw up the exception, just make note of it.
          Propel::log("Could not get a DB adapter, so sql may be wrong", PROPEL_LOG_ERR);
      }
    } else {
        $db =& $this->db;
    }

    return $db;
  }

  /**
  * Set the value of db.
  * The DBAdapter adaptor might be used to get db specific
  * variations of sql.
  * @param v  Value to assign to db.
  * @return void
  */
  function setDB(/*DBAdapter*/ &$v)
  {
    $this->db =& $v;
    for($i=0, $_i=count($this->clauses); $i < $_i; $i++) {
      $this->clauses[$i]->setDB($v);
    }
  }

  /**
  * Sets ignore case.
  *
  * @param boolean $b True if case should be ignored.
  * @return Criterion A modified Criterion object.
  */
  function setIgnoreCase($b)
  {
    $this->ignoreStringCase = $b;
    return $this;
  }

  /**
  * Is ignore case on or off?
  *
  * @return boolean True if case is ignored.
  */
  function isIgnoreCase()
  {
    return $this->ignoreStringCase;
  }

  /**
  * Get the list of clauses in this Criterion.
  * @return array
  * @private
  */
  function & getClauses()
  {
    return $this->clauses;
  }

  /**
  * Get the list of conjunctions in this Criterion
  * @return array
  * @private
  */
  function & getConjunctions()
  {
    return $this->conjunctions;
  }

  /**
  * Append an AND Criterion onto this Criterion's list.
  */
  function & addAnd(/*Criterion*/ $criterion)
  {
    $this->clauses[] =& $criterion;
    $this->conjunctions[] = Criterion::UND();
    return $this;
  }

  /**
  * Append an OR Criterion onto this Criterion's list.
  * @return Criterion
  */
  function & addOr(/*Criterion*/ $criterion)
  {
    $this->clauses[] =& $criterion;
    $this->conjunctions[] = Criterion::ODER();
    return $this;
  }

  /**
  * Appends a Prepared Statement representation of the Criterion
  * onto the buffer.
  *
  * @param string &$sb The stringbuffer that will receive the Prepared Statement
  * @param array $params A list to which Prepared Statement parameters
  *                      will be appended
  * @return PropelException If an error occures.
  */
  function appendPsTo(&$sb, &$params)
  {
    if ($this->column === null) {
      return;
    }

    $db =& $this->getDb();
    $clausesLength = count($this->clauses);

    for($j = 0; $j < $clausesLength; $j++) {
      $sb .= '(';
    }

    if (Criteria::CUSTOM() === $this->comparison)
    {
      if ($this->value !== "") {
        $sb .= (string) $this->value;
      }
    }
    else
    {
      if  ($this->table === null) {
        $field = $this->column;
      } else {
        $field = $this->table . '.' . $this->column;
      }

      // Check to see if table is an alias & store real name, if so
      // (real table name is needed for the returned $params array)
      $realtable = $this->outer->getTableForAlias($this->table);
      if(!$realtable) $realtable = $this->table;

      // There are several different types of expressions that need individual handling:
      // IN/NOT IN, LIKE/NOT LIKE, and traditional expressions.

      // OPTION 1:  table.column IN (?, ?) or table.column NOT IN (?, ?)
      if ($this->comparison === Criteria::IN() || $this->comparison === Criteria::NOT_IN())
      {
        $sb .= $field . $this->comparison;
        $values = (array) $this->value;
        for ($i=0, $valuesLength=count($values); $i < $valuesLength; $i++) {
            $params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $values[$i]);
        }
        $inString = '(' . substr(str_repeat("?,", $valuesLength), 0, -1) . ')';
        $sb .= $inString;
      }
      // OPTION 2:  table.column LIKE ? or table.column NOT LIKE ?  (or ILIKE for Postgres)
      elseif ($this->comparison === Criteria::LIKE() || $this->comparison === Criteria::NOT_LIKE()
          || $this->comparison === Criteria::ILIKE() || $this->comparison === Criteria::NOT_ILIKE())
      {
        // Handle LIKE, NOT LIKE (and related ILIKE, NOT ILIKE for Postgres)

        // If selection is case insensitive use ILIKE for PostgreSQL or SQL
        // UPPER() function on column name for other databases.
        if ($this->ignoreStringCase)
        {
          if (is_a($db, 'DBPostgres'))
          { // use is_a() because instanceof needs class to have been loaded
            if ($this->comparison === Criteria::LIKE()) {
              $this->comparison = Criteria::ILIKE();
            }
            elseif ($comparison === Criteria::NOT_LIKE()) {
              $this->comparison = Criteria::NOT_ILIKE();
            }
          }
          else {
            $field = $db->ignoreCase($field);
          }
        }

        $sb .= $field . $this->comparison;

        // If selection is case insensitive use SQL UPPER() function
        // on criteria or, if Postgres we are using ILIKE, so not necessary.
        if ($this->ignoreStringCase && !is_a($db, 'DBPostgres')) {
          $sb .= $db->ignoreCase('?');
        }
        else {
          $sb .= '?';
        }

        $params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $this->value);
      }
      // OPTION 3:  table.column = ? or table.column >= ? etc. (traditional expressions, the default)
      else
      {
        // NULL VALUES need special treatment because the SQL syntax is different
        // i.e. table.column IS NULL rather than table.column = null
        if ($this->value !== null)
        {
          // ANSI SQL functions get inserted right into SQL (not escaped, etc.)
          if ($this->value === Criteria::CURRENT_DATE() || $this->value === Criteria::CURRENT_TIME()) {
              $sb .= $field . $this->comparison . $this->value;
          }
          else
          {
            // default case, it is a normal col = value expression; value
            // will be replaced w/ '?' and will be inserted later using native Creole functions
            if ($this->ignoreStringCase) {
              $sb .= $db->ignoreCase($field) . $this->comparison . $db->ignoreCase("?");
            } else {
              $sb .= $field . $this->comparison . "?";
            }

            // need to track the field in params, because
            // we'll need it to determine the correct setter
            // method later on (e.g. field 'review.DATE' => setDate());
            $params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $this->value);
          }
        }
        else
        {
          // value is null, which means it was either not specified or specifically
          // set to null.
          if ($this->comparison === Criteria::EQUAL() || $this->comparison === Criteria::ISNULL()) {
              $sb .= $field . Criteria::ISNULL();
          } elseif ($this->comparison === Criteria::NOT_EQUAL() || $this->comparison === Criteria::ISNOTNULL()) {
              $sb .= $field . Criteria::ISNOTNULL();
          } else {
              // for now throw an exception, because not sure how to interpret this
              return new PropelException(PROPEL_ERROR_SYNTAX, "Could not build SQL for expression: $field " . $this->comparison . " NULL");
          }
        }
      }
    }

    for($i=0; $i < $clausesLength; $i++) {
      $sb .= $this->conjunctions[$i];
      $this->clauses[$i]->appendPsTo($sb, $params);
      $sb .= ')';
    }
  }

  /**
  * Build a string representation of the Criterion.
  *
  * @return string A String with the representation of the Criterion.
  */
  function toString()
  {
    //
    // it is alright if value == null
    //
    if ($this->column == null) {
       return "";
    }

    $expr = "";
    $params = array("", "");
    $this->appendPSTo($expr, $params);
    return $expr;
  }

  /**
  * This method checks another Criteria to see if they contain
  * the same attributes and hashtable entries.
  *
  * @return boolean
  */
  function equals(&$obj)
  {
    if ($this === $obj) {
      return true;
    }

    if (($obj === null) || !(is_a($obj, 'Criterion'))) {
      return false;
    }

    $crit =& $obj;

    $isEquiv = ( ( ($this->table === null && $crit->getTable() === null)
            || ( $this->table !== null && $this->table === $crit->getTable() )
                                      )
            && $this->column === $crit->getColumn()
            && $this->comparison === $crit->getComparison());


    // check chained criterion

    $clausesLength = count($this->clauses);
    $isEquiv &= (count($crit->getClauses()) == $clausesLength);
    $critConjunctions = $crit->getConjunctions();
    $critClauses = $crit->getClauses();

    for ($i=0; $i < $clausesLength && $isEquiv; $i++) {
      $isEquiv &= ($this->conjunctions[$i] === $critConjunctions[$i]);
      $isEquiv &= ($this->clauses[$i] === $critClauses[$i]);
    }

    if($isEquiv) {
      $isEquiv &= $this->value === $crit->getValue();
    }

    return $isEquiv;
  }

  /**
  * Returns a hash code value for the object.
  */
  function & hashCode()
  {
    $h = crc32(serialize($this->value)) ^ crc32($this->comparison);

    if ($this->table !== null) {
      $h ^= crc32($this->table);
    }

    if ($this->column !== null) {
      $h ^= crc32($this->column);
    }

    $clausesLength = count($this->clauses);

    for($i=0; $i < $clausesLength; $i++) {
      $h ^= crc32($this->clauses[$i]->toString());
    }

    return $h;
  }

  /**
  * Get all tables from nested criterion objects
  * @return array
  */
  function & getAllTables()
  {
    $tables = array();
    $this->addCriterionTable($this, $tables);
    return $tables;
  }

  /**
  * method supporting recursion through all criterions to give
  * us a string array of tables from each criterion
  * @return void
  * @private
  */
  function addCriterionTable(/*Criterion*/ $c, &$s)
  {
    $s[] = $c->getTable();
    $clauses = $c->getClauses();

    $clausesLength = count($clauses);

    for($i = 0; $i < $clausesLength; $i++) {
      $this->addCriterionTable($clauses[$i], $s);
    }
  }

  /**
  * get an array of all criterion attached to this
  * recursing through all sub criterion
  * @return array Criterion[]
  */
  function & getAttachedCriterion()
  {
    $crits = array();
    $this->traverseCriterion($this, $crits);
    return $crits;
  }

  /**
  * method supporting recursion through all criterions to give
  * us an array of them
  * @param Criterion $c
  * @param array &$a
  * @return void
  * @private
  */
  function traverseCriterion(/*Criterion*/ &$c, &$a)
  {
    $end = count($a);
    $a[$end] =& $c;
    $clauses =& $c->getClauses();

    $clausesLength = count($clauses);

    for($i=0; $i < $clausesLength; $i++) {
      $this->traverseCriterion($clauses[$i], $a);
    }
  }

};
