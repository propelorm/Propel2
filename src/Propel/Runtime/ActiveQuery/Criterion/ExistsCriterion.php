<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Map\RelationMap;

/**
 * Specialized Criterion used for EXISTS
 */
class ExistsCriterion extends AbstractCriterion
{
    public const TYPE_EXISTS = 'EXISTS';

    public const TYPE_NOT_EXISTS = 'NOT EXISTS';

    /**
     * The inner query of the exists
     *
     * @var \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    private $existsQuery;

    /**
     * Build NOT EXISTS instead of EXISTS
     *
     * @var string $keyword Either ExistsCriterion::TYPE_EXISTS or ExistsCriterion::TYPE_NOT_EXISTS
     *
     * @phpstan-var ExistsCriterion::TYPE_*
     */
    private $typeOfExists = self::TYPE_EXISTS;

    /**
     * @phpstan-param ExistsCriterion::TYPE_*|null $typeOfExists
     *
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $outerQuery
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $existsQuery
     * @param string|null $typeOfExists Either ExistsCriterion::TYPE_EXISTS or ExistsCriterion::TYPE_NOT_EXISTS
     * @param \Propel\Runtime\Map\RelationMap|null $relationMap where outer query is on the left side
     */
    public function __construct($outerQuery, $existsQuery, ?string $typeOfExists = null, ?RelationMap $relationMap = null)
    {
        parent::__construct($outerQuery, '', null, null);
        $this->existsQuery = $existsQuery;
        $this->typeOfExists = ($typeOfExists === self::TYPE_NOT_EXISTS) ? self::TYPE_NOT_EXISTS : self::TYPE_EXISTS;

        if ($relationMap !== null) {
            $joinCondition = $this->buildJoinCondition($outerQuery, $relationMap);
            $this->existsQuery->addAnd($joinCondition);
        }
    }

    /**
     * @see \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion::appendPsForUniqueClauseTo()
     *
     * @param string $sb The string that will receive the Prepared Statement
     * @param array $params A list to which Prepared Statement parameters will be appended
     *
     * @return void
     */
    protected function appendPsForUniqueClauseTo(&$sb, array &$params)
    {
        $existsQuery = $this->existsQuery
            ->clearSelectColumns()
            ->addAsColumn('existsFlag', '1')
            ->createSelectSql($params);
        $sb .= $this->typeOfExists . ' (' . $existsQuery . ')';
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\ModelCriteria $outerQuery
     * @param \Propel\Runtime\Map\RelationMap $relationMap where outer query is on the left side
     *
     * @return \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion
     */
    protected function buildJoinCondition($outerQuery, RelationMap $relationMap)
    {
        $join = new ModelJoin();
        $outerAlias = $outerQuery->getModelAlias();
        $innerAlias = $this->existsQuery->getModelAlias();
        $join->setRelationMap($relationMap, $outerAlias, $innerAlias);
        $join->buildJoinCondition($outerQuery);

        $joinCondition = $join->getJoinCondition();
        $joinCondition->setTable($this->existsQuery->getTableNameInQuery());

        return $joinCondition;
    }
}
