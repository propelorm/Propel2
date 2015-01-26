<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Util;

use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Map\BookTableMap;

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
    public function testDoSelect()
    {
        try {
            $c = new Criteria();
            $c->add(BookTableMap::COL_ID, 12, ' BAD SQL');
            BookTableMap::addSelectColumns($c);
            $c->doSelect();
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains($this->getSql('[SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.id BAD SQL:p1]'), $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoCount()
    {
        try {
            $c = new Criteria();
            $c->add(BookTableMap::COL_ID, 12, ' BAD SQL');
            BookTableMap::addSelectColumns($c);
            $c->doCount();
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains($this->getSql('[SELECT COUNT(*) FROM book WHERE book.id BAD SQL:p1]'), $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoDelete()
    {
        try {
            $c = new Criteria();
            $c->setPrimaryTableName(BookTableMap::TABLE_NAME);
            $c->add(BookTableMap::COL_ID, 12, ' BAD SQL');
            $c->doDelete(Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains($this->getSql('[DELETE FROM book WHERE book.id BAD SQL:p1]'), $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoUpdate()
    {
        try {
            $c1 = new Criteria();
            $c1->setPrimaryTableName(BookTableMap::TABLE_NAME);
            $c1->add(BookTableMap::COL_ID, 12, ' BAD SQL');
            $c2 = new Criteria();
            $c2->add(BookTableMap::COL_TITLE, 'Foo');

            $c1->doUpdate($c2, Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains($this->getSql('[UPDATE book SET title=:p1 WHERE book.id BAD SQL:p2]'), $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoInsert()
    {
        $con = Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME);

        try {
            $c = new Criteria();
            $c->setPrimaryTableName(BookTableMap::TABLE_NAME);
            $c->add(BookTableMap::COL_AUTHOR_ID, 'lkhlkhj');

            $db = Propel::getServiceContainer()->getAdapter($c->getDbName());

            $c->doInsert($con);
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            if ($db->isGetIdBeforeInsert()) {
                $this->assertContains($this->getSql('[INSERT INTO book (author_id,id) VALUES (:p1,:p2)]'), $e->getMessage(), 'SQL query is written in the exception message');
            } else {
                $this->assertContains($this->getSql('[INSERT INTO book (author_id) VALUES (:p1)]'), $e->getMessage(), 'SQL query is written in the exception message');
            }

        }
    }
}
