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
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author     Francois EZaninotto
 * @package    runtime.adapter
 */
class DBOracleTest extends BookstoreTestBase
{
	public function testApplyLimitSimple()
	{
		Propel::setDb('oracle', new DBOracle());
		$c = new Criteria();
		$c->setDbName('oracle');
		BookPeer::addSelectColumns($c);
		$c->setLimit(1);
		$params = array();
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM book) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with the original column names by default');
	}

	public function testApplyLimitDuplicateColumnName()
	{
		Propel::setDb('oracle', new DBOracle());
		$c = new Criteria();
		$c->setDbName('oracle');
		BookPeer::addSelectColumns($c);
		AuthorPeer::addSelectColumns($c);
		$c->setLimit(1);
		$params = array();
		$sql = BasePeer::createSelectSql($c, $params);
		$this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.ID AS book_ID, book.TITLE AS book_TITLE, book.ISBN AS book_ISBN, book.PRICE AS book_PRICE, book.PUBLISHER_ID AS book_PUBLISHER_ID, book.AUTHOR_ID AS book_AUTHOR_ID, author.ID AS author_ID, author.FIRST_NAME AS author_FIRST_NAME, author.LAST_NAME AS author_LAST_NAME, author.EMAIL AS author_EMAIL, author.AGE AS author_AGESELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID, author.ID, author.FIRST_NAME, author.LAST_NAME, author.EMAIL, author.AGE FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
	}

}