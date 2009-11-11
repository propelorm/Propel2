<?php 

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
require_once 'util/PropelPDO.php';

/**
 * Test for DebugPDO subclass.
 *
 * @author     François Zaninotto
 * @version    $Id: DebugPDOTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.util
 */
class DebugPDOTest extends BookstoreTestBase
{
	public function testGetLatestQuery()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$con->setLastExecutedQuery(123);
		$this->assertEquals(123, $con->getLastExecutedQuery(), 'DebugPDO has getter and setter for last executed query');
		$c = new Criteria();
		$c->add(BookPeer::TITLE, 'Harry%s', Criteria::LIKE);
		$books = BookPeer::doSelect($c, $con);
		$latestExecutedQuery = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE LIKE 'Harry%s'";
		if (!Propel::getDB(BookPeer::DATABASE_NAME)->useQuoteIdentifier()) {
			$latestExecutedQuery = str_replace('`', '', $latestExecutedQuery);
		}
		$this->assertEquals($latestExecutedQuery, $con->getLastExecutedQuery(), 'PropelPDO updates the last executed query on every request');
		BookPeer::doDeleteAll($con);
		$latestExecutedQuery = "DELETE FROM book";
		$this->assertEquals($latestExecutedQuery, $con->getLastExecutedQuery(), 'PropelPDO updates the last executed query on every request');
	}

}