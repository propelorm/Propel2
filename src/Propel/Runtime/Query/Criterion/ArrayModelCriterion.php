<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Query\Criterion;

/**
 * Specialized ModelCriterion used for IN or NOT IN model clauses,
 * e.g. 'book.TITLE NOT IN ?'
 */
class ArrayModelCriterion extends AbstractModelCriterion
{
    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        $bindParams = array(); // the param names used in query building
        $idxstart = count($params);
        $valuesLength = 0;
        foreach ((array) $this->value as $value) {
            $valuesLength++; // increment this first to correct for wanting bind params to start with :p1
            $params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $value);
            $bindParams[] = ':p'.($idxstart + $valuesLength);
        }
        if (0 !== $valuesLength) {
            $sb .= str_replace('?', '(' . implode(',', $bindParams) . ')', $this->clause);
        } else {
            $sb .= (stripos($this->clause, ' NOT IN ') === false) ? "1<>1" : "1=1";
        }
        unset($value, $valuesLength);
    }
   
}
