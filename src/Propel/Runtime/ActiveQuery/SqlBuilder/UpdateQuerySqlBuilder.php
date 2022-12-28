<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\SqlBuilder;

use Propel\Runtime\ActiveQuery\Criteria;

/**
 * This class produces the base object class (e.g. BaseMyTable) which contains
 * all the custom-built accessor and setter methods.
 */
class UpdateQuerySqlBuilder extends AbstractSqlQueryBuilder
{
    /**
     * @var \Propel\Runtime\ActiveQuery\Criteria
     */
    protected $updateValues;

    /**
     * @psalm-var array<string, array<string>>
     * @var array<array<string>>
     */
    protected $updateTablesColumns;

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param \Propel\Runtime\ActiveQuery\Criteria $updateValues
     */
    public function __construct(Criteria $criteria, Criteria $updateValues)
    {
        parent::__construct($criteria);
        $this->updateValues = $updateValues;
        $this->updateTablesColumns = $updateValues->getTablesColumns();
    }

    /**
     * @param string $tableName
     * @param array<string> $qualifiedTableColumnNames
     *
     * @return \Propel\Runtime\ActiveQuery\SqlBuilder\PreparedStatementDto
     */
    public function build(string $tableName, array $qualifiedTableColumnNames): PreparedStatementDto
    {
        [$tableName, $updateTable] = $this->getTableNameWithAlias($tableName);
        $tableColumnNames = $this->updateTablesColumns[$tableName];

        $updateSql = ['UPDATE'];
        $queryComment = $this->criteria->getComment();
        if ($queryComment) {
            $updateSql[] = '/* ' . $queryComment . ' */';
        }
        $updateSql[] = $this->quoteIdentifierTable($updateTable);
        $updateSql[] = 'SET';
        $updateSql[] = $this->buildAssignmentList($tableName, $tableColumnNames);

        $params = $this->buildParams($tableColumnNames, $this->updateValues);
        $whereClause = $this->buildWhereClause($qualifiedTableColumnNames, $params);
        if ($whereClause) {
            $updateSql[] = 'WHERE';
            $updateSql[] = $whereClause;
        }
        $updateSql = implode(' ', $updateSql);

        return new PreparedStatementDto($updateSql, $params);
    }

    /**
     * @psalm-param array<string, string> $qualifiedTableColumnNames
     *
     * @param string $tableName
     * @param array<string> $qualifiedTableColumnNames
     *
     * @return string
     */
    protected function buildAssignmentList(string $tableName, array $qualifiedTableColumnNames): string
    {
        $positionIndex = 1;
        $assignmentClauses = [];
        foreach ($qualifiedTableColumnNames as $qualifiedColumnName) {
            $assignmentClauses[] = $this->buildAssignementClause($tableName, $qualifiedColumnName, $positionIndex);
        }

        return implode(', ', $assignmentClauses);
    }

    /**
     * @param string $tableName
     * @param string $qualifiedColumnName
     * @param int $positionIndex
     *
     * @return string
     */
    protected function buildAssignementClause(string $tableName, string $qualifiedColumnName, int &$positionIndex): string
    {
        $dotPos = strrpos($qualifiedColumnName, '.');
        $columnNameInUpdate = substr($qualifiedColumnName, $dotPos + 1);
        $columnNameInUpdate = $this->criteria->quoteIdentifier($columnNameInUpdate, $tableName);

        $columnEquals = $columnNameInUpdate . '=';

        if ($this->updateValues->getComparison($qualifiedColumnName) !== Criteria::CUSTOM_EQUAL) {
            return $columnEquals . ':p' . $positionIndex++;
        }

        $param = $this->updateValues->get($qualifiedColumnName);
        if (!is_array($param)) {
            $this->updateValues->remove($qualifiedColumnName);

            return $columnEquals . $param;
        }

        if (isset($param['value'])) {
            $this->updateValues->put($qualifiedColumnName, $param['value']);
        }

        if (isset($param['raw'])) {
            $rawParameter = $param['raw'];

            return $columnEquals . $this->buildRawParameter($rawParameter, $positionIndex);
        }

        return $columnEquals . ':p' . $positionIndex++;
    }

    /**
     * Replaces question mark symbols with potsitional parameter placeholders (i.e. ':p2' for the second update parameter)
     *
     * @param string $rawParameter
     * @param int $positionIndex
     *
     * @return string
     */
    protected function buildRawParameter(string $rawParameter, int &$positionIndex): string
    {
        return preg_replace_callback('#\?#', function (array $match) use (&$positionIndex) {
            return ':p' . $positionIndex++;
        }, $rawParameter);
    }

    /**
     * @param array<string> $qualifiedTableColumnNames
     * @param array<mixed>|null $params
     *
     * @return string|null
     */
    protected function buildWhereClause(array $qualifiedTableColumnNames, ?array &$params): ?string
    {
        if (!$qualifiedTableColumnNames) {
            return null;
        }

        $whereClause = [];
        foreach ($qualifiedTableColumnNames as $qualifiedTableColumnName) {
            $filter = $this->criteria->getCriterion($qualifiedTableColumnName);
            $whereClause[] = $this->buildStatementFromCriterion($filter, $params);
        }

        return implode(' AND ', $whereClause);
    }
}
