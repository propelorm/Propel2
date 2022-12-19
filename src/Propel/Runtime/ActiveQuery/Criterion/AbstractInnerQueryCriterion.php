<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;

/**
 * Abstract Criterion for nested filter expression, that bind an inner query
 * with an operator like IN, EXISTS
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractInnerQueryCriterion extends AbstractCriterion
{
    /**
     * @var string|null Left side of the operator, can be empty.
     */
    protected $leftOperand;

    /**
     * @var string|null The sql operator expression, i.e. "IN" or "NOT IN".
     */
    protected $sqlOperator;

    /**
     * @var \Propel\Runtime\ActiveQuery\Criteria
     */
    protected $innerQuery;

    /**
     * Resolves the operator as given by the user to the SQL operator statement.
     *
     * @param string $operatorDeclaration
     *
     * @return string
     */
    abstract protected function resolveOperator(string $operatorDeclaration): string;

    /**
     * Allows to edit or replace the inner query before it is turned to SQL.
     *
     * @return \Propel\Runtime\ActiveQuery\Criteria
     */
    abstract protected function processInnerQuery(): Criteria;

    /**
     * Entry point for child classes to add information about the relation to the query.
     *
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $outerQuery
     * @param \Propel\Runtime\Map\RelationMap $relation
     *
     * @return void
     */
    abstract protected function initForRelation(ModelCriteria $outerQuery, RelationMap $relation): void;

    /**
     * Allows to edit or replace the inner query before it is turned to SQL.
     *
     * @param mixed $outerQuery
     * @param \Propel\Runtime\Map\RelationMap $relationMap
     * @param string|null $operator
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $innerQuery
     *
     * @return self
     */
    public static function createForRelation($outerQuery, RelationMap $relationMap, ?string $operator, ModelCriteria $innerQuery): self
    {
        $filter = new static($outerQuery, null, $operator, $innerQuery);
        $filter->initForRelation($outerQuery, $relationMap);

        return $filter;
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $outerQuery
     * @param string|null $leftOperand Left side of the operator, usually a column name or empty.
     * @param string|null $operator The operator, like IN, NOT IN, EXISTS, NOT EXISTS
     * @param \Propel\Runtime\ActiveQuery\Criteria $innerQuery
     */
    public function __construct(
        $outerQuery,
        ?string $leftOperand,
        ?string $operator,
        Criteria $innerQuery
    ) {
        parent::__construct($outerQuery, '', null, null);

        if ($operator) {
            $operator = trim($operator); // Criteria::IN is padded with spaces
        }

        $this->leftOperand = $leftOperand;
        $this->sqlOperator = $this->resolveOperator($operator);
        $this->innerQuery = $innerQuery;
    }

    /**
     * @see \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion::appendPsForUniqueClauseTo()
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(string &$sb, array &$params): void
    {
        $leftOperand = $this->leftOperand ? $this->leftOperand . ' ' : '';
        $innerQuery = $this->processInnerQuery()->createSelectSql($params);
        $sb .= $leftOperand . $this->sqlOperator . ' (' . $innerQuery . ')';
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $outerQuery
     * @param \Propel\Runtime\Map\RelationMap $relationMap where outer query is on the left side
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion
     */
    protected function buildJoinCondition($outerQuery, RelationMap $relationMap): AbstractCriterion
    {
        if (!$this->innerQuery instanceof ModelCriteria) {
            throw new PropelException('Cannot build join on regular condition');
        }
        $join = new ModelJoin();
        $outerAlias = $outerQuery->getModelAlias();
        $innerAlias = $this->innerQuery->getModelAlias();
        $join->setRelationMap($relationMap, $outerAlias, $innerAlias);
        $join->buildJoinCondition($outerQuery);

        $joinCondition = $join->getJoinCondition();
        $joinCondition->setTable($this->innerQuery->getTableNameInQuery());

        return $joinCondition;
    }
}
