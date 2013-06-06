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

/**
 * Data object to describe a joined hydration in a Model Query
 * ModelWith objects are used by formatters to hydrate related objects
 *
 * @author Francois Zaninotto (Propel)
 */
class ModelWith
{
    protected $modelName;

    protected $getTableMap;

    protected $isSingleTableInheritance = false;

    protected $isAdd = false;

    protected $isWithOneToMany = false;

    protected $relationName;

    protected $relationMethod;

    protected $initMethod;

    protected $resetPartialMethod = '';

    protected $leftPhpName;

    protected $rightPhpName;

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
        $tableMap = $join->getTableMap();
        $this->setModelName($tableMap->getClassName());
        $this->getTableMap = $tableMap;
        $this->isSingleTableInheritance = $tableMap->isSingleTableInheritance();
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
        $this->rightPhpName = $join->hasRelationAlias() ? $join->getRelationAlias() : $relationName;
        if (!$join->isPrimary()) {
            $this->leftPhpName = $join->hasLeftTableAlias() ? $join->getLeftTableAlias() : $join->getPreviousJoin()->getRelationMap()->getName();
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

    public function getTableMap()
    {
        return $this->getTableMap;
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function setIsSingleTableInheritance($isSingleTableInheritance)
    {
        $this->isSingleTableInheritance = $isSingleTableInheritance;
    }

    public function isSingleTableInheritance()
    {
        return $this->isSingleTableInheritance;
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

    public function setRelationMethod($relationMethod)
    {
        $this->relationMethod = $relationMethod;
    }

    public function getRelationMethod()
    {
        return $this->relationMethod;
    }

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

    public function setLeftPhpName($leftPhpName)
    {
        $this->leftPhpName = $leftPhpName;
    }

    public function getLeftPhpName()
    {
        return $this->leftPhpName;
    }

    public function setRightPhpName($rightPhpName)
    {
        $this->rightPhpName = $rightPhpName;
    }

    public function getRightPhpName()
    {
        return $this->rightPhpName;
    }

    // Utility methods

    public function isPrimary()
    {
        return null === $this->leftPhpName;
    }

    public function __toString()
    {
        return sprintf('modelName: %s, relationName: %s, relationMethod: %s, leftPhpName: %s, rightPhpName: %s', $this->modelName, $this->relationName, $this->relationMethod, $this->leftPhpName, $this->rightPhpName);
    }
}
