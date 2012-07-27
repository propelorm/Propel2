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
 * Specialized ModelCriterion used for LIKE expressions
 * e.g. table.column LIKE ? or table.column NOT LIKE ?
 */
class LikeModelCriterion extends BasicModelCriterion
{
    /** flag to ignore case in comparison */
    protected $ignoreStringCase = false;
    
    /**
     * Create a new instance.
     *
     * @param Criteria  $parent      The outer class (this is an "inner" class).
     * @param ColumnMap $column      A Column object to help escaping the value
     * @param mixed     $value
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
     * Sets ignore case.
     *
     * @param  boolean        $b True if case should be ignored.
     * @return ModelCriterion A modified Criterion object.
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreStringCase = (Boolean) $b;

        return $this;
    }

    /**
     * Is ignore case on or off?
     *
     * @return boolean True if case is ignored.
     */
    public function isIgnoreCase()
    {
        return $this->ignoreStringCase;
    }

    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     * For LIKE model clauses, e.g. 'book.TITLE LIKE ?'
     * Handles case insensitivity for VARCHAR columns
     *
     * @param string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        // LIKE is case insensitive in mySQL and SQLite, but not in PostGres
        // If the column is case insensitive, use ILIKE / NOT ILIKE instead of LIKE / NOT LIKE
        if ($this->ignoreStringCase && $this->getDb() instanceof PgsqlAdapter) {
            $this->clause = preg_replace('/LIKE \?$/i', 'ILIKE ?', $this->clause);
        }
        parent::appendPsForUniqueClauseTo($sb, $params);
    }
   
}
