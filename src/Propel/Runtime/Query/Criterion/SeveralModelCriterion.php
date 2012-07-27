<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Query\Criterion;

use Propel\Runtime\Query\Criterion\Exception\InvalidValueException;
use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Map\ColumnMap;

/**
 * Specialized ModelCriterion used for ternary model clause, e.G 'book.ID BETWEEN ? AND ?'
 */
class SeveralModelCriterion extends BaseModelCriterion
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
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        $clause = $this->clause;
        foreach ((array) $this->value as $value) {
            if (null === $value) {
                // FIXME we eventually need to translate a BETWEEN to
                // something like WHERE (col < :p1 OR :p1 IS NULL) AND (col < :p2 OR :p2 IS NULL)
                // in order to support null values
                throw new InvalidValueException('Null values are not supported inside BETWEEN clauses');
            }
            $params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $value);
            $clause = self::strReplaceOnce('?', ':p'.count($params), $clause);
        }
        $sb .= $clause;
    }


    /**
     * Replace only once
     * taken from http://www.php.net/manual/en/function.str-replace.php
     *
     */
    protected static function strReplaceOnce($search, $replace, $subject)
    {
        $firstChar = strpos($subject, $search);
        if (false !== $firstChar) {
            $beforeStr = substr($subject, 0, $firstChar);
            $afterStr = substr($subject, $firstChar + strlen($search));

            return $beforeStr.$replace.$afterStr;
        }

        return $subject;
    }
}
