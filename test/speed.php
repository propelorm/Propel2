<?php

$conf_path = realpath(dirname(__FILE__) . '/fixtures/bookstore/build/conf/bookstore-conf.php');
if (!file_exists($conf_path)) {
	throw new Exception('Bookstore project must be built');
}

 // Add build/classes/ and classes/ to path
set_include_path(
	realpath(dirname(__FILE__) . '/fixtures/bookstore/build/classes') . PATH_SEPARATOR .
	dirname(__FILE__) . '/../runtime/classes' . PATH_SEPARATOR .
	get_include_path()
);

require_once 'propel/Propel.php';
$conf = include $conf_path;
$conf['log'] = null;
Propel::setConfiguration($conf);
Propel::initialize();

include_once 'tools/helpers/bookstore/validator/ISBNValidator.php';

class PropelSpeedTest
{
	public $iterations;
	
	public function __construct($iterations = 100)
	{
		$this->iterations = $iterations;
	}
	
  public function run()
  {
    $timers = array();
    fwrite(STDOUT, "Running scenario");
    // perform tests
    for ($i=0; $i < $this->iterations; $i++) { 
      fwrite(STDOUT, '.');
    	$this->setUp();
      $t = microtime(true);
      $this->testSpeed();
      $timers[]= microtime(true) - $t;
      $this->tearDown();
    }
    fwrite(STDOUT, " done\n");
    // sort tests
    sort($timers);
    
    // eliminate first and last
    array_shift($timers);
    array_pop($timers);
    
    return array_sum($timers) / count($timers);
  }

	protected function emptyTables()
	{
		$res1 = AuthorPeer::doSelect(new Criteria());
		$res2 = PublisherPeer::doSelect(new Criteria());
		$res3 = AuthorPeer::doSelect(new Criteria());
		$res4 = ReviewPeer::doSelect(new Criteria());
		$res5 = MediaPeer::doSelect(new Criteria());
		$res6 = BookClubListPeer::doSelect(new Criteria());
		$res7 = BookListRelPeer::doSelect(new Criteria());	
	}

	public function setUp()
	{
		$this->con = Propel::getConnection(BookPeer::DATABASE_NAME);
		$this->con->beginTransaction();
		$this->emptyTables();
	}

	public function tearDown()
	{
		$this->emptyTables();
		$this->con->commit();
	}

	public function testSpeed()
	{
		// Add publisher records
		// ---------------------
		
		$scholastic = new Publisher();
		$scholastic->setName("Scholastic");
		// do not save, will do later to test cascade
		
		$morrow = new Publisher();
		$morrow->setName("William Morrow");
		$morrow->save();
		$morrow_id = $morrow->getId();
		
		$penguin = new Publisher();
		$penguin->setName("Penguin");
		$penguin->save();
		$penguin_id = $penguin->getId();
		
		$vintage = new Publisher();
		$vintage->setName("Vintage");
		$vintage->save();
		$vintage_id = $vintage->getId();
		
		// Add author records
		// ------------------
		
		$rowling = new Author();
		$rowling->setFirstName("J.K.");
		$rowling->setLastName("Rowling");
		// no save()
		
		$stephenson = new Author();
		$stephenson->setFirstName("Neal");
		$stephenson->setLastName("Stephenson");
		$stephenson->save();
		$stephenson_id = $stephenson->getId();
		
		$byron = new Author();
		$byron->setFirstName("George");
		$byron->setLastName("Byron");
		$byron->save();
		$byron_id = $byron->getId();
		
		$grass = new Author();
		$grass->setFirstName("Gunter");
		$grass->setLastName("Grass");
		$grass->save();
		$grass_id = $grass->getId();
		
		// Add book records
		// ----------------
		
		$phoenix = new Book();
		$phoenix->setTitle("Harry Potter and the Order of the Phoenix");
		$phoenix->setISBN("043935806X");
		
		// cascading save (Harry Potter)
		$phoenix->setAuthor($rowling);
		$phoenix->setPublisher($scholastic);
		$phoenix->save();
		$phoenix_id = $phoenix->getId();
		
		$qs = new Book();
		$qs->setISBN("0380977427");
		$qs->setTitle("Quicksilver");
		$qs->setAuthor($stephenson);
		$qs->setPublisher($morrow);
		$qs->save();
		$qs_id = $qs->getId();
		
		$dj = new Book();
		$dj->setISBN("0140422161");
		$dj->setTitle("Don Juan");
		$dj->setAuthor($byron);
		$dj->setPublisher($penguin);
		$dj->save();
		$dj_id = $qs->getId();
		
		$td = new Book();
		$td->setISBN("067972575X");
		$td->setTitle("The Tin Drum");
		$td->setAuthor($grass);
		$td->setPublisher($vintage);
		$td->save();
		$td_id = $td->getId();
		
		// Add review records
		// ------------------
		
		$r1 = new Review();
		$r1->setBook($phoenix);
		$r1->setReviewedBy("Washington Post");
		$r1->setRecommended(true);
		$r1->setReviewDate(time());
		$r1->save();
		$r1_id = $r1->getId();
		
		$r2 = new Review();
		$r2->setBook($phoenix);
		$r2->setReviewedBy("New York Times");
		$r2->setRecommended(false);
		$r2->setReviewDate(time());
		$r2->save();
		$r2_id = $r2->getId();
		
		// Perform a "complex" search
		// --------------------------
		
		$crit = new Criteria();
		$crit->add(BookPeer::TITLE, 'Harry%', Criteria::LIKE);
		$results = BookPeer::doSelect($crit);
		
		$crit2 = new Criteria();
		$crit2->add(BookPeer::ISBN, array("0380977427", "0140422161"), Criteria::IN);
		$results = BookPeer::doSelect($crit2);
		
		// Perform a "limit" search
		// ------------------------
		
		$crit = new Criteria();
		$crit->setLimit(2);
		$crit->setOffset(1);
		$crit->addAscendingOrderByColumn(BookPeer::TITLE);		
		$results = BookPeer::doSelect($crit);
		
		// Perform a lookup & update!
		// --------------------------
		
		$qs_lookup = BookPeer::retrieveByPk($qs_id);
		$new_title = "Quicksilver (".crc32(uniqid(rand())).")";
		$qs_lookup->setTitle($new_title);
		$qs_lookup->save();
		
		$qs_lookup2 = BookPeer::retrieveByPk($qs_id);
		
		// Test some basic DATE / TIME stuff
		// ---------------------------------
		
		// that's the control timestamp.
		$control = strtotime('2004-02-29 00:00:00');
	
		// should be two in the db
		$r = ReviewPeer::doSelectOne(new Criteria());
		$r_id = $r->getId();
		$r->setReviewDate($control);
		$r->save();
	
		$r2 = ReviewPeer::retrieveByPk($r_id);

		// Testing the DATE/TIME columns
		// -----------------------------
	
		// that's the control timestamp.
		$control = strtotime('2004-02-29 00:00:00');
	
		// should be two in the db
		$r = ReviewPeer::doSelectOne(new Criteria());
		$r_id = $r->getId();
		$r->setReviewDate($control);
		$r->save();
	
		$r2 = ReviewPeer::retrieveByPk($r_id);

		// Testing the column validators
		// -----------------------------
	
		$bk1 = new Book();
		$bk1->setTitle("12345"); // min length is 10
		$ret = $bk1->validate();
	
		// Unique validator
		$bk2 = new Book();
		$bk2->setTitle("Don Juan");
		$ret = $bk2->validate();
	
		// Now trying some more complex validation.
		$auth1 = new Author();
		$auth1->setFirstName("Hans");
		// last name required; will fail
	
		$bk1->setAuthor($auth1);
	
		$rev1 = new Review();
		$rev1->setReviewDate("08/09/2001");
		 // will fail: reviewed_by column required
	
		$bk1->addReview($rev1);
	
		$ret2 = $bk1->validate();
	
		$bk2 = new Book();
		$bk2->setTitle("12345678901"); // passes
	
		$auth2 = new Author();
		$auth2->setLastName("Blah"); //passes
		$auth2->setEmail("some@body.com"); //passes
		$auth2->setAge(50); //passes
		$bk2->setAuthor($auth2);
	
		$rev2 = new Review();
		$rev2->setReviewedBy("Me!"); // passes
		$rev2->setStatus("new"); // passes
		$bk2->addReview($rev2);
	
		$ret3 = $bk2->validate();

		// Testing doCount() functionality
		// -------------------------------
		
		$c = new Criteria();
		$count = BookPeer::doCount($c);

		// Testing many-to-many relationships
		// ----------------------------------
	
		// init book club list 1 with 2 books
	
		$blc1 = new BookClubList();
		$blc1->setGroupLeader("Crazyleggs");
		$blc1->setTheme("Happiness");
	
		$brel1 = new BookListRel();
		$brel1->setBook($phoenix);
	
		$brel2 = new BookListRel();
		$brel2->setBook($dj);
	
		$blc1->addBookListRel($brel1);
		$blc1->addBookListRel($brel2);
	
		$blc1->save();	
	
		// init book club list 2 with 1 book
	
		$blc2 = new BookClubList();
		$blc2->setGroupLeader("John Foo");
		$blc2->setTheme("Default");
	
		$brel3 = new BookListRel();
		$brel3->setBook($phoenix);
	
		$blc2->addBookListRel($brel3);
	
		$blc2->save();
	
		// re-fetch books and lists from db to be sure that nothing is cached
	
		$crit = new Criteria();
		$crit->add(BookPeer::ID, $phoenix->getId());
		$phoenix = BookPeer::doSelectOne($crit);
	
		$crit = new Criteria();
		$crit->add(BookClubListPeer::ID, $blc1->getId());
		$blc1 = BookClubListPeer::doSelectOne($crit);
	
		$crit = new Criteria();
		$crit->add(BookClubListPeer::ID, $blc2->getId());
		$blc2 = BookClubListPeer::doSelectOne($crit);
	
		$relCount = $phoenix->countBookListRels();
	
		$relCount = $blc1->countBookListRels();
	
		$relCount = $blc2->countBookListRels();

		// Removing books that were just created
		// -------------------------------------
	
		$hp = BookPeer::retrieveByPk($phoenix_id);
		$c = new Criteria();
		$c->add(BookPeer::ID, $hp->getId());
		// The only way for cascading to work currently
		// is to specify the author_id and publisher_id (i.e. the fkeys
		// have to be in the criteria).
		$c->add(AuthorPeer::ID, $hp->getId());
		$c->add(PublisherPeer::ID, $hp->getId());
		$c->setSingleRecord(true);
		BookPeer::doDelete($c);
	
		// Attempting to delete books by complex criteria
		$c = new Criteria();
		$cn = $c->getNewCriterion(BookPeer::ISBN, "043935806X");
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0380977427"));
		$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0140422161"));
		$c->add($cn);
		BookPeer::doDelete($c);
	
		$td->delete();
	
		AuthorPeer::doDelete($stephenson_id);
	
		AuthorPeer::doDelete($byron_id);
	
		$grass->delete();
	
		PublisherPeer::doDelete($morrow_id);
	
		PublisherPeer::doDelete($penguin_id);
	
		$vintage->delete();
	
		// These have to be deleted manually also since we have onDelete
		// set to SETNULL in the foreign keys in book. Is this correct?
		$rowling->delete();
	
		$scholastic->delete();
	
		$blc1->delete();
	
		$blc2->delete();
	}
}

$test = new PropelSpeedTest(100);
echo "Test speed: {$test->run()} ({$test->iterations} iterations)\n";
