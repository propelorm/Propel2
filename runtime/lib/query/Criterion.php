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
 * This is an "inner" class that describes an object in the criteria.
 *
 * In Torque this is an inner class of the Criteria class.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @version    $Revision: 1312 $
 * @package    propel.runtime.query
 */
class Criterion
{

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