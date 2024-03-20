<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Traversable;

/**
 * Specialized Criterion used for IN expressions, e.g. table.column IN (?, ?) or table.column NOT IN (?, ?)
 */
class InCriterion extends AbstractCriterion
{
    /**
     * Create a new instance.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $outer The outer class (this is an "inner" class).
     * @param \Propel\Runtime\Map\ColumnMap|string $column ignored
     * @param mixed $value The condition to be added to the query string
     * @param string $comparison One of Criteria::IN and Criteria::NOT_IN
     */
    public function __construct(Criteria $outer, $column, $value, string $comparison = Criteria::IN)
    {
        parent::__construct($outer, $column, $value, $comparison);
    }

    /**
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(string &$sb, array &$params): void
    {
        $bindParams = [];
        $index = count($params); // to avoid counting the number of parameters for each element in the array
        $values = ($this->value instanceof Traversable) ? iterator_to_array($this->value) : (array)$this->value;
        foreach ($values as $value) {
            $params[] = ['table' => $this->realtable, 'column' => $this->column, 'value' => $value];
            $index++; // increment this first to correct for wanting bind params to start with :p1
            $bindParams[] = ':p' . $index;
        }
        if (count($bindParams)) {
            $field = ($this->table === null) ? $this->column : $this->table . '.' . $this->column;
            $sb .= $field . $this->comparison . '(' . implode(',', $bindParams) . ')';
        } else {
            $sb .= ($this->comparison === Criteria::IN) ? '1<>1' : '1=1';
        }
    }
}
