<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookPeer;

use Propel\Runtime\Propel;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Formatter\StatementFormatter;
use Propel\Runtime\Query\ModelCriteria;

use \PDO;
use \PDOStatement;

/**
 * Test class for StatementFormatter.
 *
 * @author     Francois Zaninotto
 * @version    $Id$
 */
class StatementFormatterTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    public function testFormatNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        try {
            $books = $formatter->format($stmt);
            $this->assertTrue(true, 'StatementFormatter::format() does not trow an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->fail('StatementFormatter::format() does not trow an exception when called with no valid criteria');
        }
    }

    public function testFormatManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof PDOStatement, 'StatementFormatter::format() returns a PDOStatement');
        $this->assertEquals(4, $books->rowCount(), 'StatementFormatter::format() returns as many rows as the results in the query');
        while ($book = $books->fetch()) {
            $this->assertTrue(is_array($book), 'StatementFormatter::format() returns a statement that can be fetched');
        }
    }

    public function testFormatOneResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "Quicksilver"');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof PDOStatement, 'StatementFormatter::format() returns a PDOStatement');
        $this->assertEquals(1, $books->rowCount(), 'StatementFormatter::format() returns as many rows as the results in the query');
        $book = $books->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Quicksilver', $book['title'], 'StatementFormatter::format() returns the rows matching the query');
    }

    public function testFormatNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "foo"');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $books = $formatter->format($stmt);

        $this->assertTrue($books instanceof PDOStatement, 'StatementFormatter::format() returns a PDOStatement');
        $this->assertEquals(0, $books->rowCount(), 'StatementFormatter::format() returns as many rows as the results in the query');
    }

    public function testFormatoneNoCriteria()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        try {
            $books = $formatter->formatOne($stmt);
            $this->assertTrue(true, 'StatementFormatter::formatOne() does not trow an exception when called with no valid criteria');
        } catch (PropelException $e) {
            $this->fail('StatementFormatter::formatOne() does not trow an exception when called with no valid criteria');
        }
    }

    public function testFormatOneManyResults()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertTrue($book instanceof PDOStatement, 'StatementFormatter::formatOne() returns a PDO Statement');
    }

    public function testFormatOneNoResult()
    {
        $con = Propel::getServiceContainer()->getConnection(BookPeer::DATABASE_NAME);

        $stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "foo"');
        $formatter = new StatementFormatter();
        $formatter->init(new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book'));
        $book = $formatter->formatOne($stmt);

        $this->assertNull($book, 'StatementFormatter::formatOne() returns null when no result');
    }

}
