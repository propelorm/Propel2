<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\QueryExecutor;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\SqlBuilder\SelectQuerySqlBuilder;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;

class SelectQueryExecutor extends AbstractQueryExecutor
{
    /**
     * @see AbstractQueryExecutor::NEEDS_WRITE_CONNECTION
     *
     * @var bool
     */
    protected const NEEDS_WRITE_CONNECTION = false;

    /**
     * @see SelectQueryExecutor::runInsert()
     *
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface
     */
    public static function execute(Criteria $criteria, ?ConnectionInterface $con = null): DataFetcherInterface
    {
        $executor = new self($criteria, $con);

        return $executor->runSelect();
    }

    /**
     * Builds, binds and executes a SELECT query based on the current object.
     *
     * @return \Propel\Runtime\DataFetcher\DataFetcherInterface A dataFetcher using the connection, ready to be fetched
     */
    protected function runSelect(): DataFetcherInterface
    {
        $params = [];
        $preparedStatementDto = SelectQuerySqlBuilder::createSelectSql($this->criteria, $params);
        $stmt = $this->executeStatement($preparedStatementDto);

        return $this->con->getDataFetcher($stmt);
    }
}
