<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Propel;

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * In Torque this is an inner class of the Criteria class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
abstract class AbstractCriterion
{
    public const UND = ' AND ';
    public const ODER = ' OR ';

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Comparison value.
     *
     * @var string
     */
    protected $comparison;

    /**
     * Table name
     *
     * @var string|null
     */
    protected $table;

    /**
     * Real table name
     *
     * @var string
     */
    protected $realtable;

    /**
     * Column name
     *
     * @var string
     */
    protected $column;

    /**
     * The DBAdapter which might be used to get db specific
     * variations of sql.
     *
     * @var \Propel\Runtime\Adapter\AdapterInterface
     */
    protected $db;

    /**
     * Other connected criterions
     *
     * @var \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion[]
     */
    protected $clauses = [];

    /**
     * Operators for connected criterions
     * Only self::UND and self::ODER are accepted
     *
     * @var string[]
     */
    protected $conjunctions = [];

    /**
     * Create a new instance.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $outer The outer class (this is an "inner" class).
     * @param string $column TABLE.COLUMN format.
     * @param mixed $value
     * @param string|null $comparison
     */
    public function __construct(Criteria $outer, $column, $value, $comparison = null)
    {
        $this->value = $value;
        $this->setColumn($column);
        $this->comparison = ($comparison === null) ? Criteria::EQUAL : $comparison;
        $this->init($outer);
    }

    /**
     * Init some properties with the help of outer class
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria The outer class
     *
     * @return void
     */
    public function init(Criteria $criteria)
    {
        try {
            $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
            $this->setAdapter($db);
        } catch (Exception $e) {
            // we are only doing this to allow easier debugging, so
            // no need to throw up the exception, just make note of it.
            Propel::log('Could not get a AdapterInterface, sql may be wrong', Propel::LOG_ERR);
        }

        // init $this->realtable
        $realtable = $criteria->getTableForAlias($this->table);
        $this->realtable = $realtable ? $realtable : $this->table;
    }

    /**
     * Set the $column and $table properties based on a column name or object
     *
     * @param \Propel\Runtime\Map\ColumnMap|string $column
     *
     * @return void
     */
    protected function setColumn($column)
    {
        if ($column instanceof ColumnMap) {
            $this->column = $column->getName();
            $this->table = $column->getTable()->getName();
        } else {
            $dotPos = strrpos($column, '.');
            if ($dotPos === false) {
                // no dot => aliased column
                $this->table = null;
                $this->column = $column;
            } else {
                $this->table = substr($column, 0, $dotPos);
                $this->column = substr($column, $dotPos + 1, strlen($column));
            }
        }
    }

    /**
     * Get the column name.
     *
     * @return string A String with the column name.
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set the table name.
     *
     * @param string $name A String with the table name.
     *
     * @return void
     */
    public function setTable($name)
    {
        $this->table = $name;
    }

    /**
     * Get the table name.
     *
     * @return string A String with the table name.
     */
    public function getTable()
    {
        return $this->table;
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
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface value of db.
     */
    public function getAdapter()
    {
        return $this->db;
    }

    /**
     * Set the adapter.
     *
     * The AdapterInterface might be used to get db specific variations of sql.
     *
     * @param \Propel\Runtime\Adapter\AdapterInterface $v Value to assign to db.
     *
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
     *
     * @return self[]
     */
    public function getClauses()
    {
        return $this->clauses;
    }

    /**
     * Get the list of conjunctions in this Criterion
     *
     * @return array
     */
    public function getConjunctions()
    {
        return $this->conjunctions;
    }

    /**
     * Append an AND Criterion onto this Criterion's list.
     *
     * @param \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion $criterion
     *
     * @return $this
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
     * @param \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion $criterion
     *
     * @return $this
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
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     *
     *                                expression into proper SQL.
     */
    public function appendPsTo(&$sb, array &$params)
    {
        if (!$this->clauses) {
            $this->appendPsForUniqueClauseTo($sb, $params);

            return;
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

    /**
     * @return string
     */
    public function __toString()
    {
        $sb = '';
        $params = [];
        $this->appendPsTo($sb, $params);

        return '' . $sb;
    }

    /**
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     */
    abstract protected function appendPsForUniqueClauseTo(&$sb, array &$params);

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashtable entries.
     *
     * @param object|null $obj
     *
     * @return bool
     */
    public function equals($obj)
    {
        // TODO: optimize me with early outs
        if ($this === $obj) {
            return true;
        }

        if (($obj === null) || !($obj instanceof AbstractCriterion)) {
            return false;
        }

        /** @var \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion $crit */
        $crit = $obj;

        $isEquiv = (
            (($this->table === null && $crit->getTable() === null)
            || ($this->table !== null && $this->table === $crit->getTable()))
            && $this->column === $crit->getColumn()
            && $this->comparison === $crit->getComparison()
        );

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

        return (bool)$isEquiv;
    }

    /**
     * Get all tables from nested criterion objects
     *
     * @return array
     */
    public function getAllTables()
    {
        $tables = [];
        $this->addCriterionTable($this, $tables);

        return $tables;
    }

    /**
     * method supporting recursion through all criterions to give
     * us a string array of tables from each criterion
     *
     * @param \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion $c
     * @param array $s
     *
     * @return void
     */
    private function addCriterionTable(AbstractCriterion $c, array &$s)
    {
        $s[] = $c->getTable();
        foreach ($c->getClauses() as $clause) {
            $this->addCriterionTable($clause, $s);
        }
    }

    /**
     * get an array of all criterion attached to this
     * recursing through all sub criterion
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion[]
     */
    public function getAttachedCriterion()
    {
        $criterions = [$this];
        foreach ($this->getClauses() as $criterion) {
            $criterions = array_merge($criterions, $criterion->getAttachedCriterion());
        }

        return $criterions;
    }

    /**
     * Ensures deep cloning of attached objects
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->clauses as $key => $criterion) {
            $this->clauses[$key] = clone $criterion;
        }
    }
}
