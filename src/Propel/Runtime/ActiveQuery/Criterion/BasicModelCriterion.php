<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException;

/**
 * Specialized ModelCriterion used for traditional expressions,
 * e.g. table.column = ? or table.column >= ? etc.
 */
class BasicModelCriterion extends AbstractModelCriterion
{
    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param string &$sb    The string that will receive the Prepared Statement
     * @param array  $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        if (null !== $this->value) {
            if (!strpos($this->clause, '?')) {
                throw new InvalidClauseException('A clause must contain a question mark in order to be bound to a value');
            }
            $params[] = array(
                'table'  => $this->realtable,
                'column' => $this->column,
                'value'  => $this->value
            );
            $sb .= str_replace('?', ':p'.count($params), $this->clause);
        } else {
            $sb .= $this->clause;
        }
    }

}
