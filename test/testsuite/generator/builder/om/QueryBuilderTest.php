<?php

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';
require_once 'tools/helpers/bookstore/BookstoreDataPopulator.php';

/**
 * Test class for QueryBuilder.
 *
 * @author     François Zaninotto
 * @version    $Id: QueryBuilderTest.php 1347 2009-12-03 21:06:36Z francois $
 * @package    generator.builder.om
 */
class QueryBuilderTest extends BookstoreTestBase 
{ 
  
	public function testExtends()
	{
		$q = new BookQuery();
		$this->assertTrue($q instanceof ModelCriteria, 'Model query extends ModelCriteria');
	}
	
	public function testConstructor()
	{
		$query = new BookQuery();
		$this->assertEquals($query->getDbName(), 'bookstore', 'Constructor sets dabatase name');
		$this->assertEquals($query->getModelName(), 'Book', 'Constructor sets model name');
	}
	
	public function testCreate()
	{
		$query = BookQuery::create();
		$this->assertTrue($query instanceof BookQuery, 'create() returns an object of its class');
		$this->assertEquals($query->getDbName(), 'bookstore', 'create() sets dabatase name');
		$this->assertEquals($query->getModelName(), 'Book', 'create() sets model name');
		$query = BookQuery::create('foo');
		$this->assertTrue($query instanceof BookQuery, 'create() returns an object of its class');
		$this->assertEquals($query->getDbName(), 'bookstore', 'create() sets dabatase name');
		$this->assertEquals($query->getModelName(), 'Book', 'create() sets model name');
		$this->assertEquals($query->getModelAlias(), 'foo', 'create() can set the model alias');
	}
	
	public function testBasePreSelect()
	{
		$method = new ReflectionMethod('Table4Query', 'basePreSelect');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreSelect()');
	}

	public function testBasePreDelete()
	{
		$method = new ReflectionMethod('Table4Query', 'basePreDelete');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreDelete()');
	}

	public function testBasePreUpdate()
	{
		$method = new ReflectionMethod('Table4Query', 'basePreUpdate');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreUpdate()');
	}

	public function testQuery()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$q = new BookQuery();
		$book = $q
			->setModelAlias('b')
			->where('b.Title like ?', 'Don%')
			->orderBy('b.ISBN', 'desc')
			->findOne();
		$this->assertTrue($book instanceof Book);
		$this->assertEquals('Don Juan', $book->getTitle());
	}
	
	public function testFindPk()
	{
		$method = new ReflectionMethod('Table4Query', 'findPk');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides findPk()');
	}
	
	public function testFindPkSimpleKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		BookPeer::clearInstancePool();
		$con = Propel::getConnection('bookstore');
		
		// prepare the test data
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Id', 'desc');
		$testBook = $c->findOne();
		$count = $con->getQueryCount();

		BookPeer::clearInstancePool();
		
		$q = new BookQuery();
		$book = $q->findPk($testBook->getId());
		$this->assertEquals($testBook, $book, 'BaseQuery overrides findPk() to make it faster');
		$this->assertEquals($count+1, $con->getQueryCount(), 'findPk() issues a database query when instance pool is empty');

		$q = new BookQuery();
		$book = $q->findPk($testBook->getId());
		$this->assertEquals($testBook, $book, 'BaseQuery overrides findPk() to make it faster');
		$this->assertEquals($count+1, $con->getQueryCount(), 'findPk() does not issue a database query when instance is in pool');
	}
	
	public function testFindPkCompositeKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		// save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
		$c = new ModelCriteria('bookstore', 'Book');
		$books = $c->find();
		foreach ($books as $book) {
			$book->save();
		}

		BookPeer::clearInstancePool();
		
		// retrieve the test data
		$c = new ModelCriteria('bookstore', 'BookListRel');
		$bookListRelTest = $c->findOne();
		$pk = $bookListRelTest->getPrimaryKey();
		
		$q = new BookListRelQuery();
		$bookListRel = $q->findPk($pk);
		$this->assertEquals($bookListRelTest, $bookListRel, 'BaseQuery overrides findPk() for composite primary keysto make it faster');
	}

	public function testFindPks()
	{
		$method = new ReflectionMethod('Table4Query', 'findPks');
		$this->assertEquals('BaseTable4Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides findPks()');
	}

	public function testFindPksSimpleKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		BookPeer::clearInstancePool();
		
		// prepare the test data
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Id', 'desc');
		$testBooks = $c->find();
		$testBook1 = $testBooks->pop();
		$testBook2 = $testBooks->pop();

		$q = new BookQuery();
		$books = $q->findPks(array($testBook1->getId(), $testBook2->getId()));
		$this->assertEquals(array($testBook1, $testBook2), $books->getData(), 'BaseQuery overrides findPks() to make it faster');
	}
	
	public function testFindPksCompositeKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		// save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
		$c = new ModelCriteria('bookstore', 'Book');
		$books = $c->find();
		foreach ($books as $book) {
			$book->save();
		}

		BookPeer::clearInstancePool();
		
		// retrieve the test data
		$c = new ModelCriteria('bookstore', 'BookListRel');
		$bookListRelTest = $c->find();
		$search = array();
		foreach ($bookListRelTest as $obj) {
			$search[]= $obj->getPrimaryKey();
		}
		
		$q = new BookListRelQuery();
		$objs = $q->findPks($search);
		$this->assertEquals($bookListRelTest, $objs, 'BaseQuery overrides findPks() for composite primary keys to make it work');
	}
	
	public function testFilterBy()
	{
		foreach (BookPeer::getFieldNames(BasePeer::TYPE_PHPNAME) as $colName) {
			$filterMethod = 'filterBy' . $colName;
			$this->assertTrue(method_exists('BookQuery', $filterMethod), 'QueryBuilder adds filterByColumn() methods for every column');
			$q = BookQuery::create()->$filterMethod(1);
			$this->assertTrue($q instanceof BookQuery, 'filterByColumn() returns the current query instance');
		}
	}

	public function testFilterByPk()
	{
		$q = BookQuery::create()->filterById(12);
		$q1 = BookQuery::create()->add(BookPeer::ID, 12, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByPkColumn() translates to a Criteria::EQUAL by default');

		$q = BookQuery::create()->setModelAlias('b', true)->filterById(12);
		$q1 = BookQuery::create()->setModelAlias('b', true)->add('b.ID', 12, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByPkColumn() uses true table alias if set');

		$q = BookQuery::create()->filterById(array(10, 11, 12));
		$q1 = BookQuery::create()->add(BookPeer::ID, array(10, 11, 12), Criteria::IN);
		$this->assertEquals($q1, $q, 'filterByPkColumn() translates to a Criteria::IN when passed a simple array key');
	}
	
	public function testFilterByNumber()
	{
		$q = BookQuery::create()->filterByPrice(12);
		$q1 = BookQuery::create()->add(BookPeer::PRICE, 12, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::EQUAL by default');

		$q = BookQuery::create()->setModelAlias('b', true)->filterByPrice(12);
		$q1 = BookQuery::create()->setModelAlias('b', true)->add('b.PRICE', 12, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByNumColumn() uses true table alias if set');

		$q = BookQuery::create()->filterByPrice(array(10, 11, 12));
		$q1 = BookQuery::create()->add(BookPeer::PRICE, array(10, 11, 12), Criteria::IN);
		$this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::IN when passed a simple array key');
		
		$q = BookQuery::create()->filterByPrice(array('min' => 10));
		$q1 = BookQuery::create()->add(BookPeer::PRICE, 10, Criteria::GREATER_EQUAL);
		$this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::GREATER_EQUAL when passed a \'min\' key');

		$q = BookQuery::create()->filterByPrice(array('max' => 12));
		$q1 = BookQuery::create()->add(BookPeer::PRICE, 12, Criteria::LESS_EQUAL);
		$this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::LESS_EQUAL when passed a \'max\' key');

		$q = BookQuery::create()->filterByPrice(array('min' => 10, 'max' => 12));
		$q1 = BookQuery::create()
			->add(BookPeer::PRICE, 10, Criteria::GREATER_EQUAL)
			->addAnd(BookPeer::PRICE, 12, Criteria::LESS_EQUAL);
		$this->assertEquals($q1, $q, 'filterByNumColumn() translates to a between when passed both a \'min\' and a \'max\' key');
	}

	public function testFilterByTimestamp()
	{
		$q = BookstoreEmployeeAccountQuery::create()->filterByCreated(12);
		$q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountPeer::CREATED, 12, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByDateColumn() translates to a Criteria::EQUAL by default');

		$q = BookstoreEmployeeAccountQuery::create()->setModelAlias('b', true)->filterByCreated(12);
		$q1 = BookstoreEmployeeAccountQuery::create()->setModelAlias('b', true)->add('b.CREATED', 12, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByDateColumn() uses true table alias if set');
		
		$q = BookstoreEmployeeAccountQuery::create()->filterByCreated(array('min' => 10));
		$q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountPeer::CREATED, 10, Criteria::GREATER_EQUAL);
		$this->assertEquals($q1, $q, 'filterByDateColumn() translates to a Criteria::GREATER_EQUAL when passed a \'min\' key');

		$q = BookstoreEmployeeAccountQuery::create()->filterByCreated(array('max' => 12));
		$q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountPeer::CREATED, 12, Criteria::LESS_EQUAL);
		$this->assertEquals($q1, $q, 'filterByDateColumn() translates to a Criteria::LESS_EQUAL when passed a \'max\' key');

		$q = BookstoreEmployeeAccountQuery::create()->filterByCreated(array('min' => 10, 'max' => 12));
		$q1 = BookstoreEmployeeAccountQuery::create()
			->add(BookstoreEmployeeAccountPeer::CREATED, 10, Criteria::GREATER_EQUAL)
			->addAnd(BookstoreEmployeeAccountPeer::CREATED, 12, Criteria::LESS_EQUAL);
		$this->assertEquals($q1, $q, 'filterByDateColumn() translates to a between when passed both a \'min\' and a \'max\' key');
	}

	public function testFilterByString()
	{
		$q = BookQuery::create()->filterByTitle('foo');
		$q1 = BookQuery::create()->add(BookPeer::TITLE, 'foo', Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::EQUAL by default');

		$q = BookQuery::create()->setModelAlias('b', true)->filterByTitle('foo');
		$q1 = BookQuery::create()->setModelAlias('b', true)->add('b.TITLE', 'foo', Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByStringColumn() uses true table alias if set');
		
		$q = BookQuery::create()->filterByTitle('foo%');
		$q1 = BookQuery::create()->add(BookPeer::TITLE, 'foo%', Criteria::LIKE);
		$this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::LIKE when passed a string with a % wildcard');

		$q = BookQuery::create()->filterByTitle('*foo');
		$q1 = BookQuery::create()->add(BookPeer::TITLE, '%foo', Criteria::LIKE);
		$this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::LIKE when passed a string with a * wildcard, and turns * into %');

		$q = BookQuery::create()->filterByTitle('*f%o*o%');
		$q1 = BookQuery::create()->add(BookPeer::TITLE, '%f%o%o%', Criteria::LIKE);
		$this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::LIKE when passed a string with mixed wildcards, and turns *s into %s');
	}

	public function testFilterByBoolean()
	{
		$q = ReviewQuery::create()->filterByRecommended(true);
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, true, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a Criteria::EQUAL by default');

		$q = ReviewQuery::create()->filterByRecommended(false);
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, false, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a Criteria::EQUAL by default');
		
		$q = ReviewQuery::create()->setModelAlias('b', true)->filterByRecommended(true);
		$q1 = ReviewQuery::create()->setModelAlias('b', true)->add('b.RECOMMENDED', true, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() uses true table alias if set');
		
		$q = ReviewQuery::create()->filterByRecommended('true');
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, true, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = true when passed a true string');

		$q = ReviewQuery::create()->filterByRecommended('yes');
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, true, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = true when passed a true string');

		$q = ReviewQuery::create()->filterByRecommended('1');
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, true, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = true when passed a true string');
		
		$q = ReviewQuery::create()->filterByRecommended('false');
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, false, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed a false string');

		$q = ReviewQuery::create()->filterByRecommended('no');
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, false, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed a false string');

		$q = ReviewQuery::create()->filterByRecommended('0');
		$q1 = ReviewQuery::create()->add(ReviewPeer::RECOMMENDED, false, Criteria::EQUAL);
		$this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed a false string');
	}
		
	public function testFilterByFk()
	{
		$this->assertTrue(method_exists('BookQuery', 'filterByAuthor'), 'QueryBuilder adds filterByFk() methods');
		$this->assertTrue(method_exists('BookQuery', 'filterByPublisher'), 'QueryBuilder adds filterByFk() methods for all fkeys');
		
		$this->assertTrue(method_exists('EssayQuery', 'filterByAuthorRelatedByFirstAuthor'), 'QueryBuilder adds filterByFk() methods for several fkeys on the same table');
		$this->assertTrue(method_exists('EssayQuery', 'filterByAuthorRelatedBySecondAuthor'), 'QueryBuilder adds filterByFk() methods for several fkeys on the same table');		
	}
	
	public function testFilterByFkSimpleKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		// prepare the test data
		$testBook = BookQuery::create()
			->innerJoin('Book.Author') // just in case there are books with no author
			->findOne();
		$testAuthor = $testBook->getAuthor();

		$book = BookQuery::create()
			->filterByAuthor($testAuthor)
			->findOne();
		$this->assertEquals($testBook, $book, 'Generated query handles filterByFk() methods correctly for simple fkeys');
	}

	public function testFilterByFkCompositeKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		BookstoreDataPopulator::populateOpinionFavorite();
		
		// prepare the test data
		$testOpinion = BookOpinionQuery::create()
			->innerJoin('BookOpinion.ReaderFavorite') // just in case there are books with no author
			->findOne();
		$testFavorite = $testOpinion->getReaderFavorite();

		$favorite = ReaderFavoriteQuery::create()
			->filterByBookOpinion($testOpinion)
			->findOne();
		$this->assertEquals($testFavorite, $favorite, 'Generated query handles filterByFk() methods correctly for composite fkeys');
	}
	
		public function testFilterByRefFk()
	{
		$this->assertTrue(method_exists('BookQuery', 'filterByReview'), 'QueryBuilder adds filterByRefFk() methods');
		$this->assertTrue(method_exists('BookQuery', 'filterByMedia'), 'QueryBuilder adds filterByRefFk() methods for all fkeys');
		
		$this->assertTrue(method_exists('AuthorQuery', 'filterByEssayRelatedByFirstAuthor'), 'QueryBuilder adds filterByRefFk() methods for several fkeys on the same table');
		$this->assertTrue(method_exists('AuthorQuery', 'filterByEssayRelatedBySecondAuthor'), 'QueryBuilder adds filterByRefFk() methods for several fkeys on the same table');				
	}

	public function testFilterByRefFkSimpleKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		// prepare the test data
		$testBook = BookQuery::create()
			->innerJoin('Book.Author') // just in case there are books with no author
			->findOne();
		$testAuthor = $testBook->getAuthor();

		$author = AuthorQuery::create()
			->filterByBook($testBook)
			->findOne();
		$this->assertEquals($testAuthor, $author, 'Generated query handles filterByRefFk() methods correctly for simple fkeys');
	}

	public function testFilterByRefFkCompositeKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		BookstoreDataPopulator::populateOpinionFavorite();
		
		// prepare the test data
		$testOpinion = BookOpinionQuery::create()
			->innerJoin('BookOpinion.ReaderFavorite') // just in case there are books with no author
			->findOne();
		$testFavorite = $testOpinion->getReaderFavorite();

		$opinion = BookOpinionQuery::create()
			->filterByReaderFavorite($testFavorite)
			->findOne();
		$this->assertEquals($testOpinion, $opinion, 'Generated query handles filterByRefFk() methods correctly for composite fkeys');
	}

	public function testPrune()
	{
		$q = BookQuery::create()->prune();
		$this->assertTrue($q instanceof BookQuery, 'prune() returns the current Query object');
	}
	
	public function testPruneSimpleKey()
	{	
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		$nbBooks = BookQuery::create()->prune()->count();
		$this->assertEquals(4, $nbBooks, 'prune() does nothing when passed a null object');
		
		$testBook = BookQuery::create()->findOne();
		$nbBooks = BookQuery::create()->prune($testBook)->count();
		$this->assertEquals(3, $nbBooks, 'prune() removes an object from the result');
	}

	public function testPruneCompositeKey()
	{
		BookstoreDataPopulator::depopulate();
		BookstoreDataPopulator::populate();
		
		// save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
		$c = new ModelCriteria('bookstore', 'Book');
		$books = $c->find();
		foreach ($books as $book) {
			$book->save();
		}
		
		BookPeer::clearInstancePool();
		
		$nbBookListRel = BookListRelQuery::create()->prune()->count();
		$this->assertEquals(2, $nbBookListRel, 'prune() does nothing when passed a null object');
		
		$testBookListRel = BookListRelQuery::create()->findOne();
		$nbBookListRel = BookListRelQuery::create()->prune($testBookListRel)->count();
		$this->assertEquals(1, $nbBookListRel, 'prune() removes an object from the result');
	}
}
