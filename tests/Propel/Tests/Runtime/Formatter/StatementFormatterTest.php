<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Formatter;

use PDO;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\StatementFormatter;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for StatementFormatter.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class StatementFormatterTest extends BookstoreEmptyTestBase
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
        $formatter = new StatementFormatter();
        try {
            $books = $formatter->format($stmt);
            $this->assertTrue(true, 'StatementFormatter::format() does not trow an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->fail('StatementFormatter::format() does not trow an exception when called with no valid criteria');
        }
    }

    /**
     * @return void
     */
    public function testFormatManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertInstanceOf('Propel\Runtime\Connection\StatementWrapper', $books->getDataObject(), 'StatementFormatter::format() returns a StatementWrapper');
        $this->assertEquals(4, $books->count(), 'StatementFormatter::format() returns as many rows as the results in the query');
        while ($book = $books->fetch()) {
            $this->assertTrue(is_array($book), 'StatementFormatter::format() returns a statement that can be fetched');
        }
    }

    /**
     * @return void
     */
    public function testFormatOneResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query("SELECT * FROM book WHERE book.TITLE = 'Quicksilver'");
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertInstanceOf('Propel\Runtime\Connection\StatementWrapper', $books->getDataObject(), 'StatementFormatter::format() returns a StatementWrapper');
        $this->assertEquals(1, $books->count(), 'StatementFormatter::format() returns as many rows as the results in the query');
        $book = $books->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Quicksilver', $book['title'], 'StatementFormatter::format() returns the rows matching the query');
    }

    /**
     * @return void
     */
    public function testFormatNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query("SELECT * FROM book WHERE book.TITLE = 'foo'");
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertInstanceOf('Propel\Runtime\Connection\StatementWrapper', $books->getDataObject(), 'StatementFormatter::format() returns a StatementWrapper');
        $this->assertEquals(0, $books->count(), 'StatementFormatter::format() returns as many rows as the results in the query');
    }

    /**
     * @return void
     */
    public function testFormatoneNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        try {
            $books = $formatter->formatOne($stmt);
            $this->assertTrue(true, 'StatementFormatter::formatOne() does not trow an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->fail('StatementFormatter::formatOne() does not trow an exception when called with no valid criteria');
        }
    }

    /**
     * @return void
     */
    public function testFormatOneManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertInstanceOf('Propel\Runtime\DataFetcher\PDODataFetcher', $book, 'StatementFormatter::formatOne() returns a PDODataFetcher');
    }

    /**
     * @return void
     */
    public function testFormatOneNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        $stmt = $con->query("SELECT * FROM book WHERE book.TITLE = 'foo'");
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertNull($book, 'StatementFormatter::formatOne() returns null when no result');
    }
}
