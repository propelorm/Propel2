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
 * @see        BookstoreDataPopulator
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class GeneratedPeerTest extends BookstoreTestBase {

	/**
	 * Test ability to delete multiple rows via single Criteria object.
	 */
	public function testDoDelete_MultiTable() {

		$selc = new Criteria();
		$selc->add(BookPeer::TITLE, "Harry Potter and the Order of the Phoenix");
		$hp = BookPeer::doSelectOne($selc);

		// print "Attempting to delete [multi-table] by found pk: ";
		$c = new Criteria();
		$c->add(BookPeer::ID, $hp->getId());
		// The only way for multi-delete to work currently
		// is to specify the author_id and publisher_id (i.e. the fkeys
		// have to be in the criteria).
		$c->add(AuthorPeer::ID, $hp->getAuthorId());
		$c->add(PublisherPeer::ID, $hp->getPublisherId());
		$c->setSingleRecord(true);
		BookPeer::doDelete($c);

		//print_r(AuthorPeer::doSelect(new Criteria()));

		// check to make sure the right # of records was removed
		$this->assertEquals(3, count(AuthorPeer::doSelect(new Criteria())), "Expected 3 authors after deleting.");
		$this->assertEquals(3, count(PublisherPeer::doSelect(new Criteria())), "Expected 3 publishers after deleting.");
		$this->assertEquals(3, count(BookPeer::doSelect(new Criteria())), "Expected 3 books after deleting.");
	}

	/**
	 * Test using a complex criteria to delete multiple rows from a single table.
	 */
	public function testDoDelete_ComplexCriteria() {

		//print "Attempting to delete books by complex criteria: ";
		$c = new Criteria();
		$cn = $c->getNewCriterion(BookPeer::ISBN, "043935806X");
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0380977427"));
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0140422161"));
		$c->add($cn);
		BookPeer::doDelete($c);

		// now there should only be one book left; "The Tin Drum"

		$books = BookPeer::doSelect(new Criteria());

		$this->assertEquals(1, count($books), "Expected 1 book remaining after deleting.");
		$this->assertEquals("The Tin Drum", $books[0]->getTitle(), "Expect the only remaining book to be 'The Tin Drum'");
	}

	/**
	 * Test that cascading deletes are happening correctly (whether emulated or native).
	 */
	public function testDoDelete_Cascade_Simple()
	{

		// The 'media' table will cascade from book deletes

		// 1) Assert the row exists right now

		$medias = MediaPeer::doSelect(new Criteria());
		$this->assertTrue(count($medias) > 0, "Expected to find at least one row in 'media' table.");
		$media = $medias[0];
		$mediaId = $media->getId();

		// 2) Delete the owning book

		$owningBookId = $media->getBookId();
		BookPeer::doDelete($owningBookId);

		// 3) Assert that the media row is now also gone

		$obj = MediaPeer::retrieveByPK($mediaId);
		$this->assertNull($obj, "Expect NULL when retrieving on no matching Media.");

	}

	/**
	 * Test that cascading deletes are happening correctly for composite pk.
	 * @link       http://propel.phpdb.org/trac/ticket/544
	 */
	public function testDoDelete_Cascade_CompositePK()
	{

		$origBceCount = BookstoreContestEntryPeer::doCount(new Criteria());

		$cust1 = new Customer();
		$cust1->setName("Cust1");
		$cust1->save();

		$cust2 = new Customer();
		$cust2->setName("Cust2");
		$cust2->save();

		$c1 = new Contest();
		$c1->setName("Contest1");
		$c1->save();

		$c2 = new Contest();
		$c2->setName("Contest2");
		$c2->save();

		$store1 = new Bookstore();
		$store1->setStoreName("Store1");
		$store1->save();

		$bc1 = new BookstoreContest();
		$bc1->setBookstore($store1);
		$bc1->setContest($c1);
		$bc1->save();

		$bc2 = new BookstoreContest();
		$bc2->setBookstore($store1);
		$bc2->setContest($c2);
		$bc2->save();

		$bce1 = new BookstoreContestEntry();
		$bce1->setEntryDate("now");
		$bce1->setCustomer($cust1);
		$bce1->setBookstoreContest($bc1);
		$bce1->save();

		$bce2 = new BookstoreContestEntry();
		$bce2->setEntryDate("now");
		$bce2->setCustomer($cust1);
		$bce2->setBookstoreContest($bc2);
		$bce2->save();

		// Now, if we remove $bc1, we expect *only* bce1 to be no longer valid.

		BookstoreContestPeer::doDelete($bc1);

		$newCount = BookstoreContestEntryPeer::doCount(new Criteria());

		$this->assertEquals($origBceCount + 1, $newCount, "Expected new number of rows in BCE to be orig + 1");

		$bcetest = BookstoreContestEntryPeer::retrieveByPK($store1->getId(), $c1->getId(), $cust1->getId());
		$this->assertNull($bcetest, "Expected BCE for store1 to be cascade deleted.");

		$bcetest2 = BookstoreContestEntryPeer::retrieveByPK($store1->getId(), $c2->getId(), $cust1->getId());
		$this->assertNotNull($bcetest2, "Expected BCE for store2 to NOT be cascade deleted.");

	}

	/**
	 * Test that onDelete="SETNULL" is happening correctly (whether emulated or native).
	 */
	public function testDoDelete_SetNull() {

		// The 'author_id' column in 'book' table will be set to null when author is deleted.

		// 1) Get an arbitrary book
		$c = new Criteria();
		$book = BookPeer::doSelectOne($c);
		$bookId = $book->getId();
		$authorId = $book->getAuthorId();
		unset($book);

		// 2) Delete the author for that book
		AuthorPeer::doDelete($authorId);

		// 3) Assert that the book.author_id column is now NULL

		$book = BookPeer::retrieveByPK($bookId);
		$this->assertNull($book->getAuthorId(), "Expect the book.author_id to be NULL after the author was removed.");

	}

	/**
	 * Test deleting a row by passing in the primary key to the doDelete() method.
	 */
	public function testDoDelete_ByPK() {

		// 1) get an arbitrary book
		$book = BookPeer::doSelectOne(new Criteria());
		$bookId = $book->getId();

		// 2) now delete that book
		BookPeer::doDelete($bookId);

		// 3) now make sure it's gone
		$obj = BookPeer::retrieveByPK($bookId);
		$this->assertNull($obj, "Expect NULL when retrieving on no matching Book.");

	}

	/**
	 * Test deleting a row by passing the generated object to doDelete().
	 */
	public function testDoDelete_ByObj() {

		// 1) get an arbitrary book
		$book = BookPeer::doSelectOne(new Criteria());
		$bookId = $book->getId();

		// 2) now delete that book
		BookPeer::doDelete($book);

		// 3) now make sure it's gone
		$obj = BookPeer::retrieveByPK($bookId);
		$this->assertNull($obj, "Expect NULL when retrieving on no matching Book.");

	}


	/**
	 * Test the doDeleteAll() method for single table.
	 */
	public function testDoDeleteAll() {

		BookPeer::doDeleteAll();
		$this->assertEquals(0, count(BookPeer::doSelect(new Criteria())), "Expect all book rows to have been deleted.");
	}

	/**
	 * Test the doDeleteAll() method when onDelete="CASCADE".
	 */
	public function testDoDeleteAll_Cascade() {

		BookPeer::doDeleteAll();
		$this->assertEquals(0, count(MediaPeer::doSelect(new Criteria())), "Expect all media rows to have been cascade deleted.");
		$this->assertEquals(0, count(ReviewPeer::doSelect(new Criteria())), "Expect all review rows to have been cascade deleted.");
	}

	/**
	 * Test the doDeleteAll() method when onDelete="SETNULL".
	 */
	public function testDoDeleteAll_SetNull() {

		$c = new Criteria();
		$c->add(BookPeer::AUTHOR_ID, null, Criteria::NOT_EQUAL);

		// 1) make sure there are some books with valid authors
		$this->assertTrue(count(BookPeer::doSelect($c)) > 0, "Expect some book.author_id columns that are not NULL.");

		// 2) delete all the authors
		AuthorPeer::doDeleteAll();

		// 3) now verify that the book.author_id columns are all nul
		$this->assertEquals(0, count(BookPeer::doSelect($c)), "Expect all book.author_id columns to be NULL.");
	}

	/**
	 * Test the doInsert() method when passed a Criteria object.
	 */
	public function testDoInsert_Criteria() {

		$name = "A Sample Publisher - " . time();

		$values = new Criteria();
		$values->add(PublisherPeer::NAME, $name);
		PublisherPeer::doInsert($values);

		$c = new Criteria();
		$c->add(PublisherPeer::NAME, $name);

		$matches = PublisherPeer::doSelect($c);
		$this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
		$this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");

	}

	/**
	 * Test the doInsert() method when passed a generated object.
	 */
	public function testDoInsert_Obj() {

		$name = "A Sample Publisher - " . time();

		$values = new Publisher();
		$values->setName($name);
		PublisherPeer::doInsert($values);

		$c = new Criteria();
		$c->add(PublisherPeer::NAME, $name);

		$matches = PublisherPeer::doSelect($c);
		$this->assertEquals(1, count($matches), "Expect there to be exactly 1 publisher just-inserted.");
		$this->assertTrue( 1 != $matches[0]->getId(), "Expected to have different ID than one put in values Criteria.");

	}

	/**
	 * Tests performing doSelect() and doSelectJoin() using LIMITs.
	 */
	public function testDoSelect_Limit() {

		// 1) get the total number of items in a particular table
		$count = BookPeer::doCount(new Criteria());

		$this->assertTrue($count > 1, "Need more than 1 record in books table to perform this test.");

		$limitcount = $count - 1;

		$lc = new Criteria();
		$lc->setLimit($limitcount);

		$results = BookPeer::doSelect($lc);

		$this->assertEquals($limitcount, count($results), "Expected $limitcount results from BookPeer::doSelect()");

		// re-create it just to avoid side-effects
		$lc2 = new Criteria();
		$lc2->setLimit($limitcount);
		$results2 = BookPeer::doSelectJoinAuthor($lc2);

		$this->assertEquals($limitcount, count($results2), "Expected $limitcount results from BookPeer::doSelectJoinAuthor()");

	}

	/**
	 * Test the basic functionality of the doSelectJoin*() methods.
	 */
	public function testDoSelectJoin()
	{

		BookPeer::clearInstancePool();

		$c = new Criteria();

		$books = BookPeer::doSelect($c);
		$obj = $books[0];
		$size = strlen(serialize($obj));

		BookPeer::clearInstancePool();

		$joinBooks = BookPeer::doSelectJoinAuthor($c);
		$obj2 = $joinBooks[0];
		$joinSize = strlen(serialize($obj2));

		$this->assertEquals(count($books), count($joinBooks), "Expected to find same number of rows in doSelectJoin*() call as doSelect() call.");

		$this->assertTrue($joinSize > $size, "Expected a serialized join object to be larger than a non-join object.");
	}

	/**
	 * Test the doSelectJoin*() methods when the related object is NULL.
	 */
	public function testDoSelectJoin_NullFk()
	{
		$b1 = new Book();
		$b1->setTitle("Test NULLFK 1");
		$b1->setISBN("NULLFK-1");
		$b1->save();

		$b2 = new Book();
		$b2->setTitle("Test NULLFK 2");
		$b2->setISBN("NULLFK-2");
		$b2->setAuthor(new Author());
		$b2->getAuthor()->setFirstName("Hans")->setLastName("L");
		$b2->save();

		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();

		$c = new Criteria();
		$c->add(BookPeer::ISBN, 'NULLFK-%', Criteria::LIKE);
		$c->addAscendingOrderByColumn(BookPeer::ISBN);

		$matches = BookPeer::doSelectJoinAuthor($c);
		$this->assertEquals(2, count($matches), "Expected 2 matches back from new books; got back " . count($matches));

		$this->assertNull($matches[0]->getAuthor(), "Expected first book author to be null");
		$this->assertType('Author', $matches[1]->getAuthor(), "Expected valid Author object for second book.");
	}

	public function testObjectInstances()
	{

		$sample = BookPeer::doSelectOne(new Criteria());
		$samplePk = $sample->getPrimaryKey();

		// 1) make sure consecutive calls to retrieveByPK() return the same object.

		$b1 = BookPeer::retrieveByPK($samplePk);
		$b2 = BookPeer::retrieveByPK($samplePk);

		$sampleval = md5(microtime());

		$this->assertTrue($b1 === $b2, "Expected object instances to match for calls with same retrieveByPK() method signature.");

		// 2) make sure that calls to doSelect also return references to the same objects.
		$allbooks = BookPeer::doSelect(new Criteria());
		foreach ($allbooks as $testb) {
			if ($testb->getPrimaryKey() == $b1->getPrimaryKey()) {
				$this->assertTrue($testb === $b1, "Expected same object instance from doSelect() as from retrieveByPK()");
			}
		}

		// 3) test fetching related objects
		$book = BookPeer::retrieveByPK($samplePk);

		$bookauthor = $book->getAuthor();

		$author = AuthorPeer::retrieveByPK($bookauthor->getId());

		$this->assertTrue($bookauthor === $author, "Expected same object instance when calling fk object accessor as retrieveByPK()");

		// 4) test a doSelectJoin()
		$morebooks = BookPeer::doSelectJoinAuthor(new Criteria());
		for ($i=0,$j=0; $j < count($morebooks); $i++, $j++) {
			$testb1 = $allbooks[$i];
			$testb2 = $allbooks[$j];
			$this->assertTrue($testb1 === $testb2, "Expected the same objects from consecutive doSelect() calls.");
			// we could probably also test this by just verifying that $book & $testb are the same
			if ($testb1->getPrimaryKey() === $book) {
				$this->assertTrue($book->getAuthor() === $testb1->getAuthor(), "Expected same author object in calls to pkey-matching books.");
			}
		}


		// 5) test creating a new object, saving it, and then retrieving that object (should all be same instance)
		$b = new BookstoreEmployee();
		$b->setName("Testing");
		$b->setJobTitle("Testing");
		$b->save();

		$empId = $b->getId();

		$this->assertSame($b, BookstoreEmployeePeer::retrieveByPK($empId), "Expected newly saved object to be same instance as pooled.");

	}

	/**
	 * Test inheritance features.
	 */
	public function testInheritance()
	{
		$manager = new BookstoreManager();
		$manager->setName("Manager 1");
		$manager->setJobTitle("Warehouse Manager");
		$manager->save();
		$managerId = $manager->getId();

		$employee = new BookstoreEmployee();
		$employee->setName("Employee 1");
		$employee->setJobTitle("Janitor");
		$employee->setSupervisorId($managerId);
		$employee->save();
		$empId = $employee->getId();

		$cashier = new BookstoreCashier();
		$cashier->setName("Cashier 1");
		$cashier->setJobTitle("Cashier");
		$cashier->save();
		$cashierId = $cashier->getId();

		// 1) test the pooled instances'
		$c = new Criteria();
		$c->add(BookstoreEmployeePeer::ID, array($managerId, $empId, $cashierId), Criteria::IN);
		$c->addAscendingOrderByColumn(BookstoreEmployeePeer::ID);

		$objects = BookstoreEmployeePeer::doSelect($c);

		$this->assertEquals(3, count($objects), "Expected 3 objects to be returned.");

		list($o1, $o2, $o3) = $objects;

		$this->assertSame($o1, $manager);
		$this->assertSame($o2, $employee);
		$this->assertSame($o3, $cashier);

		// 2) test a forced reload from database
		BookstoreEmployeePeer::clearInstancePool();

		list($o1,$o2,$o3) = BookstoreEmployeePeer::doSelect($c);

		$this->assertTrue($o1 instanceof BookstoreManager, "Expected BookstoreManager object, got " . get_class($o1));
		$this->assertTrue($o2 instanceof BookstoreEmployee, "Expected BookstoreEmployee object, got " . get_class($o2));
		$this->assertTrue($o3 instanceof BookstoreCashier, "Expected BookstoreCashier object, got " . get_class($o3));

	}

	/**
	 * Tests the return type of doCount*() methods.
	 */
	public function testDoCountType()
	{
		$c = new Criteria();
		$this->assertType('integer', BookPeer::doCount($c), "Expected doCount() to return an integer.");
		$this->assertType('integer', BookPeer::doCountJoinAll($c), "Expected doCountJoinAll() to return an integer.");
		$this->assertType('integer', BookPeer::doCountJoinAuthor($c), "Expected doCountJoinAuthor() to return an integer.");
	}

	/**
	 * Tests the doCount() method with limit/offset.
	 */
	public function testDoCountLimitOffset()
	{
		BookPeer::doDeleteAll();

		for ($i=0; $i < 25; $i++) {
			$b = new Book();
			$b->setTitle("Book $i");
			$b->setISBN("ISBN $i");
			$b->save();
		}

		$c = new Criteria();
		$totalCount = BookPeer::doCount($c);

		$this->assertEquals(25, $totalCount);

		$c2 = new Criteria();
		$c2->setLimit(10);
		$this->assertEquals(10, BookPeer::doCount($c2));

		$c3 = new Criteria();
		$c3->setOffset(10);
		$this->assertEquals(15, BookPeer::doCount($c3));

		$c4 = new Criteria();
		$c4->setOffset(5);
		$c4->setLimit(5);
		$this->assertEquals(5, BookPeer::doCount($c4));

		$c5 = new Criteria();
		$c5->setOffset(20);
		$c5->setLimit(10);
		$this->assertEquals(5, BookPeer::doCount($c5));
	}

	/**
	 * Test doCountJoin*() methods.
	 */
	public function testDoCountJoin()
	{
		BookPeer::doDeleteAll();

		for ($i=0; $i < 25; $i++) {
			$b = new Book();
			$b->setTitle("Book $i");
			$b->setISBN("ISBN $i");
			$b->save();
		}

		$c = new Criteria();
		$totalCount = BookPeer::doCount($c);

		$this->assertEquals($totalCount, BookPeer::doCountJoinAuthor($c));
		$this->assertEquals($totalCount, BookPeer::doCountJoinPublisher($c));
	}

	/**
	 * Test passing null values to removeInstanceFromPool().
	 */
	public function testRemoveInstanceFromPool_Null()
	{
		// if it throws an exception, then it's broken.
		try {
			BookPeer::removeInstanceFromPool(null);
		} catch (Exception $x) {
			$this->fail("Expected to get no exception when removing an instance from the pool.");
		}
	}

	/**
	 * @see        testDoDeleteCompositePK()
	 */
	private function createBookWithId($id)
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$b = BookPeer::retrieveByPK($id);
		if (!$b) {
			$b = new Book();
			$b->setTitle("Book$id")->setISBN("BookISBN$id")->save();
			$b1Id = $b->getId();
			$sql = "UPDATE " . BookPeer::TABLE_NAME . " SET id = ? WHERE id = ?";
			$stmt = $con->prepare($sql);
			$stmt->bindValue(1, $id);
			$stmt->bindValue(2, $b1Id);
			$stmt->execute();
		}
	}

	/**
	 * @see        testDoDeleteCompositePK()
	 */
	private function createReaderWithId($id)
	{
		$con = Propel::getConnection(BookReaderPeer::DATABASE_NAME);
		$r = BookReaderPeer::retrieveByPK($id);
		if (!$r) {
			$r = new BookReader();
			$r->setName('Reader'.$id)->save();
			$r1Id = $r->getId();
			$sql = "UPDATE " . BookReaderPeer::TABLE_NAME . " SET id = ? WHERE id = ?";
			$stmt = $con->prepare($sql);
			$stmt->bindValue(1, $id);
			$stmt->bindValue(2, $r1Id);
			$stmt->execute();
		}
	}

	/**
	 * @link       http://propel.phpdb.org/trac/ticket/519
	 */
	public function testDoDeleteCompositePK()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);

		ReaderFavoritePeer::doDeleteAll();
		// Create book and reader with ID 1
		// Create book and reader with ID 2

		$this->createBookWithId(1);
		$this->createBookWithId(2);
		$this->createReaderWithId(1);
		$this->createReaderWithId(2);

		for ($i=1; $i <= 2; $i++) {
			for ($j=1; $j <= 2; $j++) {
				$bo = new BookOpinion();
				$bo->setBookId($i);
				$bo->setReaderId($j);
				$bo->save();
				
				$rf = new ReaderFavorite();
				$rf->setBookId($i);
				$rf->setReaderId($j);
				$rf->save();
			}
		}

		$this->assertEquals(4, ReaderFavoritePeer::doCount(new Criteria()));

		// Now delete 2 of those rows
		ReaderFavoritePeer::doDelete(array(array(1,1), array(2,2)));

		$this->assertEquals(2, ReaderFavoritePeer::doCount(new Criteria()));

		$this->assertNotNull(ReaderFavoritePeer::retrieveByPK(2,1));
		$this->assertNotNull(ReaderFavoritePeer::retrieveByPK(1,2));
		$this->assertNull(ReaderFavoritePeer::retrieveByPK(1,1));
		$this->assertNull(ReaderFavoritePeer::retrieveByPK(2,2));
	}


	/**
	 * Test hydration of joined rows that contain lazy load columns.
	 * @link       http://propel.phpdb.org/trac/ticket/464
	 */
	public function testHydrationJoinLazyLoad()
	{
		BookstoreEmployeeAccountPeer::doDeleteAll();
		BookstoreEmployeePeer::doDeleteAll();
		AcctAccessRolePeer::doDeleteAll();

		$bemp2 = new BookstoreEmployee();
		$bemp2->setName("Pieter");
		$bemp2->setJobTitle("Clerk");
		$bemp2->save();

		$role = new AcctAccessRole();
		$role->setName("Admin");

		$bempacct = new BookstoreEmployeeAccount();
		$bempacct->setBookstoreEmployee($bemp2);
		$bempacct->setAcctAccessRole($role);
		$bempacct->setLogin("john");
		$bempacct->setPassword("johnp4ss");
		$bempacct->save();

		$c = new Criteria();
		$results = BookstoreEmployeeAccountPeer::doSelectJoinAll($c);
		$o = $results[0];

		$this->assertEquals('Admin', $o->getAcctAccessRole()->getName());
	}

	/**
	 * Testing foreign keys with multiple referrer columns.
	 * @link       http://propel.phpdb.org/trac/ticket/606
	 */
	public function testMultiColFk()
	{
		$con = Propel::getConnection(BookPeer::DATABASE_NAME);

		ReaderFavoritePeer::doDeleteAll();
		
		$b1 = new Book();
		$b1->setTitle("Book1");
		$b1->setISBN("ISBN-1");
		$b1->save();
		
		$r1 = new BookReader();
		$r1-> setName("Me");
		$r1->save();
		
		$bo1 = new BookOpinion();
		$bo1->setBookId($b1->getId());
		$bo1->setReaderId($r1->getId());
		$bo1->setRating(9);
		$bo1->setRecommendToFriend(true);
		$bo1->save();
		
		$rf1 = new ReaderFavorite();
		$rf1->setReaderId($r1->getId());
		$rf1->setBookId($b1->getId());
		$rf1->save();
		
		$c = new Criteria(ReaderFavoritePeer::DATABASE_NAME);
		$c->add(ReaderFavoritePeer::BOOK_ID, $b1->getId());
		$c->add(ReaderFavoritePeer::READER_ID, $r1->getId());
		
		// This will produce an error!
		$results = ReaderFavoritePeer::doSelectJoinBookOpinion($c);
		$this->assertEquals(1, count($results), "Expected 1 result");
	}
	/**
	 * Testing foreign keys with multiple referrer columns.
	 * @link       http://propel.phpdb.org/trac/ticket/606
	 */
	public function testMultiColJoin()
	{
		BookstoreContestPeer::doDeleteAll();
		BookstoreContestEntryPeer::doDeleteAll();
		
		$bs = new Bookstore();
		$bs->setStoreName("Test1");
		$bs->setPopulationServed(5);
		$bs->save();
		$bs1Id = $bs->getId();
		
		$bs2 = new Bookstore();
		$bs2->setStoreName("Test2");
		$bs2->setPopulationServed(5);
		$bs2->save();
		$bs2Id = $bs2->getId();
		
		$ct1 = new Contest();
		$ct1->setName("Contest1!");
		$ct1->save();
		$ct1Id = $ct1->getId();
		
		$ct2 = new Contest();
		$ct2->setName("Contest2!");
		$ct2->save();
		$ct2Id = $ct2->getId();
		
		$cmr = new Customer();
		$cmr->setName("Customer1");
		$cmr->save();
		$cmr1Id = $cmr->getId();

		$cmr2 = new Customer();
		$cmr2->setName("Customer2");
		$cmr2->save();
		$cmr2Id = $cmr2->getId();
		
		$contest = new BookstoreContest();
		$contest->setBookstoreId($bs1Id);
		$contest->setContestId($ct1Id);
		$contest->save();
		
		$contest = new BookstoreContest();
		$contest->setBookstoreId($bs2Id);
		$contest->setContestId($ct1Id);
		$contest->save();
	
		$entry = new BookstoreContestEntry();
		$entry->setBookstoreId($bs1Id);
		$entry->setContestId($ct1Id);
		$entry->setCustomerId($cmr1Id);
		$entry->save();
		
		$entry = new BookstoreContestEntry();
		$entry->setBookstoreId($bs1Id);
		$entry->setContestId($ct1Id);
		$entry->setCustomerId($cmr2Id);
		$entry->save();
		
		// Note: this test isn't really working very well.  We setup fkeys that
		// require that the BookstoreContest rows exist and then try to violate
		// the rules ... :-/  This may work in some lenient databases, but an error
		// is expected here. 
		
		/*
		 * Commented out for now ... though without it, this test may not really be testing anything
		$entry = new BookstoreContestEntry();
		$entry->setBookstoreId($bs1Id);
		$entry->setContestId($ct2Id);
		$entry->setCustomerId($cmr2Id);
		$entry->save();
		*/
		
	
		$c = new Criteria();
		$c->addJoin(array(BookstoreContestEntryPeer::BOOKSTORE_ID, BookstoreContestEntryPeer::CONTEST_ID), array(BookstoreContestPeer::BOOKSTORE_ID, BookstoreContestPeer::CONTEST_ID) );

		$results = BookstoreContestEntryPeer::doSelect($c);
		$this->assertEquals(2, count($results) );
		foreach ($results as $result) {
			$this->assertEquals($bs1Id, $result->getBookstoreId() );
			$this->assertEquals($ct1Id, $result->getContestId() );
		}
	}
	
	
	/**
	 * Test doCountJoin*() methods with ORDER BY columns in Criteria.
	 * @link http://propel.phpdb.org/trac/ticket/627
	 */
	public function testDoCountJoinWithOrderBy()
	{
		$c = new Criteria(BookPeer::DATABASE_NAME);
		$c->addAscendingOrderByColumn(BookPeer::ID);
		
		// None of these should not throw an exception!
		BookPeer::doCountJoinAll($c); 
		BookPeer::doCountJoinAllExceptAuthor($c);
		BookPeer::doCountJoinAuthor($c);
	}
}
