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
use Propel\Runtime\ActiveQuery\Criteria;

use \PDO;

/**
 * Specialized Criterion used for custom expressions with a typed binding, e.g. 'foobar = ?'
 */
class RawCriterion extends AbstractCriterion
{

    /**
     * Binding type to be used for Criteria::RAW comparison
     * @var string any of the PDO::PARAM_ constant values
     */
    protected $type;

    /**
     * Create a new instance.
     *
     * @param Criteria $outer  The outer class (this is an "inner" class).
     * @param string   $column ignored
     * @param string   $value  The condition to be added to the query string
     * @param int      $type   A PDO type constant, e.g. PDO::PARAM_STR
     */
    public function __construct(Criteria $outer, $column, $value, $type = PDO::PARAM_STR)
    {
        $this->value = $value;
        $this->column = $column;
        $this->type = $type;
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
        if (1 !== substr_count($this->column, '?')) {
            throw new InvalidClauseException(sprintf('Could not build SQL for expression "%s" because Criteria::RAW works only with a clause containing a single question mark placeholder', $this->column));
        }
        $params[] = array('table' => null, 'type' => $this->type, 'value' => $this->value);
        $sb .= str_replace('?', ':p' . count($params), $this->column);
    }

}
