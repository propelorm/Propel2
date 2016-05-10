<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criterion\Exception\InvalidValueException;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Specialized Criterion used for traditional expressions,
 * e.g. entity.field = ? or entity.field >= ? etc.
 */
class BasicCriterion extends AbstractCriterion
{
    /** flag to ignore case in comparison */
    protected $ignoreStringCase = false;

    /**
     * Create a new instance.
     *
     * @param Criteria $outer      The outer class (this is an "inner" class).
     * @param string   $field     ignored
     * @param string   $value      The condition to be added to the query string
     * @param string   $comparison One of Criteria::LIKE and Criteria::NOT_LIKE
     */
    public function __construct(Criteria $outer, $field, $value, $comparison = Criteria::EQUAL)
    {
        return parent::__construct($outer, $field, $value, $comparison);
    }

    /**
     * Sets ignore case.
     *
     * @param  boolean              $b True if case should be ignored.
     * @return $this|BasicCriterion A modified Criterion object.
     */
    public function setIgnoreCase($b)
    {
        $this->ignoreStringCase = (boolean) $b;

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
        $field = (null === $this->entityName) ? $this->field : $this->entityName . '.' . $this->field;
        // NULL VALUES need special treatment because the SQL syntax is different
        // i.e. entity.field IS NULL rather than entity.field = null
        if ($this->value !== null) {

            // ANSI SQL functions get inserted right into SQL (not escaped, etc.)
            if (Criteria::CURRENT_DATE === $this->value || Criteria::CURRENT_TIME === $this->value || Criteria::CURRENT_TIMESTAMP === $this->value) {
                $sb .= $field . $this->comparison . $this->value;
            } else {

                $params[] = array('entity' => $this->realEntity, 'field' => $this->field, 'value' => $this->value);

                // default case, it is a normal col = value expression; value
                // will be replaced w/ '?' and will be inserted later using PDO bindValue()
                if ($this->ignoreStringCase) {
                    $sb .= $this->getAdapter()->ignoreCase($field) . $this->comparison . $this->getAdapter()->ignoreCase(':p'.count($params));
                } else {
                    $sb .= $field . $this->comparison . ':p'.count($params);
                }

            }
        } else {
            // value is null, which means it was either not specified or specifically
            // set to null.
            if (Criteria::EQUAL === $this->comparison || Criteria::ISNULL === $this->comparison) {
                $sb .= $field . Criteria::ISNULL;
            } elseif (Criteria::NOT_EQUAL === $this->comparison || Criteria::ISNOTNULL === $this->comparison) {
                $sb .= $field . Criteria::ISNOTNULL;
            } else {
                // for now throw an exception, because not sure how to interpret this
                throw new InvalidValueException(sprintf('Could not build SQL for expression: %s %s NULL', $field, $this->comparison));
            }
        }
    }
}
