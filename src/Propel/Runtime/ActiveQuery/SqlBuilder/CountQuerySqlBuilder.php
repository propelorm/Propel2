<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\SqlBuilder;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\LogicException;

class CountQuerySqlBuilder extends AbstractSqlQueryBuilder
{
    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     *
     * @return array
     */
    public static function createCountSql(Criteria $criteria): array
    {
        $builder = new CountQuerySqlBuilder($criteria);

        return $builder->build();
    }

    /**
     * Create a Sql COUNT statement.
     *
     * @throws \Propel\Runtime\Exception\LogicException
     *
     * @return array
     */
    public function build(): array
    {
        $needsComplexCount = $this->criteria->getGroupByColumns()
            || $this->criteria->getOffset()
            || $this->criteria->getLimit() >= 0
            || $this->criteria->getHaving()
            || in_array(Criteria::DISTINCT, $this->criteria->getSelectModifiers(), true)
            || $this->criteria->hasSelectQueries();

        if (!$needsComplexCount) {
            $this->criteria
                ->clearSelectColumns()
                ->addSelectColumn('COUNT(*)');

            return SelectQuerySqlBuilder::createSelectSql($this->criteria);
        }

        if ($this->criteria->needsSelectAliases()) {
            if ($this->criteria->getHaving()) {
                $errorMessage = 'Propel cannot create a COUNT query when using HAVING and duplicate column names in the SELECT part';

                throw new LogicException($errorMessage);
            }

            $this->adapter->turnSelectColumnsToAliases($this->criteria);
        }
        [$baseSelectSql, $params] = SelectQuerySqlBuilder::createSelectSql($this->criteria);

        $countStatment = "SELECT COUNT(*) FROM ($baseSelectSql) propelmatch4cnt";

        return [$countStatment, $params];
    }
}
