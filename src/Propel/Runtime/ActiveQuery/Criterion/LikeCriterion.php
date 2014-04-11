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
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;

/**
 * Specialized Criterion used for LIKE expressions
 * e.g. table.column LIKE ? or table.column NOT LIKE ?  (or ILIKE for Postgres)
 */
class LikeCriterion extends AbstractCriterion
{
    /** flag to ignore case in comparison */
    protected $ignoreStringCase = false;

    /**
     * Create a new instance.
     *
     * @param Criteria $outer      The outer class (this is an "inner" class).
     * @param string   $column     ignored
     * @param string   $value      The condition to be added to the query string
     * @param string   $comparison One of Criteria::LIKE and Criteria::NOT_LIKE
     */
    public function __construct(Criteria $outer, $column, $value, $comparison = Criteria::LIKE)
    {
        return parent::__construct($outer, $column, $value, $comparison);
    }

    /**
     * Sets ignore case.
     *
     * @param  boolean             $b True if case should be ignored.
     * @return $this|LikeCriterion A modified Criterion object.
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
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string &$sb    The string that will receive the Prepared Statement
     * @param array  $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        $field = (null === $this->table) ? $this->column : $this->table . '.' . $this->column;
        $db = $this->getAdapter();
        // If selection is case insensitive use ILIKE for PostgreSQL or SQL
        // UPPER() function on column name for other databases.
        if ($this->ignoreStringCase) {
            if ($db instanceof PgsqlAdapter) {
                if (Criteria::LIKE === $this->comparison) {
                    $this->comparison = Criteria::ILIKE;
                } elseif (Criteria::NOT_LIKE === $this->comparison) {
                    $this->comparison = Criteria::NOT_ILIKE;
                }
            } else {
                $field = $db->ignoreCase($field);
            }
        }

        $params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $this->value);

        $sb .= $field . $this->comparison;

        // If selection is case insensitive use SQL UPPER() function
        // on criteria or, if Postgres we are using ILIKE, so not necessary.
        if ($this->ignoreStringCase && !($db instanceof PgsqlAdapter)) {
            $sb .= $db->ignoreCase(':p'.count($params));
        } else {
            $sb .= ':p'.count($params);
        }
    }

}
