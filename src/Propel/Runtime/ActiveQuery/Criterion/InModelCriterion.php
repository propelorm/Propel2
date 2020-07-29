<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Traversable;

/**
 * Specialized ModelCriterion used for IN or NOT IN model clauses,
 * e.g. 'book.TITLE NOT IN ?'
 */
class InModelCriterion extends AbstractModelCriterion
{
    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        $bindParams = []; // the param names used in query building
        $index = count($params);
        $values = ($this->value instanceof Traversable) ? iterator_to_array($this->value) : (array)$this->value;
        foreach ($values as $value) {
            $params[] = [
                'table' => $this->realtable,
                'column' => $this->column,
                'value' => $value,
            ];
            $index++; // increment this first to correct for wanting bind params to start with :p1
            $bindParams[] = ':p' . $index;
        }
        if (count($bindParams)) {
            $sb .= str_replace('?', '(' . implode(',', $bindParams) . ')', $this->clause);
        } else {
            $sb .= (stripos($this->clause, ' NOT IN ') === false) ? '1<>1' : '1=1';
        }
    }
}
