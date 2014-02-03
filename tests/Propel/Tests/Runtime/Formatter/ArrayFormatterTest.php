<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\ArrayFormatter;
use Propel\Runtime\ActiveQuery\ModelCriteria;

/**
 * Test class for ArrayFormatter.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ArrayFormatterTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    public function testFormatNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query('SELECT * FROM book');
        $formatter = new ArrayFormatter();
        try {
            $books = $formatter->format($dataFetcher);
            $this->fail('ArrayFormatter::format() throws an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->assertTrue(true,'ArrayFormatter::format() throws an exception when called with no valid criteria');
        }
    }

    public function testFormatManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query('SELECT * FROM book');
        $formatter = new ArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($dataFetcher);

        $this->assertTrue($books instanceof Collection, 'ArrayFormatter::format() returns a PropelCollection');
        $this->assertEquals(4, count($books), 'ArrayFormatter::format() returns as many rows as the results in the query');
        foreach ($books as $book) {
            $this->assertTrue(is_array($book), 'ArrayFormatter::format() returns an array of arrays');
        }
    }

    public function testFormatOneResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query("SELECT id, title, isbn, price, publisher_id, author_id FROM book WHERE book.TITLE = 'Quicksilver'");
        $formatter = new ArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($dataFetcher);

        $this->assertTrue($books instanceof Collection, 'ArrayFormatter::format() returns a PropelCollection');
        $this->assertEquals(1, count($books), 'ArrayFormatter::format() returns as many rows as the results in the query');
        $book = $books->shift();
        $this->assertTrue(is_array($book), 'ArrayFormatter::format() returns an array of arrays');
        $this->assertEquals('Quicksilver', $book['Title'], 'ArrayFormatter::format() returns the arrays matching the query');
        $expected = array('Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId');
        $this->assertEquals($expected, array_keys($book), 'ArrayFormatter::format() returns an associative array with column phpNames as keys');
    }

    public function testFormatNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query("SELECT * FROM book WHERE book.TITLE = 'foo'");
        $formatter = new ArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($dataFetcher);

        $this->assertTrue($books instanceof Collection, 'ArrayFormatter::format() returns a PropelCollection');
        $this->assertEquals(0, count($books), 'ArrayFormatter::format() returns as many rows as the results in the query');
    }

    public function testFormatOneNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query('SELECT * FROM book');
        $formatter = new ArrayFormatter();
        try {
            $book = $formatter->formatOne($dataFetcher);
            $this->fail('ArrayFormatter::formatOne() throws an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->assertTrue(true,'ArrayFormatter::formatOne() throws an exception when called with no valid criteria');
        }
    }

    public function testFormatOneManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query('SELECT * FROM book');
        $formatter = new ArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($dataFetcher);

        $this->assertTrue(is_array($book), 'ArrayFormatter::formatOne() returns an array');
        $this->assertEquals(array('Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId'), array_keys($book), 'ArrayFormatter::formatOne() returns a single row even if the query has many results');
    }

    public function testFormatOneNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $dataFetcher = $con->query("SELECT * FROM book WHERE book.TITLE = 'foo'");
        $formatter = new ArrayFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($dataFetcher);

        $this->assertNull($book, 'ArrayFormatter::formatOne() returns null when no result');
    }

}
