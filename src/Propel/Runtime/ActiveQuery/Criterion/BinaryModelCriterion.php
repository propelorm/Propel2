<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

/**
 * Specialized ModelCriterion used for IN or NOT IN model clauses,
 * e.g. 'book.TITLE NOT IN ?'
 */
class BinaryModelCriterion extends AbstractModelCriterion
{
    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(string &$sb, array &$params): void
    {
        if ($this->value !== null) {
            $params[] = ['table' => $this->realtable, 'column' => $this->column, 'value' => $this->value];
            $bindParam = ':p' . count($params);
            $sb .= str_replace('?', $bindParam, $this->clause);
        } else {
            $sb .= (stripos($this->clause, '= 0') !== false) ? '1=1' : '1<>1';
        }
    }
}
