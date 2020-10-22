<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\ObjectFormatter;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\AuthorCollection;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for ObjectFormatter.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ObjectFormatterTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    /**
     * @return void
     */
    public function testFormatNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new ObjectFormatter();
        try {
            $books = $formatter->format($stmt);
            $this->fail('ObjectFormatter::format() trows an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'ObjectFormatter::format() trows an exception when called with no valid criteria');
        }
    }

    /**
     * @return void
     */
    public function testFormatValidClass()
    {
        $stmt = $this->con->query('SELECT * FROM book');
        $formatter = new ObjectFormatter();
        $formatter->setClass('\Propel\Tests\Bookstore\Book');
        $books = $formatter->format($stmt);
        $this->assertTrue($books instanceof ObjectCollection);
        $this->assertEquals(4, $books->count());
    }

    /**
     * @return void
     */
    public function testFormatValidClassCustomCollection()
    {
        $stmt = $this->con->query('SELECT * FROM author');
        $formatter = new ObjectFormatter();
        $formatter->setClass('\Propel\Tests\Bookstore\Author');
        $authors = $formatter->format($stmt);
        $this->assertTrue($authors instanceof AuthorCollection);
    }

    /**
     * @return void
     */
    public function testFormatManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof Collection, 'ObjectFormatter::format() returns a PropelCollection');
        $this->assertEquals(4, count($books), 'ObjectFormatter::format() returns as many rows as the results in the query');
        foreach ($books as $book) {
            $this->assertTrue($book instanceof Book, 'ObjectFormatter::format() returns an array of Model objects');
        }
    }

    /**
     * @return void
     */
    public function testFormatOneResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query("SELECT id, title, isbn, price, publisher_id, author_id FROM book WHERE book.TITLE = 'Quicksilver'");
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof Collection, 'ObjectFormatter::format() returns a PropelCollection');
        $this->assertEquals(1, count($books), 'ObjectFormatter::format() returns as many rows as the results in the query');
        $book = $books->shift();
        $this->assertTrue($book instanceof Book, 'ObjectFormatter::format() returns an array of Model objects');
        $this->assertEquals('Quicksilver', $book->getTitle(), 'ObjectFormatter::format() returns the model objects matching the query');
    }

    /**
     * @return void
     */
    public function testFormatNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query("SELECT * FROM book WHERE book.TITLE = 'foo'");
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof Collection, 'ObjectFormatter::format() returns a PropelCollection');
        $this->assertEquals(0, count($books), 'ObjectFormatter::format() returns as many rows as the results in the query');
    }

    /**
     * @return void
     */
    public function testFormatOneNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new ObjectFormatter();
        try {
            $book = $formatter->formatOne($stmt);
            $this->fail('ObjectFormatter::formatOne() throws an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'ObjectFormatter::formatOne() throws an exception when called with no valid criteria');
        }
    }

    /**
     * @return void
     */
    public function testFormatOneManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertTrue($book instanceof Book, 'ObjectFormatter::formatOne() returns a model object');
    }

    /**
     * @return void
     */
    public function testFormatOneNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query("SELECT * FROM book WHERE book.TITLE = 'foo'");
        $formatter = new ObjectFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertNull($book, 'ObjectFormatter::formatOne() returns null when no result');
    }
}
