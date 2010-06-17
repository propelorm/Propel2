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
 * Tests the generated Object classes.
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
class GeneratedObjectTest extends BookstoreEmptyTestBase
{
	protected function setUp()
	{
		parent::setUp();
		require_once 'tools/helpers/bookstore/behavior/TestAuthor.php';
	}

	/**
	 * Test saving an object after setting default values for it.
	 */
	public function testSaveWithDefaultValues()
	{
		// From the schema.xml, I am relying on the following:
		//  - that 'Penguin' is the default Name for a Publisher
		//  - that 2001-01-01 is the default ReviewDate for a Review

		// 1) check regular values (VARCHAR)
		$pub = new Publisher();
		$pub->setName('Penguin');
		$pub->save();
		$this->assertTrue($pub->getId() !== null, "Expect Publisher to have been saved when default value set.");

		// 2) check date/time values
		$review = new Review();
		// note that this is different from how it's represented in schema, but should resolve to same unix timestamp
		$review->setReviewDate('2001-01-01');
		$this->assertTrue($review->isModified(), "Expect Review to have been marked 'modified' after default date/time value set.");

	}
	
	/**
	 * Test isModified() to be false after setting default value second time
	 */
	public function testDefaultValueSetTwice()
	{
    $pub = new Publisher();
    $pub->setName('Penguin');
    $pub->save();
  
    $pubId = $pub->getId();
    
    PublisherPeer::clearInstancePool();
    
    $pub2 = PublisherPeer::retrieveByPK($pubId);
    $pub2->setName('Penguin');
    $this->assertFalse($pub2->isModified(), "Expect Publisher to be not modified after setting default value second time.");
	}

  public function testHasApplyDefaultValues()
  {
    $this->assertTrue(method_exists('Publisher', 'applyDefaultValues'), 'Tables with default values should have an applyDefaultValues() method');
    $this->assertFalse(method_exists('Book', 'applyDefaultValues'), 'Tables with no default values should not have an applyDefaultValues() method');
  }

	/**
	 * Test default return values.
	 */
	public function testDefaultValues()
	{
		$r = new Review();
		$this->assertEquals('2001-01-01', $r->getReviewDate('Y-m-d'));

		$this->assertFalse($r->isModified(), "expected isModified() to be false");

		$acct = new BookstoreEmployeeAccount();
		$this->assertEquals(true, $acct->getEnabled());
		$this->assertFalse($acct->isModified());

		$acct->setLogin("testuser");
		$acct->setPassword("testpass");
		$this->assertTrue($acct->isModified());
	}

	/**
	 * Tests the use of default expressions and the reloadOnInsert and reloadOnUpdate attributes.
	 *
	 * @link       http://propel.phpdb.org/trac/ticket/378
	 * @link       http://propel.phpdb.org/trac/ticket/555
	 */
	public function testDefaultExpresions()
	{
		if (Propel::getDb(BookstoreEmployeePeer::DATABASE_NAME) instanceof DBSqlite) {
			$this->markTestSkipped("Cannot test default expressions with SQLite");
		}

		$b = new Bookstore();
		$b->setStoreName("Foo!");
		$b->save();

		$employee = new BookstoreEmployee();
		$employee->setName("Johnny Walker");

		$acct = new BookstoreEmployeeAccount();
		$acct->setBookstoreEmployee($employee);
		$acct->setLogin("test-login");

		$this->assertNull($acct->getCreated(), "Expected created column to be NULL.");
		$this->assertNull($acct->getAuthenticator(), "Expected authenticator column to be NULL.");

		$acct->save();

		$acct = BookstoreEmployeeAccountPeer::retrieveByPK($acct->getEmployeeId());

		$this->assertNotNull($acct->getAuthenticator(), "Expected a valid (non-NULL) authenticator column after save.");
		$this->assertEquals('Password', $acct->getAuthenticator(), "Expected authenticator='Password' after save.");
		$this->assertNotNull($acct->getCreated(), "Expected a valid date after retrieving saved object.");

		$now = new DateTime("now");
		$this->assertEquals($now->format("Y-m-d"), $acct->getCreated("Y-m-d"));

		$acct->setCreated($now);
		$this->assertEquals($now->format("Y-m-d"), $acct->getCreated("Y-m-d"));

		// Unfortunately we can't really test the conjunction of reloadOnInsert and reloadOnUpdate when using just
		// default values. (At least not in a cross-db way.)
	}

	/**
	 * Tests the use of default expressions and the reloadOnInsert attribute.
	 *
	 * @link       http://propel.phpdb.org/trac/ticket/378
	 * @link       http://propel.phpdb.org/trac/ticket/555
	 */
	public function testDefaultExpresions_ReloadOnInsert()
	{
		if (Propel::getDb(BookstoreEmployeePeer::DATABASE_NAME) instanceof DBSqlite) {
			$this->markTestSkipped("Cannot test default date expressions with SQLite");
		}

		// Create a new bookstore, contest, bookstore_contest, and bookstore_contest_entry

		$b = new Bookstore();
		$b->setStoreName("Barnes & Noble");
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

		$this->assertNotNull($bce->getEntryDate(), "Expected a non-null entry_date after save.");
	}

	/**
	 * Tests the overriding reloadOnInsert at runtime.
	 *
	 * @link       http://propel.phpdb.org/trac/ticket/378
	 * @link       http://propel.phpdb.org/trac/ticket/555
	 */
	public function testDefaultExpresions_ReloadOnInsert_Override()
	{
		if (Propel::getDb(BookstoreEmployeePeer::DATABASE_NAME) instanceof DBSqlite) {
			$this->markTestSkipped("Cannot test default date expressions with SQLite");
		}

		// Create a new bookstore, contest, bookstore_contest, and bookstore_contest_entry
		$b = new Bookstore();
		$b->setStoreName("Barnes & Noble");
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
		$bce->save(null, $skipReload=true);

		$this->assertNull($bce->getEntryDate(), "Expected a NULL entry_date after save.");
	}

	/**
	 * Tests the use of default expressions and the reloadOnUpdate attribute.
	 *
	 * @link       http://propel.phpdb.org/trac/ticket/555
	 */
	public function testDefaultExpresions_ReloadOnUpdate()
	{
		$b = new Bookstore();
		$b->setStoreName("Foo!");
		$b->save();

		$sale = new BookstoreSale();
		$sale->setBookstore(BookstorePeer::doSelectOne(new Criteria()));
		$sale->setSaleName("Spring Sale");
		$sale->save();

		// Expect that default values are set, but not default expressions
		$this->assertNull($sale->getDiscount(), "Expected discount to be NULL.");

		$sale->setSaleName("Winter Clearance");
		$sale->save();
		// Since reloadOnUpdate = true, we expect the discount to be set now.

		$this->assertNotNull($sale->getDiscount(), "Expected discount to be non-NULL after save.");
	}

	/**
	 * Tests the overriding reloadOnUpdate at runtime.
	 *
	 * @link       http://propel.phpdb.org/trac/ticket/378
	 * @link       http://propel.phpdb.org/trac/ticket/555
	 */
	public function testDefaultExpresions_ReloadOnUpdate_Override()
	{
		$b = new Bookstore();
		$b->setStoreName("Foo!");
		$b->save();

		$sale = new BookstoreSale();
		$sale->setBookstore(BookstorePeer::doSelectOne(new Criteria()));
		$sale->setSaleName("Spring Sale");
		$sale->save();

		// Expect that default values are set, but not default expressions
		$this->assertNull($sale->getDiscount(), "Expected discount to be NULL.");

		$sale->setSaleName("Winter Clearance");
		$sale->save(null, $skipReload=true);

		// Since reloadOnUpdate = true, we expect the discount to be set now.

		$this->assertNull($sale->getDiscount(), "Expected NULL value for discount after save.");
	}

	/**
	 * Test the behavior of date/time/values.
	 * This requires that the model was built with propel.useDateTimeClass=true.
	 */
	public function testTemporalValues_PreEpoch()
	{
		$r = new Review();

		$preEpochDate = new DateTime('1602-02-02');

		$r->setReviewDate($preEpochDate);

		$this->assertEquals('1602-02-02', $r->getReviewDate(null)->format("Y-m-d"));

		$r->setReviewDate('1702-02-02');

		$this->assertTrue($r->isModified());

		$this->assertEquals('1702-02-02', $r->getReviewDate(null)->format("Y-m-d"));

		// Now test for setting null
		$r->setReviewDate(null);
		$this->assertNull($r->getReviewDate());

	}

	/**
	 * Test setting invalid date/time.
	 */
	public function testSetTemporalValue_Invalid()
	{
		$this->markTestSkipped();
		// FIXME - Figure out why this doesn't work (causes a PHP ERROR instead of throwing Exception) in
		// the Phing+PHPUnit context
		$r = new Review();
		try {
			$r->setReviewDate("Invalid Date");
			$this->fail("Expected PropelException when setting date column w/ invalid date");
		} catch (PropelException $x) {
			print "Caught expected PropelException: " . $x->__toString();
		}
	}

	/**
	 * Test setting TIMESTAMP columns w/ unix int timestamp.
	 */
	public function testTemporalValues_Unix()
	{
		$store = new Bookstore();
		$store->setStoreName("test");
		$store->setStoreOpenTime(strtotime('12:55'));
		$store->save();
		$this->assertEquals('12:55', $store->getStoreOpenTime(null)->format('H:i'));

		$acct = new BookstoreEmployeeAccount();
		$acct->setCreated(time());
		$this->assertEquals(date('Y-m-d H:i'), $acct->getCreated('Y-m-d H:i'));

		$review = new Review();
		$review->setReviewDate(time());
		$this->assertEquals(date('Y-m-d'), $review->getReviewDate('Y-m-d'));
	}

	/**
	 * Test setting empty temporal values.
	 * @link       http://propel.phpdb.org/trac/ticket/586
	 */
	public function testTemporalValues_Empty()
	{
		$review = new Review();
		$review->setReviewDate('');
		$this->assertNull($review->getReviewDate());
	}

	/**
	 * Test setting TIME columns.
	 */
	public function testTemporalValues_TimeSetting()
	{
		$store = new Bookstore();
		$store->setStoreName("test");
		$store->setStoreOpenTime("12:55");
		$store->save();

		$store = new Bookstore();
		$store->setStoreName("test2");
		$store->setStoreOpenTime(new DateTime("12:55"));
		$store->save();
	}

	/**
	 * Test setting TIME columns.
	 */
	public function testTemporalValues_DateSetting()
	{
		BookstoreDataPopulator::populate();

		$r = new Review();
		$r->setBook(BookPeer::doSelectOne(new Criteria()));
		$r->setReviewDate(new DateTime('1999-12-20'));
		$r->setReviewedBy("Hans");
		$r->setRecommended(false);
		$r->save();
	}

	/**
	 * Testing creating & saving new object & instance pool.
	 */
	public function testObjectInstances_New()
	{
		$emp = new BookstoreEmployee();
		$emp->setName(md5(microtime()));
		$emp->save();
		$id = $emp->getId();

		$retrieved = BookstoreEmployeePeer::retrieveByPK($id);
		$this->assertSame($emp, $retrieved, "Expected same object (from instance pool)");
	}

	/**
	 *
	 */
	public function testObjectInstances_Fkeys()
	{
		// Establish a relationship between one employee and account
		// and then change the employee_id and ensure that the account
		// is not pulling the old employee.

		$pub1 = new Publisher();
		$pub1->setName('Publisher 1');
		$pub1->save();

		$pub2 = new Publisher();
		$pub2->setName('Publisher 2');
		$pub2->save();

		$book = new Book();
		$book->setTitle("Book Title");
		$book->setISBN("1234");
		$book->setPublisher($pub1);
		$book->save();

		$this->assertSame($pub1, $book->getPublisher());

		// now change values behind the scenes
		$con = Propel::getConnection(BookstoreEmployeeAccountPeer::DATABASE_NAME);
		$con->exec("UPDATE " . BookPeer::TABLE_NAME . " SET "
		. " publisher_id = " . $pub2->getId()
		. " WHERE id = " . $book->getId());


		$book2 = BookPeer::retrieveByPK($book->getId());
		$this->assertSame($book, $book2, "Expected same book object instance");

		$this->assertEquals($pub1->getId(), $book->getPublisherId(), "Expected book to have OLD publisher id before reload()");

		$book->reload();

		$this->assertEquals($pub2->getId(), $book->getPublisherId(), "Expected book to have new publisher id");
		$this->assertSame($pub2, $book->getPublisher(), "Expected book to have new publisher object associated.");

		// Now let's set it back, just to be double sure ...

		$con->exec("UPDATE " . BookPeer::TABLE_NAME . " SET "
		. " publisher_id = " . $pub1->getId()
		. " WHERE id = " . $book->getId());

		$book->reload();

		$this->assertEquals($pub1->getId(), $book->getPublisherId(), "Expected book to have old publisher id (again).");
		$this->assertSame($pub1, $book->getPublisher(), "Expected book to have old publisher object associated (again).");

	}

	/**
	 * Test the effect of typecast on primary key values and instance pool retrieval.
	 */
	public function testObjectInstancePoolTypecasting()
	{
		$reader = new BookReader();
		$reader->setName("Tester");
		$reader->save();
		$readerId = $reader->getId();

		$book = new Book();
		$book->setTitle("BookTest");
		$book->setISBN("TEST");
		$book->save();
		$bookId = $book->getId();

		$opinion = new BookOpinion();
		$opinion->setBookId((string)$bookId);
		$opinion->setReaderId((string)$readerId);
		$opinion->setRating(5);
		$opinion->setRecommendToFriend(false);
		$opinion->save();


		$opinion2 = BookOpinionPeer::retrieveByPK($bookId, $readerId);

		$this->assertSame($opinion, $opinion2, "Expected same object to be retrieved from differently type-casted primary key values.");

	}

	/**
	 * Test the reload() method.
	 */
	public function testReload()
	{
		BookstoreDataPopulator::populate();
		$a = AuthorPeer::doSelectOne(new Criteria());

		$origName = $a->getFirstName();

		$a->setFirstName(md5(time()));

		$this->assertNotEquals($origName, $a->getFirstName());
		$this->assertTrue($a->isModified());

		$a->reload();

		$this->assertEquals($origName, $a->getFirstName());
		$this->assertFalse($a->isModified());

	}

	/**
	 * Test reload(deep=true) method.
	 */
	public function testReloadDeep()
	{
		BookstoreDataPopulator::populate();
		
		// arbitrary book
		$b = BookPeer::doSelectOne(new Criteria());

		// arbitrary, different author
		$c = new Criteria();
		$c->add(AuthorPeer::ID, $b->getAuthorId(), Criteria::NOT_EQUAL);
		$a = AuthorPeer::doSelectOne($c);

		$origAuthor = $b->getAuthor();

		$b->setAuthor($a);

		$this->assertNotEquals($origAuthor, $b->getAuthor(), "Expected just-set object to be different from obj from DB");
		$this->assertTrue($b->isModified());

		$b->reload($deep=true);

		$this->assertEquals($origAuthor, $b->getAuthor(), "Expected object in DB to be restored");
		$this->assertFalse($a->isModified());
	}

	/**
	 * Test saving an object and getting correct number of affected rows from save().
	 * This includes tests of cascading saves to fk-related objects.
	 */
	public function testSaveReturnValues()
	{

		$author = new Author();
		$author->setFirstName("Mark");
		$author->setLastName("Kurlansky");
		// do not save

		$pub = new Publisher();
		$pub->setName("Penguin Books");
		// do not save

		$book = new Book();
		$book->setTitle("Salt: A World History");
		$book->setISBN("0142001619");
		$book->setAuthor($author);
		$book->setPublisher($pub);

		$affected = $book->save();
		$this->assertEquals(3, $affected, "Expected 3 affected rows when saving book + publisher + author.");

		// change nothing ...
		$affected = $book->save();
		$this->assertEquals(0, $affected, "Expected 0 affected rows when saving already-saved book.");

		// modify the book (UPDATE)
		$book->setTitle("Salt A World History");
		$affected = $book->save();
		$this->assertEquals(1, $affected, "Expected 1 affected row when saving modified book.");

		// modify the related author
		$author->setLastName("Kurlanski");
		$affected = $book->save();
		$this->assertEquals(1, $affected, "Expected 1 affected row when saving book with updated author.");

		// modify both the related author and the book
		$author->setLastName("Kurlansky");
		$book->setTitle("Salt: A World History");
		$affected = $book->save();
		$this->assertEquals(2, $affected, "Expected 2 affected rows when saving updated book with updated author.");

	}

	/**
	 * Test deleting an object using the delete() method.
	 */
	public function testDelete()
	{
		BookstoreDataPopulator::populate();

		// 1) grab an arbitrary object
		$book = BookPeer::doSelectOne(new Criteria());
		$bookId = $book->getId();

		// 2) delete it
		$book->delete();

		// 3) make sure it can't be save()d now that it's deleted
		try {
			$book->setTitle("Will Fail");
			$book->save();
			$this->fail("Expect an exception to be thrown when attempting to save() a deleted object.");
		} catch (PropelException $e) {}

		// 4) make sure that it doesn't exist in db
		$book = BookPeer::retrieveByPK($bookId);
		$this->assertNull($book, "Expect NULL from retrieveByPK on deleted Book.");

	}

	/**
	 *
	 */
	public function testNoColsModified()
	{
		$e1 = new BookstoreEmployee();
		$e1->setName('Employee 1');

		$e2 = new BookstoreEmployee();
		$e2->setName('Employee 2');

		$super = new BookstoreEmployee();
		// we don't know who the supervisor is yet
		$super->addSubordinate($e1);
		$super->addSubordinate($e2);

		$affected = $super->save();

	}

	/**
	 * Tests new one-to-one functionality.
	 */
	public function testOneToOne()
	{
		BookstoreDataPopulator::populate();

		$emp = BookstoreEmployeePeer::doSelectOne(new Criteria());

		$acct = new BookstoreEmployeeAccount();
		$acct->setBookstoreEmployee($emp);
		$acct->setLogin("testuser");
		$acct->setPassword("testpass");

		$this->assertSame($emp->getBookstoreEmployeeAccount(), $acct, "Expected same object instance.");
	}

	/**
	 * Test the type sensitivity of the resturning columns.
	 *
	 */
	public function testTypeSensitive()
	{
		BookstoreDataPopulator::populate();

		$book = BookPeer::doSelectOne(new Criteria());

		$r = new Review();
		$r->setReviewedBy("testTypeSensitive Tester");
		$r->setReviewDate(time());
		$r->setBook($book);
		$r->setRecommended(true);
		$r->save();

		$id = $r->getId();
		unset($r);

		// clear the instance cache to force reload from database.
		ReviewPeer::clearInstancePool();
		BookPeer::clearInstancePool();

		// reload and verify that the types are the same
		$r2 = ReviewPeer::retrieveByPK($id);

		$this->assertType('integer', $r2->getId(), "Expected getId() to return an integer.");
		$this->assertType('string', $r2->getReviewedBy(), "Expected getReviewedBy() to return a string.");
		$this->assertType('boolean', $r2->getRecommended(), "Expected getRecommended() to return a boolean.");
		$this->assertType('Book', $r2->getBook(), "Expected getBook() to return a Book.");
		$this->assertType('float', $r2->getBook()->getPrice(), "Expected Book->getPrice() to return a float.");
		$this->assertType('DateTime', $r2->getReviewDate(null), "Expected Book->getReviewDate() to return a DateTime.");

	}

	/**
	 * This is a test for expected exceptions when saving UNIQUE.
	 * See http://propel.phpdb.org/trac/ticket/2
	 */
	public function testSaveUnique()
	{
	  // The whole test is in a transaction, but this test needs real transactions
	  $this->con->commit();
	  
		$emp = new BookstoreEmployee();
		$emp->setName(md5(microtime()));

		$acct = new BookstoreEmployeeAccount();
		$acct->setBookstoreEmployee($emp);
		$acct->setLogin("foo");
		$acct->setPassword("bar");
		$acct->save();

		// now attempt to create a new acct
		$acct2 = $acct->copy();

		try {
			$acct2->save();
			$this->fail("Expected PropelException in first attempt to save object with duplicate value for UNIQUE constraint.");
		} catch (Exception $x) {
			try {
				// attempt to save it again
				$acct3 = $acct->copy();
				$acct3->save();
				$this->fail("Expected PropelException in second attempt to save object with duplicate value for UNIQUE constraint.");
			} catch (Exception $x) {
				// this is expected.
			}
			// now let's double check that it can succeed if we're not violating the constraint.
			$acct3->setLogin("foo2");
			$acct3->save();
		}
		
		$this->con->beginTransaction();
	}

	/**
	 * Test for correct reporting of isModified().
	 */
	public function testIsModified()
	{
		// 1) Basic test

		$a = new Author();
		$a->setFirstName("John");
		$a->setLastName("Doe");
		$a->setAge(25);

		$this->assertTrue($a->isModified(), "Expected Author to be modified after setting values.");

		$a->save();

		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after saving set values.");

		// 2) Test behavior with setting vars of different types

		// checking setting int col to string val
		$a->setAge('25');
		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after setting int column to string-cast of same value.");

		$a->setFirstName("John2");
		$this->assertTrue($a->isModified(), "Expected Author to be modified after changing string column value.");

		// checking setting string col to int val
		$a->setFirstName("1");
		$a->save();
		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after saving set values.");

		$a->setFirstName(1);
		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after setting string column to int-cast of same value.");

		// 3) Test for appropriate behavior of NULL

		// checking "" -> NULL
		$a->setFirstName("");
		$a->save();
		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after saving set values.");

		$a->setFirstName(null);
		$this->assertTrue($a->isModified(), "Expected Author to be modified after changing empty string column value to NULL.");

		$a->setFirstName("John");
		$a->setAge(0);
		$a->save();
		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after saving set values.");

		$a->setAge(null);
		$this->assertTrue($a->isModified(), "Expected Author to be modified after changing 0-value int column to NULL.");

		$a->save();
		$this->assertFalse($a->isModified(), "Expected Author to be unmodified after saving set values.");

		$a->setAge(0);
		$this->assertTrue($a->isModified(), "Expected Author to be modified after changing NULL-value int column to 0.");

	}

	/**
	 * Test the BaseObject#equals().
	 */
	public function testEquals()
	{
		BookstoreDataPopulator::populate();

		$b = BookPeer::doSelectOne(new Criteria());
		$c = new Book();
		$c->setId($b->getId());
		$this->assertTrue($b->equals($c), "Expected Book objects to be equal()");

		$a = new Author();
		$a->setId($b->getId());
		$this->assertFalse($b->equals($a), "Expected Book and Author with same primary key NOT to match.");
	}

	/**
	 * Test checking for non-default values.
	 * @see        http://propel.phpdb.org/trac/ticket/331
	 */
	public function testHasOnlyDefaultValues()
	{
		$emp = new BookstoreEmployee();
		$emp->setName(md5(microtime()));

		$acct2 = new BookstoreEmployeeAccount();

		$acct = new BookstoreEmployeeAccount();
		$acct->setBookstoreEmployee($emp);
		$acct->setLogin("foo");
		$acct->setPassword("bar");
		$acct->save();

		$this->assertFalse($acct->isModified(), "Expected BookstoreEmployeeAccount NOT to be modified after save().");

		$acct->setEnabled(true);
		$acct->setPassword($acct2->getPassword());

		$this->assertTrue($acct->isModified(), "Expected BookstoreEmployeeAccount to be modified after setting default values.");

		$this->assertTrue($acct->hasOnlyDefaultValues(), "Expected BookstoreEmployeeAccount to not have only default values.");

		$acct->setPassword("bar");
		$this->assertFalse($acct->hasOnlyDefaultValues(), "Expected BookstoreEmployeeAccount to have at one non-default value after setting one value to non-default.");

		// Test a default date/time value
		$r = new Review();
		$r->setReviewDate(new DateTime("now"));
		$this->assertFalse($r->hasOnlyDefaultValues());
	}

	public function testDefaultFkColVal()
	{
		BookstoreDataPopulator::populate();

		$sale = new BookstoreSale();
		$this->assertEquals(1, $sale->getBookstoreId(), "Expected BookstoreSale object to have a default bookstore_id of 1.");

		$bookstore = BookstorePeer::doSelectOne(new Criteria());

		$sale->setBookstore($bookstore);
		$this->assertEquals($bookstore->getId(), $sale->getBookstoreId(), "Expected FK id to have changed when assigned a valid FK.");

		$sale->setBookstore(null);
		$this->assertEquals(1, $sale->getBookstoreId(), "Expected BookstoreSale object to have reset to default ID.");

		$sale->setPublisher(null);
		$this->assertEquals(null, $sale->getPublisherId(), "Expected BookstoreSale object to have reset to NULL publisher ID.");
	}

	public function testCountRefFk()
	{
		$book = new Book();
		$book->setTitle("Test Book");
		$book->setISBN("TT-EE-SS-TT");

		$num = 5;

		for ($i=2; $i < $num + 2; $i++) {
			$r = new Review();
			$r->setReviewedBy('Hans ' . $num);
			$dt = new DateTime("now");
			$dt->modify("-".$i." weeks");
			$r->setReviewDate($dt);
			$r->setRecommended(($i % 2) == 0);
			$book->addReview($r);
		}

		$this->assertEquals($num, $book->countReviews(), "Expected countReviews to return $num");
		$this->assertEquals($num, count($book->getReviews()), "Expected getReviews to return $num reviews");

		$book->save();

		BookPeer::clearInstancePool();
		ReviewPeer::clearInstancePool();

		$book = BookPeer::retrieveByPK($book->getId());
		$this->assertEquals($num, $book->countReviews(), "Expected countReviews() to return $num (after save)");
		$this->assertEquals($num, count($book->getReviews()), "Expected getReviews() to return $num (after save)");

		// Now set different criteria and expect different results
		$c = new Criteria();
		$c->add(ReviewPeer::RECOMMENDED, false);
		$this->assertEquals(floor($num/2), $book->countReviews($c), "Expected " . floor($num/2) . " results from countReviews(recomm=false)");

		// Change Criteria, run again -- expect different.
		$c = new Criteria();
		$c->add(ReviewPeer::RECOMMENDED, true);
		$this->assertEquals(ceil($num/2), count($book->getReviews($c)), "Expected " . ceil($num/2) . " results from getReviews(recomm=true)");

		$this->assertEquals($num, $book->countReviews(), "Expected countReviews to return $num with new empty Criteria");
	}

	/**
	 * Test copyInto method.
	 */
	public function testCopyInto_Deep()
	{
		BookstoreDataPopulator::populate();

		// Test a "normal" object
		$c = new Criteria();
		$c->add(BookPeer::TITLE, 'Harry%', Criteria::LIKE);

		$book = BookPeer::doSelectOne($c);
		$reviews = $book->getReviews();

		$b2 = $book->copy(true);
		$this->assertType('Book', $b2);
		$this->assertNull($b2->getId());

		$r2 = $b2->getReviews();

		$this->assertEquals(count($reviews), count($r2));

		// Test a one-to-one object
		$emp = BookstoreEmployeePeer::doSelectOne(new Criteria());
		$e2 = $emp->copy(true);

		$this->assertType('BookstoreEmployee', $e2);
		$this->assertNull($e2->getId());

		$this->assertEquals($emp->getBookstoreEmployeeAccount()->getLogin(), $e2->getBookstoreEmployeeAccount()->getLogin());
	}
	
	/**
	 * Test copying when an object has composite primary key.
	 * @link http://propel.phpdb.org/trac/ticket/618
	 */
	public function testCopy_CompositePK()
	{
		$br = new BookReader();
		$br->setName("TestReader");
		$br->save();
		$br->copy();
		
		$b = new Book();
		$b->setTitle("TestBook");
		$b->setISBN("XX-XX-XX-XX");
		$b->save();
		
		$op = new BookOpinion();
		$op->setBookReader($br);
		$op->setBook($b);
		$op->setRating(10);
		$op->setRecommendToFriend(true);
		$op->save();
		
		
		$br2 = $br->copy(true);
		
		$this->assertNull($br2->getId());
		
		$opinions = $br2->getBookOpinions();
		$this->assertEquals(1, count($opinions), "Expected to have a related BookOpinion after copy()");
		
		// We DO expect the reader_id to be null
		$this->assertNull($opinions[0]->getReaderId());
		// but we DO NOT expect the book_id to be null 
		$this->assertEquals($op->getBookId(), $opinions[0]->getBookId());
	}

	public function testToArray()
	{
		$b = new Book();
		$b->setTitle('Don Juan');

		$arr1 = $b->toArray();
		$expectedKeys = array(
			'Id',
			'Title',
			'ISBN',
			'Price',
			'PublisherId',
			'AuthorId'
		);
		$this->assertEquals($expectedKeys, array_keys($arr1), 'toArray() returns an associative array with BasePeer::TYPE_PHPNAME keys by default');
		$this->assertEquals('Don Juan', $arr1['Title'], 'toArray() returns an associative array representation of the object');
	}

	public function testToArrayKeyType()
	{
		$b = new Book();
		$b->setTitle('Don Juan');
	
		$arr1 = $b->toArray(BasePeer::TYPE_COLNAME);
		$expectedKeys = array(
			BookPeer::ID,
			BookPeer::TITLE,
			BookPeer::ISBN,
			BookPeer::PRICE,
			BookPeer::PUBLISHER_ID,
			BookPeer::AUTHOR_ID
		);
		$this->assertEquals($expectedKeys, array_keys($arr1), 'toArray() accepts a $keyType parameter to change the result keys');
		$this->assertEquals('Don Juan', $arr1[BookPeer::TITLE], 'toArray() returns an associative array representation of the object');
	}	

	/**
	 * Test the toArray() method with new lazyLoad param.
	 * @link       http://propel.phpdb.org/trac/ticket/527
	 */
	public function testToArrayLazyLoad()
	{
		BookstoreDataPopulator::populate();

		$c = new Criteria();
		$c->add(MediaPeer::COVER_IMAGE, null, Criteria::NOT_EQUAL);
		$c->add(MediaPeer::EXCERPT, null, Criteria::NOT_EQUAL);

		$m = MediaPeer::doSelectOne($c);
		if ($m === null) {
			$this->fail("Test requires at least one media row w/ cover_image and excerpt NOT NULL");
		}

		$arr1 = $m->toArray(BasePeer::TYPE_COLNAME);
		$this->assertNotNull($arr1[MediaPeer::COVER_IMAGE]);
		$this->assertType('resource', $arr1[MediaPeer::COVER_IMAGE]);

		$arr2 = $m->toArray(BasePeer::TYPE_COLNAME, false);
		$this->assertNull($arr2[MediaPeer::COVER_IMAGE]);
		$this->assertNull($arr2[MediaPeer::EXCERPT]);

		$diffKeys = array_keys(array_diff($arr1, $arr2));

		$expectedDiff = array(MediaPeer::COVER_IMAGE, MediaPeer::EXCERPT);

		$this->assertEquals($expectedDiff, $diffKeys);
	}
	
	public function testToArrayIncludeForeignObjects()
	{
		BookstoreDataPopulator::populate();
		BookPeer::clearInstancePool();
		AuthorPeer::clearInstancePool();
		PublisherPeer::clearInstancePool();
		
		$c = new Criteria();
		$c->add(BookPeer::TITLE, 'Don Juan');
		$books = BookPeer::doSelectJoinAuthor($c);
		$book = $books[0];
		
		$arr1 = $book->toArray(BasePeer::TYPE_PHPNAME, null, true);
		$expectedKeys = array(
			'Id',
			'Title',
			'ISBN',
			'Price',
			'PublisherId',
			'AuthorId',
			'Author'
		);
		$this->assertEquals($expectedKeys, array_keys($arr1), 'toArray() can return sub arrays for hydrated related objects');
		$this->assertEquals('George', $arr1['Author']['FirstName'], 'toArray() can return sub arrays for hydrated related objects');

		$c = new Criteria();
		$c->add(BookPeer::TITLE, 'Don Juan');
		$books = BookPeer::doSelectJoinAll($c);
		$book = $books[0];

		$arr2 = $book->toArray(BasePeer::TYPE_PHPNAME, null, true);
		$expectedKeys = array(
			'Id',
			'Title',
			'ISBN',
			'Price',
			'PublisherId',
			'AuthorId',
			'Publisher',
			'Author'
		);
		$this->assertEquals($expectedKeys, array_keys($arr2), 'toArray() can return sub arrays for hydrated related objects');
	}

	/**
	 * Test regexp validator for ticket:542
	 * @link       http://propel.phpdb.org/trac/ticket/542
	 */
	public function testRegexValidator()
	{
		$b = new Bookstore();
		$b->setWebsite("http://this.is.valid.com/foo.bar");
		$res = $b->validate();
		$this->assertTrue($res, "Expected URL to validate");
	}

	/**
	 * Test that setting the auto-increment primary key will result in exception.
	 */
	public function testSettingAutoIncrementPK()
	{
	  // The whole test is in a transaction, but this test needs real transactions
	  $this->con->commit();

		$b = new Bookstore();
		$b->setId(1);
		$b->setStoreName("Test");
		try {
			$b->save();
			$this->fail("Expected setting auto-increment primary key to result in Exception");
		} catch (Exception $x) {
			$this->assertType('PropelException', $x);
		}

		// ... but we should silently ignore NULL values, since these are really
		// the same as "not set" in PHP world.
		$b = new Bookstore();
		$b->setId(null);
		$b->setStoreName("Test2");
		try {
			$b->save();
		} catch (Exception $x) {
			$this->fail("Expected no exception when setting auto-increment primary key to NULL");
		}
		// success ...
		
		$this->con->beginTransaction();
	}
	
	/**
	 * Checks wether we are allowed to specify the primary key on a 
	 * table with allowPkInsert=true set
	 *
	 * saves the object, gets it from data-source again and then compares
	 * them for equality (thus the instance pool is also checked) 
	 */
	public function testAllowPkInsertOnIdMethodNativeTable()
	{
		$cu = new Customer;
		$cu->setPrimaryKey(100000);
		$cu->save();
		
		$this->assertEquals(100000, $cu->getPrimaryKey());
		
		$cu2 = CustomerPeer::retrieveByPk(100000);
				
		$this->assertSame($cu, $cu2);
	}
	/**
	 * Checks if it is allowed to save new, empty objects with a auto increment column
	 */
	public function testAllowEmptyWithAutoIncrement()
	{
		$bookreader = new BookReader();
		$bookreader->save();
				
		$this->assertFalse($bookreader->isNew() );
	}
	
	/**
	 * Test foreign key relationships based on references to unique cols but not PK.
	 * @link       http://propel.phpdb.org/trac/ticket/691
	 */
	public function testUniqueFkRel()
	{
		$employee = new BookstoreEmployee();
		$employee->setName("Johnny Walker");

		$acct = new BookstoreEmployeeAccount();
		$acct->setBookstoreEmployee($employee);
		$acct->setLogin("test-login");
		$acct->save();
		$acctId = $acct->getEmployeeId();
		
		$al = new AcctAuditLog();
		$al->setBookstoreEmployeeAccount($acct);
		$al->save();
		$alId = $al->getId();
		
		BookstoreEmployeePeer::clearInstancePool();
		BookstoreEmployeeAccountPeer::clearInstancePool();
		AcctAuditLogPeer::clearInstancePool();
		
		$al2 = AcctAuditLogPeer::retrieveByPK($alId);
		/* @var $al2 AcctAuditLog */
		$mapacct = $al2->getBookstoreEmployeeAccount();
		$lookupacct = BookstoreEmployeeAccountPeer::retrieveByPK($acctId);
		
		$logs = $lookupacct->getAcctAuditLogs();
		
		$this->assertTrue(count($logs) == 1, "Expected 1 audit log result.");
		$this->assertEquals($logs[0]->getId(), $al->getId(), "Expected returned audit log to match created audit log.");
	}
	
	public function testIsPrimaryKeyNull()
	{
		$b = new Book();
		$this->assertTrue($b->isPrimaryKeyNull());
		$b->setPrimaryKey(123);
		$this->assertFalse($b->isPrimaryKeyNull());
		$b->setPrimaryKey(null);
		$this->assertTrue($b->isPrimaryKeyNull());
	}
	
	public function testIsPrimaryKeyNullCompmosite()
	{
		$b = new BookOpinion();
		$this->assertTrue($b->isPrimaryKeyNull());
		$b->setPrimaryKey(array(123, 456));
		$this->assertFalse($b->isPrimaryKeyNull());
		$b->setPrimaryKey(array(123, null));
		$this->assertFalse($b->isPrimaryKeyNull());
		$b->setPrimaryKey(array(null, 456));
		$this->assertFalse($b->isPrimaryKeyNull());
		$b->setPrimaryKey(array(null, null));
		$this->assertTrue($b->isPrimaryKeyNull());
	}
	
	public function testAddPrimaryString()
	{
	  $this->assertFalse(method_exists('Author', '__toString'), 'addPrimaryString() does not add a __toString() method if no column has the primaryString attribute');
	  $this->assertTrue(method_exists('Book', '__toString'), 'addPrimaryString() adds a __toString() method if a column has the primaryString attribute');
	  $book = new Book();
	  $book->setTitle('foo');
	  $this->assertEquals((string) $book, 'foo', 'addPrimaryString() adds a __toString() method returning the value of the the first column where primaryString is true');
	}

	public function testPreInsert()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$this->assertEquals('PreInsertedFirstname', $author->getFirstName());
	}

	public function testPreUpdate()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$author->setNew(false);
		$author->save();
		$this->assertEquals('PreUpdatedFirstname', $author->getFirstName());
	}

	public function testPostInsert()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$this->assertEquals('PostInsertedLastName', $author->getLastName());
	}

	public function testPostUpdate()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$author->setNew(false);
		$author->save();
		$this->assertEquals('PostUpdatedLastName', $author->getLastName());
	}
	
	public function testPreSave()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$this->assertEquals('pre@save.com', $author->getEmail());
	}

	public function testPreSaveFalse()
	{
		$con = Propel::getConnection(AuthorPeer::DATABASE_NAME);
		$author = new TestAuthorSaveFalse();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$res = $author->save($con);
		$this->assertEquals(0, $res);
		$this->assertEquals('pre@save.com', $author->getEmail());
		$this->assertNotEquals(115, $author->getAge());
		$this->assertTrue($author->isNew());
		$this->assertEquals(1, $con->getNestedTransactionCount());
	}

	public function testPostSave()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$this->assertEquals(115, $author->getAge());
	}

	public function testPreDelete()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$author->delete();
		$this->assertEquals("Pre-Deleted", $author->getFirstName());
	}

	public function testPreDeleteFalse()
	{
		$con = Propel::getConnection(AuthorPeer::DATABASE_NAME);
		$author = new TestAuthorDeleteFalse();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save($con);
		$author->delete($con);
		$this->assertEquals("Pre-Deleted", $author->getFirstName());
		$this->assertNotEquals("Post-Deleted", $author->getLastName());
		$this->assertFalse($author->isDeleted());
		$this->assertEquals(1, $con->getNestedTransactionCount());
	}

	public function testPostDelete()
	{
		$author = new TestAuthor();
		$author->setFirstName("bogus");
		$author->setLastName("Lastname");
		$author->save();
		$author->delete();
		$this->assertEquals("Post-Deleted", $author->getLastName());
	}
	
	public function testMagicVirtualColumnGetter()
	{
		$book = new Book();
		$book->setVirtualColumn('Foo', 'bar');
		$this->assertEquals('bar', $book->getFoo(), 'generated __call() catches getters for virtual columns');
		$book = new Book();
		$book->setVirtualColumn('foo', 'bar');
		$this->assertEquals('bar', $book->getFoo(), 'generated __call() catches getters for virtual columns starting with a lowercase character');
	}
	
	public static function conditionsForTestReadOnly()
	{
		return array(
			array('reload'),
			array('delete'),
			array('save'),
			array('doSave'),
		);
	}
	
	/**
	 * @dataProvider conditionsForTestReadOnly
	 */
	public function testReadOnly($method)
	{
		$cv = new ContestView();
		$this->assertFalse(method_exists($cv, $method), 'readOnly tables end up with no ' . $method . ' method in the generated object class');
	}
}