<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Runtime\Propel;
use Propel\Runtime\Formatter\SimpleArrayFormatter;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

/**
 * @group database
 */
class SimpleArrayFormatterTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    public function testFormatWithOneRowAndValueIsNotZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $stmt = $con->query('SELECT 1 FROM book');

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $books = $formatter->format($stmt);
        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $books);
        $this->assertCount(4, $books);
        $this->assertSame(1, $books[0]+0);
    }

    public function testFormatWithOneRowAndValueEqualsZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $stmt = $con->query('SELECT 0 FROM book');

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $books = $formatter->format($stmt);
        $this->assertInstanceOf('\Propel\Runtime\Collection\Collection', $books);
        $this->assertCount(4, $books);
        $this->assertSame(0, $books[0]+0);
    }

    public function testFormatOneWithOneRowAndValueIsNotZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        if ($this->isDb('mysql')) {
            $stmt = $con->query('SELECT 1 FROM book LIMIT 0, 1');
        } else {
            $stmt = $con->query('SELECT 1 FROM book LIMIT 1');
        }

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $book = $formatter->formatOne($stmt);
        $this->assertSame(1, $book+0);
    }

    public function testFormatOneWithOneRowAndValueEqualsZero()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        if ($this->isDb('mysql')) {
            $stmt = $con->query('SELECT 0 FROM book LIMIT 0, 1');
        } else {
            $stmt = $con->query('SELECT 0 FROM book LIMIT 1');
        }

        $formatter = new SimpleArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));

        $book = $formatter->formatOne($stmt);
        $this->assertSame(0, $book+0);
    }
}
