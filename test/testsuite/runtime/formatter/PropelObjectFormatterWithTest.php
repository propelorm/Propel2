<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/bookstore/BookstoreEmptyTestBase.php';

/**
 * Test class for PropelObjectFormatter when Criteria uses with().
 *
 * @author     Francois Zaninotto
 * @version    $Id: PropelObjectFormatterWithTest.php 1348 2009-12-03 21:49:00Z francois $
 * @package    runtime.formatter
 */
class PropelObjectFormatterWithTest extends BookstoreEmptyTestBase
{
	protected function assertCorrectHydration1($c, $msg)
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$count = $con->getQueryCount();
		$this->assertEquals($book->getTitle(), 'Don Juan', 'Main object is correctly hydrated ' . $msg);
		$author = $book->getAuthor();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ' . $msg);
		$this->assertEquals($author->getLastName(), 'Byron', 'Related object is correctly hydrated ' . $msg);
		$publisher = $book->getPublisher();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ' . $msg);
		$this->assertEquals($publisher->getName(), 'Penguin', 'Related object is correctly hydrated ' . $msg);
	}
	
	public function testFindOneWith()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Title');
		$c->join('Book.Author');
		$c->with('Author');
		$c->join('Book.Publisher');
		$c->with('Publisher');
		$this->assertCorrectHydration1($c, 'without instance pool');
	}

	public function testFindOneWithAlias()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Title');
		$c->join('Book.Author a');
		$c->with('a');
		$c->join('Book.Publisher p');
		$c->with('p');
		$this->assertCorrectHydration1($c, 'with alias');
	}

	public function testFindOneWithMainAlias()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->setModelAlias('b', true);
		$c->orderBy('b.Title');
		$c->join('b.Author a');
		$c->with('a');
		$c->join('b.Publisher p');
		$c->with('p');
		$this->assertCorrectHydration1($c, 'with main alias');
	}
	
	public function testFindOneWithUsingInstancePool()
	{
		BookstoreDataPopulator::populate();
		// instance pool contains all objects by default, since they were just populated
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Title');
		$c->join('Book.Author');
		$c->with('Author');
		$c->join('Book.Publisher');
		$c->with('Publisher');
		$this->assertCorrectHydration1($c, 'with instance pool');
	}

	public function testFindOneWithoutUsingInstancePool()
	{
		BookstoreDataPopulator::populate();
		Propel::disableInstancePooling();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->orderBy('Book.Title');
		$c->join('Book.Author');
		$c->with('Author');
		$c->join('Book.Publisher');
		$c->with('Publisher');
		$this->assertCorrectHydration1($c, 'without instance pool');
		Propel::enableInstancePooling();
	}

	public function testFindOneWithEmptyLeftJoin()
	{
		// save a book with no author
		$b = new Book();
		$b->setTitle('Foo');
		$b->save();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->where('Book.Title = ?', 'Foo');
		$c->leftJoin('Book.Author');
		$c->with('Author');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$count = $con->getQueryCount();
		$author = $book->getAuthor();
		$this->assertNull($author, 'Related object is not hydrated if empty');
	}

	public function testFindOneWithRelationName()
	{
		BookstoreDataPopulator::populate();
		BookstoreEmployeePeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee');
		$c->join('BookstoreEmployee.Supervisor s');
		$c->with('s');
		$c->where('s.Name = ?', 'John');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$emp = $c->findOne($con);
		$count = $con->getQueryCount();
		$this->assertEquals($emp->getName(), 'Pieter', 'Main object is correctly hydrated');
		$sup = $emp->getSupervisor();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
		$this->assertEquals($sup->getName(), 'John', 'Related object is correctly hydrated');
	}

	public function testFindOneWithDuplicateRelation()
	{
		EssayPeer::doDeleteAll();
		$auth1 = new Author();
		$auth1->setFirstName('John');
		$auth1->save();
		$auth2 = new Author();
		$auth2->setFirstName('Jack');
		$auth2->save();
		$essay = new Essay();
		$essay->setTitle('Foo');
		$essay->setFirstAuthor($auth1->getId());
		$essay->setSecondAuthor($auth2->getId());
		$essay->save();
		AuthorPeer::clearInstancePool();
		EssayPeer::clearInstancePool();
		
		$c = new ModelCriteria('bookstore', 'Essay');
		$c->join('Essay.AuthorRelatedByFirstAuthor');
		$c->with('AuthorRelatedByFirstAuthor');
		$c->where('Essay.Title = ?', 'Foo');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$essay = $c->findOne($con);
		$count = $con->getQueryCount();
		$this->assertEquals($essay->getTitle(), 'Foo', 'Main object is correctly hydrated');
		$firstAuthor = $essay->getAuthorRelatedByFirstAuthor();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
		$this->assertEquals($firstAuthor->getFirstName(), 'John', 'Related object is correctly hydrated');
		$secondAuthor = $essay->getAuthorRelatedBySecondAuthor();
		$this->assertEquals($count + 1, $con->getQueryCount(), 'with() does not hydrate objects not in with');
	}
	
	public function testFindOneWithDistantClass()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		Propel::enableInstancePooling();
		$c = new ModelCriteria('bookstore', 'Review');
		$c->where('Review.Recommended = ?', true);
		$c->join('Review.Book');
		$c->with('Book');
		$c->join('Book.Author');
		$c->with('Author');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$review = $c->findOne($con);
		$count = $con->getQueryCount();
		$this->assertEquals($review->getReviewedBy(), 'Washington Post', 'Main object is correctly hydrated');
		$book = $review->getBook();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
		$this->assertEquals('Harry Potter and the Order of the Phoenix', $book->getTitle(), 'Related object is correctly hydrated');
		$author = $book->getAuthor();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
		$this->assertEquals('J.K.', $author->getFirstName(), 'Related object is correctly hydrated');
	}
	
	/**
	 * @expectedException PropelException
	 */
	public function testFindOneWithOneToManyAndLimit()
	{
		$c = new ModelCriteria('bookstore', 'Book');
		$c->add(BookPeer::ISBN, '043935806X');
		$c->leftJoin('Book.Review');
		$c->with('Review');
		$c->limit(5);
		$books = $c->find();
	}
	
	public function testFindOneWithOneToMany()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->add(BookPeer::ISBN, '043935806X');
		$c->leftJoin('Book.Review');
		$c->with('Review');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$books = $c->find($con);
		$this->assertEquals(1, count($books), 'with() does not duplicate the main object');
		$book = $books[0];
		$count = $con->getQueryCount();
		$this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Main object is correctly hydrated');
		$reviews = $book->getReviews();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
		try {
			$book->save();
		} catch (Exception $e) {
			$this->fail('with() does not force objects to be new');
		}
	}
	
	public function testFindOneWithOneToManyCustomOrder()
	{
		$author1 = new Author();
		$author1->setFirstName('AA');
		$author2 = new Author();
		$author2->setFirstName('BB');
		$book1 = new Book();
		$book1->setTitle('Aaa');
		$book1->setAuthor($author1);
		$book1->save();
		$book2 = new Book();
		$book2->setTitle('Bbb');
		$book2->setAuthor($author2);
		$book2->save();
		$book3 = new Book();
		$book3->setTitle('Ccc');
		$book3->setAuthor($author1);
		$book3->save();
		$authors = AuthorQuery::create()
			->leftJoin('Author.Book')
			->orderBy('Book.Title')
			->with('Book')
			->find();
		$this->assertEquals(2, count($authors), 'with() used on a many-to-many doesn\'t change the main object count');
	}
	
	public function testFindOneWithOneToManyThenManyToOne()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Author');
		$c->add(AuthorPeer::LAST_NAME, 'Rowling');
		$c->leftJoinWith('Author.Book');
		$c->leftJoinWith('Book.Review');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$authors = $c->find($con);
		$this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
		$rowling = $authors[0];
		$count = $con->getQueryCount();
		$this->assertEquals($rowling->getFirstName(), 'J.K.', 'Main object is correctly hydrated');
		$books = $rowling->getBooks();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
		$book = $books[0];
		$this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
		$reviews = $book->getReviews();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
	}
	
	public function testFindOneWithOneToManyThenManyToOneUsingJoinRelated()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();

		$con = Propel::getConnection(AuthorPeer::DATABASE_NAME);
		$authors = AuthorQuery::create()
			->filterByLastName('Rowling')
			->joinBook('book')
			->with('book')
			->useQuery('book')
				->joinReview('review')
				->with('review')
			->endUse()
			->find($con);
		$this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
		$rowling = $authors[0];
		$count = $con->getQueryCount();
		$this->assertEquals($rowling->getFirstName(), 'J.K.', 'Main object is correctly hydrated');
		$books = $rowling->getBooks();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
		$book = $books[0];
		$this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
		$reviews = $book->getReviews();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
	}	

	public function testFindOneWithOneToManyThenManyToOneUsingAlias()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Author');
		$c->add(AuthorPeer::LAST_NAME, 'Rowling');
		$c->leftJoinWith('Author.Book b');
		$c->leftJoinWith('b.Review r');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$authors = $c->find($con);
		$this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
		$rowling = $authors[0];
		$count = $con->getQueryCount();
		$this->assertEquals($rowling->getFirstName(), 'J.K.', 'Main object is correctly hydrated');
		$books = $rowling->getBooks();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
		$book = $books[0];
		$this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
		$reviews = $book->getReviews();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
	}
	
	public function testFindOneWithColumn()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->filterByTitle('The Tin Drum');
		$c->join('Book.Author');
		$c->withColumn('Author.FirstName', 'AuthorName');
		$c->withColumn('Author.LastName', 'AuthorName2');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
		$this->assertEquals('The Tin Drum', $book->getTitle());
		$this->assertEquals('Gunter', $book->getVirtualColumn('AuthorName'), 'PropelObjectFormatter adds withColumns as virtual columns');
		$this->assertEquals('Grass', $book->getVirtualColumn('AuthorName2'), 'PropelObjectFormatter correctly hydrates all virtual columns');
		$this->assertEquals('Gunter', $book->getAuthorName(), 'PropelObjectFormatter adds withColumns as virtual columns');
	}

	public function testFindOneWithClassAndColumn()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->filterByTitle('The Tin Drum');
		$c->join('Book.Author');
		$c->withColumn('Author.FirstName', 'AuthorName');
		$c->withColumn('Author.LastName', 'AuthorName2');
		$c->with('Author');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
		$this->assertEquals('The Tin Drum', $book->getTitle());
		$this->assertTrue($book->getAuthor() instanceof Author, 'PropelObjectFormatter correctly hydrates with class');
		$this->assertEquals('Gunter', $book->getAuthor()->getFirstName(), 'PropelObjectFormatter correctly hydrates with class');
		$this->assertEquals('Gunter', $book->getVirtualColumn('AuthorName'), 'PropelObjectFormatter adds withColumns as virtual columns');
		$this->assertEquals('Grass', $book->getVirtualColumn('AuthorName2'), 'PropelObjectFormatter correctly hydrates all virtual columns');
	}

	public function testFindPkWithOneToMany()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = BookQuery::create()
			->findOneByTitle('Harry Potter and the Order of the Phoenix', $con);
		$pk = $book->getPrimaryKey();
		BookPeer::clearInstancePool();
		$book = BookQuery::create()
			->joinWith('Review')
			->findPk($pk, $con);
		$count = $con->getQueryCount();
		$reviews = $book->getReviews();
		$this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
		$this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
	}
}
