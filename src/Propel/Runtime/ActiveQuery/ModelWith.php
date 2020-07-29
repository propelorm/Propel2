<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    /**
     * @var string
     */
    protected $modelName;

    /**
     * @var \Propel\Runtime\Map\TableMap
     */
    protected $getTableMap;

    /**
     * @var bool
     */
    protected $isSingleTableInheritance = false;

    /**
     * @var bool
     */
    protected $isAdd = false;

    /**
     * @var bool
     */
    protected $isWithOneToMany = false;

    /**
     * @var string
     */
    protected $relationName;

    /**
     * @var string
     */
    protected $relationMethod;

    /**
     * @var string
     */
    protected $initMethod;

    /**
     * @var string
     */
    protected $resetPartialMethod = '';

    /**
     * @var string
     */
    protected $leftPhpName;

    /**
     * @var string
     */
    protected $rightPhpName;

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelJoin|null $join
     */
    public function __construct(?ModelJoin $join = null)
    {
        if ($join !== null) {
            $this->init($join);
        }
    }

    /**
     * Define the joined hydration schema based on a join object.
     * Fills the ModelWith properties using a ModelJoin as source
     *
     * @param \Propel\Runtime\ActiveQuery\ModelJoin $join
     *
     * @return void
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

    /**
     * @param string $modelName
     *
     * @return void
     */
    public function setModelName($modelName)
    {
        if (strpos($modelName, '\\') === 0) {
            $this->modelName = substr($modelName, 1);
        } else {
            $this->modelName = $modelName;
        }
    }

    /**
     * @return \Propel\Runtime\Map\TableMap
     */
    public function getTableMap()
    {
        return $this->getTableMap;
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * @param bool $isSingleTableInheritance
     *
     * @return void
     */
    public function setIsSingleTableInheritance($isSingleTableInheritance)
    {
        $this->isSingleTableInheritance = $isSingleTableInheritance;
    }

    /**
     * @return bool
     */
    public function isSingleTableInheritance()
    {
        return $this->isSingleTableInheritance;
    }

    /**
     * @param bool $isAdd
     *
     * @return void
     */
    public function setIsAdd($isAdd)
    {
        $this->isAdd = $isAdd;
    }

    /**
     * @return bool
     */
    public function isAdd()
    {
        return $this->isAdd;
    }

    /**
     * @param bool $isWithOneToMany
     *
     * @return void
     */
    public function setIsWithOneToMany($isWithOneToMany)
    {
        $this->isWithOneToMany = $isWithOneToMany;
    }

    /**
     * @return bool
     */
    public function isWithOneToMany()
    {
        return $this->isWithOneToMany;
    }

    /**
     * @param string $relationName
     *
     * @return void
     */
    public function setRelationName($relationName)
    {
        $this->relationName = $relationName;
    }

    /**
     * @return string
     */
    public function getRelationName()
    {
        return $this->relationName;
    }

    /**
     * @param string $relationMethod
     *
     * @return void
     */
    public function setRelationMethod($relationMethod)
    {
        $this->relationMethod = $relationMethod;
    }

    /**
     * @return string
     */
    public function getRelationMethod()
    {
        return $this->relationMethod;
    }

    /**
     * @param string $initMethod
     *
     * @return void
     */
    public function setInitMethod($initMethod)
    {
        $this->initMethod = $initMethod;
    }

    /**
     * @return string
     */
    public function getInitMethod()
    {
        return $this->initMethod;
    }

    /**
     * @param string $resetPartialMethod
     *
     * @return void
     */
    public function setResetPartialMethod($resetPartialMethod)
    {
        $this->resetPartialMethod = $resetPartialMethod;
    }

    /**
     * @return string
     */
    public function getResetPartialMethod()
    {
        return $this->resetPartialMethod;
    }

    /**
     * @param string $leftPhpName
     *
     * @return void
     */
    public function setLeftPhpName($leftPhpName)
    {
        $this->leftPhpName = $leftPhpName;
    }

    /**
     * @return string
     */
    public function getLeftPhpName()
    {
        return $this->leftPhpName;
    }

    /**
     * @param string $rightPhpName
     *
     * @return void
     */
    public function setRightPhpName($rightPhpName)
    {
        $this->rightPhpName = $rightPhpName;
    }

    /**
     * @return string
     */
    public function getRightPhpName()
    {
        return $this->rightPhpName;
    }

    // Utility methods

    /**
     * @return bool
     */
    public function isPrimary()
    {
        return $this->leftPhpName === null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('modelName: %s, relationName: %s, relationMethod: %s, leftPhpName: %s, rightPhpName: %s', $this->modelName, $this->relationName, $this->relationMethod, $this->leftPhpName, $this->rightPhpName);
    }
}
