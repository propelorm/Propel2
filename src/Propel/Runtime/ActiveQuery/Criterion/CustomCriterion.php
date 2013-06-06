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
 * Specialized Criterion used for custom expressions with no binding, e.g. 'NOW() = 1'
 */
class CustomCriterion extends AbstractCriterion
{
    /**
     * Create a new instance.
     *
     * @param Criteria $outer The outer class (this is an "inner" class).
     * @param string   $value The condition to be added to the query string
     */
    public function __construct(Criteria $outer, $value)
    {
        $this->value = $value;
        $this->init($outer);
    }

    /**
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string &$sb    The string that will receive the Prepared Statement
     * @param array  $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        if ('' !== $this->value) {
            $sb .= (string) $this->value;
        }
    }

}
