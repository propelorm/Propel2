<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\EntityMap;

/**
 * A ModelJoin is a Join object tied to a RelationMap object
 *
 * @author Francois Zaninotto (Propel)
 */
class ModelJoin extends Join
{
    /** @var RelationMap */
    protected $relationMap;

    protected $entityMap;

    protected $previousJoin;

    public function setRelationMap(RelationMap $relationMap, $leftEntityAlias = null, $relationAlias = null)
    {
        $leftCols = $relationMap->getLeftFields();
        $rightCols = $relationMap->getRightFields();
        $nbFields = $relationMap->countFieldMappings();

        for ($i = 0; $i < $nbFields; $i++) {
            $this->addExplicitCondition(
                $relationMap->getLeftEntity()->getFullClassName(), $leftCols[$i]->getName(), $leftEntityAlias,
                $relationMap->getRightEntity()->getFullClassName(), $rightCols[$i]->getName(), $relationAlias,
                Criteria::EQUAL);
        }
        $this->relationMap = $relationMap;

        return $this;
    }

    /**
     * @return RelationMap
     */
    public function getRelationMap()
    {
        return $this->relationMap;
    }

    /**
     * Sets the right entityMap for this join
     *
     * @param EntityMap $entityMap The entity map to use
     *
     * @return $this|ModelJoin The current join object, for fluid interface
     */
    public function setEntityMap(EntityMap $entityMap)
    {
        $this->entityMap = $entityMap;

        return $this;
    }

    /**
     * Gets the right entityMap for this join
     *
     * @return EntityMap The entity map
     */
    public function getEntityMap()
    {
        if (null === $this->entityMap && null !== $this->relationMap) {
            $this->entityMap = $this->relationMap->getRightEntity();
        }

        return $this->entityMap;
    }

    public function setPreviousJoin(ModelJoin $join)
    {
        $this->previousJoin = $join;

        return $this;
    }

    /**
     * @return ModelJoin
     */
    public function getPreviousJoin()
    {
        return $this->previousJoin;
    }

    public function isPrimary()
    {
        return null === $this->previousJoin;
    }

    public function setRelationAlias($relationAlias)
    {
        return $this->setRightTableAlias($relationAlias);
    }

    public function getRelationAlias()
    {
        return $this->getRightTableAlias();
    }

    public function hasRelationAlias()
    {
        return $this->hasRightTableAlias();
    }

    public function isIdentifierQuotingEnabled()
    {
        return $this->getEntityMap()->isIdentifierQuotingEnabled();
    }

    /**
     * This method returns the last related, but already hydrated object up until this join
     * Starting from $startObject and continuously calling the getters to get
     * to the base object for the current join.
     *
     * This method only works if PreviousJoin has been defined,
     * which only happens when you provide dotted relations when calling join
     *
     * @param  Object $startObject the start object all joins originate from and which has already hydrated
     * @return Object the base Object of this join
     */
    public function getObjectToRelate($startObject)
    {
        if ($this->isPrimary()) {
            return $startObject;
        }

        $previousJoin = $this->getPreviousJoin();
        $previousObject = $previousJoin->getObjectToRelate($startObject);
        $method = 'get' . $previousJoin->getRelationMap()->getName();

        return $previousObject->$method();
    }

    public function getClause(&$params)
    {
        if (null === $this->joinCondition) {
            $conditions = array();
            for ($i = 0; $i < $this->count; $i++) {
                $conditions [] = $this->getLeftField($i) . $this->getOperator($i) . $this->getRightField($i);
            }
            $joinCondition = sprintf('(%s)', implode($conditions, ' AND '));
        } else {
            $joinCondition = '';
            $this->joinCondition->appendPsTo($joinCondition, $params);
        }

        $rightEntityName = $this->getEntityMap()->getFQTableName();

        if ($this->hasRightTableAlias()) {
            $rightEntityName .= ' ' . $this->getRightTableAlias();
        }

        if ($this->isIdentifierQuotingEnabled()) {
            $rightEntityName = $this->getAdapter()->quoteTableIdentifier($rightEntityName);
        }

        return sprintf(
            '%s %s ON %s',
            $this->getJoinType(),
            $rightEntityName,
            $joinCondition
        );
    }

    public function equals($join)
    {
        /** @var ModelJoin $join */

        return parent::equals($join)
        && $this->relationMap == $join->getRelationMap()
        && $this->previousJoin == $join->getPreviousJoin()
        && $this->rightTableAlias == $join->getRightTableAlias();
    }

    public function __toString()
    {
        return parent::toString()
        . ' entityMap: ' . ($this->entityMap ? get_class($this->entityMap) : 'null')
        . ' relationMap: ' . $this->relationMap->getName()
        . ' previousJoin: ' . ($this->previousJoin ? '(' . $this->previousJoin . ')' : 'null')
        . ' relationAlias: ' . $this->rightTableAlias;
    }
}
