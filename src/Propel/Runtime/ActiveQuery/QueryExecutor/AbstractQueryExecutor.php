<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery\QueryExecutor;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\SqlBuilder\PreparedStatementDto;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\StatementWrapper;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use RuntimeException;
use Throwable;

abstract class AbstractQueryExecutor
{
    /**
     * Indicates if the executor performs writes.
     * Defaults to true, but is overridden by subclasses.
     *
     * @see AbstractQueryExecutor::getConnection()
     *
     * @var bool
     */
    protected const NEEDS_WRITE_CONNECTION = true;

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $con;

    /**
     * @var \Propel\Runtime\Adapter\SqlAdapterInterface
     */
    protected $adapter;

    /**
     * @var \Propel\Runtime\ActiveQuery\Criteria
     */
    protected $criteria;

    /**
     * @var \Propel\Runtime\Map\DatabaseMap
     */
    protected $dbMap;

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $con
     */
    public function __construct(Criteria $criteria, ?ConnectionInterface $con = null)
    {
        $this->criteria = $criteria;

        $dbName = $criteria->getDbName();
        $serviceContainer = Propel::getServiceContainer();

        $this->con = $con ?: $this->retrieveConnection($serviceContainer, $dbName, static::NEEDS_WRITE_CONNECTION);

        /** @var \Propel\Runtime\Adapter\SqlAdapterInterface $adapter */
        $adapter = $serviceContainer->getAdapter($dbName);
        $this->adapter = $adapter;

        $this->dbMap = $serviceContainer->getDatabaseMap($dbName);
    }

    /**
     * Retrieves a read or write connection from the service container.
     *
     * @param \Propel\Runtime\ServiceContainer\ServiceContainerInterface $sc
     * @param string $dbName
     * @param bool $getWritableConnection
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    protected function retrieveConnection(ServiceContainerInterface $sc, string $dbName, bool $getWritableConnection = true): ConnectionInterface
    {
        return ($getWritableConnection) ? $sc->getWriteConnection($dbName) : $sc->getReadConnection($dbName);
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\SqlBuilder\PreparedStatementDto $preparedStatementDto
     *
     * @throws \RuntimeException
     *
     * @return \PDOStatement|\Propel\Runtime\Connection\StatementInterface|bool|null
     */
    protected function executeStatement(PreparedStatementDto $preparedStatementDto)
    {
        $sql = $preparedStatementDto->getSqlStatement();
        $params = $preparedStatementDto->getParameters();
        $this->adapter->cleanupSQL($sql, $params, $this->criteria, $this->dbMap);

        $stmt = null;
        try {
            /** @var \Propel\Runtime\Connection\StatementInterface|false $stmt */
            $stmt = $this->con->prepare($sql);

            if ($stmt === false) {
                throw new RuntimeException('PdoConnection::prepare() failed and did not return statement object for execution.');
            }

            if ($params) {
                $this->adapter->bindValues($stmt, $params, $this->dbMap);
            }
            $stmt->execute();
        } catch (Throwable $e) {
            $this->handleStatementException($e, $sql, $stmt ?: null);
        }

        return $stmt;
    }

    /**
     * Logs an exception and adds the complete SQL statement to the exception.
     *
     * @param \Throwable $e The initial exception.
     * @param string|null $sql The SQL statement which triggered the exception.
     * @param \Propel\Runtime\Connection\StatementInterface|\PDOStatement|null $stmt The prepared statement.
     *
     * @throws \Propel\Runtime\ActiveQuery\QueryExecutor\QueryExecutionException
     *
     * @return void
     */
    protected function handleStatementException(Throwable $e, ?string $sql, $stmt = null): void
    {
        $internalMessage = $e->getMessage();
        Propel::log($internalMessage, Propel::LOG_ERR);

        $isDebugMode = $this->connectionIsInDebugMode();
        if ($isDebugMode && $stmt instanceof StatementWrapper) {
            $sql = $stmt->getExecutedQueryString();
        }
        $publicMessage = "Unable to execute statement [$sql]";
        if ($isDebugMode) {
            $publicMessage .= PHP_EOL . "Reason: [$internalMessage]";
        }

        throw new QueryExecutionException($publicMessage, 0, $e);
    }

    /**
     * Check if the current connection has debug mode enabled
     *
     * @return bool
     */
    protected function connectionIsInDebugMode(): bool
    {
        return ($this->con instanceof ConnectionWrapper && $this->con->isInDebugMode());
    }
}
