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
 * Specialized Criterion used for IN
 */
class InQueryCriterion extends AbstractInnerQueryCriterion
{
    /**
     * @var string
     */
    public const IN = 'IN';

    /**
     * @var string
     */
    public const NOT_IN = 'NOT IN';

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
        $outerColumns = $relation->getLeftColumns();
        $this->leftOperand = ($outerColumns) ? reset($outerColumns)->getFullyQualifiedName() : null;

        $innerColumns = $relation->getRightColumns();
        if ($innerColumns && $this->innerQuery instanceof ModelCriteria) {
            $columnName = reset($innerColumns)->getFullyQualifiedName();
            $this->innerQuery->select($columnName);
        }
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
        return ($operatorDeclaration === static::NOT_IN) ? static::NOT_IN : static::IN;
    }

    /**
     * @see AbstractNestedQueryCriterion::processInnerQuery()
     * Allows to edit or replace the inner query before it is turned to SQL.
     *
     * @return \Propel\Runtime\ActiveQuery\Criteria
     */
    protected function processInnerQuery(): Criteria
    {
        return $this->innerQuery;
    }
}
