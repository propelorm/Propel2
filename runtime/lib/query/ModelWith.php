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
	protected $modelPeerName;
	protected $relationMethod;
	protected $relatedClass;
	
	public function __construct(ModelJoin $join)
	{
		$this->join = $join;
		$this->modelPeerName = $join->getTableMap()->getPeerClassname();
		$relation = $join->getRelationMap();
		$this->relationMethod = ($relation->getType() == RelationMap::ONE_TO_MANY) ? 'add' . $relation->getName() : 'set' . $relation->getName();
		if (!$join->isPrimary()) {
			$this->relatedClass = $join->hasLeftTableAlias() ? $join->getLeftTableAlias() : $relation->getLeftTable()->getPhpName();
		}
	}
	
	public function getJoin()
	{
		return $this->join;
	}
	
	public function getModelPeerName()
	{
		return $this->modelPeerName;
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