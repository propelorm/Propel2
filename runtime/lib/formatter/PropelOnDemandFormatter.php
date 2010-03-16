<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Object formatter for Propel query
 * format() returns a PropelOnDemandCollection that hydrates objects as the use iterates on the collection
 * This formatter consumes less memory than the PropelObjectFormatter, but doesn't use Instance Pool
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelOnDemandFormatter extends PropelObjectFormatter
{
	protected $collectionName = 'PropelOnDemandCollection';
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if ($this->getCriteria()->isWithOneToMany()) {
			throw new PropelException('PropelOnDemandFormatter cannot hydrate related objects using a one-to-many relationship. Try removing with() from your query.');
		}
		$class = $this->collectionName;
		$collection = new $class();
		$collection->setModel($this->getCriteria()->getModelName());
		$collection->setFormatter($this);
		$collection->setStatement($stmt);
		
		return $collection;
	}
	
	/**
	 * Hydrates a series of objects from a result row
	 * The first object to hydrate is the model of the Criteria
	 * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
	 *
	 *  @param    array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 *
	 * @return    BaseObject
	 */
	public function getAllObjectsFromRow($row)
	{
		$col = 0;
		$tableMap = $this->getCriteria()->getTableMap(); 
		$class = $tableMap->isSingleTableInheritance() ? call_user_func(array($tableMap->getPeerClassname(), 'getOMClass'), $row, $col, false) : $this->class;
		$obj = $this->getSingleObjectFromRow($row, $class, $col);
		
		foreach ($this->getCriteria()->getWith() as $join) {
			$startObject = $join->getObjectToRelate($obj);
			$tableMap = $join->getTableMap(); 
			$class = $tableMap->isSingleTableInheritance() ? call_user_func(array($tableMap->getPeerClassname(), 'getOMClass'), $row, $col, false) : $tableMap->getClassname(); 
			$endObject = $this->getSingleObjectFromRow($row, $class, $col);
			// as we may be in a left join, the endObject may be empty
			// in which case it should not be related to the previous object
			if ($endObject->isPrimaryKeyNull()) {
				continue;
			}
			$method = 'set' . $join->getRelationMap()->getName();
			$startObject->$method($endObject);
		}
		foreach ($this->getCriteria()->getAsColumns() as $alias => $clause) {
			$obj->setVirtualColumn($alias, $row[$col]);
			$col++;
		}
		return $obj;
	}
	
}