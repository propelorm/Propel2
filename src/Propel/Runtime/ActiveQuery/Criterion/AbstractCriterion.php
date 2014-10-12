<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\Configuration;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Map\FieldMap;

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * In Torque this is an inner class of the Criteria class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
abstract class AbstractCriterion
{
    const UND = " AND ";
    const ODER = " OR ";

    /** Value of the criterion */
    protected $value;

    /**
     * Comparison value.
     * @var string
     */
    protected $comparison;

    /**
     * Entity name
     * @var string
     */
    protected $entityName;

    /**
     * Real entityName name
     * @var string
     */
    protected $realEntity;

    /**
     * Field name
     * @var string
     */
    protected $field;

    /**
     * The DBAdapter which might be used to get db specific
     * variations of sql.
     */
    protected $db;

    /**
     * Other connected criterions
     * @var AbstractCriterion[]
     */
    protected $clauses = array();

    /**
     * Operators for connected criterions
     * Only self::UND and self::ODER are accepted
     * @var string[]
     */
    protected $conjunctions = array();

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Create a new instance.
     *
     * @param Criteria $outer      The outer class (this is an "inner" class).
     * @param string   $field     TABLE.COLUMN format.
     * @param mixed    $value
     * @param string   $comparison
     */
    public function __construct(Criteria $outer, $field, $value, $comparison = null)
    {
        $this->value = $value;
        $this->setField($field);
        $this->comparison = (null === $comparison) ? Criteria::EQUAL : $comparison;
        $this->init($outer);
    }

    /**
    * Init some properties with the help of outer class
    * @param      Criteria $criteria The outer class
    */
    public function init(Criteria $criteria)
    {
        if ($criteria->hasConfiguration()) {
            $this->configuration = $criteria->getConfiguration();
        }

        $this->setAdapter($this->getConfiguration()->getAdapter($criteria->getDbName()));

        // init $this->realEntity
        $realEntity = $criteria->getEntityForAlias($this->entityName);
        $this->realEntity = $realEntity ? $realEntity : $this->entityName;
    }

    /**
     * Set the $field and $entity properties based on a field name or object
     */
    protected function setField($field)
    {
        if ($field instanceof FieldMap) {
            $this->field = $field->getName();
            $this->entityName = $field->getEntity()->getFullClassName();
        } else {
            $dotPos = strrpos($field, '.');
            if ($dotPos === false) {
                // no dot => aliased field
                $this->entityName = null;
                $this->field = $field;
            } else {
                $this->entityName = substr($field, 0, $dotPos);
                $this->field = substr($field, $dotPos + 1, strlen($field));
            }
        }
    }

    /**
     * Get the field name.
     *
     * @return string A String with the field name.
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        if ($this->configuration) {
            return $this->configuration;
        }

        return Configuration::getCurrentConfiguration();
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Set the entityName name.
     *
     * @param  string $name A String with the entityName name.
     * @return void
     */
    public function setEntityName($name)
    {
        $this->entityName = $name;
    }

    /**
     * Get the entityName name.
     *
     * @return string A String with the entityName name.
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Get the comparison.
     *
     * @return string A String with the comparison.
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * Get the value.
     *
     * @return mixed An Object with the value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the adapter.
     *
     * The AdapterInterface which might be used to get db specific
     * variations of sql.
     * @return AdapterInterface value of db.
     */
    public function getAdapter()
    {
        return $this->db;
    }

    /**
     * Set the adapter.
     *
     * The AdapterInterface might be used to get db specific variations of sql.
     * @param  AdapterInterface $v Value to assign to db.
     * @return void
     */
    public function setAdapter(AdapterInterface $v)
    {
        $this->db = $v;
        foreach ($this->clauses as $clause) {
            $clause->setAdapter($v);
        }
    }

    /**
     * Get the list of clauses in this Criterion.
     * @return self[]
     */
    private function getClauses()
    {
        return $this->clauses;
    }

    /**
     * Get the list of conjunctions in this Criterion
     * @return array
     */
    public function getConjunctions()
    {
        return $this->conjunctions;
    }

    /**
     * Append an AND Criterion onto this Criterion's list.
     *
     * @param  AbstractCriterion       $criterion
     * @return $this|AbstractCriterion
     */
    public function addAnd(AbstractCriterion $criterion)
    {
        $this->clauses[] = $criterion;
        $this->conjunctions[] = self::UND;

        return $this;
    }

    /**
     * Append an OR Criterion onto this Criterion's list.
     *
     * @param  AbstractCriterion       $criterion
     * @return $this|AbstractCriterion
     */
    public function addOr(AbstractCriterion $criterion)
    {
        $this->clauses[] = $criterion;
        $this->conjunctions[] = self::ODER;

        return $this;
    }

    /**
     * Appends a Prepared Statement representation of the Criterion
     * onto the buffer.
     *
     * @param  string          &$sb    The string that will receive the Prepared Statement
     * @param  array           $params A list to which Prepared Statement parameters will be appended
     * @return void
     * @throws PropelException - if the expression builder cannot figure out how to turn a specified
     *                                expression into proper SQL.
     */
    public function appendPsTo(&$sb, array &$params)
    {
        if (!$this->clauses) {
            return $this->appendPsForUniqueClauseTo($sb, $params);
        }
        // if there are sub criterions, they must be combined to this criterion
        $sb .= str_repeat('(', count($this->clauses));
        $this->appendPsForUniqueClauseTo($sb, $params);
        foreach ($this->clauses as $key => $clause) {
            $sb .= $this->conjunctions[$key];
            $clause->appendPsTo($sb, $params);
            $sb .= ')';
        }
    }

    public function __toString()
    {
        $sb = '';
        $params = [];
        $this->appendPsTo($sb, $params);

        return "" . $sb;
    }

    /**
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string &$sb    The string that will receive the Prepared Statement
     * @param array  $params A list to which Prepared Statement parameters will be appended
     */
    abstract protected function appendPsForUniqueClauseTo(&$sb, array &$params);

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashentity entries.
     * @return boolean
     */
    public function equals($obj)
    {
        // TODO: optimize me with early outs
        if ($this === $obj) {
            return true;
        }

        if ((null === $obj) || !($obj instanceof AbstractCriterion)) {
            return false;
        }

        /** @var AbstractCriterion $crit */
        $crit = $obj;

        $isEquiv = (((null === $this->entityName && null === $crit->getEntityName())
            || (null !== $this->entityName && $this->entityName === $crit->getEntityName()))
            && $this->field === $crit->getField()
            && $this->comparison === $crit->getComparison())
        ;

        // check chained criterion

        $clausesLength = count($this->clauses);
        $isEquiv &= (count($crit->getClauses()) == $clausesLength);
        $critConjunctions = $crit->getConjunctions();
        $critClauses = $crit->getClauses();
        for ($i = 0; $i < $clausesLength && $isEquiv; $i++) {
            $isEquiv &= ($this->conjunctions[$i] === $critConjunctions[$i]);
            $isEquiv &= ($this->clauses[$i] === $critClauses[$i]);
        }

        if ($isEquiv) {
            $isEquiv &= $this->value === $crit->getValue();
        }

        return $isEquiv;
    }

    /**
     * Get all entities from nested criterion objects
     * @return array
     */
    public function getAllEntities()
    {
        $entities = array();
        $this->addCriterionEntity($this, $entities);

        return $entities;
    }

    /**
     * method supporting recursion through all criterions to give
     * us a string array of entities from each criterion
     * @return void
     */
    private function addCriterionEntity(AbstractCriterion $c, array &$s)
    {
        $s[] = $c->getEntityName();
        foreach ($c->getClauses() as $clause) {
            $this->addCriterionEntity($clause, $s);
        }
    }

    /**
     * get an array of all criterion attached to this
     * recursing through all sub criterion
     * @return AbstractCriterion[]
     */
    public function getAttachedCriterion()
    {
        $criterions = array($this);
        foreach ($this->getClauses() as $criterion) {
            $criterions = array_merge($criterions, $criterion->getAttachedCriterion());
        }

        return $criterions;
    }

    /**
     * Ensures deep cloning of attached objects
     */
    public function __clone()
    {
        foreach ($this->clauses as $key => $criterion) {
            $this->clauses[$key] = clone $criterion;
        }
    }
}
