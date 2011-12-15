<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Query;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Query\PropelQuery;

use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;

/**
 * Test class for PropelQuery
 *
 * @author     Francois Zaninotto
 * @version    $Id: PropelQueryTest.php 1351 2009-12-04 22:05:01Z francois $
 */
class PropelQueryTest extends BookstoreTestBase
{
    public function testFrom()
    {
        $q = PropelQuery::from('\Propel\Tests\Bookstore\Book');
        $expected = new BookQuery();
        $this->assertEquals($expected, $q, 'from() returns a Model query instance based on the model name');

        $q = PropelQuery::from('\Propel\Tests\Bookstore\Book b');
        $expected = new BookQuery();
        $expected->setModelAlias('b');
        $this->assertEquals($expected, $q, 'from() sets the model alias if found after the blank');

        $q = PropelQuery::from('\Propel\Tests\Runtime\Query\myBook');
        $expected = new myBookQuery();
        $this->assertEquals($expected, $q, 'from() can find custom query classes');

        try {
            $q = PropelQuery::from('Foo');
            $this->fail('PropelQuery::from() throws an exception when called on a non-existing query class');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'PropelQuery::from() throws an exception when called on a non-existing query class');
        }
    }

    public function testQuery()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $book = PropelQuery::from('\Propel\Tests\Bookstore\Book b')
            ->where('b.Title like ?', 'Don%')
            ->orderBy('b.ISBN', 'desc')
            ->findOne();
        $this->assertTrue($book instanceof Book);
        $this->assertEquals('Don Juan', $book->getTitle());

    }
}

class myBookQuery extends BookQuery
{
}
