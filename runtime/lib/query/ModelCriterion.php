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
 * @author     Francois
 * @version    $Revision$
 * @package    propel.runtime.query
 */
class ModelCriterion extends Criterion
{		
	protected $clause = '';

	/**
	 * Create a new instance.
	 *
	 * @param      Criteria $parent The outer class (this is an "inner" class).
	 * @param      ColumnMap $column A Column object to help escaping the value
	 * @param      mixed $value
	 * @param      string $comparison, among ModelCriteria::MODEL_CLAUSE
	 * @param      string $clause A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
	 */
	public function __construct(Criteria $outer, $column, $value = null, $comparison = ModelCriteria::MODEL_CLAUSE, $clause)
	{
		$this->value = $value;
		if ($column instanceof ColumnMap) {
			$this->column = $column->getName();
			$this->table = $column->getTable()->getName();
		} else {
			$dotPos = strrpos($column,'.');
			if ($dotPos === false) {
				// no dot => aliased column
				$this->table = null;
				$this->column = $column;
			} else {
				$this->table = substr($column, 0, $dotPos); 
				$this->column = substr($column, $dotPos+1, strlen($column));
			}
		}
		$this->comparison = ($comparison === null ? Criteria::EQUAL : $comparison);
		$this->clause = $clause;
		$this->init($outer);
	}
	
	public function getClause()
	{
		return $this->clause;
	}
	
	/**
	 * Figure out which MocelCriterion method to use 
	 * to build the prepared statement and parameters using to the Criterion comparison
	 * and call it to append the prepared statement and the parameters of the current clause
	 *
	 * @param      string &$sb The string that will receive the Prepared Statement
	 * @param      array $params A list to which Prepared Statement parameters will be appended
	 */
	protected function dispatchPsHandling(&$sb, array &$params)
	{
		switch ($this->comparison) {
			case ModelCriteria::MODEL_CLAUSE:
				// regular model clause, e.g. 'book.TITLE = ?'
				$this->appendModelClauseToPs($sb, $params);
				break;
			case ModelCriteria::MODEL_CLAUSE_LIKE:
				// regular model clause, e.g. 'book.TITLE = ?'
				$this->appendModelClauseLikeToPs($sb, $params);
				break;
			case ModelCriteria::MODEL_CLAUSE_SEVERAL:
				// Ternary model clause, e.G 'book.ID BETWEEN ? AND ?'
				$this->appendModelClauseSeveralToPs($sb, $params);
				break;
			case ModelCriteria::MODEL_CLAUSE_ARRAY:
				// IN or NOT IN model clause, e.g. 'book.TITLE NOT IN ?'
				$this->appendModelClauseArrayToPs($sb, $params);
				break;							
			default:
				// fallback to Criterion methods
				parent::dispatchHandling($sb, $params);
		}
	}

	/**
	 * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
	 * For regular model clauses, e.g. 'book.TITLE = ?'
	 *
	 * @param      string &$sb The string that will receive the Prepared Statement
	 * @param      array $params A list to which Prepared Statement parameters will be appended
	 */
	public function appendModelClauseToPs(&$sb, array &$params)
	{
		if ($this->value !== null) {
			$params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $this->value);
			$sb .= str_replace('?', ':p'.count($params), $this->clause);
		} else {
			$sb .= $this->clause;
		}
	}

	/**
	 * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
	 * For LIKE model clauses, e.g. 'book.TITLE LIKE ?'
	 * Handles case insensitivity for VARCHAR columns
	 *
	 * @param      string &$sb The string that will receive the Prepared Statement
	 * @param      array $params A list to which Prepared Statement parameters will be appended
	 */
	public function appendModelClauseLikeToPs(&$sb, array &$params)
	{
		// LIKE is case insensitive in mySQL and SQLite, but not in PostGres
		// If the column is case insensitive, use ILIKE / NOT ILIKE instead of LIKE / NOT LIKE
		if ($this->ignoreStringCase && $this->getDb() instanceof DBPostgres) {
			$this->clause = preg_replace('/LIKE \?$/i', 'ILIKE ?', $this->clause); 
		}
		$this->appendModelClauseToPs($sb, $params);
	}
	
	/**
	 * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
	 * For ternary model clauses, e.G 'book.ID BETWEEN ? AND ?'
	 *
	 * @param      string &$sb The string that will receive the Prepared Statement
	 * @param      array $params A list to which Prepared Statement parameters will be appended
	 */
	public function appendModelClauseSeveralToPs(&$sb, array &$params)
	{
		$clause = $this->clause;
		foreach ((array) $this->value as $value) {
			if ($value === null) {
				// FIXME we eventually need to translate a BETWEEN to
				// something like WHERE (col < :p1 OR :p1 IS NULL) AND (col < :p2 OR :p2 IS NULL)
				// in order to support null values
				throw new PropelException('Null values are not supported inside BETWEEN clauses');
			}
			$params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $value);
			$clause = self::strReplaceOnce('?', ':p'.count($params), $clause);
		}
		$sb .= $clause;
	}

	/**
	 * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
	 * For IN or NOT IN model clauses, e.g. 'book.TITLE NOT IN ?'
	 *
	 * @param      string &$sb The string that will receive the Prepared Statement
	 * @param      array $params A list to which Prepared Statement parameters will be appended
	 */
	public function appendModelClauseArrayToPs(&$sb, array &$params)
	{
		$_bindParams = array(); // the param names used in query building
		$_idxstart = count($params);
		$valuesLength = 0;
		foreach ( (array) $this->value as $value ) {
			$valuesLength++; // increment this first to correct for wanting bind params to start with :p1
			$params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $value);
			$_bindParams[] = ':p'.($_idxstart + $valuesLength);
		}
		if ($valuesLength !== 0) {
			$sb .= str_replace('?', '(' . implode(',', $_bindParams) . ')', $this->clause);
		} else {
			$sb .= (stripos($this->clause, ' NOT IN ') === false) ? "1=1" : "1<>1";
		}
		unset ( $value, $valuesLength );
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

		if (($obj === null) || !($obj instanceof ModelCriterion)) {
			return false;
		}

		$crit = $obj;

		$isEquiv = ( ( ($this->table === null && $crit->getTable() === null)
			|| ( $this->table !== null && $this->table === $crit->getTable() )
						  )
			&& $this->clause === $crit->getClause()
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
		$h = crc32(serialize($this->value)) ^ crc32($this->comparison) ^ crc32($this->clause);

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
	 * Replace only once
	 * taken from http://www.php.net/manual/en/function.str-replace.php
	 *
	 */
	protected static function strReplaceOnce($search, $replace, $subject)
	{
    $firstChar = strpos($subject, $search);
    if($firstChar !== false) {
        $beforeStr = substr($subject,0,$firstChar);
        $afterStr = substr($subject, $firstChar + strlen($search));
        return $beforeStr.$replace.$afterStr;
    } else {
        return $subject;
    }
	}	
}