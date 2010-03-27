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
 * Tests relationships between generated Object classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * object operations.  The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    generator.builder.om
 */
class GeneratedObjectRelTest extends BookstoreEmptyTestBase
{

	protected function setUp()
	{
		parent::setUp();
	}

	/**
	 * Tests one side of a bi-directional setting of many-to-many relationships.
	 */
	public function testManyToMany_Dir1()
	{
		$list = new BookClubList();
		$list->setGroupLeader('Archimedes Q. Porter');
		// No save ...

		$book = new Book();
		$book->setTitle( "Jungle Expedition Handbook" );
		$book->setISBN('TEST');
		// No save ...

		$this->assertEquals(0, count($list->getBookListRels()) );
		$this->assertEquals(0, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );

		$xref = new BookListRel();
		$xref->setBook($book);
		$list->addBookListRel($xref);

		$this->assertEquals(1, count($list->getBookListRels()));
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );

		$list->save();

		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(1, count(BookListRelPeer::doSelect(new Criteria())) );

	}

	/**
	 * Tests reverse setting of one of many-to-many relationship, with all saves cascaded.
	 */
	public function testManyToMany_Dir2_Unsaved()
	{
		$list = new BookClubList();
		$list->setGroupLeader('Archimedes Q. Porter');
		// No save ...

		$book = new Book();
		$book->setTitle( "Jungle Expedition Handbook" );
		$book->setISBN('TEST');
		// No save (yet) ...

		$this->assertEquals(0, count($list->getBookListRels()) );
		$this->assertEquals(0, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );

		$xref = new BookListRel();
		$xref->setBookClubList($list);
		$book->addBookListRel($xref);

		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );
		$book->save();

		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(1, count(BookListRelPeer::doSelect(new Criteria())) );

	}

	/**
	 * Tests reverse setting of relationships, saving one of the objects first.
	 * @link       http://propel.phpdb.org/trac/ticket/508
	 */
	public function testManyToMany_Dir2_Saved()
	{
		$list = new BookClubList();
		$list->setGroupLeader('Archimedes Q. Porter');
		$list->save();

		$book = new Book();
		$book->setTitle( "Jungle Expedition Handbook" );
		$book->setISBN('TEST');
		// No save (yet) ...

		$this->assertEquals(0, count($list->getBookListRels()) );
		$this->assertEquals(0, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );

		// Now set the relationship from the opposite direction.

		$xref = new BookListRel();
		$xref->setBookClubList($list);
		$book->addBookListRel($xref);

		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(0, count(BookListRelPeer::doSelect(new Criteria())) );
		$book->save();

		$this->assertEquals(1, count($list->getBookListRels()) );
		$this->assertEquals(1, count($book->getBookListRels()) );
		$this->assertEquals(1, count(BookListRelPeer::doSelect(new Criteria())) );

	}
	
	public function testManyToManyGetterExists()
	{
		$this->assertTrue(method_exists('BookClubList', 'getBooks'), 'Object generator correcly adds getter for the crossRefFk');
		$this->assertFalse(method_exists('BookClubList', 'getBookClubLists'), 'Object generator correcly adds getter for the crossRefFk');
	}
	
	public function testManyToManyGetterNewObject()
	{
		$blc1 = new BookClubList();
		$books = $blc1->getBooks();
		$this->assertTrue($books instanceof PropelObjectCollection, 'getCrossRefFK() returns a Propel collection');
		$this->assertEquals('Book', $books->getModel(), 'getCrossRefFK() returns a collection of the correct model');
		$this->assertEquals(0, count($books), 'getCrossRefFK() returns an empty list for new objects');
		$query = BookQuery::create()
			->filterByTitle('Harry Potter and the Order of the Phoenix');
		$books = $blc1->getBooks($query);
		$this->assertEquals(0, count($books), 'getCrossRefFK() accepts a query as first parameter');	
	}
	
	public function testManyToManyGetter()
	{
		BookstoreDataPopulator::populate();
		$blc1 = BookClubListQuery::create()->findOneByGroupLeader('Crazyleggs');
		$books = $blc1->getBooks();
		$this->assertTrue($books instanceof PropelObjectCollection, 'getCrossRefFK() returns a Propel collection');
		$this->assertEquals('Book', $books->getModel(), 'getCrossRefFK() returns a collection of the correct model');
		$this->assertEquals(2, count($books), 'getCrossRefFK() returns the correct list of objects');
		$query = BookQuery::create()
			->filterByTitle('Harry Potter and the Order of the Phoenix');
		$books = $blc1->getBooks($query);
		$this->assertEquals(1, count($books), 'getCrossRefFK() accepts a query as first parameter');	
	}

	public function testManyToManyCounterExists()
	{
		$this->assertTrue(method_exists('BookClubList', 'countBooks'), 'Object generator correcly adds counter for the crossRefFk');
		$this->assertFalse(method_exists('BookClubList', 'countBookClubLists'), 'Object generator correcly adds counter for the crossRefFk');
	}
	
	public function testManyToManyCounterNewObject()
	{
		$blc1 = new BookClubList();
		$nbBooks = $blc1->countBooks();
		$this->assertEquals(0, $nbBooks, 'countCrossRefFK() returns 0 for new objects');
		$query = BookQuery::create()
			->filterByTitle('Harry Potter and the Order of the Phoenix');
		$nbBooks = $blc1->countBooks($query);
		$this->assertEquals(0, $nbBooks, 'countCrossRefFK() accepts a query as first parameter');	
	}
	
	public function testManyToManyCounter()
	{
		BookstoreDataPopulator::populate();
		$blc1 = BookClubListQuery::create()->findOneByGroupLeader('Crazyleggs');
		$nbBooks = $blc1->countBooks();
		$this->assertEquals(2, $nbBooks, 'countCrossRefFK() returns the correct list of objects');
		$query = BookQuery::create()
			->filterByTitle('Harry Potter and the Order of the Phoenix');
		$nbBooks = $blc1->countBooks($query);
		$this->assertEquals(1, $nbBooks, 'countCrossRefFK() accepts a query as first parameter');	
	}
	
	public function testManyToManyAdd()
	{
		$list = new BookClubList();
		$list->setGroupLeader('Archimedes Q. Porter');

		$book = new Book();
		$book->setTitle( "Jungle Expedition Handbook" );
		$book->setISBN('TEST');
		
		$list->addBook($book);
		$this->assertEquals(1, $list->countBooks(), 'addCrossFk() sets the internal collection properly');
		$this->assertEquals(1, $list->countBookListRels(), 'addCrossFk() sets the internal cross reference collection properly');
		
		$list->save();
		$this->assertFalse($book->isNew(), 'related object is saved if added');
		$rels = $list->getBookListRels();
		$rel = $rels[0];
		$this->assertFalse($rel->isNew(), 'cross object is saved if added');
		
		$list->clearBookListRels();
		$list->clearBooks();
		$books = $list->getBooks();
		$expected = new PropelObjectCollection(array($book));
		$expected->setModel('Book');
		$this->assertEquals($expected, $books, 'addCrossFk() adds the object properly');
		$this->assertEquals(1, $list->countBookListRels());
	}

	
	/**
	 * Test behavior of columns that are implicated in multiple foreign keys.
	 * @link       http://propel.phpdb.org/trac/ticket/228
	 */
	public function testMultiFkImplication()
	{
		BookstoreDataPopulator::populate();
		// Create a new bookstore, contest, bookstore_contest, and bookstore_contest_entry
		$b = new Bookstore();
		$b->setStoreName("Foo!");
		$b->save();

		$c = new Contest();
		$c->setName("Bookathon Contest");
		$c->save();

		$bc = new BookstoreContest();
		$bc->setBookstore($b);
		$bc->setContest($c);
		$bc->save();

		$c = new Customer();
		$c->setName("Happy Customer");
		$c->save();

		$bce = new BookstoreContestEntry();
		$bce->setBookstore($b);
		$bce->setBookstoreContest($bc);
		$bce->setCustomer($c);
		$bce->save();

		$bce->setBookstoreId(null);

		$this->assertNull($bce->getBookstoreContest());
		$this->assertNull($bce->getBookstore());
	}

	/**
	 * Test the clearing of related object collection.
	 * @link       http://propel.phpdb.org/trac/ticket/529
	 */
	public function testClearRefFk()
	{
		BookstoreDataPopulator::populate();
		$book = new Book();
		$book->setISBN("Foo-bar-baz");
		$book->setTitle("The book title");

		// No save ...

		$r = new Review();
		$r->setReviewedBy('Me');
		$r->setReviewDate(new DateTime("now"));

		$book->addReview($r);

		// No save (yet) ...

		$this->assertEquals(1, count($book->getReviews()) );
		$book->clearReviews();
		$this->assertEquals(0, count($book->getReviews()));
	}

	/**
	 * This tests to see whether modified objects are being silently overwritten by calls to fk accessor methods.
	 * @link       http://propel.phpdb.org/trac/ticket/509#comment:5
	 */
	public function testModifiedObjectOverwrite()
	{
		BookstoreDataPopulator::populate();
		$author = new Author();
		$author->setFirstName("John");
		$author->setLastName("Public");

		$books = $author->getBooks(); // empty, of course
		$this->assertEquals(0, count($books), "Expected empty collection.");

		$book = new Book();
		$book->setTitle("A sample book");
		$book->setISBN("INITIAL ISBN");

		$author->addBook($book);

		$author->save();

		$book->setISBN("MODIFIED ISBN");

		$books = $author->getBooks();
		$this->assertEquals(1, count($books), "Expected 1 book.");
		$this->assertSame($book, $books[0], "Expected the same object to be returned by fk accessor.");
		$this->assertEquals("MODIFIED ISBN", $books[0]->getISBN(), "Expected the modified value NOT to have been overwritten.");
	}

	public function testFKGetterUseInstancePool()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$author = AuthorPeer::doSelectOne(new Criteria(), $con);
		// populate book instance pool
		$books = $author->getBooks(null, $con);
		$sql = $con->getLastExecutedQuery();
		$author = $books[0]->getAuthor($con);
		$this->assertEquals($sql, $con->getLastExecutedQuery(), 'refFK getter uses instance pool if possible');
	}
	
	public function testRefFKGetJoin()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		PublisherPeer::clearInstancePool();
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$author = AuthorPeer::doSelectOne(new Criteria(), $con);
		// populate book instance pool
		$books = $author->getBooksJoinPublisher(null, $con);
		$sql = $con->getLastExecutedQuery();
		$publisher = $books[0]->getPublisher($con);
		$this->assertEquals($sql, $con->getLastExecutedQuery(), 'refFK getter uses instance pool if possible');
	}
}
