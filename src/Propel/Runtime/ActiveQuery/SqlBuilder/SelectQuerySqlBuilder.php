<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\SqlBuilder;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;

/**
 * This class produces the base object class (e.g. BaseMyTable) which contains
 * all the custom-built accessor and setter methods.
 */
class SelectQuerySqlBuilder extends AbstractSqlQueryBuilder
{
    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param array<mixed> $params
     *
     * @return \Propel\Runtime\ActiveQuery\SqlBuilder\PreparedStatementDto
     */
    public static function createSelectSql(Criteria $criteria, array &$params = []): PreparedStatementDto
    {
        $builder = new self($criteria);

        return $builder->build($params);
    }

    /**
     * Method to create an SQL query based on values in a Criteria.
     *
     * This method creates only prepared statement SQL (using ? where values
     * will go). The second parameter ($params) stores the values that need
     * to be set before the statement is executed. The reason we do it this way
     * is to let the PDO layer handle all escaping & value formatting.
     *
     * @param array<mixed> $params
     *
     * @return \Propel\Runtime\ActiveQuery\SqlBuilder\PreparedStatementDto
     */
    public function build(array &$params): PreparedStatementDto
    {
        $sourceTableNamesCollector = [];

        $selectSql = $this->buildSelectClause($sourceTableNamesCollector);
        $joinClauses = $this->buildJoinClauses($params, $sourceTableNamesCollector); // we want params from joins resolved before params from where
        $whereSql = $this->buildWhereClause($params, $sourceTableNamesCollector);
        $fromSql = $this->buildFromClause($params, $sourceTableNamesCollector, $joinClauses);

        [$orderBySql, $additionalSelectStatements] = $this->buildOrderByClause($params);
        $selectSql .= $additionalSelectStatements;

        $sqlClauses = [$selectSql, $fromSql];

        if ($whereSql) {
            $sqlClauses[] = $whereSql;
        }

        $groupBy = $this->adapter->getGroupBy($this->criteria);
        if ($groupBy) {
            $this->criteria->replaceNames($groupBy);
            $sqlClauses[] = $groupBy;
        }

        $havingSql = $this->buildHavingClause($params);
        if ($havingSql) {
            $sqlClauses[] = $havingSql;
        }

        if ($orderBySql) {
            $sqlClauses[] = $orderBySql;
        }

        $sql = implode(' ', $sqlClauses);

        $limit = $this->criteria->getLimit();
        $offset = $this->criteria->getOffset();
        if ($limit >= 0 || $offset) {
            $this->adapter->applyLimit($sql, $offset, $limit, $this->criteria);
        }

        $lock = $this->criteria->getLock();
        if ($lock !== null) {
            $this->adapter->applyLock($sql, $lock);
        }

        return new PreparedStatementDto($sql, $params);
    }

    /**
     * @param array<string> $sourceTableNamesCollector
     *
     * @return string
     */
    protected function buildSelectClause(array &$sourceTableNamesCollector): string
    {
        $selectSql = $this->adapter->createSelectSqlPart($this->criteria, $sourceTableNamesCollector);
        $this->criteria->replaceNames($selectSql);

        return $selectSql;
    }

    /**
     * @param array<mixed>|null $params
     * @param array<string> $sourceTableNames
     * @param array<string> $joinClause
     *
     * @return string
     */
    protected function buildFromClause(?array &$params, array $sourceTableNames, array $joinClause): string
    {
        $sourceTableNames = array_filter($sourceTableNames);
        $sourceTableNames = array_unique($sourceTableNames);

        $joinTableNames = $this->getJoinTableNames();
        if ($joinTableNames) {
            $sourceTableNames = array_diff($sourceTableNames, $joinTableNames);
        }

        $this->removeRecursiveSubqueryTableAliases($sourceTableNames);

        $sourceTableNames = array_map([$this, 'quoteIdentifierTable'], $sourceTableNames);

        foreach ($this->criteria->getSelectQueries() as $subQueryAlias => $subQueryCriteria) {
            $sourceTableNames[] = '(' . $subQueryCriteria->createSelectSql($params) . ') AS ' . $subQueryAlias;
        }

        if (!$sourceTableNames && $this->criteria->getPrimaryTableName()) {
            $primaryTable = $this->criteria->getPrimaryTableName();
            $sourceTableNames[] = $this->quoteIdentifierTable($primaryTable);
        }

        $glue = ($joinClause && count($sourceTableNames) > 1) ? ' CROSS JOIN ' : ', ';
        $from = 'FROM ' . implode($glue, $sourceTableNames);

        if ($joinClause) {
            $from .= ' ' . implode(' ', $joinClause);
        }

        return $from;
    }

    /**
     * If a subqueries uses the same table as the outer query, it adds an alias to the parent query (legacy behavior).
     * This method removes those aliases from the list of source table names.
     *
     * @see \Propel\Runtime\ActiveQuery\ModelCriteria::addSelectQuery()
     *
     * @param array<string> $sourceTableNames
     *
     * @return void
     */
    protected function removeRecursiveSubqueryTableAliases(array &$sourceTableNames): void
    {
        if (!$this->criteria->hasSelectQueries()) {
            return;
        }

        foreach ($sourceTableNames as $index => $rawTableName) {
            $spacePos = strpos($rawTableName, ' ');
            $tableName = ($spacePos !== false) ? substr($rawTableName, $spacePos + 1) : $rawTableName;

            if ($this->criteria->hasSelectQuery($tableName)) {
                unset($sourceTableNames[$index]);
            }
        }
    }

    /**
     * Handle joins
     *  joins with a null join type will be added to the FROM clause and the condition added to the WHERE clause.
     *  joins of a specified type: the LEFT side will be added to the fromClause and the RIGHT to the joinClause
     *
     * @param array|null $params
     * @param array<string> $sourceTableNamesCollector
     *
     * @return array<string>
     */
    protected function buildJoinClauses(?array &$params, array &$sourceTableNamesCollector): array
    {
        $joinClause = [];

        foreach ($this->criteria->getJoins() as $join) {
            if (!$sourceTableNamesCollector) {
                $sourceTableNamesCollector[] = $join->getLeftTableWithAlias();
            }
            $join->setAdapter($this->adapter);
            $joinClauseString = $join->getClause($params);
            $this->criteria->replaceNames($joinClauseString);
            $joinClause[] = $joinClauseString;
        }

        return $joinClause;
    }

    /**
     * @return array<string>
     */
    protected function getJoinTableNames(): array
    {
        $joinTables = [];
        foreach ($this->criteria->getJoins() as $join) {
            /** @var string $table */
            $table = $join->getRightTableWithAlias();
            $joinTables[] = $table;
        }

        return $joinTables;
    }

    /**
     * this will also add the table names to the FROM clause if they are not already included via a LEFT JOIN
     *
     * @param array|null $params
     * @param array<string> $sourceTableNamesCollector
     *
     * @return string|null
     */
    protected function buildWhereClause(?array &$params, array &$sourceTableNamesCollector): ?string
    {
        $columnNameToCriterions = $this->criteria->getMap();
        if (!$columnNameToCriterions) {
            return null;
        }

        $whereClause = [];

        foreach ($columnNameToCriterions as $criterion) {
            foreach ($criterion->getAttachedCriterion() as $attachedCriterion) {
                $rawTableName = $attachedCriterion->getTable();
                if (!$rawTableName) {
                    continue;
                }
                [$realTableName, $sourceTableNamesCollector[]] = $this->getTableNameWithAlias($rawTableName);
                $this->setCriterionsIgnoreCase($attachedCriterion, $realTableName);
            }
            $criterion->setAdapter($this->adapter);

            $whereClause[] = $this->buildStatementFromCriterion($criterion, $params);
        }

        return 'WHERE ' . implode(' AND ', $whereClause);
    }

    /**
     * Set the criterion to be case insensitive if requested.
     *
     * @param \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion $criterion
     * @param string $realTableName
     *
     * @return void
     */
    protected function setCriterionsIgnoreCase(AbstractCriterion $criterion, string $realTableName): void
    {
        if (!$this->criteria->isIgnoreCase()) {
            return;
        }

        if (!method_exists($criterion, 'setIgnoreCase')) {
            return;
        }

        $column = $criterion->getColumn();
        $isTextColumn = $this->dbMap->getTable($realTableName)->getColumn($column)->isText();
        if (!$isTextColumn) {
            return;
        }

        $criterion->setIgnoreCase(true);
    }

    /**
     * @param array $params
     *
     * @return array<string>
     */
    protected function buildOrderByClause(array &$params): array
    {
        $orderBy = $this->criteria->getOrderByColumns();
        if (!$orderBy) {
            return ['', ''];
        }
        $orderByClause = [];
        $additionalSelectStatements = [];

        foreach ($orderBy as $orderByColumn) {
            $parenthesesOpenPos = strpos($orderByColumn, '(');
            $isFunctionStatement = ($parenthesesOpenPos !== false);
            if ($isFunctionStatement) {
                $orderByClause[] = $orderByColumn;

                continue;
            }

            $dotPos = strrpos($orderByColumn, '.');
            if ($dotPos !== false) {
                $tableName = substr($orderByColumn, 0, $dotPos);
                $columnName = substr($orderByColumn, $dotPos + 1);
            } else {
                $tableName = '';
                $columnName = $orderByColumn;
            }

            $spacePos = strpos($columnName, ' ');
            if ($spacePos !== false) {
                $direction = substr($columnName, $spacePos); // keep leading space
                $columnName = substr($columnName, 0, $spacePos);
            } else {
                $direction = '';
            }

            $tableAlias = $tableName;
            $aliasTableName = $this->criteria->getTableForAlias($tableName);
            if ($aliasTableName) {
                $tableName = $aliasTableName;
            }

            $columnAlias = $columnName;
            $asColumnName = $this->criteria->getColumnForAs($columnName);
            if ($asColumnName) {
                $columnName = $asColumnName;
            }

            $column = ($tableName) ? $this->dbMap->getTable($tableName)->getColumn($columnName) : null;
            if ($this->criteria->isIgnoreCase() && $column && $column->isText()) {
                $ignoreCaseColumn = $this->adapter->ignoreCaseInOrderBy("$tableAlias.$columnAlias");
                $this->criteria->replaceNames($ignoreCaseColumn);
                $orderByClause[] = $ignoreCaseColumn . $direction;
                $additionalSelectStatements[] = ', ' . $ignoreCaseColumn;
            } else {
                $this->criteria->replaceNames($orderByColumn);
                $orderByClause[] = $orderByColumn;
            }
        }

        $orderBySql = 'ORDER BY ' . implode(',', $orderByClause);
        $additionalSelect = implode('', $additionalSelectStatements);

        return [$orderBySql, $additionalSelect];
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    protected function buildHavingClause(array &$params): ?string
    {
        $havingCriterion = $this->criteria->getHaving();
        if (!$havingCriterion) {
            return null;
        }
        $havingStatement = $this->buildStatementFromCriterion($havingCriterion, $params);

        return "HAVING $havingStatement";
    }
}
