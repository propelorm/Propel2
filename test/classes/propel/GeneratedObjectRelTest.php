<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'bookstore/BookstoreTestBase.php';

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
 */
class GeneratedObjectRelTest extends BookstoreTestBase {

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

	/**
	 * Test behavior of columns that are implicated in multiple foreign keys.
	 * @link       http://propel.phpdb.org/trac/ticket/228
	 */
	public function testMultiFkImplication()
	{
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
		$author = new Author();
		$author->setFirstName("John");
		$author->setLastName("Public");

		$books = $author->getBooks(); // empty, of course
		$this->assertEquals(array(), $books, "Expected empty array.");

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
}
