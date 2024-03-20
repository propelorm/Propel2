<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use PDO;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException;

/**
 * Specialized ModelCriterion used for custom expressions with a typed binding,
 * e.g. 'foobar = ?'
 */
class RawModelCriterion extends AbstractModelCriterion
{
    /**
     * Binding type to be used for Criteria::RAW comparison
     *
     * @var int Any of the PDO::PARAM_ constant values
     */
    protected $type;

    /**
     * Create a new instance.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $outer The outer class (this is an "inner" class).
     * @param string $clause A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     * @param \Propel\Runtime\Map\ColumnMap|string $column A Column object to help escaping the value
     * @param mixed $value
     * @param string|null $tableAlias optional table alias
     * @param int $type A PDO type constant, e.g. PDO::PARAM_STR
     */
    public function __construct(
        Criteria $outer,
        string $clause,
        $column,
        $value = null,
        ?string $tableAlias = null,
        int $type = PDO::PARAM_STR
    ) {
        $this->type = $type;
        parent::__construct($outer, $clause, $column, $value, $tableAlias);
    }

    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @throws \Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidClauseException
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(string &$sb, array &$params): void
    {
        if (substr_count($this->clause, '?') !== 1) {
            throw new InvalidClauseException(sprintf('Could not build SQL for expression `%s` because Criteria::MODEL_CLAUSE_RAW works only with a clause containing a single question mark placeholder', $this->column));
        }
        $params[] = [
            'table' => null,
            'type' => $this->type,
            'value' => $this->value,
        ];
        $sb .= str_replace('?', ':p' . count($params), $this->clause);
    }
}
