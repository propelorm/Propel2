<?php

require_once 'tools/helpers/bookstore/BookstoreEmptyTestBase.php';

/**
 * Test class for PropelArrayFormatter.
 *
 * @author     Francois Zaninotto
 * @version    $Id: PropelArrayFormatterTest.php 1318 2009-11-19 20:03:01Z francois $
 * @package    runtime.formatter
 */
class PropelArrayFormatterTest extends BookstoreEmptyTestBase
{
	protected function setUp()
	{
		parent::setUp();
		BookstoreDataPopulator::populate();
	}


	public function testFormatNoCriteria()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);

		$stmt = $con->query('SELECT * FROM book');
		$formatter = new PropelArrayFormatter();
		try {
			$books = $formatter->format($stmt);
			$this->fail('PropelArrayFormatter::format() trows an exception when called with no valid criteria');
		} catch (PropelException $e) {
			$this->assertTrue(true,'PropelArrayFormatter::format() trows an exception when called with no valid criteria');
		}
	}
	
	public function testFormatManyResults()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);

		$stmt = $con->query('SELECT * FROM book');
		$formatter = new PropelArrayFormatter();
		$formatter->setCriteria(new ModelCriteria('bookstore', 'Book'));
		$books = $formatter->format($stmt);
		
		$this->assertTrue(is_array($books), 'PropelArrayFormatter::format() returns an array');
		$this->assertEquals(4, count($books), 'PropelArrayFormatter::format() returns as many rows as the results in the query');
		foreach ($books as $book) {
			$this->assertTrue(is_array($book), 'PropelArrayFormatter::format() returns an array of arrays');
		}
	}

	public function testFormatOneResult()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);

		$stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "Quicksilver"');
		$formatter = new PropelArrayFormatter();
		$formatter->setCriteria(new ModelCriteria('bookstore', 'Book'));
		$books = $formatter->format($stmt);
		
		$this->assertTrue(is_array($books), 'PropelArrayFormatter::format() returns an array');
		$this->assertEquals(1, count($books), 'PropelArrayFormatter::format() returns as many rows as the results in the query');
		$book = array_shift($books);
		$this->assertTrue(is_array($book), 'PropelArrayFormatter::format() returns an array of arrays');
		$this->assertEquals('Quicksilver', $book['Title'], 'PropelArrayFormatter::format() returns the arrays matching the query');
		$expected = array('Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId');
		$this->assertEquals($expected, array_keys($book), 'PropelArrayFormatter::format() returns an associative array with column phpNames as keys');
	}

	public function testFormatNoResult()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
				
		$stmt = $con->query('SELECT * FROM book WHERE book.TITLE = "foo"');
		$formatter = new PropelArrayFormatter();
		$formatter->setCriteria(new ModelCriteria('bookstore', 'Book'));
		$books = $formatter->format($stmt);
		
		$this->assertTrue(is_array($books), 'PropelArrayFormatter::format() returns an array');
		$this->assertEquals(0, count($books), 'PropelArrayFormatter::format() returns as many rows as the results in the query');
	}
}
