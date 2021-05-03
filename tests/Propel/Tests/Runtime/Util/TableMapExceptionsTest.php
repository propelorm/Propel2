<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Util;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Exception;
use Propel\Tests\Bookstore\BookQuery;

/**
 * Tests the exceptions thrown by the TableMap classes.
 *
 * @see BookstoreDataPopulator
 * @author Francois Zaninotto
 *
 * @group database
 */
class TableMapExceptionsTest extends BookstoreTestBase
{
    /**
     * Get BookQuery child class through method, since the parent class is not always available on CI tool.
     *
     * @throws CorrectlyHandledException
     * @return BookQuery class instance
     */
    public static function createHandledBookQuery() : BookQuery
    {
        return new class() extends BookQuery{
            protected function handleStatementException(Exception $e, ?string $sql, ?ConnectionInterface $con = null, $stmt = null): void
            {
                throw new CorrectlyHandledException();
            }
        };
    }
    
    /**
     * @return void
     */
    public function testDoSelectExceptionsAreHandledCorrectly()
    {
        $this->expectException(CorrectlyHandledException::class);
        self::createHandledBookQuery()->where('oh this is no sql')->find();
    }

    /**
     * @return void
     */
    public function testDoCountExceptionsAreHandledCorrectly()
    {
        $this->expectException(CorrectlyHandledException::class);
        self::createHandledBookQuery()->where('oh this is no sql')->count();
    }

    /**
     * @return void
     */
    public function testDoDeleteExceptionsAreHandledCorrectly()
    {
        $this->expectException(CorrectlyHandledException::class);
        self::createHandledBookQuery()->where('oh this is no sql')->delete();
    }

    /**
     * @return void
     */
    public function testDoUpdateExceptionsAreHandledCorrectly()
    {
        $c1 = new TableMapExceptionsTestCriteria();
        $c1->setPrimaryTableName(BookTableMap::TABLE_NAME);
        $c1->add(BookTableMap::COL_ID, 12, ' BAD SQL');
        $c2 = new Criteria();
        $c2->add(BookTableMap::COL_TITLE, 'Foo');

        $this->expectException(CorrectlyHandledException::class);
        $c1->doUpdate($c2, Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
    }

    /**
     * @return void
     */
    public function testDoInsertExceptionsAreHandledCorrectly()
    {

        $c = new TableMapExceptionsTestCriteria();
        $c->setPrimaryTableName(BookTableMap::TABLE_NAME);
        $c->add(BookTableMap::COL_ID, 'lkhlkhj');
        $c->add(BookTableMap::COL_AUTHOR_ID, 'lkhlkhj');

        $this->expectException(CorrectlyHandledException::class);
        $c->doInsert($this->con);
    }

    /**
     * @return array
     */
    public function queryExceptionOutputFormatDataProvider()
    {
        // [$useDebug, $sqlStatement, $internalErrorMessage, $expectedPublicMessage]
        return [
            [false, '<SQL>', '<ERROR>', 'Unable to execute statement [<SQL>]'],
            [true, '<SQL>', '<ERROR>', "Unable to execute statement [<SQL>]\nReason: [<ERROR>]"],
        ];
    }

    /**
     * @dataProvider queryExceptionOutputFormatDataProvider
     *
     * @param bool $useDebug
     * @param string $sqlStatement
     * @param string $internalErrorMessage
     * @param string $expectedPublicMessage
     *
     * @return void
     */
    public function testQueryExceptionOutputFormat($useDebug, $sqlStatement, $internalErrorMessage, $expectedPublicMessage)
    {
        $c = new class () extends Criteria {
            public function simulateException($msg, $sql, $con)
            {
                return $this->handleStatementException(new PropelException($msg), $sql, $con);
            }
        };

        $con = new ConnectionWrapper($this->con->getWrappedConnection());
        $con->useDebug($useDebug);

        try {
            $c->simulateException($internalErrorMessage, $sqlStatement, $con);
            $this->fail('Cannot test without exception');
        } catch (PropelException $e) {
            $this->assertEquals($expectedPublicMessage, $e->getMessage());
        }
    }
}

class CorrectlyHandledException extends Exception
{
}

class TableMapExceptionsTestCriteria extends Criteria
{
    protected function handleStatementException(Exception $e, ?string $sql, ?ConnectionInterface $con = null, $stmt = null): void
    {
        throw new CorrectlyHandledException();
    }
}
