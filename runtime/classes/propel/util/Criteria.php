<?php
/*
 *  $Id$
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
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Kaspars Jaudzems <kaspars.jaudzems@inbox.lv> (Propel)
 * @author     Frank Y. Kim <frank.kim@clearink.com> (Torque)
 * @author     John D. McNally <jmcnally@collab.net> (Torque)
 * @author     Brett McLaughlin <bmclaugh@algx.net> (Torque)
 * @author     Eric Dobbs <eric@dobbse.net> (Torque)
 * @author     Henning P. Schmiedehausen <hps@intermeta.de> (Torque)
 * @author     Sam Joseph <sam@neurogrid.com> (Torque)
 * @version    $Revision$
 * @package    propel.util
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

	/** Comparison type for update */
	const CUSTOM_EQUAL = "CUSTOM_EQUAL";

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

	/** Binary math operator: AND */
	const BINARY_AND = "&";

	/** Binary math operator: OR */
	const BINARY_OR = "|";

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

	/** "LEFT JOIN" SQL statement */
	const LEFT_JOIN = "LEFT JOIN";

	/** "RIGHT JOIN" SQL statement */
	const RIGHT_JOIN = "RIGHT JOIN";

	/** "INNER JOIN" SQL statement */
	const INNER_JOIN = "INNER JOIN";

	private $ignoreCase = false;
	private $singleRecord = false;
	private $selectModifiers = array();
	private $selectColumns = array();
	private $orderByColumns = array();
	private $groupByColumns = array();
	private $having = null;
	private $asColumns = array();
	private $joins = array();

	/** The name of the database. */
	private $dbName;

	/**
	 * The primary table for this Criteria.
	 * Useful in cases where there are no select or where
	 * columns.
	 * @var        string
	 */
	private $primaryTableName;

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

	private $aliases = array();

	private $useTransaction = false;

	/**
	 * Primary storage of criteria data.
	 * @var        array
	 */
	private $map = array();

	/**
	 * Creates a new instance with the default capacity which corresponds to
	 * the specified database.
	 *
	 * @param      dbName The dabase name.
	 */
	public function __construct($dbName = null)
	{
		$this->setDbName($dbName);
		$this->originalDbName = $dbName;
	}

	/**
	 * Implementing SPL IteratorAggregate interface.  This allows
	 * you to foreach () over a Criteria object.
	 */
	public function getIterator()
	{
		return new CriterionIterator($this);
	}

	/**
	 * Get the criteria map.
	 * @return     array
	 */
	public function getMap()
	{
		return $this->map;
	}

	/**
	 * Brings this criteria back to its initial state, so that it
	 * can be reused as if it was new. Except if the criteria has grown in
	 * capacity, it is left at the current capacity.
	 * @return     void
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
		$this->joins = array();
		$this->dbName = $this->originalDbName;
		$this->offset = 0;
		$this->limit = -1;
		$this->blobFlag = null;
		$this->aliases = array();
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
	 * @param      string $name Wanted Name of the column (alias).
	 * @param      string $clause SQL clause to select from the table
	 *
	 * If the name already exists, it is replaced by the new clause.
	 *
	 * @return     Criteria A modified Criteria object.
	 */
	public function addAsColumn($name, $clause)
	{
		$this->asColumns[$name] = $clause;
		return $this;
	}

	/**
	 * Get the column aliases.
	 *
	 * @return     array An assoc array which map the column alias names
	 * to the alias clauses.
	 */
	public function getAsColumns()
	{
		return $this->asColumns;
	}

		/**
	 * Returns the column name associated with an alias (AS-column).
	 *
	 * @param      string $alias
	 * @return     string $string
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
	 * @param      string $alias
	 * @param      string $table
	 * @return     void
	 */
	public function addAlias($alias, $table)
	{
		$this->aliases[$alias] = $table;
	}

	/**
	 * Returns the table name associated with an alias.
	 *
	 * @param      string $alias
	 * @return     string $string
	 */
	public function getTableForAlias($alias)
	{
		if (isset($this->aliases[$alias])) {
			return $this->aliases[$alias];
		}
	}

	/**
	 * Get the keys for the criteria map.
	 * @return     array
	 */
	public function keys()
	{
		return array_keys($this->map);
	}

	/**
	 * Does this Criteria object contain the specified key?
	 *
	 * @param      string $column [table.]column
	 * @return     boolean True if this Criteria object contain the specified key.
	 */
	public function containsKey($column)
	{
		// must use array_key_exists() because the key could
		// exist but have a NULL value (that'd be valid).
		return array_key_exists($column, $this->map);
	}

	/**
	 * Does this Criteria object contain the specified key and does it have a value set for the key 
	 *
	 * @param      string $column [table.]column
	 * @return     boolean True if this Criteria object contain the specified key and a value for that key
	 */
	public function keyContainsValue($column)
	{
		// must use array_key_exists() because the key could
		// exist but have a NULL value (that'd be valid).
		return (array_key_exists($column, $this->map) && ($this->map[$column]->getValue() !== null) );
	}

	/**
	 * Will force the sql represented by this criteria to be executed within
	 * a transaction.  This is here primarily to support the oid type in
	 * postgresql.  Though it can be used to require any single sql statement
	 * to use a transaction.
	 * @return     void
	 */
	public function setUseTransaction($v)
	{
		$this->useTransaction = (boolean) $v;
	}

	/**
	 * Whether the sql command specified by this criteria must be wrapped
	 * in a transaction.
	 *
	 * @return     boolean
	 */
	public function isUseTransaction()
	{
		return $this->useTransaction;
	}

	/**
	 * Method to return criteria related to columns in a table.
	 *
	 * @param      string $column Column name.
	 * @return     Criterion A Criterion or null if $column is invalid.
	 */
	public function getCriterion($column)
	{
		if ( isset ( $this->map[$column] ) ) {
			return $this->map[$column];
		}
		return null;
	}

	/**
	 * Method to return criterion that is not added automatically
	 * to this Criteria.  This can be used to chain the
	 * Criterions to form a more complex where clause.
	 *
	 * @param      string $column Full name of column (for example TABLE.COLUMN).
	 * @param      mixed $value
	 * @param      string $comparison
	 * @return     Criterion
	 */
	public function getNewCriterion($column, $value, $comparison = null)
	{
		return new Criterion($this, $column, $value, $comparison);
	}

	/**
	 * Method to return a String table name.
	 *
	 * @param      string $name Name of the key.
	 * @return     string The value of the object at key.
	 */
	public function getColumnName($name)
	{
		if (isset($this->map[$name])) {
			return $this->map[$name]->getColumn();
		}
		return null;
	}

	/**
	 * Shortcut method to get an array of columns indexed by table.
	 * @return     array array(table => array(table.column1, table.column2))
	 */
	public function getTablesColumns()
	{
		$tables = array();
		foreach ( array_keys ( $this->map ) as $key) {
			$t = substr ( $key, 0, strrpos ( $key, '.' ) );
			if ( ! isset ( $tables[$t] ) ) {
				$tables[$t] = array( $key );
			} else {
				$tables[$t][] = $key;
			}
		}
		return $tables;
	}

	/**
	 * Method to return a comparison String.
	 *
	 * @param      string $key String name of the key.
	 * @return     string A String with the value of the object at key.
	 */
	public function getComparison($key)
	{
		if ( isset ( $this->map[$key] ) ) {
			return $this->map[$key]->getComparison();
		}
		return null;
	}

	/**
	 * Get the Database(Map) name.
	 *
	 * @return     string A String with the Database(Map) name.
	 */
	public function getDbName()
	{
		return $this->dbName;
	}

	/**
	 * Set the DatabaseMap name.  If <code>null</code> is supplied, uses value
	 * provided by <code>Propel::getDefaultDB()</code>.
	 *
	 * @param      string $dbName The Database (Map) name.
	 * @return     void
	 */
	public function setDbName($dbName = null)
	{
		$this->dbName = ($dbName === null ? Propel::getDefaultDB() : $dbName);
	}

	/**
	 * Get the primary table for this Criteria.
	 *
	 * This is useful for cases where a Criteria may not contain
	 * any SELECT columns or WHERE columns.  This must be explicitly
	 * set, of course, in order to be useful.
	 *
	 * @return     string
	 */
	public function getPrimaryTableName()
	{
		return $this->primaryTableName;
	}

	/**
	 * Sets the primary table for this Criteria.
	 *
	 * This is useful for cases where a Criteria may not contain
	 * any SELECT columns or WHERE columns.  This must be explicitly
	 * set, of course, in order to be useful.
	 *
	 * @param      string $v
	 */
	public function setPrimaryTableName($tableName)
	{
		$this->primaryTableName = $tableName;
	}

	/**
	 * Method to return a String table name.
	 *
	 * @param      string $name The name of the key.
	 * @return     string The value of table for criterion at key.
	 */
	public function getTableName($name)
	{
		if (isset($this->map[$name])) {
			return $this->map[$name]->getTable();
		}
		return null;
	}

	/**
	 * Method to return the value that was added to Criteria.
	 *
	 * @param      string $name A String with the name of the key.
	 * @return     mixed The value of object at key.
	 */
	public function getValue($name)
	{
		if (isset($this->map[$name])) {
			return $this->map[$name]->getValue();
		}
		return null;
	}

	/**
	 * An alias to getValue() -- exposing a Hashtable-like interface.
	 *
	 * @param      string $key An Object.
	 * @return     mixed The value within the Criterion (not the Criterion object).
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
	 * @param      string $key
	 * @param      mixed $value
	 * @return     Instance of self.
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
	 * @param      mixed $t Mappings to be stored in this map.
	 */
	public function putAll($t)
	{

		if (is_array($t)) {

			foreach ($t as $key=>$value) {

				if ($value instanceof Criterion) {

					$this->map[$key] = $value;

				} else {

					$this->put($key, $value);

				}

			}

		} elseif ($t instanceof Criteria) {

			$this->joins = $t->joins;

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
	 * @param      string $critOrColumn The column to run the comparison on, or Criterion object.
	 * @param      mixed $value
	 * @param      string $comparison A String.
	 *
	 * @return     A modified Criteria object.
	 */
	public function add($p1, $value = null, $comparison = null)
	{
		if ($p1 instanceof Criterion) {
			$this->map[$p1->getTable() . '.' . $p1->getColumn()] = $p1;
		} else {
			$this->map[$p1] = new Criterion($this, $p1, $value, $comparison);
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
	 * @param      mixed $left A String with the left side of the join.
	 * @param      mixed $right A String with the right side of the join.
	 * @param      mixed $operator A String with the join operator e.g. LEFT JOIN, ...
   *
	 * @return     Criteria A modified Criteria object.
	 */
	public function addJoin($left, $right, $operator = null)
	{
		$join = new Join();
    if (!is_array($left)) {
      // simple join
      $join->addCondition($left, $right);
    } else {
      // join with multiple conditions
      // deprecated: use addMultipleJoin() instead
      foreach ($left as $key => $value)
      {
        $join->addCondition($value, $right[$key]);
      }
    }
		$join->setJoinType($operator);
		
		return $this->addJoinObject($join);
	}

	/**
	 * Add a join with multiple conditions
	 * see http://propel.phpdb.org/trac/ticket/167, http://propel.phpdb.org/trac/ticket/606
	 * 
	 * Example usage:
	 * $c->addMultipleJoin(array(
	 *     array(LeftPeer::LEFT_COLUMN, RightPeer::RIGHT_COLUMN),  // if no third argument, defaults to Criteria::EQUAL
	 *     array(FoldersPeer::alias( 'fo', FoldersPeer::LFT ), FoldersPeer::alias( 'parent', FoldersPeer::RGT ), Criteria::LESS_EQUAL )
	 *   ),
	 *   Criteria::LEFT_JOIN
 	 * );
	 * 
	 * @see        addJoin()
	 * @param      array $conditions An array of conditions, each condition being an array (left, right, operator)
	 * @param      string $joinType  A String with the join operator. Defaults to an implicit join.
	 *
	 * @return     Criteria A modified Criteria object.
	 */
	public function addMultipleJoin($conditions, $joinType = null) 
  {
		$join = new Join();
		foreach ($conditions as $condition) {
		  $join->addCondition($condition[0], $condition[1], isset($condition[2]) ? $condition[2] : Criteria::EQUAL);
		}
		$join->setJoinType($joinType);
		
		return $this->addJoinObject($join);
	}
	
	/**
	 * Add a join object to the Criteria
	 *
	 * @param Join $join A join object
	 *
	 * @return Criteria A modified Criteria object
	 */
	public function addJoinObject(Join $join)
	{
	  if (!in_array($join, $this->joins)) { // compare equality, NOT identity
			$this->joins[] = $join;
		}
		return $this;
	}


	/**
	 * Get the array of Joins.
	 * @return     array Join[]
	 */
	public function getJoins()
	{
		return $this->joins;
	}

	/**
	 * Adds "ALL" modifier to the SQL statement.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function setAll()
	{
		$this->selectModifiers[] = self::ALL;
		return $this;
	}

	/**
	 * Adds "DISTINCT" modifier to the SQL statement.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function setDistinct()
	{
		$this->selectModifiers[] = self::DISTINCT;
		return $this;
	}

	/**
	 * Sets ignore case.
	 *
	 * @param      boolean $b True if case should be ignored.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function setIgnoreCase($b)
	{
		$this->ignoreCase = (boolean) $b;
		return $this;
	}

	/**
	 * Is ignore case on or off?
	 *
	 * @return     boolean True if case is ignored.
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
	 * @param      boolean $b Set to TRUE if you expect the query to select just one record.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function setSingleRecord($b)
	{
		$this->singleRecord = (boolean) $b;
		return $this;
	}

	/**
	 * Is single record?
	 *
	 * @return     boolean True if a single record is being returned.
	 */
	public function isSingleRecord()
	{
		return $this->singleRecord;
	}

	/**
	 * Set limit.
	 *
	 * @param      limit An int with the value for limit.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function setLimit($limit)
	{
		// TODO: do we enforce int here? 32bit issue if we do
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Get limit.
	 *
	 * @return     int An int with the value for limit.
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * Set offset.
	 *
	 * @param      int $offset An int with the value for offset.  (Note this values is
	 * 							cast to a 32bit integer and may result in truncatation)
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function setOffset($offset)
	{
		$this->offset = (int) $offset;
		return $this;
	}

	/**
	 * Get offset.
	 *
	 * @return     An int with the value for offset.
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * Add select column.
	 *
	 * @param      string $name Name of the select column.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function addSelectColumn($name)
	{
		$this->selectColumns[] = $name;
		return $this;
	}
	
	/**
	 * Whether this Criteria has any select columns.
	 * 
	 * This will include columns added with addAsColumn() method.
	 *
	 * @return     boolean
	 * @see        addAsColumn()
	 * @see        addSelectColumn()
	 */
	public function hasSelectClause()
	{
		return (!empty($this->selectColumns) || !empty($this->asColumns));
	}
	
	/**
	 * Get select columns.
	 *
	 * @return     array An array with the name of the select
	 * columns.
	 */
	public function getSelectColumns()
	{
		return $this->selectColumns;
	}

	/**
	 * Clears current select columns.
	 *
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function clearSelectColumns() {
		$this->selectColumns = $this->asColumns = array();
		return $this;
	}

	/**
	 * Get select modifiers.
	 *
	 * @return     An array with the select modifiers.
	 */
	public function getSelectModifiers()
	{
		return $this->selectModifiers;
	}

	/**
	 * Add group by column name.
	 *
	 * @param      string $groupBy The name of the column to group by.
	 * @return     A modified Criteria object.
	 */
	public function addGroupByColumn($groupBy)
	{
		$this->groupByColumns[] = $groupBy;
		return $this;
	}

	/**
	 * Add order by column name, explicitly specifying ascending.
	 *
	 * @param      name The name of the column to order by.
	 * @return     A modified Criteria object.
	 */
	public function addAscendingOrderByColumn($name)
	{
		$this->orderByColumns[] = $name . ' ' . self::ASC;
		return $this;
	}

	/**
	 * Add order by column name, explicitly specifying descending.
	 *
	 * @param      string $name The name of the column to order by.
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function addDescendingOrderByColumn($name)
	{
		$this->orderByColumns[] = $name . ' ' . self::DESC;
		return $this;
	}

	/**
	 * Get order by columns.
	 *
	 * @return     array An array with the name of the order columns.
	 */
	public function getOrderByColumns()
	{
		return $this->orderByColumns;
	}

	/**
	 * Clear the order-by columns.
	 *
	 * @return     Criteria Modified Criteria object (for fluent API)
	 */
	public function clearOrderByColumns()
	{
		$this->orderByColumns = array();
		return $this;
	}

	/**
	 * Clear the group-by columns.
	 *
	 * @return     Criteria
	 */
	public function clearGroupByColumns()
	{
		$this->groupByColumns = array();
		return $this;
	}

	/**
	 * Get group by columns.
	 *
	 * @return     array
	 */
	public function getGroupByColumns()
	{
		return $this->groupByColumns;
	}

	/**
	 * Get Having Criterion.
	 *
	 * @return     Criterion A Criterion object that is the having clause.
	 */
	public function getHaving()
	{
		return $this->having;
	}

	/**
	 * Remove an object from the criteria.
	 *
	 * @param      string $key A string with the key to be removed.
	 * @return     mixed The removed value.
	 */
	public function remove($key)
	{
		if ( isset ( $this->map[$key] ) ) {
			$removed = $this->map[$key];
			unset ( $this->map[$key] );
			if ( $removed instanceof Criterion ) {
				return $removed->getValue();
			}
			return $removed;
		}
	}

	/**
	 * Build a string representation of the Criteria.
	 *
	 * @return     string A String with the representation of the Criteria.
	 */
	public function toString()
	{

		$sb = "Criteria:";
		try {

			$params = array();
			$sb .= "\nSQL (may not be complete): "
			  . BasePeer::createSelectSql($this, $params);

			$sb .= "\nParams: ";
			$paramstr = array();
			foreach ($params as $param) {
				$paramstr[] = $param['table'] . '.' . $param['column'] . ' => ' . var_export($param['value'], true);
			}
			$sb .= implode(", ", $paramstr);

		} catch (Exception $exc) {
			$sb .= "(Error: " . $exc->getMessage() . ")";
		}

		return $sb;
	}

	/**
	 * Returns the size (count) of this criteria.
	 * @return     int
	 */
	public function size()
	{
		return count($this->map);
	}

	/**
	 * This method checks another Criteria to see if they contain
	 * the same attributes and hashtable entries.
	 * @return     boolean
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
				&& $this->groupByColumns === $criteria->getGroupByColumns()
			   )
			{
				$isEquiv = true;
				foreach ($criteria->keys() as $key) {
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
	 * @param      having A Criterion object
	 *
	 * @return     A modified Criteria object.
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
	 * @return     Criteria A modified Criteria object.
	 */
	public function addAnd($p1, $p2 = null, $p3 = null)
	{
		if ($p3 !== null) {
			// addAnd(column, value, comparison)
			$oc = $this->getCriterion($p1);
			$nc = new Criterion($this, $p1, $p2, $p3);
			if ( $oc === null) {
				$this->map[$p1] = $nc;
			} else {
				$oc->addAnd($nc);
			}
		} elseif ($p2 !== null) {
			// addAnd(column, value)
			$this->addAnd($p1, $p2, self::EQUAL);
		} elseif ($p1 instanceof Criterion) {
			// addAnd(Criterion)
			$oc = $this->getCriterion($p1->getTable() . '.' . $p1->getColumn());
			if ($oc === null) {
				$this->add($p1);
			} else {
				$oc->addAnd($p1);
			}
		} elseif ($p2 === null && $p3 === null) {
			// client has not specified $p3 (comparison)
			// which means Criteria::EQUAL but has also specified $p2 == null
			// which is a valid combination we should handle by creating "IS NULL"
			$this->addAnd($p1, $p2, self::EQUAL);
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
	 * @return     Criteria A modified Criteria object.
	 */
	public function addOr($p1, $p2 = null, $p3 = null)
	{
		if ($p3 !== null) {
			// addOr(column, value, comparison)
			$nc = new Criterion($this, $p1, $p2, $p3);
			$oc = $this->getCriterion($p1);
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
			$oc = $this->getCriterion($p1->getTable() . '.' . $p1->getColumn());
			if ($oc === null) {
				$this->add($p1);
			} else {
				$oc->addOr($p1);
			}
		} elseif ($p2 === null && $p3 === null) {
			// client has not specified $p3 (comparison)
			// which means Criteria::EQUAL but has also specified $p2 == null
			// which is a valid combination we should handle by creating "IS NULL"
			$this->addOr($p1, $p2, self::EQUAL);
		}

		return $this;
	}
}

// --------------------------------------------------------------------
// Criterion Iterator class -- allows foreach ($criteria as $criterion)
// --------------------------------------------------------------------

/**
 * Class that implements SPL Iterator interface.  This allows foreach () to
 * be used w/ Criteria objects.  Probably there is no performance advantage
 * to doing it this way, but it makes sense -- and simpler code.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.util
 */
class CriterionIterator implements Iterator {

	private $idx = 0;
	private $criteria;
	private $criteriaKeys;
	private $criteriaSize;

	public function __construct(Criteria $criteria) {
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
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @package    propel.util
 */
class Criterion  {

	const UND = " AND ";
	const ODER = " OR ";

	/** Value of the CO. */
	private $value;

	/** Comparison value.
	 * @var        SqlEnum
	 */
	private $comparison;

	/** Table name. */
	private $table;

	/** Real table name */
	private $realtable;

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
	 * @param      Criteria $parent The outer class (this is an "inner" class).
	 * @param      string $column TABLE.COLUMN format.
	 * @param      mixed $value
	 * @param      string $comparison
	 */
	public function __construct(Criteria $outer, $column, $value, $comparison = null)
	{
		$this->value = $value;
		$dotPos = strrpos($column,'.');
		if ($dotPos === false) {
			// no dot => aliased column
			$this->table = null;
			$this->column = $column;
		} else {
			$this->table = substr($column, 0, $dotPos); 
			$this->column = substr($column, $dotPos+1, strlen($column));
		}
		$this->comparison = ($comparison === null ? Criteria::EQUAL : $comparison);
		$this->init($outer);
	}

	/**
	* Init some properties with the help of outer class
	* @param      Criteria $criteria The outer class
	*/
	public function init(Criteria $criteria)
	{
		//init $this->db
		try {
			$db = Propel::getDB($criteria->getDbName());
			$this->setDB($db);
		} catch (Exception $e) {
			// we are only doing this to allow easier debugging, so
			// no need to throw up the exception, just make note of it.
			Propel::log("Could not get a DBAdapter, sql may be wrong", Propel::LOG_ERR);
		}

		//init $this->realtable
		$realtable = $criteria->getTableForAlias($this->table);
		if (! strlen ( $realtable ) ) {
			$realtable = $this->table;
		}
		$this->realtable = $realtable;

	}

	/**
	 * Get the column name.
	 *
	 * @return     string A String with the column name.
	 */
	public function getColumn()
	{
		return $this->column;
	}

	/**
	 * Set the table name.
	 *
	 * @param      name A String with the table name.
	 * @return     void
	 */
	public function setTable($name)
	{
		$this->table = $name;
	}

	/**
	 * Get the table name.
	 *
	 * @return     string A String with the table name.
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Get the comparison.
	 *
	 * @return     string A String with the comparison.
	 */
	public function getComparison()
	{
		return $this->comparison;
	}

	/**
	 * Get the value.
	 *
	 * @return     mixed An Object with the value.
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Get the value of db.
	 * The DBAdapter which might be used to get db specific
	 * variations of sql.
	 * @return     DBAdapter value of db.
	 */
	public function getDB()
	{
		return $this->db;
	}

	/**
	 * Set the value of db.
	 * The DBAdapter might be used to get db specific variations of sql.
	 * @param      DBAdapter $v Value to assign to db.
	 * @return     void
	 */
	public function setDB(DBAdapter $v)
	{
		$this->db = $v;
		foreach ( $this->clauses as $clause ) {
			$clause->setDB($v);
		}
	}

	/**
	 * Sets ignore case.
	 *
	 * @param      boolean $b True if case should be ignored.
	 * @return     Criterion A modified Criterion object.
	 */
	public function setIgnoreCase($b)
	{
		$this->ignoreStringCase = (boolean) $b;
		return $this;
	}

	/**
	 * Is ignore case on or off?
	 *
	 * @return     boolean True if case is ignored.
	 */
	 public function isIgnoreCase()
	 {
		 return $this->ignoreStringCase;
	 }

	/**
	 * Get the list of clauses in this Criterion.
	 * @return     array
	 */
	private function getClauses()
	{
		return $this->clauses;
	}

	/**
	 * Get the list of conjunctions in this Criterion
	 * @return     array
	 */
	public function getConjunctions()
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
	 * @return     Criterion
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
	 * @param      string &$sb The string that will receive the Prepared Statement
	 * @param      array $params A list to which Prepared Statement parameters
	 * will be appended
	 * @return     void
	 * @throws     PropelException - if the expression builder cannot figure out how to turn a specified
	 *                           expression into proper SQL.
	 */
	public function appendPsTo(&$sb, array &$params)
	{
		if ($this->column === null) {
			return;
		}

		$db = $this->getDb();
		$sb .= str_repeat ( '(', count($this->clauses) );

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
			$realtable = $this->realtable;

			// There are several different types of expressions that need individual handling:
			// IN/NOT IN, LIKE/NOT LIKE, and traditional expressions.

			// OPTION 1:  table.column IN (?, ?) or table.column NOT IN (?, ?)
			if ($this->comparison === Criteria::IN || $this->comparison === Criteria::NOT_IN) {
				
				$_bindParams = array(); // the param names used in query building
				$_idxstart = count($params);
				$valuesLength = 0;
				foreach ( (array) $this->value as $value ) {
					$valuesLength++; // increment this first to correct for wanting bind params to start with :p1
					$params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $value);
					$_bindParams[] = ':p'.($_idxstart + $valuesLength);
				}
				if ( $valuesLength !== 0 ) {
					$sb .= $field . $this->comparison . '(' . implode(',', $_bindParams) . ')';
				} else {
					$sb .= ($this->comparison === Criteria::IN) ? "1<>1" : "1=1";
				}
				unset ( $value, $valuesLength );

			// OPTION 2:  table.column LIKE ? or table.column NOT LIKE ?  (or ILIKE for Postgres)
			} elseif ($this->comparison === Criteria::LIKE || $this->comparison === Criteria::NOT_LIKE
				|| $this->comparison === Criteria::ILIKE || $this->comparison === Criteria::NOT_ILIKE) {
				// Handle LIKE, NOT LIKE (and related ILIKE, NOT ILIKE for Postgres)

				// If selection is case insensitive use ILIKE for PostgreSQL or SQL
				// UPPER() function on column name for other databases.
				if ($this->ignoreStringCase) {
					if ($db instanceof DBPostgres) {
						if ($this->comparison === Criteria::LIKE) {
							$this->comparison = Criteria::ILIKE;
						} elseif ($this->comparison === Criteria::NOT_LIKE) {
							$this->comparison = Criteria::NOT_ILIKE;
						}
					} else {
						$field = $db->ignoreCase($field);
					}
				}
				
				$params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $this->value);
				
				$sb .= $field . $this->comparison;

				// If selection is case insensitive use SQL UPPER() function
				// on criteria or, if Postgres we are using ILIKE, so not necessary.
				if ($this->ignoreStringCase && !($db instanceof DBPostgres)) {
					$sb .= $db->ignoreCase(':p'.count($params));
				} else {
					$sb .= ':p'.count($params);
				}
				
			// OPTION 3:  table.column = ? or table.column >= ? etc. (traditional expressions, the default)
			} else {

				// NULL VALUES need special treatment because the SQL syntax is different
				// i.e. table.column IS NULL rather than table.column = null
				if ($this->value !== null) {

					// ANSI SQL functions get inserted right into SQL (not escaped, etc.)
					if ($this->value === Criteria::CURRENT_DATE || $this->value === Criteria::CURRENT_TIME || $this->value === Criteria::CURRENT_TIMESTAMP) {
						$sb .= $field . $this->comparison . $this->value;
					} else {
						
						$params[] = array('table' => $realtable, 'column' => $this->column, 'value' => $this->value);
						
						// default case, it is a normal col = value expression; value
						// will be replaced w/ '?' and will be inserted later using PDO bindValue()
						if ($this->ignoreStringCase) {
							$sb .= $db->ignoreCase($field) . $this->comparison . $db->ignoreCase(':p'.count($params));
						} else {
							$sb .= $field . $this->comparison . ':p'.count($params);
						}
						
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

		foreach ( $this->clauses as $key=>$clause ) {
			$sb .= $this->conjunctions[$key];
			$clause->appendPsTo($sb, $params);
			$sb .= ')';
		}
	}

	/**
	 * This method checks another Criteria to see if they contain
	 * the same attributes and hashtable entries.
	 * @return     boolean
	 */
	public function equals($obj)
	{
		// TODO: optimize me with early outs
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

		foreach ( $this->clauses as $clause ) {
			// TODO: i KNOW there is a php incompatibility with the following line
			// but i dont remember what it is, someone care to look it up and
			// replace it if it doesnt bother us?
			// $clause->appendPsTo($sb='',$params=array());
			$sb = '';
			$params = array();
			$clause->appendPsTo($sb,$params);
			$h ^= crc32(serialize(array($sb,$params)));
			unset ( $sb, $params );
		}

		return $h;
	}

	/**
	 * Get all tables from nested criterion objects
	 * @return     array
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
	 * @return     void
	 */
	private function addCriterionTable(Criterion $c, array &$s)
	{
		$s[] = $c->getTable();
		foreach ( $c->getClauses() as $clause ) {
			$this->addCriterionTable($clause, $s);
		}
	}

	/**
	 * get an array of all criterion attached to this
	 * recursing through all sub criterion
	 * @return     array Criterion[]
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
	 * @param      Criterion $c
	 * @param      array &$a
	 * @return     void
	 */
	private function traverseCriterion(Criterion $c, array &$a)
	{
		$a[] = $c;
		foreach ( $c->getClauses() as $clause ) {
			$this->traverseCriterion($clause, $a);
		}
	}
}