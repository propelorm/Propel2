<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Query\Criterion;

use Propel\Runtime\Query\Criteria;
use Propel\Runtime\Map\ColumnMap;

/**
 * Specialized ModelCriterion used for custom expressions with a typed binding,
 * e.g. 'foobar = ?'
 */
class RawModelCriterion extends BaseModelCriterion
{
    /**
     * Binding type to be used for Criteria::RAW comparison
     * @var string any of the PDO::PARAM_ constant values
     */
    protected $type;

    /**
     * Create a new instance.
     *
     * @param Criteria  $parent      The outer class (this is an "inner" class).
     * @param ColumnMap $column      A Column object to help escaping the value
     * @param mixed     $value
     * @param string    $clause      A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     */
    public function __construct(Criteria $outer, $column, $value = null, $clause = null, $type = null)
    {
        $this->type = $type;
        parent::__construct($outer, $column, $value, $clause);
    }

    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        if (1 !== substr_count($this->clause, '?')) {
            throw new PropelException(sprintf('Could not build SQL for expression "%s" because Criteria::MODEL_CLAUSE_RAW works only with a clause containing a single question mark placeholder', $this->column));
        }
        $params[] = array('table' => null, 'type' => $this->type, 'value' => $this->value);
        $sb .= str_replace('?', ':p' . count($params), $this->clause);
    }
   
}
