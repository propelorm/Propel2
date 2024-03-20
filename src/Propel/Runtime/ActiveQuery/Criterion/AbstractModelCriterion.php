<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;

/**
 * This is an "inner" class that describes an object in the criteria.
 *
 * @author Francois
 */
abstract class AbstractModelCriterion extends AbstractCriterion
{
    /**
     * @var string
     */
    protected $clause = '';

    /**
     * Create a new instance.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $outer The outer class (this is an "inner" class).
     * @param string $clause A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     * @param \Propel\Runtime\Map\ColumnMap|string $column A Column object to help escaping the value
     * @param mixed $value
     * @param string|null $tableAlias optional table alias
     */
    public function __construct(Criteria $outer, string $clause, $column, $value = null, ?string $tableAlias = null)
    {
        $this->value = $value;
        $this->setColumn($column);
        if ($tableAlias) {
            $this->table = $tableAlias;
        }
        $this->clause = $clause;
        $this->init($outer);
    }

    /**
     * @return string
     */
    public function getClause(): string
    {
        return $this->clause;
    }

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashtable entries.
     *
     * @param object|null $obj
     *
     * @return bool
     */
    public function equals(?object $obj): bool
    {
        // TODO: optimize me with early outs
        if ($this === $obj) {
            return true;
        }

        if (!$obj instanceof AbstractModelCriterion) {
            return false;
        }

        /** @var \Propel\Runtime\ActiveQuery\Criterion\AbstractModelCriterion $crit */
        $crit = $obj;

        $isEquiv = (
            (
                ($this->table === null && $crit->getTable() === null)
                || ($this->table !== null && $crit->getTable() === $this->table)
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

        return (bool)$isEquiv;
    }
}
