<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Map\EntityMap;
use Propel\Runtime\Map\RelationMap;

/**
 * Data object to describe a joined hydration in a Model Query
 * ModelWith objects are used by formatters to hydrate related objects
 *
 * @author Francois Zaninotto (Propel)
 */
class ModelWith
{
    protected $modelName;

    protected $getEntityMap;

    protected $isSingleEntityInheritance = false;

    protected $isAdd = false;

    protected $isWithOneToMany = false;

    protected $relationName;

    protected $relationMethod;

    protected $initMethod;

    protected $resetPartialMethod = '';

    protected $leftName;

    protected $rightName;

    public function __construct(ModelJoin $join = null)
    {
        if (null !== $join) {
            $this->init($join);
        }
    }

    /**
     * Define the joined hydration schema based on a join object.
     * Fills the ModelWith properties using a ModelJoin as source
     *
     * @param ModelJoin $join
     */
    public function init(ModelJoin $join)
    {
        $entityMap = $join->getEntityMap();
        $this->setModelName($entityMap->getFullClassName());
        $this->getEntityMap = $entityMap;
        $this->isSingleEntityInheritance = $entityMap->isSingleEntityInheritance();
        $relation = $join->getRelationMap();
        $relationName = $relation->getName();
        if ($relation->getType() == RelationMap::ONE_TO_MANY) {
            $this->isAdd = $this->isWithOneToMany = true;
            $this->relationName = $relation->getPluralName();
            $this->relationMethod = 'add' . $relationName;
            $this->initMethod = 'init' . $this->relationName;
            $this->resetPartialMethod = 'resetPartial' . $this->relationName;
        } else {
            $this->relationName = $relationName;
            $this->relationMethod = 'set' . $relationName;
        }
        $this->rightName = $join->hasRelationAlias() ? $join->getRelationAlias() : $relationName;
        if (!$join->isPrimary()) {
            $this->leftName = $join->hasLeftTableAlias() ? $join->getLeftTableAlias() : $join->getPreviousJoin()->getRelationMap()->getName();
        }
    }

    // DataObject getters & setters

    public function setModelName($modelName)
    {
        if (0 === strpos($modelName, '\\')) {
            $this->modelName = substr($modelName, 1);
        } else {
            $this->modelName = $modelName;
        }
    }

    /**
     * @return EntityMap
     */
    public function getEntityMap()
    {
        return $this->getEntityMap;
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function setIsSingleEntityInheritance($isSingleEntityInheritance)
    {
        $this->isSingleEntityInheritance = $isSingleEntityInheritance;
    }

    public function isSingleEntityInheritance()
    {
        return $this->isSingleEntityInheritance;
    }

    public function setIsAdd($isAdd)
    {
        $this->isAdd = $isAdd;
    }

    public function isAdd()
    {
        return $this->isAdd;
    }

    public function setIsWithOneToMany($isWithOneToMany)
    {
        $this->isWithOneToMany = $isWithOneToMany;
    }

    public function isWithOneToMany()
    {
        return $this->isWithOneToMany;
    }

    public function setRelationName($relationName)
    {
        $this->relationName = $relationName;
    }

    public function getRelationName()
    {
        return $this->relationName;
    }

//    public function setRelationMethod($relationMethod)
//    {
//        $this->relationMethod = $relationMethod;
//    }
//
//    public function getRelationMethod()
//    {
//        return $this->relationMethod;
//    }

    public function setInitMethod($initMethod)
    {
        $this->initMethod = $initMethod;
    }

    public function getInitMethod()
    {
        return $this->initMethod;
    }

    public function setResetPartialMethod($resetPartialMethod)
    {
        $this->resetPartialMethod = $resetPartialMethod;
    }

    public function getResetPartialMethod()
    {
        return $this->resetPartialMethod;
    }

    public function setLeftName($leftName)
    {
        $this->leftName = $leftName;
    }

    public function getLeftName()
    {
        return $this->leftName;
    }

    public function setRightName($rightName)
    {
        $this->rightName = $rightName;
    }

    public function getRightName()
    {
        return $this->rightName;
    }

    // Utility methods

    public function isPrimary()
    {
        return null === $this->leftName;
    }

    public function __toString()
    {
        return sprintf('entityName: %s, relationName: %s, relationMethod: %s, leftName: %s, rightName: %s', $this->modelName, $this->relationName, $this->relationMethod, $this->leftName, $this->rightName);
    }
}
