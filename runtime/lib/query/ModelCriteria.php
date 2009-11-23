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
	
	protected static function getClassAndAlias($class)
  {
    if(strpos($class, ' ') !== false)
    {
      list($class, $alias) = explode(' ', $class);
    }
    else
    {
      $alias = null;
    }
    return array($class, $alias);
  }
  
	/**
	 * Add a condition on a column based on a pseudo SQL clause
	 * Uses introspection to translate the column phpName into a fully qualified name
	 *
	 * @see Criteria::add()
	 * 
	 * @param      string $clause The pseudo SQL clause, e.g. 'AuthorId = ?'
	 * @param      mixed  $value A value for the condition
	 * @param      string $namedCondition A name to store the condition for a later combination with combine()
	 *
	 * @return     ModelCriteria The current object, for fluid interface
	 */
	public function where($clause, $value = null, $namedCondition = null)
	{	
		$this->add($this->getCriterionForClause($clause, $value), null, null, $namedCondition);		
		return $this;
	}
	
	/**
	 * Add a condition on a column based on a pseudo SQL clause
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
		$this->addOr($this->getCriterionForClause($clause, $value), null, null);
		return $this;
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
		if($columns = $this->replaceNames($clause)) {
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
		  $criterion = new ModelCriterion($this, $columns[0], $value, $operator, $clause);
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
	 * Replace complete column names (like Article.AuthorId) in an SQL clause
	 * by their exact Propel column fully qualified name (e.g. article.AUTHOR_ID)
	 * but ignores the column names inside quotes
	 *
	 * Note: if you know a way to do so in one step, and in an efficient way, I'm interested :)
	 *
	 * @param string $clause SQL clause to inspect (modified by the method)
	 *
	 * @return array List of Propel Column names used for replacement
	 */
	protected function replaceNames(&$clause)
	{
		$this->replacedColumns = array();
		$regexp = <<<EOT
|
	(["'][^"']*?["'])?  # string
	([^"']+)?           # not string
|x
EOT;
		$clause = preg_replace_callback($regexp, array($this, 'doReplaceName'), $clause);
		return $this->replacedColumns;
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
		if ($column = $this->getColumnFromName($key)) {
			$this->replacedColumns[]= $column;
			return $column->getFullyQualifiedName();
		} else {
			return $key;
		}
	}

	protected function getColumnFromName($phpName)
	{
	  if(strpos($phpName, '.') !== false) {
			// Table.Column
			list($class, $phpName) = explode('.', $phpName);
			if (array_key_exists($class, $this->tableMaps) && $this->tableMaps[$class]->hasColumnByPhpName($phpName)) {
				return $this->tableMaps[$class]->getColumnByPhpName($phpName);
			}
			return null;
		}
	}
	
}