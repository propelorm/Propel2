<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Query;

use \Exception;

use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Adapter\AdapterInterface;

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * In Torque this is an inner class of the Criteria class.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 */
class Criterion
{
    const UND = " AND ";
    const ODER = " OR ";

    /** Value of the CO. */
    protected $value;

    /**
     * Comparison value.
     * @var string
     */
    protected $comparison;

    /** Table name. */
    protected $table;

    /** Real table name */
    protected $realtable;

    /** Column name. */
    protected $column;

    /**
     * The DBAdaptor which might be used to get db specific
     * variations of sql.
     */
    protected $db;

    /**
     * other connected criteria and their conjunctions.
     */
    protected $clauses = array();

    protected $conjunctions = array();

    /** "Parent" Criteria class */
    protected $parent;

    /**
     * Create a new instance.
     *
     * @param Criteria $parent     The outer class (this is an "inner" class).
     * @param string   $column     TABLE.COLUMN format.
     * @param mixed    $value
     * @param string   $comparison
     */
    public function __construct(Criteria $outer, $column, $value, $comparison = null)
    {
        $this->value = $value;
        $dotPos = strrpos($column, '.');
        if (false === $dotPos) {
            // no dot => aliased column
            $this->table = null;
            $this->column = $column;
        } else {
            $this->table = substr($column, 0, $dotPos);
            $this->column = substr($column, $dotPos + 1);
        }
        $this->comparison = (null === $comparison) ? Criteria::EQUAL : $comparison;
        $this->init($outer);
    }

    /**
    * Init some properties with the help of outer class
    * @param      Criteria $criteria The outer class
    */
    public function init(Criteria $criteria)
    {
        // init $this->db
        try {
            $db = Propel::getServiceContainer()->getAdapter($criteria->getDbName());
            $this->setAdapter($db);
        } catch (Exception $e) {
            // we are only doing this to allow easier debugging, so
            // no need to throw up the exception, just make note of it.
            Propel::log("Could not get a AdapterInterface, sql may be wrong", Propel::LOG_ERR);
        }

        // init $this->realtable
        $realtable = $criteria->getTableForAlias($this->table);
        $this->realtable = $realtable ? $realtable : $this->table;

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
     * @return array
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
     */
    public function addAnd(Criterion $criterion)
    {
        $this->clauses[] = $criterion;
        $this->conjunctions[] = self::UND;

        return $this;
    }

    /**
     * Append an OR Criterion onto this Criterion's list.
     * @return Criterion
     */
    public function addOr(Criterion $criterion)
    {
        $this->clauses[] = $criterion;
        $this->conjunctions[] = self::ODER;

        return $this;
    }

    /**
     * Appends a Prepared Statement representation of the Criterion
     * onto the buffer.
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param  array           $params A list to which Prepared Statement parameters will be appended
     * @return void
     * @throws PropelException - if the expression builder cannot figure out how to turn a specified
     *                           expression into proper SQL.
     */
    public function appendPsTo(&$sb, array &$params)
    {
        $sb .= str_repeat ( '(', count($this->clauses) );

        $this->appendPsForUniqueClauseTo($sb, $params);

        foreach ($this->clauses as $key => $clause) {
            $sb .= $this->conjunctions[$key];
            $clause->appendPsTo($sb, $params);
            $sb .= ')';
        }
    }

    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        // overridden by subclasses
    }

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashtable entries.
     * @return boolean
     */
    public function equals($obj)
    {
        // TODO: optimize me with early outs
        if ($this === $obj) {
            return true;
        }

        if ((null === $obj) || !($obj instanceof Criterion)) {
            return false;
        }

        $crit = $obj;

        $isEquiv = (((null === $this->table && null === $crit->getTable())
            || (null !== $this->table && $this->table === $crit->getTable()))
            && $this->column === $crit->getColumn()
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
     * Returns a hash code value for the object.
     */
    public function hashCode()
    {
        $h = crc32(serialize($this->value)) ^ crc32($this->comparison);

        if (null !== $this->table) {
            $h ^= crc32($this->table);
        }

        if (null !== $this->column) {
            $h ^= crc32($this->column);
        }

        foreach ($this->clauses as $clause) {
            // TODO: i KNOW there is a php incompatibility with the following line
            // but i don't remember what it is, someone care to look it up and
            // replace it if it doesn't bother us?
            // $clause->appendPsTo($sb='',$params=array());
            $sb = '';
            $params = array();
            $clause->appendPsTo($sb,$params);
            $h ^= crc32(serialize(array($sb,$params)));
            unset ($sb, $params);
        }

        return $h;
    }

    /**
     * Get all tables from nested criterion objects
     * @return array
     */
    public function getAllTables()
    {
        $tables = array();
        $this->addCriterionTable($this, $tables);

        return $tables;
    }

    /**
     * method supporting recursion through all criterions to give
     * us a string array of tables from each criterion
     * @return void
     */
    private function addCriterionTable(Criterion $c, array &$s)
    {
        $s[] = $c->getTable();
        foreach ($c->getClauses() as $clause) {
            $this->addCriterionTable($clause, $s);
        }
    }

    /**
     * get an array of all criterion attached to this
     * recursing through all sub criterion
     * @return Criterion[]
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
