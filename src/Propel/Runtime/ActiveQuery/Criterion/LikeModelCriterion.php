<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;

/**
 * Specialized ModelCriterion used for LIKE expressions
 * e.g. table.column LIKE ? or table.column NOT LIKE ?
 */
class LikeModelCriterion extends BasicModelCriterion
{
    /** flag to ignore case in comparison */
    protected $ignoreStringCase = false;

    /**
     * Sets ignore case.
     *
     * @param  boolean                  $b True if case should be ignored.
     * @return $this|LikeModelCriterion A modified Criterion object.
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
     * Handles case insensitivity for VARCHAR columns
     *
     * @param string &$sb    The string that will receive the Prepared Statement
     * @param array  $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        // LIKE is case insensitive in mySQL and SQLite, but not in PostGres
        // If the column is case insensitive, use ILIKE / NOT ILIKE instead of LIKE / NOT LIKE
        if ($this->ignoreStringCase) {
            if ($this->getAdapter() instanceof PgsqlAdapter) {
                $this->clause = preg_replace('/LIKE \?$/i', 'ILIKE ?', $this->clause);
            } else {
                throw new InvalidClauseException('Case insensitive LIKE is only supported in PostreSQL');
            }
        }
        parent::appendPsForUniqueClauseTo($sb, $params);
    }

}
