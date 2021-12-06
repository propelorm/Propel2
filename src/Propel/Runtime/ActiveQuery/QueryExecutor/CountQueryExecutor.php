<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\QueryExecutor;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\SqlBuilder\CountQuerySqlBuilder;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

class CountQueryExecutor extends AbstractQueryExecutor
{
    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con a connection object
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public static function execute(Criteria $criteria, ?ConnectionInterface $con = null): DataFetcherInterface
    {
        $executor = new self($criteria, $con);

        return $executor->runCount();
    }

    /**
     * Execute a count statement.
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    protected function runCount(): DataFetcherInterface
    {
        $preparedStatementDto = CountQuerySqlBuilder::createCountSql($this->criteria);
        $stmt = $this->executeStatement($preparedStatementDto);

        return $this->con->getDataFetcher($stmt);
    }
}
