<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Util;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\QueryExecutor\QueryExecutionException;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests the exceptions thrown by the TableMap classes.
 *
 * @see BookstoreDataPopulator
 *
 * @group database
 */
class TableMapExceptionsTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    public function testDoSelectExceptionsAreHandledCorrectly()
    {
        $this->expectException(QueryExecutionException::class);
        BookQuery::create()->where('oh this is no sql')->find();
    }

    /**
     * @return void
     */
    public function testDoCountExceptionsAreHandledCorrectly()
    {
        $this->expectException(QueryExecutionException::class);
        BookQuery::create()->where('oh this is no sql')->count();
    }

    /**
     * @return void
     */
    public function testDoDeleteExceptionsAreHandledCorrectly()
    {
        $this->expectException(QueryExecutionException::class);
        BookQuery::create()->where('oh this is no sql')->delete();
    }

    /**
     * @return void
     */
    public function testDoUpdateExceptionsAreHandledCorrectly()
    {
        $c1 = new Criteria();
        $c1->setPrimaryTableName(BookTableMap::TABLE_NAME);
        $c1->add(BookTableMap::COL_ID, 12, ' BAD SQL');
        $c2 = new Criteria();
        $c2->add(BookTableMap::COL_TITLE, 'Foo');

        $this->expectException(QueryExecutionException::class);
        $c1->doUpdate($c2, Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
    }

    /**
     * @return void
     */
    public function testDoInsertExceptionsAreHandledCorrectly()
    {
        $c = new Criteria();
        $c->setPrimaryTableName(BookTableMap::TABLE_NAME);
        $c->add(BookTableMap::COL_ID, 'lkhlkhj');
        $c->add(BookTableMap::COL_AUTHOR_ID, 'lkhlkhj');

        $this->expectException(QueryExecutionException::class);
        $c->doInsert($this->con);
    }
}
