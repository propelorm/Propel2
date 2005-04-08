<?php
/*
 *  $Id: GeneratedPeerTest.php,v 1.4 2005/03/19 13:35:47 micha Exp $
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
 * Tests the generated Peer classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various 
 * peer operations.
 * 
 * The database is relaoded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same 
 * for each test method in this class.  See the BookstoreDataPopulator::populate() 
 * method for the exact contents of the database.
 * 
 * @see BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratedPeerTest extends BookstoreTestBase
{

  /**
  * Test ability to delete multiple rows via single Criteria object.
  */
  function testDoDelete_MultiTable()
  {
    // print "Attempting to delete [multi-table] by found pk: ";

    $selc = new Criteria();
    $selc->add(BookPeer::TITLE(), "Harry Potter and the Order of the Phoenix");
    $hp = BookPeer::doSelectOne($selc);
    
    $c = new Criteria();
    $c->add(BookPeer::ID(), $hp->getId());
    // The only way for multi-delete to work currently
    // is to specify the author_id and publisher_id (i.e. the fkeys
    // have to be in the criteria).
    $c->add(AuthorPeer::ID(), $hp->getAuthorId());
    $c->add(PublisherPeer::ID(), $hp->getPublisherId());
    $c->setSingleRecord(true);

    $e = BookPeer::doDelete($c);
    if (Propel::isError($e)) $this->fail($e->getMessage());

    // check to make sure the right # of records was removed
    $this->assertEquals(3, count(AuthorPeer::doSelect(new Criteria())), "Expected 3 authors after deleting.");
    $this->assertEquals(3, count(PublisherPeer::doSelect(new Criteria())), "Expected 3 publishers after deleting.");
    $this->assertEquals(3, count(BookPeer::doSelect(new Criteria())), "Expected 3 books after deleting.");
  }
  
 /**
  * Test using a complex criteria to delete multiple rows from a single table.
  */
  function testDoDelete_ComplexCriteria()
  {
    // print "Attempting to delete books by complex criteria: ";
    $c = new Criteria();
    $cn = $c->getNewCriterion(BookPeer::ISBN(), "043935806X");
    $cn->addOr($c->getNewCriterion(BookPeer::ISBN(), "0380977427"));
    $cn->addOr($c->getNewCriterion(BookPeer::ISBN(), "0140422161"));
    $c->add($cn);

    $e = BookPeer::doDelete($c);
    if (Propel::isError($e)) $this->fail($e->getMessage());
    
    // now there should only be one book left; "The Tin Drum"
    
    $books = BookPeer::doSelect(new Criteria());
    if (Propel::isError($e)) $this->fail($e->getMessage());
    
    $this->assertEquals(1, count($books), "Expected 1 book remaining after deleting.");
    $this->assertEquals("The Tin Drum", $books[0]->getTitle(), "Expect the only remaining book to be 'The Tin Drum'");
  }
  
 /**
  * Test that cascading deletes are happening correctly (whether emulated or native).
  */
  function testDoDelete_Cascade()
  {
    // print "testDoDelete_Cascade\n";

    // The 'media' table will cascade from book deletes
    
    // 1) Assert the row exists right now    
    $medias = MediaPeer::doSelect(new Criteria());
    if (Propel::isError($medias)) {
      $this->fail($medias->getMessage());
      return;
    }

    $this->assertTrue(count($medias) > 0, "Expected to find at least one row in 'media' table.");

    $media = $medias[0];
    $mediaId = $media->getId();

    // 2) Delete the owning book
    $owningBookId = $media->getBookId();
    $e = BookPeer::doDelete($owningBookId);
    if (Propel::isError($e)) $this->fail($e->getMessage());

    // 3) Assert that the media row is now also gone
    $media = MediaPeer::retrieveByPK($mediaId);
    $this->assertNull($media, "Expect NULL when retrieving on no matching media.");
  }
  
  /**
  * Test that onDelete="SETNULL" is happening correctly (whether emulated or native).
  */
  function testDoDelete_SetNull()
  {
    // The 'author_id' column in 'book' table will be set to null when author is deleted.
    // 1) Get an arbitrary book

    $book = BookPeer::doSelectOne(new Criteria());
    if(!$book) {
      $this->fail('Failed to select a book');
      return;
    }

    $bookId = $book->getId();
    $authorId = $book->getAuthorId();

    // 2) Delete the author for that book
    $e = AuthorPeer::doDelete($authorId);
    if (Propel::isError($e)) $this->fail($e->getMessage());

    // 3) Assert that the book.author_id column is now NULL
    $book = BookPeer::retrieveByPK($bookId);
    if (!$book) {
      $this->fail('Failed to retrieve book');
      return;
    }

    $this->assertNull($book->getAuthorId(), "Expect the book.author_id to be NULL after the author was removed.");
  }
  
  /**
  * Test deleting a row by passing in the primary key to the doDelete() method.
  */
  function testDoDelete_ByPK()
  {
    // 1) get an arbitrary book
    $book = BookPeer::doSelectOne(new Criteria());
    if (!$book) {
      $this->fail('Failed to select book');
      return;
    }

    $bookId = $book->getId();

    // 2) now delete that book
    $e = BookPeer::doDelete($bookId);
    if (Propel::isError($e)) $this->fail($e->getMessage());

    // 3) now make sure it's gone
    $book = BookPeer::retrieveByPK($bookId);
    $this->assertNull($book, 'Expect NULL when retrieving on no matching book');
  }
  
  /**
  * Test deleting a row by passing the generated object to doDelete().
  */
  function testDoDelete_ByObj()
  {
    // 1) get an arbitrary book
    $book = BookPeer::doSelectOne(new Criteria());
    if (!$book) {
      $this->fail('Failed to select book');
      return;
    }

    $bookId = $book->getId();

    // 2) now delete that book
    $e = BookPeer::doDelete($book);
    if (Propel::isError($e)) $this->fail($e->getMessage());

    // 3) now make sure it's gone
    $book = BookPeer::retrieveByPK($bookId);
    $this->assertNull($book, 'Expect NULL when retrieving on no matching book');
  }
  
  /**
   * Test the doDeleteAll() method for single table.
  */
  function testDoDeleteAll()
  {
    // print "Attempting to delete all: ";

    $e = BookPeer::doDeleteAll();
    if (Propel::isError($e)) $this->fail($e->getMessage());
    $this->assertEquals(0, count(BookPeer::doSelect(new Criteria())), "Expect all book rows to have been deleted.");
  }

  /**
   * Test the doDeleteAll() method when onDelete="CASCADE".
  */   
  function testDoDeleteAll_Cascade()
  {
    // print "Attempting to cascading delete on all entries: ";

    $e = BookPeer::doDeleteAll();
    if (Propel::isError($e)) $this->fail($e->getMessage());
    $this->assertEquals(0, count(MediaPeer::doSelect(new Criteria())), "Expect all media rows to have been cascade deleted.");
    $this->assertEquals(0, count(ReviewPeer::doSelect(new Criteria())), "Expect all review rows to have been cascade deleted.");
  }

  
  /**
   * Test the doDeleteAll() method when onDelete="SETNULL".
  */   
  function testDoDeleteAll_SetNull()
  {
    // print "Attempting to do delete set null on all entries: ";

    $c = new Criteria();
    $c->add(BookPeer::AUTHOR_ID(), null, Criteria::NOT_EQUAL());
    
    // 1) make sure there are some books with valid authors
    $this->assertTrue(count(BookPeer::doSelect($c)) > 0, "Expect some book.author_id columns that are not NULL.");
    
    // 2) delete all the authors
    $e = AuthorPeer::doDeleteAll();
    if (Propel::isError($e)) $this->fail($e->getMessage());
    
    // 3) now verify that the book.author_id columns are all nul
    $this->assertEquals(0, count(BookPeer::doSelect($c)), "Expect all book.author_id columns to be NULL.");
  }

  
  /**
   * Test the doInsert() method when passed a Criteria object.
  */ 
  function testDoInsert_Criteria()
  {
    // print "Attempting to insert with a criteria: ";

    $name = "A Sample Publisher - " . time();
    
    $values = new Criteria();
    $values->add(PublisherPeer::ID(), 1);
    $values->add(PublisherPeer::NAME(), $name);

    $e = PublisherPeer::doInsert($values);
    if (Propel::isError($e)) $this->fail($e->getMessage());
    
    $c = new Criteria();
    $c->add(PublisherPeer::NAME(), $name);
    
    $matches = PublisherPeer::doSelect($c);
    if (Propel::isError($matches)) $this->fail($matches->getMessage());

    $this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
    $this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");
  }
  
  /**
   * Test the doInsert() method when passed a generated object.
  */ 
  function testDoInsert_Obj()
  {
    // print "Attempting to insert with an object: ";

    $name = "A Sample Publisher - " . time();
    
    $values = new Publisher();
    $values->setName($name);

    $e = PublisherPeer::doInsert($values);
    if (Propel::isError($e)) $this->fail($e->getMessage());
    
    $c = new Criteria();
    $c->add(PublisherPeer::NAME(), $name);
    
    $matches = PublisherPeer::doSelect($c);
    if (Propel::isError($matches)) $this->fail($matches->getMessage());

    $this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
    $this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");
  }
  
  /**
   * Tests performing doSelect() and doSelectJoin() using LIMITs.
   */
  function testDoSelect_Limit()
  {
    // print "Attempting to select with limit: ";

    // 1) get the total number of items in a particular table
    $count = BookPeer::doCount(new Criteria());
    if (Propel::isError($count)) $this->fail($count->getMessage());
    
    $this->assertTrue($count > 1, "Need more than 1 record in books table to perform this test.");
    
    $limitcount = $count - 1;
    
    $lc = new Criteria();
    $lc->setLimit($limitcount);
    
    $results = BookPeer::doSelect($lc);
    if (Propel::isError($results)) $this->fail($results->getMessage());
    
    $this->assertTrue($limitcount, count($results), "Expected $limitcount results from BookPeer::doSelect()");
    
    // re-create it just to avoid side-effects
    $lc2 = new Criteria();
    $lc2->setLimit($limitcount);
    $results2 = BookPeer::doSelectJoinAuthor($lc2);
    if (Propel::isError($results2)) $this->fail($results2->getMessage());
    
    $this->assertTrue($limitcount, count($results2), "Expected $limitcount results from BookPeer::doSelectJoinAuthor()");
  }
}
