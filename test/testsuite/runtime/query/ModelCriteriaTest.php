<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Test class for ModelCriteria.
 *
 * @author     Francois Zaninotto
 * @version    $Id: ModelCriteriaTest.php 1318 2009-11-19 20:03:01Z francois $
 * @package    runtime.query
 */
class ModelCriteriaTest extends BookstoreTestBase
{
	public static function conditionsForTestReplaceNames()
	{
		return array(
			array('Book.Title = ?', 'Title', 'book.TITLE = ?'), // basic case
			array('Book.Title=?', 'Title', 'book.TITLE=?'), // without spaces
			array('Book.Id<= ?', 'Id', 'book.ID<= ?'), // with non-equal comparator
			array('Book.AuthorId LIKE ?', 'AuthorId', 'book.AUTHOR_ID LIKE ?'), // with SQL keyword separator
			array('(Book.AuthorId) LIKE ?', 'AuthorId', '(book.AUTHOR_ID) LIKE ?'), // with parenthesis
			array('(Book.Id*1.5)=1', 'Id', '(book.ID*1.5)=1'), // ignore numbers
			array('1=1', null, '1=1'), // with no name
			array('', null, '') // with empty string
		);
	}
	
	/**
	 * @dataProvider conditionsForTestReplaceNames
	 */	
	public function testReplaceNames($origClause, $columnPhpName = false, $modifiedClause)
	{
		$c = new TestableModelCriteria('bookstore', 'Book');
		$columns = $c->replaceNames($origClause);
		if ($columnPhpName) {
			$this->assertEquals(array(BookPeer::getTableMap()->getColumnByPhpName($columnPhpName)), $columns);
		}
		$this->assertEquals($modifiedClause, $origClause);		
	}
	
	public static function conditionsForTestReplaceMultipleNames()
	{
		return array(
			array('(Book.Id+Book.Id)=1', array('Id', 'Id'), '(book.ID+book.ID)=1'), // match multiple names
			array('CONCAT(Book.Title,"Book.Id")= ?', array('Title', 'Id'), 'CONCAT(book.TITLE,"Book.Id")= ?'), // ignore names in strings
			array('CONCAT(Book.Title," Book.Id ")= ?', array('Title', 'Id'), 'CONCAT(book.TITLE," Book.Id ")= ?'), // ignore names in strings
			array('MATCH (Book.Title,Book.ISBN) AGAINST (?)', array('Title', 'ISBN'), 'MATCH (book.TITLE,book.ISBN) AGAINST (?)'),
		);
	}
	
	/**
	 * @dataProvider conditionsForTestReplaceMultipleNames
	 */	
	public function testReplaceMultipleNames($origClause, $expectedColumns, $modifiedClause)
	{
		$c = new TestableModelCriteria('bookstore', 'Book');
		$foundColumns = $c->replaceNames($origClause);
		foreach ($foundColumns as $column) {
			$expectedColumn = BookPeer::getTableMap()->getColumnByPhpName(array_shift($expectedColumns));
			$this->assertEquals($expectedColumn, $column);
		}
		$this->assertEquals($modifiedClause, $origClause);		
	}

	public function testTableAlias()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		
		$expect = "SELECT  FROM `book` WHERE book.TITLE = :p1";

    $params = array();
    $result = BasePeer::createSelectSql($c, $params);

    $expect_params = array(
      array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
    );

    $this->assertEquals($expect, $result, 'A ModelCriteria accepts an alias for its model');
    $this->assertEquals($expect_params, $params, 'A ModelCriteria accepts an alias for its model'); 
	}

	public static function conditionsForTestWhere()
	{
		return array(
			array('Book.Title = ?', 'foo', 'book.TITLE = :p1', array(array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'))),
			array('Book.AuthorId = ?', 12, 'book.AUTHOR_ID = :p1', array(array('table' => 'book', 'column' => 'AUTHOR_ID', 'value' => 12))),
			array('Book.AuthorId IS NULL', null, 'book.AUTHOR_ID IS NULL', array()),
			array('Book.Id BETWEEN ? AND ?', array(3, 4), 'book.ID BETWEEN :p1 AND :p2', array(array('table' => 'book', 'column' => 'ID', 'value' => 3), array('table' => 'book', 'column' => 'ID', 'value' => 4))),
			array('Book.Id betWEen ? and ?', array(3, 4), 'book.ID betWEen :p1 and :p2', array(array('table' => 'book', 'column' => 'ID', 'value' => 3), array('table' => 'book', 'column' => 'ID', 'value' => 4))),
			array('Book.Id IN ?', array(1, 2, 3), 'book.ID IN (:p1,:p2,:p3)', array(array('table' => 'book', 'column' => 'ID', 'value' => 1), array('table' => 'book', 'column' => 'ID', 'value' => 2), array('table' => 'book', 'column' => 'ID', 'value' => 3))),
			array('Book.Id in ?', array(1, 2, 3), 'book.ID in (:p1,:p2,:p3)', array(array('table' => 'book', 'column' => 'ID', 'value' => 1), array('table' => 'book', 'column' => 'ID', 'value' => 2), array('table' => 'book', 'column' => 'ID', 'value' => 3))),
			array('Book.Id IN ?', array(), '1=1', array()),
			array('Book.Id not in ?', array(), '1<>1', array()),
			array('UPPER(Book.Title) = ?', 'foo', 'UPPER(book.TITLE) = :p1', array(array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'))),
			array('MATCH (Book.Title,Book.ISBN) AGAINST (?)', 'foo', 'MATCH (book.TITLE,book.ISBN) AGAINST (:p1)', array(array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'))),
		);
	}
	
	/**
	 * @dataProvider conditionsForTestWhere
	 */
	public function testWhere($clause, $value, $expectedSQL, $expectedParams)
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where($clause, $value);
		
    $params = array();
    $result = BasePeer::createSelectSql($c, $params);

		$expectedSQL = 'SELECT  FROM `book` WHERE ' . $expectedSQL;

    $this->assertEquals($expectedSQL, $result);
		$this->assertEquals($expectedParams, $params);
	}
	
	public function testWhereNoReplacement()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$c->where('1=1');
		
		$expect = "SELECT  FROM `book` WHERE book.TITLE = :p1 AND 1=1";

		$params = array();
		$result = BasePeer::createSelectSql($c, $params);
		
		$expect_params = array(
		  array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
		);
		
		$this->assertEquals($expect, $result, 'where() results in a Criteria::CUSTOM if no column name is matched');
		$this->assertEquals($expect_params, $params, 'where() results in a Criteria::CUSTOM if no column name is matched'); 
		
		$c = new ModelCriteria('bookstore', 'Book');
		try {
			$c->where('b.Title = ?', 'foo');
			$this->fail('where() throws an exception when it finds a ? but cannot determine a column');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'where() throws an exception when it finds a ? but cannot determine a column');
		}
	}
	
	public function testWhereFunction()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('UPPER(b.Title) = ?', 'foo');
		
		$expect = "SELECT  FROM `book` WHERE UPPER(book.TITLE) = :p1";

		$params = array();
		$result = BasePeer::createSelectSql($c, $params);
		
		$expect_params = array(
		  array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
		);
		
		$this->assertEquals($expect, $result, 'where() accepts a complex calculation');
		$this->assertEquals($expect_params, $params, 'where() accepts a complex calculation'); 
	}
		
	public function testWhereNamedCondition()
	{	
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Title <> ?', 'foo', 'cond1');
		$c->where('Book.Title like ?', '%bar%', 'cond2');
		$c->combine(array('cond1', 'cond2'), 'or');
		
		$expect = "SELECT  FROM `book` WHERE (book.TITLE <> :p1 OR book.TITLE like :p2)";

    $params = array();
    $result = BasePeer::createSelectSql($c, $params);

    $expect_params = array(
      array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
      array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
    );

    $this->assertEquals($expect, $result, 'where() can combine conditions');
    $this->assertEquals($expect_params, $params, 'where() can combine conditions');    
	}
	
	public function testOrWhere()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Title <> ?', 'foo');
		$c->orWhere('Book.Title like ?', '%bar%');
		
		$expect = "SELECT  FROM `book` WHERE (book.TITLE <> :p1 OR book.TITLE like :p2)";

    $params = array();
    $result = BasePeer::createSelectSql($c, $params);

    $expect_params = array(
      array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
      array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
    );

    $this->assertEquals($expect, $result, 'orWhere() combines the clause with the previous one using  OR');
    $this->assertEquals($expect_params, $params, 'orWhere() combines the clause with the previous one using  OR');
	}
	
	public function testMixedCriteria()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Title = ?', 'foo');
		$c->add(BookPeer::ID, array(1, 2), Criteria::IN);
		
    $params = array();
    $result = BasePeer::createSelectSql($c, $params);

		$expectedSQL = 'SELECT  FROM `book` WHERE book.TITLE = :p1 AND book.ID IN (:p2,:p3)';
		$expectedParams =  array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'ID', 'value' => 1),
			array('table' => 'book', 'column' => 'ID', 'value' => 2)
		);

    $this->assertEquals($expectedSQL, $result, 'ModelCriteria accepts Criteria operators');
		$this->assertEquals($expectedParams, $params, 'ModelCriteria accepts Criteria operators');
	}

}

class TestableModelCriteria extends ModelCriteria
{
	public function replaceNames(&$clause)
	{
		return parent::replaceNames($clause);
	}
}
