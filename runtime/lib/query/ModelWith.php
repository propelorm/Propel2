<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Data object to describe a joined hydration in a Model Query
 *
 * @author     Francois Zaninotto (Propel)
 * @package    propel.runtime.query
 */
class ModelWith
{
	protected $join;
	protected $modelName;
	protected $modelPeerName;
	protected $isSingleTableInheritance = false;
	protected $isAdd = false;
	protected $relationName = '';
	protected $relationMethod;
	protected $relatedClass;
	
	public function __construct(ModelJoin $join)
	{
		$this->join = $join;
		$tableMap = $join->getTableMap();
		$this->modelName = $tableMap->getClassname();
		$this->modelPeerName = $tableMap->getPeerClassname();
		$this->isSingleTableInheritance = $tableMap->isSingleTableInheritance();
		$relation = $join->getRelationMap();
		if ($relation->getType() == RelationMap::ONE_TO_MANY) {
			$this->isAdd = true;
			$this->relationName = $relation->getName() . 's';
			$this->relationMethod = 'add' . $relation->getName();
		} else {
			$this->relationName = $relation->getName();
			$this->relationMethod = 'set' . $relation->getName();
		}
		if (!$join->isPrimary()) {
			$this->relatedClass = $join->hasLeftTableAlias() ? $join->getLeftTableAlias() : $relation->getLeftTable()->getPhpName();
		}
	}
	
	public function getJoin()
	{
		return $this->join;
	}
	
	public function getModelName()
	{
		return $this->modelName;
	}
	
	public function getModelPeerName()
	{
		return $this->modelPeerName;
	}
	
	public function isSingleTableInheritance()
	{
		return $this->isSingleTableInheritance;
	}
	
	public function isAdd()
	{
		return $this->isAdd;
	}
	
	public function getRelationName()
	{
		return $this->relationName;
	}
	
	public function getRelationMethod()
	{
		return $this->relationMethod;
	}
	
	public function isPrimary()
	{
		return null === $this->relatedClass;
	}
	
	public function getRelatedClass()
	{
		return $this->relatedClass;
	}
}