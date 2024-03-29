<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\QueryExecutor;

use Propel\Runtime\ActiveQuery\QueryExecutor\AbstractQueryExecutor;
use Propel\Runtime\ActiveQuery\QueryExecutor\QueryExecutionException;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * @group database
 */
class AbstractQueryExecutorTest extends BookstoreTestBase
{
    /**
     * @return array
     */
    public function queryExceptionOutputFormatDataProvider()
    {
        return [
            [
                '$useDebug' => false,
                '$sqlStatement' => '<SQL>',
                '$params' => [
                    ['column' => 'some_column', 'value' => 'text'],
                ],
                '$internalErrorMessage' => '<ERROR>',
                '$expectedPublicMessage' => 'Unable to execute statement [<SQL>] with params [column=`some_column` value=`text`, ]. Reason: [<ERROR>]',
            ],
            [
                '$useDebug' => true,
                '$sqlStatement' => '<SQL>',
                '$params' => [
                    ['column' => 'some_column', 'value' => 'text'],
                    ['column' => 'id', 'value' => 1],
                ],
                '$internalErrorMessage' => '<ERROR>',
                '$expectedPublicMessage' => 'Unable to execute statement [<SQL>] with params [column=`some_column` value=`text`, column=`id` value=`1`, ]. Reason: [<ERROR>]',
            ],
        ];
    }

    /**
     * @dataProvider queryExceptionOutputFormatDataProvider
     *
     * @param bool $useDebug
     * @param string $sqlStatement
     * @param array $params
     * @param string $internalErrorMessage
     * @param string $expectedPublicMessage
     *
     * @return void
     */
    public function testQueryExceptionOutputFormat($useDebug, $sqlStatement, $params, $internalErrorMessage, $expectedPublicMessage)
    {
        $query = BookQuery::create();
        $con = new ConnectionWrapper($this->con->getWrappedConnection());
        $con->useDebug($useDebug);

        $c = new class ($query, $con) extends AbstractQueryExecutor {
            public function simulateException($msg, $sql, $params, $con)
            {
                return $this->handleStatementException(new PropelException($msg), $params, $sql);
            }
        };

        try {
            $c->simulateException($internalErrorMessage, $sqlStatement, $params, $con);
            $this->fail('Cannot test without exception');
        } catch (QueryExecutionException $e) {
            $this->assertEquals($expectedPublicMessage, $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testGetConnectionDefaultsToWritableConnection(): void
    {
        $query = BookQuery::create();
        $executor = new class ($query) extends AbstractQueryExecutor{
            public $isWriteConnection;

            protected function retrieveConnection(ServiceContainerInterface $sc, string $dbName, bool $getWritableConnection = false): ConnectionInterface
            {
                $this->isWriteConnection = $getWritableConnection;

                return parent::retrieveConnection($sc, $dbName, $getWritableConnection);
            }
        };

        $this->assertTrue($executor->isWriteConnection, 'AbstractQueryExecutor should default to a writable connection');
    }
}
