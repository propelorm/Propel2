<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Tests\Bookstore\BookPeer;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Runtime\Formatter\SimpleArrayFormatter;
use Propel\Runtime\Propel;
use Propel\Runtime\Query\ModelCriteria;


class SimpleArrayFormatterTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    public function testFormatWithOneRowAndValueIsNotZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);
        $stmt = $con->query('SELECT 1 FROM book');

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $books = $formatter->format($stmt);
        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $books);
        $this->assertCount(4, $books);
        $this->assertSame('1', $books[0]);
    }

    public function testFormatWithOneRowAndValueEqualsZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);
        $stmt = $con->query('SELECT 0 FROM book');

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $books = $formatter->format($stmt);
        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $books);
        $this->assertCount(4, $books);
        $this->assertSame('0', $books[0]);
    }

    public function testFormatOneWithOneRowAndValueIsNotZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);
        $stmt = $con->query('SELECT 1 FROM book LIMIT 0, 1');

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $book = $formatter->formatOne($stmt);
        $this->assertSame('1', $book);
    }

    public function testFormatOneWithOneRowAndValueEqualsZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);
        $stmt = $con->query('SELECT 0 FROM book LIMIT 0, 1');

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $book = $formatter->formatOne($stmt);
        $this->assertSame('0', $book);
    }
}
