<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Test class for ModelCriteria.
 *
 * @author     Francois Zaninotto
 * @version    $Id$
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
	
	public function testGetModelName()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$this->assertEquals('Book', $c->getModelName(), 'getModelName() returns the name of the class associated to the model class');
	}
	
	public function testGetModelPeerName()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$this->assertEquals('BookPeer', $c->getModelPeerName(), 'getModelPeerName() returns the name of the Peer class associated to the model class');
	}
	
	public function testFormatter()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$this->assertTrue($c->getFormatter() instanceof PropelFormatter, 'getFormatter() returns a PropelFormatter instance');
		
		$c = new ModelCriteria('bookstore', 'Book');
		$c->setFormatter(ModelCriteria::FORMAT_STATEMENT);
		$this->assertTrue($c->getFormatter() instanceof PropelStatementFormatter, 'setFormatter() accepts the name of a PropelFormatter class');
		
		try {
			$c->setFormatter('Book');
			$this->fail('setFormatter() throws an exception when passed the name of a class not extending PropelFormatter');
		} catch(PropelException $e) {
			$this->assertTrue(true, 'setFormatter() throws an exception when passed the name of a class not extending PropelFormatter');
		}
		$c = new ModelCriteria('bookstore', 'Book');
		$formatter = new PropelStatementFormatter();
		$c->setFormatter($formatter);
		$this->assertTrue($c->getFormatter() instanceof PropelStatementFormatter, 'setFormatter() accepts a PropelFormatter instance');
		
		try {
			$formatter = new Book();
			$c->setFormatter($formatter);
			$this->fail('setFormatter() throws an exception when passed an object not extending PropelFormatter');
		} catch(PropelException $e) {
			$this->assertTrue(true, 'setFormatter() throws an exception when passedan object not extending PropelFormatter');
		}
		
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
		$c->replaceNames($origClause);
		$columns = $c->replacedColumns;
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
		$c->replaceNames($origClause);
		$foundColumns = $c->replacedColumns;
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
		$c->join('Book.Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() uses a relation to guess the columns');

		$c = new ModelCriteria('bookstore', 'Book');
		try {
			$c->join('Book.Foo');
			$this->fail('join() throws an exception when called with a non-existing relation');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'join() throws an exception when called with a non-existing relation');
		}
		
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author');
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
		$c->join('Book.Author');
		$c->where('Author.FirstName = ?', 'Neal');
		$books = BookPeer::doSelect($c);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID) WHERE author.FIRST_NAME = 'Neal'";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'join() issues a real JOIN query');
		$this->assertEquals(1, count($books), 'join() issues a real JOIN query');
	}

	public function testJoinRelationName()
	{
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee');
		$c->join('BookstoreEmployee.Supervisor');
		$sql = 'SELECT  FROM  INNER JOIN bookstore_employee ON (bookstore_employee.SUPERVISOR_ID=bookstore_employee.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() uses relation names as defined in schema.xml');
	}
	
	public function testJoinComposite()
	{
		$c = new ModelCriteria('bookstore', 'ReaderFavorite');
		$c->join('ReaderFavorite.BookOpinion');
		$sql = 'SELECT  FROM `reader_favorite` INNER JOIN book_opinion ON (reader_favorite.BOOK_ID=book_opinion.BOOK_ID AND reader_favorite.READER_ID=book_opinion.READER_ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() knows how to create a JOIN clause for relationships with composite fkeys');
	}
		
	public function testJoinType()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds an INNER JOIN by default');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author', Criteria::INNER_JOIN);
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds an INNER JOIN by default');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author', Criteria::LEFT_JOIN);
		$sql = 'SELECT  FROM `book` LEFT JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() can add a LEFT JOIN');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author', Criteria::RIGHT_JOIN);
		$sql = 'SELECT  FROM `book` RIGHT JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() can add a RIGHT JOIN');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author', 'incorrect join');
		$sql = 'SELECT  FROM `book` incorrect join author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() accepts any join string');
	}

	public function testJoinDirection()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for many to one relationship');

		$c = new ModelCriteria('bookstore', 'Author');
		$c->join('Author.Book');
		$sql = 'SELECT  FROM `author` INNER JOIN book ON (author.ID=book.AUTHOR_ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to many relationship');
		
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee');
		$c->join('BookstoreEmployee.BookstoreEmployeeAccount');
		$sql = 'SELECT  FROM `bookstore_employee` INNER JOIN bookstore_employee_account ON (bookstore_employee.ID=bookstore_employee_account.EMPLOYEE_ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to one relationship');

		$c = new ModelCriteria('bookstore', 'BookstoreEmployeeAccount');
		$c->join('BookstoreEmployeeAccount.BookstoreEmployee');
		$sql = 'SELECT  FROM `bookstore_employee_account` INNER JOIN bookstore_employee ON (bookstore_employee_account.EMPLOYEE_ID=bookstore_employee.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() adds a JOIN clause correctly for one to one relationship');
	}
	
	public function testJoinSeveral()
	{
		$c = new ModelCriteria('bookstore', 'Author');
		$c->join('Author.Book');
		$c->join('Book.Publisher');
		$c->where('Publisher.Name = ?', 'foo');
		$sql = 'SELECT  FROM  INNER JOIN book ON (author.ID=book.AUTHOR_ID) INNER JOIN publisher ON (book.PUBLISHER_ID=publisher.ID) WHERE publisher.NAME = :p1';
		$params = array(
			array('table' => 'publisher', 'column' => 'NAME', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() can guess relationships from related tables');
	}
	
	public function testJoinAlias()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->join('b.Author');
		$sql = 'SELECT  FROM `book` INNER JOIN author ON (book.AUTHOR_ID=author.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation on main alias');

		$c = new ModelCriteria('bookstore', 'Book');
		$c->join('Book.Author a');
		$sql = 'SELECT  FROM `book` INNER JOIN author a ON (book.AUTHOR_ID=a.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation alias');
	  
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->join('b.Author a');
		$sql = 'SELECT  FROM `book` INNER JOIN author a ON (book.AUTHOR_ID=a.ID)';
		$params = array();
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() supports relation alias on main alias');
		
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->join('b.Author a');
		$c->where('a.FirstName = ?', 'Leo');
		$sql = 'SELECT  FROM  INNER JOIN author a ON (book.AUTHOR_ID=a.ID) WHERE a.FIRST_NAME = :p1';
		$params = array(
			array('table' => 'author', 'column' => 'FIRST_NAME', 'value' => 'Leo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() allows the use of relation alias in where()');

		$c = new ModelCriteria('bookstore', 'Author a');
		$c->join('a.Book b');
		$c->join('b.Publisher p');
		$c->where('p.Name = ?', 'foo');
		$sql = 'SELECT  FROM  INNER JOIN book b ON (author.ID=b.AUTHOR_ID) INNER JOIN publisher p ON (b.PUBLISHER_ID=p.ID) WHERE p.NAME = :p1';
		$params = array(
			array('table' => 'publisher', 'column' => 'NAME', 'value' => 'foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() allows the use of relation alias in further join()');
	}
	
	public function testJoinOnSameTable()
	{
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee be');
		$c->join('be.Supervisor sup');
		$c->join('sup.Subordinate sub');
		$c->where('sub.Name = ?', 'Foo');
		$sql = 'SELECT  FROM  INNER JOIN bookstore_employee sup ON (bookstore_employee.SUPERVISOR_ID=sup.ID) INNER JOIN bookstore_employee sub ON (sup.ID=sub.SUPERVISOR_ID) WHERE sub.NAME = :p1';
		$params = array(
			array('table' => 'bookstore_employee', 'column' => 'NAME', 'value' => 'Foo'),
		);
		$this->assertCriteriaTranslation($c, $sql, $params, 'join() allows two joins on the same table thanks to aliases');
	}
	
	public function testJoinAliasQuery()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->join('b.Author a');
		$c->where('a.FirstName = ?', 'Leo');
		$books = BookPeer::doSelect($c, $con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` INNER JOIN author a ON (book.AUTHOR_ID=a.ID) WHERE a.FIRST_NAME = 'Leo'";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'join() allows the use of relation alias in where()');

		$c = new ModelCriteria('bookstore', 'BookstoreEmployee be');
		$c->join('be.Supervisor sup');
		$c->join('sup.Subordinate sub');
		$c->where('sub.Name = ?', 'Foo');
		$employees = BookstoreEmployeePeer::doSelect($c, $con);
		$expectedSQL = "SELECT bookstore_employee.ID, bookstore_employee.CLASS_KEY, bookstore_employee.NAME, bookstore_employee.JOB_TITLE, bookstore_employee.SUPERVISOR_ID FROM `bookstore_employee` INNER JOIN bookstore_employee sup ON (bookstore_employee.SUPERVISOR_ID=sup.ID) INNER JOIN bookstore_employee sub ON (sup.ID=sub.SUPERVISOR_ID) WHERE sub.NAME = 'Foo'";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'join() allows the use of relation alias in further joins()');
	}
	
	public function testFind()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$books = $c->find();
		$this->assertTrue(is_array($books), 'find() returns an array by default');
		$this->assertEquals(0, count($books), 'find() returns an empty array when the query returns no result');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->join('b.Author a');
		$c->where('a.FirstName = ?', 'Neal');
		$books = $c->find();
		$this->assertTrue(is_array($books), 'find() returns an array by default');
		$this->assertEquals(1, count($books), 'find() returns as many rows as the results in the query');
		$book = array_shift($books);
		$this->assertTrue($book instanceof Book, 'find() returns an array of Model objects by default');
		$this->assertEquals('Quicksilver', $book->getTitle(), 'find() returns the model objects matching the query');
	}

	public function testFindOne()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$book = $c->findOne();
		$this->assertNull($books, 'findOne() returns null when the query returns no result');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->orderBy('b.Title');
		$book = $c->findOne();
		$this->assertTrue($book instanceof Book, 'findOne() returns a Model object by default');
		$this->assertEquals('Don Juan', $book->getTitle(), 'find() returns the model objects matching the query');
	}
	
	public function testFindBy()
	{
		try {
			$c = new ModelCriteria('bookstore', 'Book b');
			$books = $c->findBy('Foo', 'Bar', $con);
			$this->fail('findBy() throws an exception when called on an unknown column name');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'findBy() throws an exception when called on an unknown column name');
		}

		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->findBy('Title', 'Don Juan', $con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan'";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findBy() adds simple column conditions');
		$this->assertTrue(is_array($books), 'findBy() issues a find()');
		$this->assertEquals(1, count($books), 'findBy() adds simple column conditions');
		$book = array_shift($books);
		$this->assertTrue($book instanceof Book, 'findBy() returns an array of Model objects by default');
		$this->assertEquals('Don Juan', $book->getTitle(), 'findBy() returns the model objects matching the query');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->findBy(array('Title', 'ISBN'), array('Don Juan', 12345), $con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan' AND book.ISBN=12345";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findBy() adds multiple column conditions');
	}
	
	public function testFindOneBy()
	{
		try {
			$c = new ModelCriteria('bookstore', 'Book b');
			$book = $c->findOneBy('Foo', 'Bar', $con);
			$this->fail('findOneBy() throws an exception when called on an unknown column name');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'findOneBy() throws an exception when called on an unknown column name');
		}

		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$c = new ModelCriteria('bookstore', 'Book b');
		$book = $c->findOneBy('Title', 'Don Juan', $con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan' LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneBy() adds simple column conditions');
		$this->assertTrue($book instanceof Book, 'findOneBy() returns a Model object by default');
		$this->assertEquals('Don Juan', $book->getTitle(), 'findOneBy() returns the model object matching the query');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->findOneBy(array('Title', 'ISBN'), array('Don Juan', 12345), $con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan' AND book.ISBN=12345 LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneBy() adds multiple column conditions');
	}
	
	public function testCount()
	{
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$nbBooks = $c->count();
		$this->assertTrue(is_int($nbBooks), 'count() returns an integer');
		$this->assertEquals(0, $nbBooks, 'count() returns 0 when the query returns no result');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->join('b.Author a');
		$c->where('a.FirstName = ?', 'Neal');
		$nbBooks = $c->count();
		$this->assertTrue(is_int($nbBooks), 'count() returns an integer');
		$this->assertEquals(1, $nbBooks, 'count() returns the number of results in the query');
	}
	
	public function testPreSelect()
	{
		$c = new ModelCriteriaWithPreSelectHook('bookstore', 'Book b');
		$books = $c->find();
		$this->assertEquals(1, count($books), 'preSelect() can modify the Criteria before find() fires the query');
		
		$c = new ModelCriteriaWithPreSelectHook('bookstore', 'Book b');
		$nbBooks = $c->count();
		$this->assertEquals(1, $nbBooks, 'preSelect() can modify the Criteria before count() fires the query');
	}
	
	public function testDelete()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$c = new ModelCriteria('bookstore', 'Book b');
		try {
			$nbBooks = $c->delete();
			$this->fail('delete() throws an exception when called on an empty Criteria');
		} catch (PropelException $e) {
			$this->assertTrue(true, 'delete() throws an exception when called on an empty Criteria');
		}
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$nbBooks = $c->delete();
		$this->assertTrue(is_int($nbBooks), 'delete() returns an integer');
		$this->assertEquals(0, $nbBooks, 'delete() returns 0 when the query deleted no rows');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$nbBooks = $c->delete();
		$this->assertTrue(is_int($nbBooks), 'delete() returns an integer');
		$this->assertEquals(1, $nbBooks, 'delete() returns the number of the deleted rows');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$nbBooks = $c->count();
		$this->assertEquals(3, $nbBooks, 'delete() deletes rows in the database');
	}
	
	public function testDeleteAll()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$nbBooks = $c->deleteAll();
		$this->assertTrue(is_int($nbBooks), 'deleteAll() returns an integer');
		$this->assertEquals(4, $nbBooks, 'deleteAll() returns the number of deleted rows');
		
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$nbBooks = $c->deleteAll();
		$this->assertEquals(4, $nbBooks, 'deleteAll() ignores conditions on the criteria');
	}
	
	public function testPreDelete()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->find();
		$count = count($books);
		$book = array_shift($books);
		
		$c = new ModelCriteriaWithPreDeleteHook('bookstore', 'Book b');
		$c->where('b.Id = ?', $book->getId());
		$nbBooks = $c->delete();
		$this->assertEquals(12, $nbBooks, 'preDelete() can change the return value of delete()');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$nbBooks = $c->count();
		$this->assertEquals($count, $nbBooks, 'preDelete() can bypass the row deletion');
		
		$c = new ModelCriteriaWithPreDeleteHook('bookstore', 'Book b');
		$nbBooks = $c->deleteAll();
		$this->assertEquals(12, $nbBooks, 'preDelete() can change the return value of deleteAll()');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$nbBooks = $c->count();
		$this->assertEquals($count, $nbBooks, 'preDelete() can bypass the row deletion');
	}
	
	public function testUpdate()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		$count = $con->getQueryCount();
		$c = new ModelCriteria('bookstore', 'Book b');
		$nbBooks = $c->update(array('Title' => 'foo'), $con);
		$this->assertEquals(4, $nbBooks, 'update() returns the number of updated rows');
		$this->assertEquals($count + 1, $con->getQueryCount(), 'update() updates all the objects in one query by default');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$nbBooks = $c->count();
		$this->assertEquals(4, $nbBooks, 'update() updates all records by default');
		
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);

		$count = $con->getQueryCount();
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$nbBooks = $c->update(array('ISBN' => '3456'), $con);
		$this->assertEquals(1, $nbBooks, 'update() updates only the records matching the criteria');
		$this->assertEquals($count + 1, $con->getQueryCount(), 'update() updates all the objects in one query by default');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$book = $c->findOne();
		$this->assertEquals('3456', $book->getISBN(), 'update() updates only the records matching the criteria');
	}
	
	public function testUpdateOneByOne()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		// save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->find();
		foreach ($books as $book) {
			$book->save();
		}
		
		$count = $con->getQueryCount();
		$c = new ModelCriteria('bookstore', 'Book b');
		$nbBooks = $c->update(array('Title' => 'foo'), $con, true);
		$this->assertEquals(4, $nbBooks, 'update() returns the number of updated rows');
		$this->assertEquals($count + 1 + 4, $con->getQueryCount(), 'update() updates the objects one by one when called with true as last parameter');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$nbBooks = $c->count();
		$this->assertEquals(4, $nbBooks, 'update() updates all records by default');
		
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		// save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->find();
		foreach ($books as $book) {
			$book->save();
		}

		$count = $con->getQueryCount();
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$nbBooks = $c->update(array('ISBN' => '3456'), $con, true);
		$this->assertEquals(1, $nbBooks, 'update() updates only the records matching the criteria');
		$this->assertEquals($count + 1 + 1, $con->getQueryCount(), 'update() updates the objects one by one when called with true as last parameter');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$book = $c->findOne();
		$this->assertEquals('3456', $book->getISBN(), 'update() updates only the records matching the criteria');
	}
	
	public function testPreUpdate()
	{
		BookstoreDataPopulator::depopulate($con);
		BookstoreDataPopulator::populate($con);
		
		$c = new ModelCriteriaWithPreUpdateHook('bookstore', 'Book b');
		$c->where('b.Title = ?', 'Don Juan');
		$nbBooks = $c->update(array('Title' => 'foo'));
		
		$c = new ModelCriteriaWithPreUpdateHook('bookstore', 'Book b');
		$c->where('b.Title = ?', 'foo');
		$book = $c->findOne();
		
		$this->assertEquals('1234', $book->getISBN(), 'preUpdate() can modify the values');
	}
	
	public function testMagicJoin()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->leftJoin('b.Author a');
		$c->where('a.FirstName = ?', 'Leo');
		$books = $c->findOne($con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` LEFT JOIN author a ON (book.AUTHOR_ID=a.ID) WHERE a.FIRST_NAME = 'Leo' LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'leftJoin($x) is turned into join($x, Criteria::LEFT_JOIN)');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->innerJoin('b.Author a');
		$c->where('a.FirstName = ?', 'Leo');
		$books = $c->findOne($con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` INNER JOIN author a ON (book.AUTHOR_ID=a.ID) WHERE a.FIRST_NAME = 'Leo' LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'innerJoin($x) is turned into join($x, Criteria::INNER_JOIN)');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$c->rightJoin('b.Author a');
		$c->where('a.FirstName = ?', 'Leo');
		$books = $c->findOne($con);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` RIGHT JOIN author a ON (book.AUTHOR_ID=a.ID) WHERE a.FIRST_NAME = 'Leo' LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'leftJoin($x) is turned into join($x, Criteria::RIGHT_JOIN)');
	}
	
	public function testMagicFind()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->findByTitle('Don Juan');
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan'";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByXXX($value) is turned into findBy(XXX, $value)');

		$c = new ModelCriteria('bookstore', 'Book b');
		$books = $c->findByTitleAndISBN('Don Juan', 1234);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan' AND book.ISBN=1234";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findByXXXAndYYY($value) is turned into findBy(array(XXX,YYY), $value)');
		
		$c = new ModelCriteria('bookstore', 'Book b');
		$book = $c->findOneByTitle('Don Juan');
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan' LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneByXXX($value) is turned into findOneBy(XXX, $value)');

		$c = new ModelCriteria('bookstore', 'Book b');
		$book = $c->findOneByTitleAndISBN('Don Juan', 1234);
		$expectedSQL = "SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.TITLE='Don Juan' AND book.ISBN=1234 LIMIT 1";
		$this->assertEquals($expectedSQL, $con->getLastExecutedQuery(), 'findOneByXXX($value) is turned into findOneBy(XXX, $value)');
	}
}

class TestableModelCriteria extends ModelCriteria
{	
	public function replaceNames(&$clause)
	{
		return parent::replaceNames($clause);
	}
}

class ModelCriteriaWithPreSelectHook extends ModelCriteria
{	
	public function preSelect(PropelPDO $con)
	{
		$this->where($this->getModelAlias() . '.Title = ?', 'Don Juan');
	}
}

class ModelCriteriaWithPreDeleteHook extends ModelCriteria
{	
	public function preDelete(PropelPDO $con)
	{
		return 12;
	}
}

class ModelCriteriaWithPreUpdateHook extends ModelCriteria
{	
	public function preUpdate(&$values, PropelPDO $con)
	{
		$values['ISBN'] = '1234';
	}
}

