<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
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
     * @param Criteria  $outer      The outer class (this is an "inner" class).
     * @param string    $clause     A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     * @param ColumnMap $column     A Column object to help escaping the value
     * @param mixed     $value
     * @param string    $tableAlias optional table alias
     */
    public function __construct(Criteria $outer, $clause, $column, $value = null, $tableAlias = null)
    {
        $this->value = $value;
        $this->setColumn($column);
        if ($tableAlias) {
            $this->table = $tableAlias;
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

        /** @var AbstractModelCriterion $crit */
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
}
