<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\SqlBuilder;

use Propel\Runtime\ActiveQuery\Criteria;

class DeleteQuerySqlBuilder extends AbstractSqlQueryBuilder
{
    /**
     * Build a Sql DELETE statment which deletes all data
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param string $tableName
     *
     * @return string
     */
    public static function createDeleteAllSql(Criteria $criteria, string $tableName): string
    {
        $builder = new DeleteQuerySqlBuilder($criteria);

        return $builder->buildDeleteFromClause($tableName);
    }

    /**
     * Create a Sql DELETE statement.
     *
     * @param string $tableName
     * @param array $columnNames
     *
     * @return array
     */
    public function build(string $tableName, array $columnNames): array
    {
        $deleteFrom = $this->buildDeleteFromClause($tableName);
        [$where, $params] = $this->buildWhereClause($columnNames);
        $deleteStatement = "$deleteFrom $where";

        return [$deleteStatement, $params];
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function buildDeleteFromClause(string $tableName): string
    {
        $sql = ['DELETE'];
        if ($queryComment = $this->criteria->getComment()) {
            $sql[] = '/* ' . $queryComment . ' */';
        }

        $realTableName = $this->criteria->getTableForAlias($tableName);
        if (!$realTableName) {
            $tableName = $this->quoteIdentifierTable($tableName);
            $sql[] = "FROM $tableName";
        } else {
            $realTableName = $this->quoteIdentifierTable($realTableName);
            if ($this->adapter->supportsAliasesInDelete()) {
                $sql[] = $tableName;
            }
            $sql[] = "FROM $realTableName AS $tableName";
        }

        return implode(' ', $sql);
    }

    /**
     * Build WHERE clause from the given column names.
     *
     * @param array $columnNames
     *
     * @return array
     */
    protected function buildWhereClause(array $columnNames): array
    {
        $whereClause = [];
        $params = [];
        foreach ($columnNames as $columnName) {
            $filter = $this->criteria->getCriterion($columnName);
            $whereClause[] = $this->buildStatementFromCriterion($filter, $params);
        }
        $whereStatement = 'WHERE ' . implode(' AND ', $whereClause);

        return [$whereStatement, $params];
    }
}
