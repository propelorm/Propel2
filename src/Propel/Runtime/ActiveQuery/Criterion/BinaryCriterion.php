<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @param \Propel\Runtime\ActiveQuery\Criteria $outer The outer class (this is an "inner" class).
     * @param \Propel\Runtime\Map\ColumnMap|string $column ignored
     * @param mixed $value The condition to be added to the query string
     * @param string $comparison One of Criteria::BINARY_NONE, Criteria::BINARY_ALL
     */
    public function __construct(Criteria $outer, $column, $value, string $comparison = Criteria::BINARY_ALL)
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
        if ($this->value !== null) {
            $params[] = ['table' => $this->realtable, 'column' => $this->column, 'value' => $this->value];
            $bindParam = ':p' . count($params);
            $field = ($this->table === null) ? $this->column : $this->table . '.' . $this->column;

            if ($this->comparison === Criteria::BINARY_ALL) {
                // With ATTR_EMULATE_PREPARES => false, we can't have two identical params, so let's add another param
                // https://github.com/propelorm/Propel2/issues/1192
                $params[] = ['table' => $this->realtable, 'column' => $this->column, 'value' => $this->value];
                $bindParam2 = ':p' . count($params);
                $sb .= $field . ' & ' . $bindParam . ' = ' . $bindParam2;
            } else {
                $sb .= $field . ' & ' . $bindParam . ' = 0';
            }
        } else {
            $sb .= $this->comparison === Criteria::BINARY_ALL ? '1<>1' : '1=1';
        }
    }
}
