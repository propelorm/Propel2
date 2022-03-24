<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\QueryExecutor;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\SqlBuilder\UpdateQuerySqlBuilder;
use Propel\Runtime\Connection\ConnectionInterface;

class UpdateQueryExecutor extends AbstractQueryExecutor
{
    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param \Propel\Runtime\ActiveQuery\Criteria $updateValues
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return int
     */
    public static function execute(Criteria $criteria, Criteria $updateValues, ?ConnectionInterface $con = null): int
    {
        $executor = new self($criteria, $con);

        return $executor->runUpdate($updateValues);
    }

    /**
     * Method used to update rows in the DB. Rows are selected based
     * on selectCriteria and updated using values in updateValues.
     * <p>
     * Use this method for performing an update of the kind:
     * <p>
     * WHERE some_column = some value AND could_have_another_column =
     * another value AND so on.
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $updateValues A Criteria object containing values used in set clause.
     *
     * @return int The number of rows affected by last update statement.
     *             For most uses there is only one update statement executed, so this number will
     *             correspond to the number of rows affected by the call to this method.
     *             Note that the return value does require that this information is returned
     *             (supported) by the Propel db driver.
     */
    protected function runUpdate(Criteria $updateValues): int
    {
        $updateTablesColumns = $updateValues->getTablesColumns();

        if (!$updateTablesColumns) {
            return 0;
        }

        $tablesColumns = $this->criteria->getTablesColumns();
        $table = $this->criteria->getPrimaryTableName();
        if (!$tablesColumns && $table) {
            $tablesColumns = [$table => []];
        }

        $builder = new UpdateQuerySqlBuilder($this->criteria, $updateValues);

        $affectedRows = 0;
        foreach ($tablesColumns as $tableName => $columns) {
            $preparedStatementDto = $builder->build($tableName, $columns);
            /** @var \Propel\Runtime\Connection\StatementInterface $stmt */
            $stmt = $this->executeStatement($preparedStatementDto);
            $affectedRows += $stmt->rowCount();
        }

        return $affectedRows;
    }
}
