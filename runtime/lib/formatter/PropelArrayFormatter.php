<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Array formatter for Propel query
 * format() returns a PropelArrayCollection of associative arrays
 *
 * @author     Francois Zaninotto
 * @version    $Revision$
 * @package    propel.runtime.formatter
 */
class PropelArrayFormatter extends PropelFormatter
{
	protected $collectionName = 'PropelArrayCollection';
	
	public function format(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if($class = $this->collectionName) {
			$collection = new $class();
			$collection->setModel($this->class);
			$collection->setFormatter($this);
		} else {
			$collection = array();
		}
		if ($this->getCriteria()->isWithOneToMany()) {
			$pks = array();
			while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$key = call_user_func(array($this->peer, 'getPrimaryKeyHashFromRow'), $row);
				$object = $this->getStructuredArrayFromRow($row);
				if (!array_key_exists($key, $collection)) {
					$collection[$key] = $object;
				} else {
					foreach ($object as $columnKey => $value) {
						if(is_array($value)) {
							$collection[$key][$columnKey][] = $value[0];
						}
					}
				}
			}
			$collection->setData(array_values($collection->getArrayCopy()));
		} else {
			while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$collection[] =  $this->getStructuredArrayFromRow($row);
			}
		}
		$this->currentObjects = array();
		$stmt->closeCursor();
		
		return $collection;
	}

	public function formatOne(PDOStatement $stmt)
	{
		$this->checkCriteria();
		if ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$result = $this->getStructuredArrayFromRow($row);
		} else {
			$result = null;
		}
		$this->currentObjects = array();
		$stmt->closeCursor();
		return $result;
	}

	public function isObjectFormatter()
	{
		return false;
	}
	

	/**
	 * Hydrates a series of objects from a result row
	 * The first object to hydrate is the model of the Criteria
	 * The following objects (the ones added by way of ModelCriteria::with()) are linked to the first one
	 *
	 *  @param    array  $row associative array indexed by column number,
	 *                   as returned by PDOStatement::fetch(PDO::FETCH_NUM)
	 *
	 * @return    Array
	 */
	public function getStructuredArrayFromRow($row)
	{
		$col = 0;
		$mainObjectArray = $this->getSingleObjectFromRow($row, $this->class, $col)->toArray();
		foreach ($this->getCriteria()->getWith() as $join) {
			$secondaryObject = $this->getSingleObjectFromRow($row, $join->getTableMap()->getClassname(), $col);
			if ($secondaryObject->isPrimaryKeyNull()) {
				$secondaryObjectArray = array();
			} else {
				$secondaryObjectArray = $secondaryObject->toArray();
			}
			$arrayToAugment = &$mainObjectArray;
			if (!$join->isPrimary()) {
				$prevJoin = $join;
				while($prevJoin = $prevJoin->getPreviousJoin()) {
					$arrayToAugment = &$arrayToAugment[$prevJoin->getRelationMap()->getName()];
				}
			}
			$relation = $join->getRelationMap();
			if ($relation->getType() == RelationMap::ONE_TO_MANY) {
				$arrayToAugment[$join->getRelationMap()->getName().'s'][] = $secondaryObjectArray;
			} else {
				$arrayToAugment[$join->getRelationMap()->getName()] = $secondaryObjectArray;
			}
		}
		foreach ($this->getCriteria()->getAsColumns() as $alias => $clause) {
			$mainObjectArray[$alias] = $row[$col];
			$col++;
		}
		return $mainObjectArray;
	}

}