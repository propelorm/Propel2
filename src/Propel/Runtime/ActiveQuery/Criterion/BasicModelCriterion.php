<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

/**
 * Specialized ModelCriterion used for traditional expressions,
 * e.g. entity.field = ? or entity.field >= ? etc.
 */
class BasicModelCriterion extends AbstractModelCriterion
{
    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        if (null !== $this->value) {
            if (false !== strpos($this->clause, '?')) {
                $params[] = array(
                    'entity' => $this->realEntity,
                    'field' => $this->field,
                    'value' => $this->value
                );
                $sb .= str_replace('?', ':p' . count($params), $this->clause);
            } else {
                $sb .= $this->clause;
            }
        } else {
            $sb .= $this->clause;
        }
    }

}
