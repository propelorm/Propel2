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

/**
 * Specialized Criterion used for IN expressions, e.g. table.column IN (?, ?) or table.column NOT IN (?, ?)
 */
class InCriterion extends AbstractCriterion
{

    /**
     * Create a new instance.
     *
     * @param Criteria $outer      The outer class (this is an "inner" class).
     * @param string   $column     ignored
     * @param string   $value      The condition to be added to the query string
     * @param string   $comparison One of Criteria::IN and Criteria::NOT_IN
     */
    public function __construct(Criteria $outer, $column, $value, $comparison = Criteria::IN)
    {
        return parent::__construct($outer, $column, $value, $comparison);
    }

    /**
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string &$sb    The string that will receive the Prepared Statement
     * @param array  $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        $bindParams = array();
        $index = count($params); // to avoid counting the number of parameters for each element in the array
        $values = ($this->value instanceof \Traversable) ? iterator_to_array($this->value) : (array) $this->value;
        foreach ($values as $value) {
            $params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $value);
            $index++; // increment this first to correct for wanting bind params to start with :p1
            $bindParams[] = ':p' . $index;
        }
        if (count($bindParams)) {
            $field = ($this->table === null) ? $this->column : $this->table . '.' . $this->column;
            $sb .= $field . $this->comparison . '(' . implode(',', $bindParams) . ')';
        } else {
            $sb .= (Criteria::IN === $this->comparison) ? '1<>1' : '1=1';
        }
    }

}
