<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Query\Criterion;

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Query\ModelCriteria;
use Propel\Runtime\Map\ColumnMap;

/**
 * Specialized ModelCriterion used for traditional expressions,
 * e.g. table.column = ? or table.column >= ? etc.
 */
class BasicModelCriterion extends BaseModelCriterion
{
    /**
     * Create a new instance.
     *
     * @param Criteria  $parent      The outer class (this is an "inner" class).
     * @param ColumnMap $column      A Column object to help escaping the value
     * @param mixed     $value
     * @param string    $comparison, among ModelCriteria::MODEL_CLAUSE
     * @param string    $clause      A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     */
    public function __construct(Criteria $outer, $column, $value = null, $clause = null)
    {
        $this->value = $value;
        if ($column instanceof ColumnMap) {
            $this->column = $column->getName();
            $this->table = $column->getTable()->getName();
        } else {
            $dotPos = strrpos($column,'.');
            if ($dotPos === false) {
                // no dot => aliased column
                $this->table = null;
                $this->column = $column;
            } else {
                $this->table = substr($column, 0, $dotPos);
                $this->column = substr($column, $dotPos+1, strlen($column));
            }
        }
        $this->clause = $clause;
        $this->init($outer);
    }

    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     * For regular model clauses, e.g. 'book.TITLE = ?'
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        if (null !== $this->value) {
            $params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $this->value);
            $sb .= str_replace('?', ':p'.count($params), $this->clause);
        } else {
            $sb .= $this->clause;
        }
    }
   
}
