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
 */
class TableMapExceptionsTest extends BookstoreTestBase
{
    public function testDoSelect()
    {
        try {
            $c = new Criteria();
            $c->add(BookTableMap::ID, 12, ' BAD SQL');
            BookTableMap::addSelectColumns($c);
            $c->doSelect();
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains('[SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.ID BAD SQL:p1]', $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoCount()
    {
        try {
            $c = new Criteria();
            $c->add(BookTableMap::ID, 12, ' BAD SQL');
            BookTableMap::addSelectColumns($c);
            $c->doCount();
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains('[SELECT COUNT(*) FROM `book` WHERE book.ID BAD SQL:p1]', $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoDelete()
    {
        try {
            $c = new Criteria();
            $c->setPrimaryTableName(BookTableMap::TABLE_NAME);
            $c->add(BookTableMap::ID, 12, ' BAD SQL');
            $c->doDelete(Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains('[DELETE FROM `book` WHERE book.ID BAD SQL:p1]', $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoUpdate()
    {
        try {
            $c1 = new Criteria();
            $c1->setPrimaryTableName(BookTableMap::TABLE_NAME);
            $c1->add(BookTableMap::ID, 12, ' BAD SQL');
            $c2 = new Criteria();
            $c2->add(BookTableMap::TITLE, 'Foo');

            $c1->doUpdate($c2, Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains('[UPDATE `book` SET `TITLE`=:p1 WHERE book.ID BAD SQL:p2]', $e->getMessage(), 'SQL query is written in the exception message');
        }
    }

    public function testDoInsert()
    {
        try {
            $c = new Criteria();
            $c->setPrimaryTableName(BookTableMap::TABLE_NAME);
            $c->add(BookTableMap::AUTHOR_ID, 'lkhlkhj');
            $c->doInsert(Propel::getServiceContainer()->getWriteConnection(BookTableMap::DATABASE_NAME));
            $this->fail('Missing expected exception on BAD SQL');
        } catch (PropelException $e) {
            $this->assertContains('[INSERT INTO `book` (`AUTHOR_ID`) VALUES (:p1)]', $e->getMessage(), 'SQL query is written in the exception message');
        }
    }
}
