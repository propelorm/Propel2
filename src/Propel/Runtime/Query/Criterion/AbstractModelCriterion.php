<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Query\Criterion;

use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Map\ColumnMap;

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * @author Francois
 */
Abstract class AbstractModelCriterion extends AbstractCriterion
{
    protected $clause = '';

    /**
     * Create a new instance.
     *
     * @param Criteria  $parent The outer class (this is an "inner" class).
     * @param ColumnMap $column A Column object to help escaping the value
     * @param mixed     $value
     * @param string    $clause A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     */
    public function __construct(Criteria $outer, $column, $value = null, $clause = null)
    {
        $this->value = $value;
        if ($column instanceof ColumnMap) {
            $this->column = $column->getName();
            $this->table = $column->getTable()->getName();
        } else {
            $dotPos = strrpos($column,'.');
            if ($dotPos === false) {
                // no dot => aliased column
                $this->table = null;
                $this->column = $column;
            } else {
                $this->table = substr($column, 0, $dotPos);
                $this->column = substr($column, $dotPos+1, strlen($column));
            }
        }
        $this->clause = $clause;
        $this->init($outer);
    }

    public function getClause()
    {
        return $this->clause;
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

        if (null === $obj || !($obj instanceof AbstractModelCriterion)) {
            return false;
        }

        $crit = $obj;

        $isEquiv = (((null === $this->table && null === $crit->getTable())
            || (null !== $this->table && $crit->getTable() === $this->table)
                          )
            && $this->clause === $crit->getClause()
            && $this->column === $crit->getColumn()
            && $this->comparison === $crit->getComparison());

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
        $h = crc32(serialize($this->value)) ^ crc32($this->comparison) ^ crc32($this->clause);

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
            unset($sb, $params);
        }

        return $h;
    }

}
