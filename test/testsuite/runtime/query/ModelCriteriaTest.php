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
	protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
	{
		$params = array();
		$result = BasePeer::createSelectSql($criteria, $params);
		
		$this->assertEquals($expectedSql, $result, $message);
		$this->assertEquals($expectedParams, $params, $message); 
	}
	
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
		
		$sql = "SELECT  FROM `book` WHERE book.TITLE = :p1";
		$params = array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'A ModelCriteria accepts an alias for its model');
	}
		
	public function testCondition()
	{	
		$c = new ModelCriteria('bookstore', 'Book');
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->combine(array('cond1', 'cond2'), 'or');
		
		$sql = "SELECT  FROM `book` WHERE (book.TITLE <> :p1 OR book.TITLE like :p2)";
		$params = array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'condition() can store condition for later combination');
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
	public function testWhere($clause, $value, $sql, $params)
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where($clause, $value);
		$sql = 'SELECT  FROM `book` WHERE ' . $sql;
		$this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts a string clause');
	}
	
	public function testWhereConditions()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->where(array('cond1', 'cond2'));
		
		$sql = "SELECT  FROM `book` WHERE (book.TITLE <> :p1 AND book.TITLE like :p2)";
		$params = array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts an array of named conditions');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->where(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
		
		$sql = "SELECT  FROM `book` WHERE (book.TITLE <> :p1 OR book.TITLE like :p2)";
		$this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts an array of named conditions with operator');
	}
	
	public function testWhereNoReplacement()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$c->where('1=1');
		
		$sql = "SELECT  FROM `book` WHERE book.TITLE = :p1 AND 1=1";
		$params = array(
		  array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'where() results in a Criteria::CUSTOM if no column name is matched');
		
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
		
		$sql = "SELECT  FROM `book` WHERE UPPER(book.TITLE) = :p1";
		$params = array(
		  array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'where() accepts a complex calculation');
	}
	
	public function testOrWhere()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Title <> ?', 'foo');
		$c->orWhere('Book.Title like ?', '%bar%');
		
		$sql = "SELECT  FROM `book` WHERE (book.TITLE <> :p1 OR book.TITLE like :p2)";
		$params = array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'orWhere() combines the clause with the previous one using  OR');
	}
	
	public function testOrWhereConditions()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Id = ?', 12);
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->orWhere(array('cond1', 'cond2'));
		
		$sql = "SELECT  FROM `book` WHERE (book.ID = :p1 OR (book.TITLE <> :p2 AND book.TITLE like :p3))";
		$params = array(
			array('table' => 'book', 'column' => 'ID', 'value' => 12),
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'orWhere() accepts an array of named conditions');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Id = ?', 12);
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->orWhere(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
		
		$sql = "SELECT  FROM `book` WHERE (book.ID = :p1 OR (book.TITLE <> :p2 OR book.TITLE like :p3))";
		$this->assertCriteriaTranslation($c, $sql, $params, 'orWhere() accepts an array of named conditions with operator');
	}
	
	public function testMixedCriteria()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Title = ?', 'foo');
		$c->add(BookPeer::ID, array(1, 2), Criteria::IN);

		$sql = 'SELECT  FROM `book` WHERE book.TITLE = :p1 AND book.ID IN (:p2,:p3)';
		$params =  array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'ID', 'value' => 1),
			array('table' => 'book', 'column' => 'ID', 'value' => 2)
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'ModelCriteria accepts Criteria operators');
	}
	
	public function testHaving()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->having('Book.Title <> ?', 'foo');
		
		$sql = "SELECT  FROM  HAVING book.TITLE <> :p1";
		$params = array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts a string clause');
	}

	public function testHavingConditions()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->having(array('cond1', 'cond2'));
		
		$sql = "SELECT  FROM  HAVING (book.TITLE <> :p1 AND book.TITLE like :p2)";
		$params = array(
			array('table' => 'book', 'column' => 'TITLE', 'value' => 'foo'),
			array('table' => 'book', 'column' => 'TITLE', 'value' => '%bar%'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts an array of named conditions');
		
		$c = new ModelCriteria('bookstore', 'Book');
		$c->condition('cond1', 'Book.Title <> ?', 'foo');
		$c->condition('cond2', 'Book.Title like ?', '%bar%');
		$c->having(array('cond1', 'cond2'), Criteria::LOGICAL_OR);
		
		$sql = "SELECT  FROM  HAVING (book.TITLE <> :p1 OR book.TITLE like :p2)";
		$this->assertCriteriaTranslation($c, $sql, $params, 'having() accepts an array of named conditions with an operator');
	}
		
	public function testOrderBy()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Title');
		
		$sql = 'SELECT  FROM  ORDER BY book.TITLE ASC';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'orderBy() accepts a column name and adds an ORDER BY clause');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Title', 'desc');
		
		$sql = 'SELECT  FROM  ORDER BY book.TITLE DESC';
		$this->assertCriteriaTranslation($c, $sql, $params, 'orderBy() accepts an order parameter');
				
		$c = new ModelCriteria('bookstore', 'Book');
		try {
			$c->orderBy('Book.Foo');
			$this->fail('orderBy() throws an exception when called with an unkown column name');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'orderBy() throws an exception when called with an unkown column name');
		}
		$c = new ModelCriteria('bookstore', 'Book');
		try {
			$c->orderBy('Book.Title', 'foo');
			$this->fail('orderBy() throws an exception when called with an unkown order');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'orderBy() throws an exception when called with an unkown order');
		}
	}
	
	public function testGroupBy()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->groupBy('Book.AuthorId');
		
		$sql = 'SELECT  FROM  GROUP BY book.AUTHOR_ID';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'groupBy() accepts a column name and adds a GROUP BY clause');
				
		$c = new ModelCriteria('bookstore', 'Book');
		try {
			$c->groupBy('Book.Foo');
			$this->fail('groupBy() throws an exception when called with an unkown column name');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'groupBy() throws an exception when called with an unkown column name');
		}
	}
	
	public function testDistinct()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->distinct();
		$sql = 'SELECT DISTINCT   FROM ';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'distinct() adds a DISTINCT clause');
	}
	
	public function testLimit()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->limit(10);
		$sql = 'SELECT  FROM  LIMIT 10';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'limit() adds a LIMIT clause');
	}

	public function testOffset()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->limit(50);
		$c->offset(10);
		$sql = 'SELECT  FROM  LIMIT 10, 50';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'offset() adds an OFFSET clause');
	}

	public function testJoin()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() uses a relation to guess the columns');

		$c = new ModelCriteria('bookstore', 'Book');
		try {
			$c->join('Foo');
			$this->fail('join() throws an exception when called with a non-existing relation');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'join() throws an exception when called with a non-existing relation');
		}
		
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author');
		$c->where('Author.FirstName = ?', 'Leo');
		$sql = 'SELECT  FROM  INNER JOIN author ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = :p1';
		$params = array(
			array('table' => 'author', 'column' => 'FIRST_NAME', 'value' => 'Leo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() uses a relation to guess the columns');
	}
	
	public function testJoinQuery()
	{	
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		require_once 'tools/helpers/bookstore/BookstoreDataPopulator.php';
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author');
		$c->where('Author.FirstName = ?', 'Neal');
		$books = BookPeer::doSelect($c);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = 'Neal'";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'join() issues a real JOIN query');
		$this->assertEquals(1, count($books), 'join() issues a real JOIN query');
	}

	public function testJoinRelationName()
	{
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee');
		$c->join('Supervisor');
		$sql = 'SELECT  FROM  INNER JOIN bookstore_employee ON (bookstore_employee.SUPERVISOR_ID=bookstore_employee.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() uses relation names as defined in schema.xml');
	}
		
	public function testJoinType()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds an INNER JOIN by default');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author', Criteria::INNER_JOIN);
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds an INNER JOIN by default');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author', Criteria::LEFT_JOIN);
		$sql = 'SELECT  FROM `book` LEFT JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() can add a LEFT JOIN');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author', Criteria::RIGHT_JOIN);
		$sql = 'SELECT  FROM `book` RIGHT JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() can add a RIGHT JOIN');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author', 'incorrect join');
		$sql = 'SELECT  FROM `book` incorrect join author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() accepts any join string');
	}

	public function testJoinDirection()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for many to one relationship');

		$c = new ModelCriteria('bookstore', 'Author');
		$c->join('Book');
		$sql = 'SELECT  FROM `author` INNER JOIN book ON (author.ID=book.AUTHOR_ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to many relationship');
		
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee');
		$c->join('BookstoreEmployeeAccount');
		$sql = 'SELECT  FROM `bookstore_employee` INNER JOIN bookstore_employee_account ON (bookstore_employee.ID=bookstore_employee_account.EMPLOYEE_ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to one relationship');

		$c = new ModelCriteria('bookstore', 'BookstoreEmployeeAccount');
		$c->join('BookstoreEmployee');
		$sql = 'SELECT  FROM `bookstore_employee_account` INNER JOIN bookstore_employee ON (bookstore_employee_account.EMPLOYEE_ID=bookstore_employee.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to one relationship');
	}
	
	public function testJoinSeveral()
	{
		$c = new ModelCriteria('bookstore', 'Author');
		$c->join('Book');
		$c->join('Publisher');
		$c->where('Publisher.Name = ?', 'foo');
		$sql = 'SELECT  FROM  INNER JOIN book ON (author.ID=book.AUTHOR_ID) INNER JOIN publisher ON (book.PUBLISHER_ID=publisher.ID) WHERE publisher.NAME = :p1';
		$params = array(
			array('table' => 'publisher', 'column' => 'NAME', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() can guess relationships from related tables');
	}
}

class TestableModelCriteria extends ModelCriteria
{
	public function replaceNames(&$clause)
	{
		return parent::replaceNames($clause);
	}
}
