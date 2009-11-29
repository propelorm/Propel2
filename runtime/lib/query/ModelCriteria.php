<?php
/*
 *  $Id: Criteria.php 1320 2009-11-19 22:17:12Z francois $
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
 * This class extends the Criteria by adding runtime introspection abilities
 * in order to ease the building of queries.
 * 
 * A ModelCriteria requires additional information to be initialized. 
 * Using a model name and tablemaps, a ModelCriteria can do more powerful things than a simple Criteria
 *
 * @author     François Zaninotto
 * @version    $Revision: 1320 $
 * @package    propel.runtime.query
 */
class ModelCriteria extends Criteria
{
	const MODEL_CLAUSE = "MODEL CLAUSE";
	const MODEL_CLAUSE_ARRAY = "MODEL CLAUSE ARRAY";
	const MODEL_CLAUSE_LIKE = "MODEL CLAUSE LIKE";
	const MODEL_CLAUSE_SEVERAL = "MODEL CLAUSE SEVERAL";

	protected $modelName;
	protected $modelAlias;
	protected $tableMaps = array();
		
	/**
	 * Creates a new instance with the default capacity which corresponds to
	 * the specified database.
	 *
	 * @param      string $dbName The dabase name.
	 * @param      string $modelName The phpName of a model, e.g. 'Book'
	 */
	public function __construct($dbName = null, $modelName)
	{
		$this->setDbName($dbName);
		$this->originalDbName = $dbName;
		list($this->modelName, $this->modelAlias) = $this->getClassAndAlias($modelName);
		$modelName = $this->modelAlias ? $this->modelAlias : $this->modelName;
		$this->tableMaps[$modelName] = Propel::getDatabaseMap($dbName)->getTablebyPhpName($this->modelName);
	}
	
	/**
	 * Returns the class and alias of a string representing a model or a relation
	 * e.g. 'Book b' => array('Book', 'b')
	 * e.g. 'Book'   => array('Book', null)
	 *
	 * @param      string $class The classname to explode
	 *
	 * @return     array  list($className, $aliasName)
	 */
	protected static function getClassAndAlias($class)
  {
    if(strpos($class, ' ') !== false) {
      list($class, $alias) = explode(' ', $class);
    } else {
      $alias = null;
    }
    return array($class, $alias);
  }
  
	/**
	 * Adds a condition on a column based on a pseudo SQL clause
	 * but keeps it for later use with combine()
	 * Until combine() is called, the condition is not added to the query
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @see Criteria::add()
	 * 
	 * @param      string $conditionName A name to store the condition for a later combination with combine()
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function condition($conditionName, $clause, $value = null)
	{
		$this->addCond($conditionName, $this->getCriterionForClause($clause, $value), null, null);
		
		return $this;
	}
  
  
	/**
	 * Adds a condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @see Criteria::add()
	 * 
	 * @param      mixed $clause A string representing the pseudo SQL clause, e.g. 'Book.AuthorId = ?'
	 *                           Or an array of condition names
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function where($clause, $value = null)
	{
		if (is_array($clause)) {
			// where(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
			$criterion = $this->getCriterionForConditions($clause, $value);	
		} else {
			// where('Book.AuthorId = ?', 12)
			$criterion = $this->getCriterionForClause($clause, $value);
		}
		$this->add($criterion, null, null);
		
		return $this;
	}
	
	/**
	 * Adds a condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @see Criteria::addOr()
	 * 
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function orWhere($clause, $value = null)
	{
		if (is_array($clause)) {
			// orWhere(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
			$criterion = $this->getCriterionForConditions($clause, $value);
		} else {
			// orWhere('Book.AuthorId = ?', 12)
			$criterion = $this->getCriterionForClause($clause, $value);
		}
		$this->addOr($criterion, null, null);
		
		return $this;
	}

	/**
	 * Adds a having condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @see Criteria::addHaving()
	 * 
	 * @param      mixed $clause A string representing the pseudo SQL clause, e.g. 'Book.AuthorId = ?'
	 *                           Or an array of condition names
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function having($clause, $value = null)
	{
		if (is_array($clause)) {
			// having(array('cond1', 'cond2'), Criteria::LOGICAL_OR)
			$criterion = $this->getCriterionForConditions($clause, $value);
		} else {
			// having('Book.AuthorId = ?', 12)
			$criterion = $this->getCriterionForClause($clause, $value);
		}
		$this->addHaving($criterion);
		
		return $this;
	}
		
	/**
	 * Adds an ORDER BY clause to the query
	 * Usability layer on top of Criteria::addAscendingOrderByColumn() and Criteria::addDescendingOrderByColumn()
	 * Infers $column and $order from $columnName and some optional arguments
	 * Examples:
	 *   $c->orderBy('Book.CreatedAt')
	 *    => $c->addAscendingOrderByColumn(BookPeer::CREATED_AT)
	 *   $c->orderBy('Book.CategoryId', 'desc')
	 *    => $c->addDescendingOrderByColumn(BookPeer::CATEGORY_ID)
	 *
	 * @param string $columnName The column to order by
	 * @param string $order      The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function orderBy($columnName, $order = Criteria::ASC)
	{
		list($column, $realColumnName) = $this->getColumnFromName($columnName);
		if (!$column instanceof ColumnMap) {
			throw new PropelException('ModelCriteria::orderBy() expects a valid column name (e.g. Book.Title) as first argument');
		}
		$order = strtoupper($order);
		
		switch ($order) {
			case Criteria::ASC:
				$this->addAscendingOrderByColumn($realColumnName);
				break;
			case Criteria::DESC:
				$this->addDescendingOrderByColumn($realColumnName);
				break;
			default:
				throw new PropelException('ModelCriteria::orderBy() only accepts "asc" or "desc" as argument');
		}
		
		return $this;
	}
	
	/**
	 * Adds a GROUB BY clause to the query
	 * Usability layer on top of Criteria::addGroupByColumn()
	 * Infers $column $columnName
	 * Examples:
	 *   $c->groupBy('Book.AuthorId')
	 *    => $c->addGroupByColumn(BookPeer::AUTHOR_ID)
	 *
	 * @param      string $columnName The column to group by
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function groupBy($columnName)
	{
		list($column, $realColumnName) = $this->getColumnFromName($columnName);
		if (!$column instanceof ColumnMap) {
			throw new PropelException('ModelCriteria::groupBy() expects a valid column name (e.g. Book.AuthorId) as first argument');
		}
		$this->addGroupByColumn($realColumnName);
		
		return $this;
	}
  
	/**
	 * Adds a DISTINCT clause to the query
	 * Alias for Criteria::setDistinct()
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function distinct()
	{
		$this->setDistinct();
		
		return $this;
	}
	
	/**
	 * Adds a LIMIT clause (or its subselect equivalent) to the query
	 * Alias for Criteria:::setLimit()
	 *
	 * @param      int $limit Maximum number of results to return by the query
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function limit($limit)
	{
		$this->setLimit($limit);
		
		return $this;
	}
	
	/**
	 * Adds an OFFSET clause (or its subselect equivalent) to the query
	 * Alias for of Criteria::setOffset()
	 *
	 * @param      int $offset Offset of the first result to return
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function offset($offset)
	{
		$this->setOffset($offset);
		
		return $this;
	}

	/**
	 * Adds a JOIN clause to the query
	 * Infers the ON clause from a relation name
	 * Uses the Propel table maps, based on the schema, to guess the related columns
	 * Beware that the default JOIN operator is INNER JOIN, while Criteria defaults to WHERE
	 * Examples:
	 *   $c->join('Author')
	 *    => $c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID, Criteria::INNER_JOIN)
	 *   $c->join('Author', Criteria::RIGHT_JOIN)
	 *    => $c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID, Criteria::RIGHT_JOIN)
	 * 
	 * @param      string $relation Relation to use for the join
	 * @param      string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function join($relation, $joinType = Criteria::INNER_JOIN)
	{
		list($relationName, $alias) = self::getClassAndAlias($relation);
		list($leftName, $relationName) = explode('.', $relationName);
		if(!isset($this->tableMaps[$leftName])) {
			throw new PropelException('Unknown table or alias ' . $leftName);
		}
		if(!$this->tableMaps[$leftName]->hasRelation($relationName)) {
			throw new PropelException('Unknown relation ' . $relationName . ' on the ' . $leftName .' table');
		}
		$relationMap = $this->tableMaps[$leftName]->getRelation($relationName);
		
		$rightTable = $relationMap->getRightTable();
		if($alias !== null) {
			$this->addAlias($alias, $rightTable->getName());
			$this->tableMaps[$alias] = $rightTable;
		} else {
			$this->tableMaps[$relationName] = $rightTable;
		}
		
		$leftCols = $relationMap->getLeftColumns();
		$rightCols = $relationMap->getRightColumns();
		$joinCols = array();
		$nbColumns = $relationMap->countColumnMappings();
		for ($i=0; $i < $nbColumns; $i++) {
			$leftColName = (array_key_exists($leftName, $this->aliases) ? $leftName : $leftCols[$i]->getTableName()) . '.' . $leftCols[$i]->getName();
			$rightColName = ($alias ? $alias : $rightCols[$i]->getTableName()) . '.' . $rightCols[$i]->getName();
			$joinCols []= array($leftColName, $rightColName);
		}
		$this->addMultipleJoin($joinCols, $joinType);
		
		return $this;
	}
	
	/**
	 * Creates a Criterion object based on a list of existing condition names and a comparator
	 *
	 * @param      array $conditions The list of condition names, e.g. array('cond1', 'cond2')
	 * @param      string  $comparator A comparator, Criteria::LOGICAL_AND (default) or Criteria::LOGICAL_OR
	 *
	 * @return     Criterion a Criterion or ModelCriterion object
	 */
	protected function getCriterionForConditions($conditions, $comparator = null)
	{
		$comparator = (null === $comparator) ? Criteria::LOGICAL_AND : $comparator;
		$this->combine($conditions, $comparator, 'propel_temp_name');
		$criterion = $this->namedCriterions['propel_temp_name'];
		unset($this->namedCriterions['propel_temp_name']);
		
		return $criterion;
	}
	  
	/**
	 * Creates a Criterion object based on a SQL clause and a value
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 *
	 * @return     Criterion a Criterion or ModelCriterion object
	 */
	protected function getCriterionForClause($clause, $value)
	{
		$clause = trim($clause);
		if($this->replaceNames($clause)) {
			// at least one column name was found and replaced in the clause
			// this is enough to determine the type to bind the parameter to
			if (preg_match('/IN \?$/i', $clause) !== 0) {
				$operator = ModelCriteria::MODEL_CLAUSE_ARRAY;
			} elseif (preg_match('/LIKE \?$/i', $clause) !== 0) {
				$operator = ModelCriteria::MODEL_CLAUSE_LIKE;
			} elseif (substr_count($clause, '?') > 1) {
				$operator = ModelCriteria::MODEL_CLAUSE_SEVERAL;
			} else {
				$operator = ModelCriteria::MODEL_CLAUSE;
			}
		  $criterion = new ModelCriterion($this, $this->replacedColumns[0], $value, $operator, $clause);
		  if ($this->currentAlias != '') {
		  	$criterion->setTable($this->currentAlias);
		  }
		} else {
			// no column match in clause, must be an expression like '1=1'
			if (strpos($clause, '?') !== false) {
				throw new PropelException("Cannot determine the column to bind to the parameter in clause '$clause'");
			}
		  $criterion = new Criterion($this, null, $clause, Criteria::CUSTOM);
		}
		return $criterion;		
	}
	
	/**
	 * Replaces complete column names (like Article.AuthorId) in an SQL clause
	 * by their exact Propel column fully qualified name (e.g. article.AUTHOR_ID)
	 * but ignores the column names inside quotes
	 *
	 * Note: if you know a way to do so in one step, and in an efficient way, I'm interested :)
	 *
	 * @param string $clause SQL clause to inspect (modified by the method)
	 *
	 * @return boolean Whether the method managed to find and replace at least one column name
	 */
	protected function replaceNames(&$clause)
	{
		$this->replacedColumns = array();
		$this->currentAlias = '';
		$this->foundMatch = false;
		$regexp = <<<EOT
|
	(["'][^"']*?["'])?  # string
	([^"']+)?           # not string
|x
EOT;
		$clause = preg_replace_callback($regexp, array($this, 'doReplaceName'), $clause);
		return $this->foundMatch;
	}
	
	/**
	 * Callback function to replace expressions containing column names with expressions using the real column names
	 * Handles strings properly
	 * e.g. 'CONCAT(Book.Title, "Book.Title") = ?'
	 *   => 'CONCAT(book.TITLE, "Book.Title") = ?'
	 *
	 * @param array $matches Matches found by preg_replace_callback
	 *
	 * @return string the expression replacement
	 */
	protected function doReplaceName($matches)
	{
		// replace names only in expressions, not in strings delimited by quotes
		return $matches[1] . preg_replace_callback('/\w+\.\w+/', array($this, 'doReplaceNameInExpression'), $matches[2]);
	}
	
	/**
	 * Callback function to replace column names by their real name in a clause
	 * e.g.  'Book.Title IN ?'
	 *    => 'book.TITLE IN ?'
	 *
	 * @param array $matches Matches found by preg_replace_callback
	 *
	 * @return string the column name replacement
	 */
	protected function doReplaceNameInExpression($matches)
	{
		$key = $matches[0];
		list($column, $realColumnName) = $this->getColumnFromName($key);
		if ($column instanceof ColumnMap) {
			$this->replacedColumns[]= $column;
			$this->foundMatch = true;
			return $realColumnName;
		} else {
			return $key;
		}
	}

	/**
	 * Finds a column and a SQL translation for a pseudo SQL column name
	 * Respects table aliases previously registered in a join() or addAlias()
	 * Examples:
	 * <code>
	 * $c->getColumnFromName('Book.Title');
	 *   => array($bookTitleColumnMap, 'book.TITLE')
	 * $c->join('Book.Author a')
	 *   ->getColumnFromName('a.FirstName');
	 *   => array($authorFirstNameColumnMap, 'a.FIRST_NAME')
	 * </code>
	 *
	 * @param      string $phpName String representing the column name in a pseudo SQL clause, e.g. 'Book.Title'
	 *
	 * @return     array List($columnMap, $realColumnName)
	 */
	protected function getColumnFromName($phpName)
	{
	  if(strpos($phpName, '.') !== false) {
			// Table.Column
			list($class, $phpName) = explode('.', $phpName);
			if (array_key_exists($class, $this->tableMaps) && $this->tableMaps[$class]->hasColumnByPhpName($phpName)) {
				$column = $this->tableMaps[$class]->getColumnByPhpName($phpName);
			  if (array_key_exists($class, $this->aliases)) {
			  	$this->currentAlias = $class;
			    $realColumnName = $class . '.' . $column->getName();
			  } else {
			    $realColumnName = $column->getFullyQualifiedName();
			  }
			  return array($column, $realColumnName);
			}
			return null;
		}
	}
	
}