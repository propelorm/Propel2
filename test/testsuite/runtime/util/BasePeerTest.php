<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests the BasePeer classes.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    runtime.util
 */
class BasePeerTest extends BookstoreTestBase
{

	/**
	 * @link       http://propel.phpdb.org/trac/ticket/425
	 */
	public function testMultipleFunctionInCriteria()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		try {
			$c = new Criteria();
			$c->setDistinct();
			if ($db instanceof DBPostgres) {
				$c->addSelectColumn("substring(".BookPeer::TITLE." from position('Potter' in ".BookPeer::TITLE.")) AS col");
			} else {
				$this->markTestSkipped();
			}
			$stmt = BookPeer::doSelectStmt( $c );
		} catch (PropelException $x) {
			$this->fail("Paring of nested functions failed: " . $x->getMessage());
		}
	}

	public function testNeedsSelectAliases()
	{
		$c = new Criteria();
		$this->assertFalse(BasePeer::needsSelectAliases($c), 'Empty Criterias dont need aliases');

		$c = new Criteria();
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);
		$this->assertFalse(BasePeer::needsSelectAliases($c), 'Criterias with distinct column names dont need aliases');
		
		$c = new Criteria();
		BookPeer::addSelectColumns($c);
		$this->assertFalse(BasePeer::needsSelectAliases($c), 'Criterias with only the columns of a model dont need aliases');

		$c = new Criteria();
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(AuthorPeer::ID);
		$this->assertTrue(BasePeer::needsSelectAliases($c), 'Criterias with common column names do need aliases');
	}
	
	public function testTurnSelectColumnsToAliases()
	{
		$c1 = new Criteria();
		$c1->addSelectColumn(BookPeer::ID);
		BasePeer::turnSelectColumnsToAliases($c1);
		
		$c2 = new Criteria();
		$c2->addAsColumn('book_ID', BookPeer::ID);
		$this->assertTrue($c1->equals($c2));
	}

	public function testTurnSelectColumnsToAliasesPreservesAliases()
	{
		$c1 = new Criteria();
		$c1->addSelectColumn(BookPeer::ID);
		$c1->addAsColumn('foo', BookPeer::TITLE);
		BasePeer::turnSelectColumnsToAliases($c1);
		
		$c2 = new Criteria();
		$c2->addAsColumn('book_ID', BookPeer::ID);
		$c2->addAsColumn('foo', BookPeer::TITLE);
		$this->assertTrue($c1->equals($c2));
	}

	public function testTurnSelectColumnsToAliasesExisting()
	{
		$c1 = new Criteria();
		$c1->addSelectColumn(BookPeer::ID);
		$c1->addAsColumn('book_ID', BookPeer::ID);
		BasePeer::turnSelectColumnsToAliases($c1);
		
		$c2 = new Criteria();
		$c2->addAsColumn('book_ID_1', BookPeer::ID);
		$c2->addAsColumn('book_ID', BookPeer::ID);
		$this->assertTrue($c1->equals($c2));
	}

	public function testTurnSelectColumnsToAliasesDuplicate()
	{
		$c1 = new Criteria();
		$c1->addSelectColumn(BookPeer::ID);
		$c1->addSelectColumn(BookPeer::ID);
		BasePeer::turnSelectColumnsToAliases($c1);
		
		$c2 = new Criteria();
		$c2->addAsColumn('book_ID', BookPeer::ID);
		$c2->addAsColumn('book_ID_1', BookPeer::ID);
		$this->assertTrue($c1->equals($c2));
	}
	
	public function testDoCountDuplicateColumnName()
	{
		$con = Propel::getConnection();
		$c = new Criteria();
		$c->addSelectColumn(BookPeer::ID);
		$c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID);
		$c->addSelectColumn(AuthorPeer::ID);
		$c->setLimit(3);
		try {
			$count = BasePeer::doCount($c, $con);
		} catch (Exception $e) {
			$this->fail('doCount() cannot deal with a criteria selecting duplicate column names ');
		}
	}
	
	/**
	 *
	 */
	public function testBigIntIgnoreCaseOrderBy()
	{
		BookstorePeer::doDeleteAll();

		// Some sample data
		$b = new Bookstore();
		$b->setStoreName("SortTest1")->setPopulationServed(2000)->save();

		$b = new Bookstore();
		$b->setStoreName("SortTest2")->setPopulationServed(201)->save();

		$b = new Bookstore();
		$b->setStoreName("SortTest3")->setPopulationServed(302)->save();

		$b = new Bookstore();
		$b->setStoreName("SortTest4")->setPopulationServed(10000000)->save();

		$c = new Criteria();
		$c->setIgnoreCase(true);
		$c->add(BookstorePeer::STORE_NAME, 'SortTest%', Criteria::LIKE);
		$c->addAscendingOrderByColumn(BookstorePeer::POPULATION_SERVED);

		$rows = BookstorePeer::doSelect($c);
		$this->assertEquals('SortTest2', $rows[0]->getStoreName());
		$this->assertEquals('SortTest3', $rows[1]->getStoreName());
		$this->assertEquals('SortTest1', $rows[2]->getStoreName());
		$this->assertEquals('SortTest4', $rows[3]->getStoreName());
	}

	/**
	 *
	 */
	public function testMixedJoinOrder()
	{
		$this->markTestSkipped('Famous cross join problem, to be solved one day');
		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);

		$c->addJoin(BookPeer::PUBLISHER_ID, PublisherPeer::ID, Criteria::LEFT_JOIN);
		$c->addJoin(BookPeer::AUTHOR_ID, AuthorPeer::ID);

		$params = array();
		$sql = BasePeer::createSelectSql($c, $params);

		$expectedSql = "SELECT book.ID, book.TITLE FROM book LEFT JOIN publisher ON (book.PUBLISHER_ID=publisher.ID), author WHERE book.AUTHOR_ID=author.ID";
		$this->assertEquals($expectedSql, $sql);
	}
	
	public function testMssqlApplyLimitNoOffset()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		if(! ($db instanceof DBMSSQL))
		{
			$this->markTestSkipped();
		}

		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);
		$c->addSelectColumn(PublisherPeer::NAME);
		$c->addAsColumn('PublisherName','(SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID)');

		$c->addJoin(BookPeer::PUBLISHER_ID, PublisherPeer::ID, Criteria::LEFT_JOIN);

		$c->setOffset(0);
		$c->setLimit(20);

		$params = array();
		$sql = BasePeer::createSelectSql($c, $params);

		$expectedSql = "SELECT TOP 20 book.ID, book.TITLE, publisher.NAME, (SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID) AS PublisherName FROM book LEFT JOIN publisher ON (book.PUBLISHER_ID=publisher.ID)";
		$this->assertEquals($expectedSql, $sql);
	}

	public function testMssqlApplyLimitWithOffset()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		if(! ($db instanceof DBMSSQL))
		{
			$this->markTestSkipped();
		}

		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);
		$c->addSelectColumn(PublisherPeer::NAME);
		$c->addAsColumn('PublisherName','(SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID)');
		$c->addJoin(BookPeer::PUBLISHER_ID, PublisherPeer::ID, Criteria::LEFT_JOIN);
		$c->setOffset(20);
		$c->setLimit(20);

		$params = array();

		$expectedSql = "SELECT [book.ID], [book.TITLE], [publisher.NAME], [PublisherName] FROM (SELECT ROW_NUMBER() OVER(ORDER BY book.ID) AS RowNumber, book.ID AS [book.ID], book.TITLE AS [book.TITLE], publisher.NAME AS [publisher.NAME], (SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID) AS [PublisherName] FROM book LEFT JOIN publisher ON (book.PUBLISHER_ID=publisher.ID)) AS derivedb WHERE RowNumber BETWEEN 21 AND 40";
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}

	public function testMssqlApplyLimitWithOffsetOrderByAggregate()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		if(! ($db instanceof DBMSSQL))
		{
			$this->markTestSkipped();
		}

		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);
		$c->addSelectColumn(PublisherPeer::NAME);
		$c->addAsColumn('PublisherName','(SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID)');
		$c->addJoin(BookPeer::PUBLISHER_ID, PublisherPeer::ID, Criteria::LEFT_JOIN);
		$c->addDescendingOrderByColumn('PublisherName');
		$c->setOffset(20);
		$c->setLimit(20);

		$params = array();

		$expectedSql = "SELECT [book.ID], [book.TITLE], [publisher.NAME], [PublisherName] FROM (SELECT ROW_NUMBER() OVER(ORDER BY (SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID) DESC) AS RowNumber, book.ID AS [book.ID], book.TITLE AS [book.TITLE], publisher.NAME AS [publisher.NAME], (SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID) AS [PublisherName] FROM book LEFT JOIN publisher ON (book.PUBLISHER_ID=publisher.ID)) AS derivedb WHERE RowNumber BETWEEN 21 AND 40";
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}

	public function testMssqlApplyLimitWithOffsetMultipleOrderBy()
	{
		$db = Propel::getDB(BookPeer::DATABASE_NAME);
		if(! ($db instanceof DBMSSQL))
		{
			$this->markTestSkipped();
		}

		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addSelectColumn(BookPeer::ID);
		$c->addSelectColumn(BookPeer::TITLE);
		$c->addSelectColumn(PublisherPeer::NAME);
		$c->addAsColumn('PublisherName','(SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID)');
		$c->addJoin(BookPeer::PUBLISHER_ID, PublisherPeer::ID, Criteria::LEFT_JOIN);
		$c->addDescendingOrderByColumn('PublisherName');
		$c->addAscendingOrderByColumn(BookPeer::TITLE);
		$c->setOffset(20);
		$c->setLimit(20);

		$params = array();

		$expectedSql = "SELECT [book.ID], [book.TITLE], [publisher.NAME], [PublisherName] FROM (SELECT ROW_NUMBER() OVER(ORDER BY (SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID) DESC, book.TITLE ASC) AS RowNumber, book.ID AS [book.ID], book.TITLE AS [book.TITLE], publisher.NAME AS [publisher.NAME], (SELECT MAX(publisher.NAME) FROM publisher WHERE publisher.ID = book.PUBLISHER_ID) AS [PublisherName] FROM book LEFT JOIN publisher ON (book.PUBLISHER_ID=publisher.ID)) AS derivedb WHERE RowNumber BETWEEN 21 AND 40";
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals($expectedSql, $sql);
	}	
}
