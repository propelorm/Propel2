<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Map\RelationMap;

/**
 * Specialized Criterion used for EXISTS
 */
class ExistsQueryCriterion extends AbstractInnerQueryCriterion
{
    /**
     * @var string
     */
    public const TYPE_EXISTS = 'EXISTS';

    /**
     * @var string
     */
    public const TYPE_NOT_EXISTS = 'NOT EXISTS';

    /**
     * @see AbstractInnerQueryCriterion::initRelation()
     *
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $outerQuery
     * @param \Propel\Runtime\Map\RelationMap $relation
     *
     * @return void
     */
    protected function initForRelation(ModelCriteria $outerQuery, RelationMap $relation): void
    {
        $joinCondition = $this->buildJoinCondition($outerQuery, $relation);
        $this->innerQuery->addAnd($joinCondition);
    }

    /**
     * @see AbstractNestedQueryCriterion::resolveOperator()
     *
     * @param string|null $operatorDeclaration
     *
     * @return string
     */
    protected function resolveOperator(?string $operatorDeclaration): string
    {
        return ($operatorDeclaration === static::TYPE_NOT_EXISTS) ? static::TYPE_NOT_EXISTS : static::TYPE_EXISTS;
    }

    /**
     * @see AbstractNestedQueryCriterion::processInnerQuery()
     * Allows to edit or replace the inner query before it is turned to SQL.
     *
     * @return \Propel\Runtime\ActiveQuery\Criteria
     */
    protected function processInnerQuery(): Criteria
    {
        return $this->innerQuery
            ->clearSelectColumns()
            ->addAsColumn('existsFlag', '1');
    }
}
