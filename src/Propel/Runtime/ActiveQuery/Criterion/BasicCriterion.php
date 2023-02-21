<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException;

/**
 * Specialized Criterion used for traditional expressions,
 * e.g. table.column = ? or table.column >= ? etc.
 */
class BasicCriterion extends AbstractCriterion
{
    /**
     * @var bool
     */
    protected $ignoreStringCase = false;

    /**
     * Create a new instance.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $outer The outer class (this is an "inner" class).
     * @param \Propel\Runtime\Map\ColumnMap|string $column ignored
     * @param mixed $value The condition to be added to the query string
     * @param string|null $comparison One of Criteria::LIKE and Criteria::NOT_LIKE
     */
    public function __construct(Criteria $outer, $column, $value, ?string $comparison = null)
    {
        parent::__construct($outer, $column, $value, $comparison);
    }

    /**
     * Sets ignore case.
     *
     * @param bool $b True if case should be ignored.
     *
     * @return $this A modified Criterion object.
     */
    public function setIgnoreCase(bool $b)
    {
        $this->ignoreStringCase = $b;

        return $this;
    }

    /**
     * Is ignore case on or off?
     *
     * @return bool True if case is ignored.
     */
    public function isIgnoreCase(): bool
    {
        return $this->ignoreStringCase;
    }

    /**
     * Appends a Prepared Statement representation of the Criterion onto the buffer
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @throws \Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(string &$sb, array &$params): void
    {
        $field = ($this->table === null) ? $this->column : $this->table . '.' . $this->column;
        // NULL VALUES need special treatment because the SQL syntax is different
        // i.e. table.column IS NULL rather than table.column = null
        if ($this->value !== null) {
            // ANSI SQL functions get inserted right into SQL (not escaped, etc.)
            if ($this->value === Criteria::CURRENT_DATE || $this->value === Criteria::CURRENT_TIME || $this->value === Criteria::CURRENT_TIMESTAMP) {
                $sb .= $field . $this->comparison . $this->value;
            } else {
                $params[] = ['table' => $this->realtable, 'column' => $this->column, 'value' => $this->value];

                // default case, it is a normal col = value expression; value
                // will be replaced w/ '?' and will be inserted later using PDO bindValue()
                if ($this->ignoreStringCase) {
                    /** @var \Propel\Runtime\Adapter\SqlAdapterInterface $sqlAdapter */
                    $sqlAdapter = $this->getAdapter();
                    $sb .= $sqlAdapter->ignoreCase($field) . $this->comparison . $sqlAdapter->ignoreCase(':p' . count($params));
                } else {
                    $sb .= $field . $this->comparison . ':p' . count($params);
                }
            }
        } else {
            // value is null, which means it was either not specified or specifically
            // set to null.
            if ($this->comparison === Criteria::EQUAL || $this->comparison === Criteria::ISNULL) {
                $sb .= $field . Criteria::ISNULL;
            } elseif ($this->comparison === Criteria::NOT_EQUAL || $this->comparison === Criteria::ISNOTNULL) {
                $sb .= $field . Criteria::ISNOTNULL;
            } else {
                // for now throw an exception, because not sure how to interpret this
                throw new InvalidValueException(sprintf('Could not build SQL for expression: `%s %s NULL`', $field, $this->comparison));
            }
        }
    }
}
