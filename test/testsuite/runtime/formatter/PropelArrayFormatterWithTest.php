<?php

require_once 'tools/helpers/bookstore/BookstoreEmptyTestBase.php';

/**
 * Test class for PropelArrayFormatter when Criteria uses with().
 *
 * @author     Francois Zaninotto
 * @version    $Id: PropelArrayFormatterWithTest.php 1348 2009-12-03 21:49:00Z francois $
 * @package    runtime.formatter
 */
class PropelArrayFormatterWithTest extends BookstoreEmptyTestBase
{
	protected function assertCorrectHydration1($c, $msg)
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$count = $con->getQueryCount();
		$this->assertEquals($book['Title'], 'Don Juan', 'Main object is correctly hydrated ' . $msg);
		$author = $book['Author'];
		$this->assertEquals($author['LastName'], 'Byron', 'Related object is correctly hydrated ' . $msg);
		$publisher = $book['Publisher'];
		$this->assertEquals($publisher['Name'], 'Penguin', 'Related object is correctly hydrated ' . $msg);
	}
	
	public function testFindOneWith()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
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
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
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
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
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
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->orderBy('Book.Title');
		$c->join('Book.Author');
		$c->with('Author');
		$c->join('Book.Publisher');
		$c->with('Publisher');
		$this->assertCorrectHydration1($c, 'with instance pool');
	}

	public function testFindOneWithEmptyLeftJoin()
	{
		// save a book with no author
		$b = new Book();
		$b->setTitle('Foo');
		$b->save();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->where('Book.Title = ?', 'Foo');
		$c->leftJoin('Book.Author');
		$c->with('Author');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$count = $con->getQueryCount();
		$author = $book['Author'];
		$this->assertEquals(array(), $author, 'Related object is not hydrated if empty');
	}

	public function testFindOneWithRelationName()
	{
		BookstoreDataPopulator::populate();
		BookstoreEmployeePeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'BookstoreEmployee');
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->join('BookstoreEmployee.Supervisor s');
		$c->with('s');
		$c->where('s.Name = ?', 'John');
		$emp = $c->findOne();
		$this->assertEquals($emp['Name'], 'Pieter', 'Main object is correctly hydrated');
		$sup = $emp['Supervisor'];
		$this->assertEquals($sup['Name'], 'John', 'Related object is correctly hydrated');
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
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->join('Essay.AuthorRelatedByFirstAuthor');
		$c->with('AuthorRelatedByFirstAuthor');
		$c->where('Essay.Title = ?', 'Foo');
		$essay = $c->findOne();
		$this->assertEquals($essay['Title'], 'Foo', 'Main object is correctly hydrated');
		$firstAuthor = $essay['AuthorRelatedByFirstAuthor'];
		$this->assertEquals($firstAuthor['FirstName'], 'John', 'Related object is correctly hydrated');
		$this->assertFalse(array_key_exists('AuthorRelatedBySecondAuthor', $essay), 'Only related object specified in with() is hydrated');
	}
	
	public function testFindOneWithDistantClass()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Review');
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->where('Review.Recommended = ?', true);
		$c->join('Review.Book');
		$c->with('Book');
		$c->join('Book.Author');
		$c->with('Author');
		$review = $c->findOne();
		$this->assertEquals($review['ReviewedBy'], 'Washington Post', 'Main object is correctly hydrated');
		$book = $review['Book'];
		$this->assertEquals('Harry Potter and the Order of the Phoenix', $book['Title'], 'Related object is correctly hydrated');
		$author = $book['Author'];
		$this->assertEquals('J.K.', $author['FirstName'], 'Related object is correctly hydrated');
	}

	public function testFindOneWithColumn()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->filterByTitle('The Tin Drum');
		$c->join('Book.Author');
		$c->withColumn('Author.FirstName', 'AuthorName');
		$c->withColumn('Author.LastName', 'AuthorName2');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$this->assertEquals(array('Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId', 'AuthorName', 'AuthorName2'), array_keys($book), 'withColumn() do not change the resulting model class');
		$this->assertEquals('The Tin Drum', $book['Title']);
		$this->assertEquals('Gunter', $book['AuthorName'], 'PropelArrayFormatter adds withColumns as columns');
		$this->assertEquals('Grass', $book['AuthorName2'], 'PropelArrayFormatter correctly hydrates all as columns');
	}

	public function testFindOneWithClassAndColumn()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();
		$c = new ModelCriteria('bookstore', 'Book');
		$c->setFormatter(ModelCriteria::FORMAT_ARRAY);
		$c->filterByTitle('The Tin Drum');
		$c->join('Book.Author');
		$c->withColumn('Author.FirstName', 'AuthorName');
		$c->withColumn('Author.LastName', 'AuthorName2');
		$c->with('Author');
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$book = $c->findOne($con);
		$this->assertEquals(array('Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId', 'Author', 'AuthorName', 'AuthorName2'), array_keys($book), 'withColumn() do not change the resulting model class');
		$this->assertEquals('The Tin Drum', $book['Title']);
		$this->assertEquals('Gunter', $book['Author']['FirstName'], 'PropelArrayFormatter correctly hydrates withclass and columns');
		$this->assertEquals('Gunter', $book['AuthorName'], 'PropelArrayFormatter adds withColumns as columns');
		$this->assertEquals('Grass', $book['AuthorName2'], 'PropelArrayFormatter correctly hydrates all as columns');
	}
}
