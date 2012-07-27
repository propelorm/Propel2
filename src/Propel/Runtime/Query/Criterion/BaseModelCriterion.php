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
 * This is an "inner" class that describes an object in the criteria.
 *
 * @author Francois
 */
class BaseModelCriterion extends AbstractCriterion
{
    protected $clause = '';

    /**
     * Create a new instance.
     *
     * @param Criteria  $parent      The outer class (this is an "inner" class).
     * @param ColumnMap $column      A Column object to help escaping the value
     * @param mixed     $value
     * @param string    $comparison, among ModelCriteria::MODEL_CLAUSE
     * @param string    $clause      A simple pseudo-SQL clause, e.g. 'foo.BAR LIKE ?'
     */
    public function __construct(Criteria $outer, $column, $value = null, $comparison = ModelCriteria::MODEL_CLAUSE, $clause = null, $type = null)
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
        $this->comparison = ($comparison === null ? Criteria::EQUAL : $comparison);
        $this->clause = $clause;
        $this->type = $type;
        $this->init($outer);
    }

    public function getClause()
    {
        return $this->clause;
    }

    /**
     * Figure out which ModelCriterion method to use
     * to build the prepared statement and parameters using to the Criterion comparison
     * and call it to append the prepared statement and the parameters of the current clause.
     * For performance reasons, this method tests the cases of parent::dispatchPsHandling()
     * first, and that is not possible through inheritance ; that's why the parent
     * code is duplicated here.
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        switch ($this->comparison) {
            case ModelCriteria::MODEL_CLAUSE_ARRAY:
                // IN or NOT IN model clause, e.g. 'book.TITLE NOT IN ?'
                $this->appendModelClauseArrayToPs($sb, $params);
                break;
            case ModelCriteria::MODEL_CLAUSE_RAW:
                // raw model clause, with type, e.g. 'foobar = ?'
                $this->appendModelClauseRawToPs($sb, $params);
                break;
            default:
                // table.column = ? or table.column >= ? etc. (traditional expressions, the default)
                $this->appendBasicToPs($sb, $params);

        }
    }

    /**
     * Appends a Prepared Statement representation of the ModelCriterion onto the buffer
     * For IN or NOT IN model clauses, e.g. 'book.TITLE NOT IN ?'
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    public function appendModelClauseArrayToPs(&$sb, array &$params)
    {
        $bindParams = array(); // the param names used in query building
        $idxstart = count($params);
        $valuesLength = 0;
        foreach ((array) $this->value as $value) {
            $valuesLength++; // increment this first to correct for wanting bind params to start with :p1
            $params[] = array('table' => $this->realtable, 'column' => $this->column, 'value' => $value);
            $bindParams[] = ':p'.($idxstart + $valuesLength);
        }
        if (0 !== $valuesLength) {
            $sb .= str_replace('?', '(' . implode(',', $bindParams) . ')', $this->clause);
        } else {
            $sb .= (stripos($this->clause, ' NOT IN ') === false) ? "1<>1" : "1=1";
        }
        unset($value, $valuesLength);
    }

    /**
     * For custom expressions with a typed binding, e.g. 'foobar = ?'
     *
     * @param      string &$sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     */
    protected function appendModelClauseRawToPs(&$sb, array &$params)
    {
        if (1 !== substr_count($this->clause, '?')) {
            throw new PropelException(sprintf('Could not build SQL for expression "%s" because Criteria::MODEL_CLAUSE_RAW works only with a clause containing a single question mark placeholder', $this->column));
        }
        $params[] = array('table' => null, 'type' => $this->type, 'value' => $this->value);
        $sb .= str_replace('?', ':p' . count($params), $this->clause);
    }

    /**
     * This method checks another Criteria to see if they contain
     * the same attributes and hashtable entries.
     * @return boolean
     */
    public function equals($obj)
    {
        // TODO: optimize me with early outs
        if ($this === $obj) {
            return true;
        }

        if (null === $obj || !($obj instanceof BaseModelCriterion)) {
            return false;
        }

        $crit = $obj;

        $isEquiv = (((null === $this->table && null === $crit->getTable())
            || (null !== $this->table && $crit->getTable() === $this->table)
                          )
            && $this->clause === $crit->getClause()
            && $this->column === $crit->getColumn()
            && $this->comparison === $crit->getComparison());

        // check chained criterion

        $clausesLength = count($this->clauses);
        $isEquiv &= (count($crit->getClauses()) == $clausesLength);
        $critConjunctions = $crit->getConjunctions();
        $critClauses = $crit->getClauses();
        for ($i = 0; $i < $clausesLength && $isEquiv; $i++) {
            $isEquiv &= ($this->conjunctions[$i] === $critConjunctions[$i]);
            $isEquiv &= ($this->clauses[$i] === $critClauses[$i]);
        }

        if ($isEquiv) {
            $isEquiv &= $this->value === $crit->getValue();
        }

        return $isEquiv;
    }

    /**
     * Returns a hash code value for the object.
     */
    public function hashCode()
    {
        $h = crc32(serialize($this->value)) ^ crc32($this->comparison) ^ crc32($this->clause);

        if (null !== $this->table) {
            $h ^= crc32($this->table);
        }

        if (null !== $this->column) {
            $h ^= crc32($this->column);
        }

        foreach ($this->clauses as $clause) {
            // TODO: i KNOW there is a php incompatibility with the following line
            // but i don't remember what it is, someone care to look it up and
            // replace it if it doesn't bother us?
            // $clause->appendPsTo($sb='',$params=array());
            $sb = '';
            $params = array();
            $clause->appendPsTo($sb,$params);
            $h ^= crc32(serialize(array($sb,$params)));
            unset($sb, $params);
        }

        return $h;
    }

}
