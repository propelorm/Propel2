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
 * Specialized Criterion used for binary expressions, e.g. table.column & 5 = 0 (similar to NOT IN logic)
 */
class BinaryCriterion extends AbstractCriterion
{

    /**
     * Create a new instance.
     *
     * @param Criteria $outer      The outer class (this is an "inner" class).
     * @param string   $column     ignored
     * @param string   $value      The condition to be added to the query string
     * @param string   $comparison One of Criteria::BINARY_NONE, Criteria::BINARY_ALL
     */
    public function __construct(Criteria $outer, $column, $value, $comparison = Criteria::BINARY_ALL)
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
        if ($this->value !== null) {
            $params[] = ['table' => $this->realtable, 'column' => $this->column, 'value' => $this->value];
            $bindParam = ':p' . count($params);
            $field = ($this->table === null) ? $this->column : $this->table . '.' . $this->column;
            
            if ($this->comparison === Criteria::BINARY_ALL) {
                $sb .= $field . ' & ' . $bindParam . ' = ' . $bindParam;
            } else {
                $sb .= $field . ' & ' . $bindParam . ' = 0';
            }
        } else {
            $sb .= $this->comparison === Criteria::BINARY_ALL ? '1<>1' : '1=1';
        }
    }

}
