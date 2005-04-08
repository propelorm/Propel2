<?php
/*
 *  $Id: Criteria.php,v 1.62 2005/03/31 01:56:44 hlellelid Exp $
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

/**
 * This is a utility class for holding criteria information for a query.
 * 
 * BasePeer constructs SQL statements based on the values in this class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author Eric Dobbs <eric@dobbse.net> (Torque)
 * @author Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author Sam Joseph <sam@neurogrid.com> (Torque)
 * @version $Revision: 1.62 $
 * @package propel.util
 */
class Criteria implements IteratorAggregate {

    /** Comparison type. */
    const EQUAL = "=";

    /** Comparison type. */
    const NOT_EQUAL = "<>";

    /** Comparison type. */
    const ALT_NOT_EQUAL = "!=";

    /** Comparison type. */
    const GREATER_THAN = ">";

    /** Comparison type. */
    const LESS_THAN = "<";

    /** Comparison type. */
    const GREATER_EQUAL = ">=";

    /** Comparison type. */
    const LESS_EQUAL = "<=";

    /** Comparison type. */
    const LIKE = " LIKE ";

    /** Comparison type. */
    const NOT_LIKE = " NOT LIKE ";

    /** PostgreSQL comparison type */
    const ILIKE = " ILIKE ";
    
    /** PostgreSQL comparison type */
    const NOT_ILIKE = " NOT ILIKE ";

    /** Comparison type. */
    const CUSTOM = "CUSTOM";

    /** Comparison type. */
    const DISTINCT = "DISTINCT ";

    /** Comparison type. */
    const IN = " IN ";

    /** Comparison type. */
    const NOT_IN = " NOT IN ";

    /** Comparison type. */
    const ALL = "ALL ";
    
    /** Comparison type. */
    const JOIN = "JOIN";

    /** "Order by" qualifier - ascending */
    const ASC = "ASC";

    /** "Order by" qualifier - descending */
    const DESC = "DESC";

    /** "IS NULL" null comparison */
    const ISNULL = " IS NULL ";

    /** "IS NOT NULL" null comparison */
    const ISNOTNULL = " IS NOT NULL ";

    /** "CURRENT_DATE" ANSI SQL function */
    const CURRENT_DATE = "CURRENT_DATE";

    /** "CURRENT_TIME" ANSI SQL function */
    const CURRENT_TIME = "CURRENT_TIME";    
    
    /** "CURRENT_TIMESTAMP" ANSI SQL function */
    const CURRENT_TIMESTAMP = "CURRENT_TIMESTAMP";
    
    private $ignoreCase = false;
    private $singleRecord = false;
    private $selectModifiers = array();
    private $selectColumns = array();
    private $orderByColumns = array();
    private $groupByColumns = array();
    private $having = null;
    private $asColumns = array();
    private $joinL = null;
    private $joinR = null;

    private $leftJoinL = null;

    /** The name of the database. */
    private $dbName;

    /** The name of the database as given in the contructor. */
    private $originalDbName;

    /**
     * To limit the number of rows to return.  <code>0</code> means return all
     * rows.
     */
    private $limit = 0;

    /** To start the results at a row other than the first one. */
    private $offset = 0;

    // flag to note that the criteria involves a blob.
    private $blobFlag = null;

    private $aliases = null;

    private $useTransaction = false;

    /**
     * Primary storage of criteria data.
     * @var array
     */
    private $map = array();
    
    /**
     * Creates a new instance with the default capacity which corresponds to
     * the specified database.
     *
     * @param dbName The dabase name.
     */
    public function __construct($dbName = null)
    {
        $this->setDbName($dbName);
        $this->originalDbName = $dbName;
    }
    
    /**
     * Implementing SPL IteratorAggregate interface.  This allows
     * you to foreach() over a Criteria object.
     */
    public function getIterator()
	{
        return new CriterionIterator($this);
    }
    
	/**
	 * Get the criteria map.
	 * @return array
	 */
	public function getMap()
	{
		return $this->map;
	}
	
    /**
     * Brings this criteria back to its initial state, so that it
     * can be reused as if it was new. Except if the criteria has grown in
     * capacity, it is left at the current capacity.
     * @return void
     */
    public function clear()
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
     * myCrit->addAsColumn("alias", "ALIAS(".MyPeer::ID.")");
     * </code>
     *
     * @param string $name Wanted Name of the column (alias).
     * @param string $clause SQL clause to select from the table
     *
     * If the name already exists, it is replaced by the new clause.
     *
     * @return Criteria A modified Criteria object.
     */
    public function addAsColumn($name, $clause)
    {
        $this->asColumns[$name] = $clause;
        return $this;
    }

    /**
     * Get the column aliases.
     *
     * @return array An assoc array which map the column alias names
     * to the alias clauses.
     */
    public function getAsColumns()
    {
        return $this->asColumns;
    }

	/**
     * Returns the column name associated with an alias (AS-column).
     *
     * @param string $alias
     * @return string $string
     */
    public function getColumnForAs($as)
    {
		if (isset($this->asColumns[$as])) {
		    return $this->asColumns[$as];
		}
    }
	
    /**
     * Allows one to specify an alias for a table that can
     * be used in various parts of the SQL.
     *
     * @param string $alias
     * @param string $table
     * @return void
     */
    public function addAlias($alias, $table)
    {
        if ($this->aliases === null) {
            $this->aliases = array();
        }
        $this->aliases[$alias] = $table;
    }

    /**
     * Returns the table name associated with an alias.
     *
     * @param string $alias
     * @return string $string
     */
    public function getTableForAlias($alias)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }
    }
    
    /**
     * Get the keys for the criteria map.
     * @return array
     */
    public function keys()
    {
        return array_keys($this->map);
    }
    
    /**
     * Does this Criteria object contain the specified key?
     *
     * @param string $column [table.]column
     * @return boolean True if this Criteria object contain the specified key.
     */
    public function containsKey($column)
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
    public function setUseTransaction($v)
    {
        $this->useTransaction = (boolean) $v;
    }

    /**
     * called by BasePeer to determine whether the sql command specified by
     * this criteria must be wrapped in a transaction.
     *
     * @return a <code>boolean</code> value
     */
    public function isUseTransaction()
    {
        return $this->useTransaction;
    }

    /**
     * Method to return criteria related to columns in a table.
     *
	 * @param string $column Column name.
     * @return A Criterion or null if $column is invalid.
     */
    public function getCriterion($column)
    {
		if (isset($this->map[$column])) {
		    return $this->map[$column];
		}
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
    public function getNewCriterion($column, $value, $comparison = null)
    {
        return new Criterion($this, $column, $value, $comparison);
    }

    /**
     * Method to return a String table name.
     *
     * @param name A String with the name of the key.
     * @return A String with the value of the object at key.
     */
    public function getColumnName($name)
    {
        $c = isset($this->map[$name]) ? $this->map[$name] : null;
        $val = null;
        if ($c !== null) {
            $val = $c->getColumn();
        }
        return $val;
    }

    /**
     * Shortcut method to get an array of columns indexed by table.
     * @return array array(table => array(table.column1, table.column2))
     */
    function getTablesColumns()
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
    public function getComparison($key)
    {
        $c = isset($this->map[$key]) ? $this->map[$key] : null;
        $val = null;
        if ($c !== null) {
            $val = $c->getComparison();
        }
        return $val;
    }
    
    /**
     * Get the Database(Map) name.
     *
     * @return string A String with the Database(Map) name.
     */
    public function getDbName()
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
    public function setDbName($dbName = null)
    {
        $this->dbName = ($dbName === null ? Propel::getDefaultDB() : $dbName);
    }

    /**
     * Method to return a String table name.
     *
     * @param $name A String with the name of the key.
     * @return string A String with the value of table for criterion at key.
     */
    public function getTableName($name)
    {
        $c = isset($this->map[$name]) ? $this->map[$name] : null;
        $val = null;
        if ($c !== null) {
            $val = $c->getTable();
        }
        return $val;
    }

    /**
     * Method to return the value that was added to Criteria.
     *
     * @param string $name A String with the name of the key.
     * @return mixed The value of object at key.
     */
    public function getValue($name)
    {
        $c = isset($this->map[$name]) ? $this->map[$name] : null;
        $val = null;
        if ($c !== null) {
            $val = $c->getValue();
        }
        return $val;
    }     

    /**
     * An alias to getValue() -- exposing a Hashtable-like interface.
     *
     * @param string $key An Object.
     * @return mixed The value within the Criterion (not the Criterion object).
     */
    public function get($key)
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
     * @param mixed $value
     * @return Instance of self.
     */
    public function put($key, $value)
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
    public function putAll($t)
    {
    
        if (is_array($t)) {
        
            $keys = array_keys($t);
            foreach ($keys as $key) {
                $val = $t[$key];
                if ($val instanceof Criterion) {
                    $this->map[$key] = $val;
                } else {
                    // put throws an exception ... right?
                    // otherwise there's no difference ... not sure why this is here
                    // %%%
                    $this->put($key, $val);
                }
            }
            
        } elseif ($t instanceof Criteria) {
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
     * $crit = new Criteria();
     * $crit->add(&quot;column&quot;,
     *                                      &quot;value&quot;
     *                                      &quot;Criteria::GREATER_THAN&quot;);
     * </code>
     *
     * Any comparison can be used.
     *
     * The name of the table must be used implicitly in the column name,
     * so the Column name must be something like 'TABLE.id'. If you
     * don't like this, you can use the add(table, column, value) method.
     *
     * @param string $critOrColumn The column to run the comparison on, or Criterion object.
     * @param mixed $value
     * @param string $comparison A String.
     *
     * @return A modified Criteria object.
     */
    public function add($p1, $value = null, $comparison = null)
    {
        if ($p1 instanceof Criterion) {
            $c = $p1;
            $this->map[$c->getTable() . '.' . $c->getColumn()] = $c;
        } else {
            $column = $p1;
            $this->map[$column] = new Criterion($this, $column, $value, $comparison);
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
    public function addJoin($left, $right)
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
    public function getJoinL()
    {
        return $this->joinL;
    }

    /**
     * get one side of the set of possible joins.  This method is meant to
     * be called by BasePeer.
     * @return array
     */
    public function getJoinR()
    {
        return $this->joinR;
    }    
    
    /**
     * Adds "ALL " to the SQL statement.
     * @return void
     */
    public function setAll()
    {
        $this->selectModifiers[] = self::ALL;
    }

    /**
     * Adds "DISTINCT " to the SQL statement.
     * @return void
     */
    public function setDistinct()
    {
        $this->selectModifiers[] = self::DISTINCT;
    }

    /**
     * Sets ignore case.
     *
     * @param boolean $b True if case should be ignored.
     * @return A modified Criteria object.
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreCase = (boolean) $b;
        return $this;
    }

    /**
     * Is ignore case on or off?
     *
     * @return boolean True if case is ignored.
     */
    public function isIgnoreCase()
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
     * @param b set to <code>true</code> if you expect the query to select just
     * one record.
     * @return A modified Criteria object.
     */
    public function setSingleRecord($b)
    {
        $this->singleRecord = (boolean) $b;
        return $this;
    }

    /**
     * Is single record?
     *
     * @return boolean True if a single record is being returned.
     */
    public function isSingleRecord()
    {
        return $this->singleRecord;
    }
    
    /**
     * Set limit.
     *
     * @param limit An int with the value for limit.
     * @return A modified Criteria object.
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Get limit.
     *
     * @return int An int with the value for limit.
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Set offset.
     *
     * @param int $offset An int with the value for offset.
     * @return A modified Criteria object.
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get offset.
     *
     * @return An int with the value for offset.
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Add select column.
     *
     * @param name A String with the name of the select column.
     * @return A modified Criteria object.
     */
    public function addSelectColumn($name)
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
    public function getSelectColumns()
    {
        return $this->selectColumns;
    }
    
    /**
     * Clears current select columns.
     * 
     * @return Criteria A modified Criteria object.
     */
    public function clearSelectColumns() {
        $this->selectColumns = array();
		$this->asColumns= array();
        return $this;
    }

    /**
     * Get select modifiers.
     *
     * @return An array with the select modifiers.
     */
    public function getSelectModifiers()
    {
        return $this->selectModifiers;
    }
    
    /**
     * Add group by column name.
     *
     * @param string $groupBy The name of the column to group by.
     * @return A modified Criteria object.
     */
    public function addGroupByColumn($groupBy)
    {
        $this->groupByColumns[] = $groupBy;
        return $this;
    }

    /**
     * Add order by column name, explicitly specifying ascending.
     *
     * @param name The name of the column to order by.
     * @return A modified Criteria object.
     */
    public function addAscendingOrderByColumn($name)
    {
        $this->orderByColumns[] = $name . ' ' . self::ASC;
        return $this;
    }

    /**
     * Add order by column name, explicitly specifying descending.
     *
     * @param string $name The name of the column to order by.
     * @return Criteria The modified Criteria object.
     */
    public function addDescendingOrderByColumn($name)
    {
        $this->orderByColumns[] = $name . ' ' . self::DESC;
        return $this;
    }

    /**
     * Get order by columns.
     *
     * @return array An array with the name of the order columns.
     */
    public function getOrderByColumns()
    {
        return $this->orderByColumns;
    }
    
    /**
     * Clear the order-by columns.
     * 
     * @return Criteria 
     */
    public function clearOrderByColumns()
    {
        $this->orderByColumns = array();
        return $this;
    }
    
    /**
     * Clear the group-by columns.
     * 
     * @return Criteria 
     */
    public function clearGroupByColumns()
    {
        $this->groupByColumns = array();
        return $this;
    }
    
    /**
     * Get group by columns.
     *
     * @return array
     */
    public function getGroupByColumns()
    {
        return $this->groupByColumns;
    }

    /**
     * Get Having Criterion.
     *
     * @return Criterion A Criterion object that is the having clause.
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Remove an object from the criteria.
     *
     * @param string $key A string with the key to be removed.
     * @return mixed The removed value.
     */
    public function remove($key)
    {
        $c = isset($this->map[$key]) ? $this->map[$key] : null;
        unset($this->map[$key]);
        if ($c instanceof Criterion) {
            return $c->getValue();
        }
        return $c;
    }

    /**
     * Build a string representation of the Criteria.
     *
     * @return string A String with the representation of the Criteria.
     */
    public function toString()
    {
        $sb = "Criteria:: ";        

        try {
            
            $sb .= "\nCurrent Query SQL (may not be complete or applicable): "
              . BasePeer::createSelectSql($this, $params=array());
             
            $sb .= "\nParameters to replace: " . var_export($params, true);
 
        } catch (Exception $exc) {
            $sb .= "(Error: " . $exc->getMessage() . ")";
        }

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
    public function equals($crit)
    {
        $isEquiv = false;
        if ($crit === null || !($crit instanceof Criteria)) {
            $isEquiv = false;
        } elseif ($this === $crit) {
            $isEquiv = true;
        } elseif ($this->size() === $crit->size()) {
            
            // Important: nested criterion objects are checked
            
            $criteria = $crit; // alias
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
     * $c = $crit->getNewCriterion(BasePeer::ID, 5, Criteria::LESS_THAN);
     * $crit->addHaving($c);
     * </code>
     *
     * @param having A Criterion object
     *
     * @return A modified Criteria object.
     */
    public function addHaving(Criterion $having)
    {
        $this->having = $having;
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
     *
     * @return Criteria A modified Criteria object.
     */
    public function addAnd($p1, $p2 = null, $p3 = null)
    {        
        if ($p3 !== null) {            
            // addAnd(column, value, comparison)
            $oc = $this->getCriterion($p1);
            $nc = new Criterion($this, $p1, $p2, $p3);
            if ($oc === null) {
                $this->map[$p1] = $nc;
            } else {
                $oc->addAnd($nc);
            }
        } elseif ($p2 !== null) {
            // addAnd(column, value)
            $this->addAnd($p1, $p2, self::EQUAL);
        } elseif ($p1 instanceof Criterion) {
            // addAnd(Criterion)
            $c = $p1;
            $oc = $this->getCriterion($c->getTable() . '.' . $c->getColumn());
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
     *                                      &quot;value&quot;
     *                                      &quot;Criterion::GREATER_THAN&quot;);
     * </code>
     *
     * addOr(column, value)
     * <code>
     * $crit = $orig_crit->addOr(&quot;column&quot;, &quot;value&quot;);
     * </code>
     * 
     * addOr(Criterion)
     * 
     * @return Criteria A modified Criteria object.
     */
    public function addOr($p1, $p2 = null, $p3 = null)
    {
        if ($p3 !== null) {            
            // addOr(column, value, comparison)
            $oc = $this->getCriterion($p1);
            $nc = new Criterion($this, $p1, $p2, $p3);
            if ($oc === null) {
                $this->map[$p1] = $nc;
            } else {
                $oc->addOr($nc);
            }                        
        } elseif ($p2 !== null) {
            // addOr(column, value)
            $this->addOr($p1, $p2, self::EQUAL);
        } elseif ($p1 instanceof Criterion) {
            // addOr(Criterion)
            $c = $p1;
            $oc = $this->getCriterion($c->getTable() . '.' . $c->getColumn());
            if ($oc === null) {
                $this->add($c);
            } else {
                $oc->addOr($c);
            }
        }
                                    
        return $this;
    }   
}

// --------------------------------------------------------------------
// Criterion Iterator class -- allows foreach($criteria as $criterion)
// --------------------------------------------------------------------

/**
 * Class that implements SPL Iterator interface.  This allows foreach() to 
 * be used w/ Criteria objects.  Probably there is no performance advantage
 * to doing it this way, but it makes sense -- and simpler code.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.util
 */
class CriterionIterator implements Iterator {

    private $idx = 0;
    private $criteria;
    private $criteriaKeys;
    private $criteriaSize;
    
    public function __construct($criteria) {
        $this->criteria = $criteria;
        $this->criteriaKeys = $criteria->keys();
        $this->criteriaSize = count($this->criteriaKeys);
    }

    public function rewind() {
        $this->idx = 0;
    }
    
    public function valid() {
        return $this->idx < $this->criteriaSize;
    }
    
    public function key() {
        return $this->criteriaKeys[$this->idx];
    }
    
    public function current() {
        return $this->criteria->getCriterion($this->criteriaKeys[$this->idx]);
    }
    
    public function next() {
        $this->idx++;
    }

}

// --------------------------------------------------------------------
// Criterion "inner" class
// --------------------------------------------------------------------

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * In Torque this is an inner class of the Criteria class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @package propel.util
 */
class Criterion  {

    const UND = " AND ";
    const ODER = " OR ";

    /** Value of the CO. */
    private $value;

    /** Comparison value. 
     * @var SqlEnum
     */
    private $comparison;

    /** Table name. */
    private $table;

    /** Column name. */
    private $column;

    /** flag to ignore case in comparision */
    private $ignoreStringCase = false;

    /**
     * The DBAdaptor which might be used to get db specific
     * variations of sql.
     */
    private $db;

    /**
     * other connected criteria and their conjunctions.
     */
    private $clauses = array();
    private $conjunctions = array();

    /** "Parent" Criteria class */
    private $parent;
    
    /**
     * Create a new instance.
     *
     * @param Criteria $parent The outer class (this is an "inner" class).
     * @param string $column TABLE.COLUMN format.
     * @param mixed $value
     * @param string $comparison
     */
    public function __construct(Criteria $outer, $column, $value, $comparison = null)
    {
        $this->outer = $outer;        
        list($this->table, $this->column) = explode('.', $column);        
        $this->value = $value;
        $this->comparison = ($comparison === null ? Criteria::EQUAL : $comparison);
    }
        
    /**
     * Get the column name.
     *
     * @return string A String with the column name.
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set the table name.
     *
     * @param name A String with the table name.
     * @return void
     */
    public function setTable($name)
    {
        $this->table = $name;
    }

    /**
     * Get the table name.
     *
     * @return string A String with the table name.
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the comparison.
     *
     * @return string A String with the comparison.
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * Get the value.
     *
     * @return mixed An Object with the value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the value of db.
     * The DBAdapter which might be used to get db specific
     * variations of sql.
     * @return DBAdapter value of db.
     */
    public function getDB()
    {
        $db = null;
        if ($this->db === null) {
            // db may not be set if generating preliminary sql for
            // debugging.
            try {
                $db = Propel::getDB($this->outer->getDbName());
            } catch (Exception $e) {
                // we are only doing this to allow easier debugging, so
                // no need to throw up the exception, just make note of it.
                Propel::log("Could not get a DBAdapter, so sql may be wrong", Propel::LOG_ERR);
            }
        } else {
            $db = $this->db;
        }

        return $db;
    }

    /**
     * Set the value of db.
     * The DBAdapter might be used to get db specific variations of sql.
     * @param DBAdapter $v Value to assign to db.
     * @return void
     */
    public function setDB(DBAdapter $v)
    {
        $this->db = $v;
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
    public function setIgnoreCase($b)
    {
        $this->ignoreStringCase = $b;
        return $this;
    }

    /**
     * Is ignore case on or off?
     *
     * @return boolean True if case is ignored.
     */
     public function isIgnoreCase()
     {
         return $this->ignoreStringCase;
     }

    /**
     * Get the list of clauses in this Criterion.
     * @return array
     */
    private function getClauses()
    {
        return $this->clauses;
    }

    /**
     * Get the list of conjunctions in this Criterion
     * @return array
     */
    private function getConjunctions()
    {
        return $this->conjunctions;
    }

    /**
     * Append an AND Criterion onto this Criterion's list.
     */
    public function addAnd(Criterion $criterion)
    {
        $this->clauses[] = $criterion;
        $this->conjunctions[] = self::UND;
        return $this;
    }

    /**
     * Append an OR Criterion onto this Criterion's list.
     * @return Criterion
     */
    public function addOr(Criterion $criterion)
    {
        $this->clauses[] = $criterion;
        $this->conjunctions[] = self::ODER;
        return $this;
    }    

    /**
     * Appends a Prepared Statement representation of the Criterion
     * onto the buffer.
     *
     * @param string &$sb The stringbuffer that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters
     * will be appended
     * @return void
     * @throws PropelException - if the expression builder cannot figure out how to turn a specified 
     *                           expression into proper SQL.
     */
    public function appendPsTo(&$sb, &$params)
    {
        if ($this->column === null) {
            return;
        }

        $db = $this->getDb();
        $clausesLength = count($this->clauses);
        for($j = 0; $j < $clausesLength; $j++) {
            $sb .= '(';
        }
        
        if (Criteria::CUSTOM === $this->comparison) {
            if ($this->value !== "") {
                $sb .= (string) $this->value;
            }
        } else {
        
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
            if ($this->comparison === Criteria::IN || $this->comparison === Criteria::NOT_IN) {
                
                $sb .= $field . $this->comparison;
                $values = (array) $this->value;                
                for ($i=0, $valuesLength=count($values); $i < $valuesLength; $i++) {
                    $params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $values[$i]);
                }
                $inString = '(' . substr(str_repeat("?,", $valuesLength), 0, -1) . ')';
                $sb .= $inString;
            
            // OPTION 2:  table.column LIKE ? or table.column NOT LIKE ?  (or ILIKE for Postgres)
            } elseif ($this->comparison === Criteria::LIKE || $this->comparison === Criteria::NOT_LIKE 
                || $this->comparison === Criteria::ILIKE || $this->comparison === Criteria::NOT_ILIKE) {
                // Handle LIKE, NOT LIKE (and related ILIKE, NOT ILIKE for Postgres)
                
                // If selection is case insensitive use ILIKE for PostgreSQL or SQL 
                // UPPER() function on column name for other databases.
                if ($this->ignoreStringCase) {
                    include_once 'propel/adapter/DBPostgres.php'; // for instanceof, since is_a() is not E_STRICT
                    if ($db instanceof DBPostgres) { // use is_a() because instanceof needs class to have been loaded
                        if ($this->comparison === Criteria::LIKE) {
                            $this->comparison = Criteria::ILIKE; 
                        } elseif ($this->comparison === Criteria::NOT_LIKE) {
                            $this->comparison = Criteria::NOT_ILIKE; 
                          }
                    } else {
                        $field = $db->ignoreCase($field);
                    }
                }
                
                $sb .= $field . $this->comparison;
        
                // If selection is case insensitive use SQL UPPER() function
                // on criteria or, if Postgres we are using ILIKE, so not necessary.
                if ($this->ignoreStringCase && !($db instanceof DBPostgres)) {
                    $sb .= $db->ignoreCase('?');
                } else {
                    $sb .= '?';
                }
                
                $params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $this->value);
            
            // OPTION 3:  table.column = ? or table.column >= ? etc. (traditional expressions, the default)
            } else {            
            
                // NULL VALUES need special treatment because the SQL syntax is different
                // i.e. table.column IS NULL rather than table.column = null
                if ($this->value !== null) {
                
                    // ANSI SQL functions get inserted right into SQL (not escaped, etc.)                    
                    if ($this->value === Criteria::CURRENT_DATE || $this->value === Criteria::CURRENT_TIME) {
                        $sb .= $field . $this->comparison . $this->value;
                    } else {
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
                } else {
                    
                    // value is null, which means it was either not specified or specifically
                    // set to null.                    
                    if ($this->comparison === Criteria::EQUAL || $this->comparison === Criteria::ISNULL) {
                        $sb .= $field . Criteria::ISNULL;
                    } elseif ($this->comparison === Criteria::NOT_EQUAL || $this->comparison === Criteria::ISNOTNULL) {
                        $sb .= $field . Criteria::ISNOTNULL;
                    } else {
                        // for now throw an exception, because not sure how to interpret this
                        throw new PropelException("Could not build SQL for expression: $field " . $this->comparison . " NULL");
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
     * This method checks another Criteria to see if they contain
     * the same attributes and hashtable entries.
     * @return boolean
     */
    public function equals($obj)
    {
        if ($this === $obj) {
            return true;
        }

        if (($obj === null) || !($obj instanceof Criterion)) {
            return false;
        }

        $crit = $obj;

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
        
		if ($isEquiv) {
		    $isEquiv &= $this->value === $crit->getValue();
		}
		
        return $isEquiv;
    }

    /**
     * Returns a hash code value for the object.
     */
    public function hashCode()
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
            $this->clauses[$i]->appendPsTo($sb="", $params=array());
            $h ^= crc32(serialize(array($sb, $params)));
        }

        return $h;
    }

    /**
     * Get all tables from nested criterion objects
     * @return array
     */
    public function getAllTables()
    {
        $tables = array();
        $this->addCriterionTable($this, $tables);
        return $tables;
    }

    /**
     * method supporting recursion through all criterions to give
     * us a string array of tables from each criterion
     * @return void
     */
    private function addCriterionTable(Criterion $c, &$s)
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
    public function getAttachedCriterion()
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
     */
    private function traverseCriterion(Criterion $c, &$a)
    {        
        $a[] = $c;
        $clauses = $c->getClauses();
        $clausesLength = count($clauses);
        for($i=0; $i < $clausesLength; $i++) {
            $this->traverseCriterion($clauses[$i], $a);
        }        
    }
}
