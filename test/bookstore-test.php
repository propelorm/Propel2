<?php

/**
 * Simple test script for Propel drivers.
 *
 * This script will no do in-depth testing, but is designed to test whether drivers
 * are correctly performing basic operations -- SELECT, UPDATE, DELETE, limit support,
 * prepared query emulation, etc.
 *
 * IMPORTANT:
 *
 * Use this script with a clean version of the [example] bookstore database.  If records
 * already exist, an error will be displayed.
 *
 * TODO:
 * A more advanced driver test system should be developed that could test capabilities
 * of driver-specific things like callable statements (stored procedures), etc.  Perhaps break
 * functionality into class & provide ability to subclass.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 */

// Setup configuration.  It is expected that the bookstore-conf.php file exists in ../build/conf
//

error_reporting(E_ALL);

$conf_path = realpath(dirname(__FILE__) . '/../projects/bookstore/build/conf/bookstore-conf.php');
if (!file_exists($conf_path)) {
	print "Make sure that you specify properties in conf/bookstore.properties and "
	."build propel before running this script.\n";
	exit;
}

// Add PHP_CLASSPATH, if set
if (getenv("PHP_CLASSPATH")) {
	set_include_path(getenv("PHP_CLASSPATH") . PATH_SEPARATOR . get_include_path());
}

 // Add build/classes/ and classes/ to path
set_include_path(
	realpath(dirname(__FILE__) . '/../projects/bookstore/build/classes') . PATH_SEPARATOR .
	dirname(__FILE__) . '/../../runtime/classes' . PATH_SEPARATOR .
	get_include_path()
);


 // Require classes.
 require 'propel/Propel.php';

 include_once 'Benchmark/Timer.php';

 $timer = new Benchmark_Timer;

 $timer->start();

 // Some utility functions
 function boolTest($cond) {
	 if ($cond) {
		 return "[OK]\n";
	 } else {
		return "[FAILED]\n";
	}
 }

 try {
	// Initialize Propel
	 Propel::init($conf_path);
 } catch (Exception $e) {
	 die("Error initializing propel: ". $e->__toString());
 }

function check_tables_empty() {
	try {

		print "\nChecking to see that tables are empty\n";
		print "-------------------------------------\n\n";

		print "Ensuring that there are no records in [author] table: ";
		$res = AuthorPeer::doSelect(new Criteria());
		print boolTest(empty($res));

		print "Ensuring that there are no records in [publisher] table: ";
		$res2 = PublisherPeer::doSelect(new Criteria());
		print boolTest(empty($res2));

		print "Ensuring that there are no records in [book] table: ";
		$res3 = AuthorPeer::doSelect(new Criteria());
		print boolTest(empty($res3));

		print "Ensuring that there are no records in [review] table: ";
		$res4 = ReviewPeer::doSelect(new Criteria());
		print boolTest(empty($res4));

		print "Ensuring that there are no records in [media] table: ";
		$res5 = MediaPeer::doSelect(new Criteria());
		print boolTest(empty($res5));

		print "Ensuring that there are no records in [book_club_list] table: ";
		$res6 = BookClubListPeer::doSelect(new Criteria());
		print boolTest(empty($res6));

		print "Ensuring that there are no records in [book_x_list] table: ";
		$res7 = BookListRelPeer::doSelect(new Criteria());
		print boolTest(empty($res7));

		return (empty($res) && empty($res2) && empty($res3) && empty($res4) && empty($res5));

	} catch (Exception $e) {
		die("Error ensuring tables were empty: " . $e->__toString());
	}
}

// Check to see if records already exist in any of the three tables.  If so, display an error
// and exit.

if (!check_tables_empty()) {
	die("Tables must be empty to perform these tests.");
}

// Add publisher records
// ---------------------

try {
	print "\nAdding some new publishers to the list\n";
	print "--------------------------------------\n\n";

	$scholastic = new Publisher();
	$scholastic->setName("Scholastic");
	// do not save, will do later to test cascade
	print "Added publisher \"Scholastic\" [not saved yet].\n";

	$morrow = new Publisher();
	$morrow->setName("William Morrow");
	$morrow->save();
	$morrow_id = $morrow->getId();
	print "Added publisher \"William Morrow\" [id = $morrow_id].\n";

	$penguin = new Publisher();
	$penguin->setName("Penguin");
	$penguin->save();
	$penguin_id = $penguin->getId();
	print "Added publisher \"Penguin\" [id = $penguin_id].\n";

	$vintage = new Publisher();
	$vintage->setName("Vintage");
	$vintage->save();
	$vintage_id = $vintage->getId();
	print "Added publisher \"Vintage\" [id = $vintage_id].\n";

} catch (Exception $e) {
	die("Error adding publisher: " . $e->__toString());
}

// Add author records
// ------------------

try {
	print "\nAdding some new authors to the list\n";
	print "--------------------------------------\n\n";

	$rowling = new Author();
	$rowling->setFirstName("J.K.");
	$rowling->setLastName("Rowling");
	// no save()
	print "Added author \"J.K. Rowling\" [not saved yet].\n";

	$stephenson = new Author();
	$stephenson->setFirstName("Neal");
	$stephenson->setLastName("Stephenson");
	$stephenson->save();
	$stephenson_id = $stephenson->getId();
	print "Added author \"Neal Stephenson\" [id = $stephenson_id].\n";

	$byron = new Author();
	$byron->setFirstName("George");
	$byron->setLastName("Byron");
	$byron->save();
	$byron_id = $byron->getId();
	print "Added author \"George Byron\" [id = $byron_id].\n";


	$grass = new Author();
	$grass->setFirstName("Gunter");
	$grass->setLastName("Grass");
	$grass->save();
	$grass_id = $grass->getId();
	print "Added author \"Gunter Grass\" [id = $grass_id].\n";

} catch (Exception $e) {
	die("Error adding author: " . $e->__toString());
}

// Add book records
// ----------------

try {

	print "\nAdding some new books to the list\n";
	print "-------------------------------------\n\n";

	$phoenix = new Book();
	$phoenix->setTitle("Harry Potter and the Order of the Phoenix");
	$phoenix->setISBN("043935806X");



	print "Trying cascading save (Harry Potter): ";
	$phoenix->setAuthor($rowling);
	$phoenix->setPublisher($scholastic);
	$phoenix->save();
	$phoenix_id = $phoenix->getId();
	print boolTest(true);
	print "Added book \"Harry Potter and the Order of the Phoenix\" [id = $phoenix_id].\n";

	$qs = new Book();
	$qs->setISBN("0380977427");
	$qs->setTitle("Quicksilver");
	$qs->setAuthor($stephenson);
	$qs->setPublisher($morrow);
	$qs->save();
	$qs_id = $qs->getId();
	print "Added book \"Quicksilver\" [id = $qs_id].\n";

	$dj = new Book();
	$dj->setISBN("0140422161");
	$dj->setTitle("Don Juan");
	$dj->setAuthor($byron);
	$dj->setPublisher($penguin);
	$dj->save();
	$dj_id = $qs->getId();
	print "Added book \"Don Juan\" [id = $dj_id].\n";

	$td = new Book();
	$td->setISBN("067972575X");
	$td->setTitle("The Tin Drum");
	$td->setAuthor($grass);
	$td->setPublisher($vintage);
	$td->save();
	$td_id = $td->getId();
	print "Added book \"The Tin Drum\" [id = $dj_id].\n";

} catch (Exception $e) {
	die("Error saving book: " . $e->__toString());
}

// Add review records
// ------------------

try {

	print "\nAdding some book reviews to the list\n";
	print "------------------------------------\n\n";

	$r1 = new Review();
	$r1->setBook($phoenix);
	$r1->setReviewedBy("Washington Post");
	$r1->setRecommended(true);
	$r1->setReviewDate(time());
	$r1->save();
	$r1_id = $r1->getId();
	print "Added Washington Post book review  [id = $r1_id].\n";

	$r2 = new Review();
	$r2->setBook($phoenix);
	$r2->setReviewedBy("New York Times");
	$r2->setRecommended(false);
	$r2->setReviewDate(time());
	$r2->save();
	$r2_id = $r2->getId();
	print "Added New York Times book review  [id = $r2_id].\n";

} catch (Exception $e) {
	die("Error saving book review: " . $e->__toString());
}

// Perform a "complex" search
// --------------------------

try {

	print "\nDoing complex search on books\n";
	print "-----------------------------\n\n";

	$crit = new Criteria();
	$crit->add(BookPeer::TITLE, 'Harry%', Criteria::LIKE);

	print "Looking for \"Harry%\": ";
	$results = BookPeer::doSelect($crit);
	print boolTest(count($results) === 1);


	$crit2 = new Criteria();
	$crit2->add(BookPeer::ISBN, array("0380977427", "0140422161"), Criteria::IN);
	$results = BookPeer::doSelect($crit2);
	print "Looking for ISBN IN (\"0380977427\", \"0140422161\"): ";
	print boolTest(count($results) === 2);

} catch (Exception $e) {
	die("Error while performing complex query: " . $e->__toString());
}


// Perform a "limit" search
// ------------------------

try {

	print "\nDoing LIMITed search on books\n";
	print "-----------------------------\n\n";

	$crit = new Criteria();
	$crit->setLimit(2);
	$crit->setOffset(1);
	$crit->addAscendingOrderByColumn(BookPeer::TITLE);

	print "Checking to make sure correct number returned: ";
	$results = BookPeer::doSelect($crit);
	print boolTest(count($results) === 2);

	print "Checking to make sure correct books returned: ";
	// we ordered on book title, so we expect to get
	print boolTest( $results[0]->getTitle() == "Harry Potter and the Order of the Phoenix" && $results[1]->getTitle() == "Quicksilver"  );


} catch (Exception $e) {
	die("Error while performing LIMIT query: " . $e->__toString());
}



// Perform a lookup & update!
// --------------------------

try {

	print "\nUpdating just-created book title\n";
	print "--------------------------------\n\n";

	print "First finding book by PK (=$qs_id) .... ";

	try {
		$qs_lookup = BookPeer::retrieveByPk($qs_id);
	} catch (Exception $e) {
		print "ERROR!\n";
		die("Error retrieving by pk: " . $e->__toString());
	}

	if ($qs_lookup) {
		print "FOUND!\n";
	} else {
		print "NOT FOUND :(\n";
		die("Couldn't find just-created book: book_id = $qs_id");
	}

	try {
		$new_title = "Quicksilver (".crc32(uniqid(rand())).")";
		print "Attempting to update found object (".$qs_lookup->getTitle()." -> ".$new_title."): ";
		$qs_lookup->setTitle($new_title);
		$qs_lookup->save();
		print boolTest(true);
	} catch (Exception $e) {
		die("Error saving (updating) book: " . $e->__toString());
	}

	print "Making sure object was correctly updated: ";
	$qs_lookup2 = BookPeer::retrieveByPk($qs_id);
	print boolTest($qs_lookup2->getTitle() == $new_title);

} catch (Exception $e) {
	die("Error updating book: " . $e->__toString());
}


// Test some basic DATE / TIME stuff
// ---------------------------------

try {
	print "\nTesting the DATE/TIME columns\n";
	print "-----------------------------\n\n";

	// that's the control timestamp.
	$control = strtotime('2004-02-29 00:00:00');

	// should be two in the db
	$r = ReviewPeer::doSelectOne(new Criteria());
	$r_id = $r->getId();
	$r->setReviewDate($control);
	$r->save();

	$r2 = ReviewPeer::retrieveByPk($r_id);

	print "Checking ability to fetch native unix timestamp: ";
	print boolTest($r2->getReviewDate(null) === $control);

	print "Checking ability to use date() formatter: ";
	print boolTest($r2->getReviewDate('n-j-Y') === '2-29-2004');

	print "[FYI] Here's the strftime() formatter for current locale: " . $r2->getReviewDate('%x') . "\n";

} catch (Exception $e) {
	die("Error test date/time: "  . $e->__toString());
}

// Handle BLOB/CLOB Columns
// ------------------------

try {
	print "\nTesting the BLOB/CLOB columns\n";
	print "-------------------------------\n\n";

	$blob_path = dirname(__FILE__) . '/etc/lob/tin_drum.gif';
	$blob2_path = dirname(__FILE__) . '/etc/lob/propel.gif';
	$clob_path = dirname(__FILE__) . '/etc/lob/tin_drum.txt';

	$m1 = new Media();
	$m1->setBook($phoenix);
	$m1->setCoverImage(file_get_contents($blob_path));
	$m1->setExcerpt(file_get_contents($clob_path));
	$m1->save();
	$m1_id = $m1->getId();
	print "Added Media collection [id = $m1_id].\n";

	print "Looking for just-created mediat by PK (=$m1_id) .... ";

	try {
		$m1_lookup = MediaPeer::retrieveByPk($m1_id);
	} catch (Exception $e) {
		print "ERROR!\n";
		die("Error retrieving media by pk: " . $e->__toString());
	}

	if ($m1_lookup) {
		print "FOUND!\n";
	} else {
		print "NOT FOUND :(\n";
		die("Couldn't find just-created media item: media_id = $m1_id");
	}

	print "Making sure BLOB was correctly updated: ";
	print boolTest( $m1_lookup->getCoverImage() === file_get_contents($blob_path));
	print "Making sure CLOB was correctly updated: ";
	print boolTest((string) $m1_lookup->getExcerpt() === file_get_contents($clob_path));


	// now update the BLOB column and save it & check the results

	$m1_lookup->setCoverImage(file_get_contents($blob2_path));
	$m1_lookup->save();

	try {
		$m2_lookup = MediaPeer::retrieveByPk($m1_id);
	} catch (Exception $e) {
		print "ERROR!\n";
		die("Error retrieving media by pk: " . $e->__toString());
	}

	print "Making sure BLOB was correctly overwritten: ";
	print boolTest($m2_lookup->getCoverImage() === file_get_contents($blob2_path));

} catch (Exception $e) {
	die("Error doing blob/clob updates: " . $e->__toString());
}

// Test Validators
// ---------------

try {

	print "\nTesting the column validators\n";
	print "-----------------------------\n\n";

	$bk1 = new Book();
	$bk1->setTitle("12345"); // min length is 10
	$ret = $bk1->validate();

	print "Making sure validation failed: ";
	print boolTest($ret !== true);

	$failures = $bk1->getValidationFailures();

	print "Making sure 1 validation message was returned: ";
	print boolTest(count($failures) === 1);

	print "Making sure expected validation message was returned: ";
	$el = array_shift($failures);
	print boolTest(stripos($el->getMessage(), "must be more than") !== false);

	print "\n(Unique validator)\n";

	$bk2 = new Book();
	$bk2->setTitle("Don Juan");
	$ret = $bk2->validate();

	print "Making sure validation failed: ";
	print boolTest($ret !== true);

	$failures = $bk2->getValidationFailures();
	print "Making sure 1 validation message was returned: ";
	print boolTest(count($failures) === 1);

	print "Making sure expected validation message was returned: ";
	$el = array_shift($failures);
	print boolTest(stripos($el->getMessage(), "Book title already in database.") !== false);

	print "\n(Now trying some more complex validation.)\n";
	$auth1 = new Author();
	$auth1->setFirstName("Hans");
	// last name required; will fail

	$bk1->setAuthor($auth1);

	$rev1 = new Review();
	$rev1->setReviewDate("08/09/2001");
	 // will fail: reviewed_by column required

	$bk1->addReview($rev1);

	$ret2 = $bk1->validate();

	$failures2 = $bk1->getValidationFailures();

	print "Making sure 6 validation messages were returned: ";
	print boolTest(count($failures2) === 3);

	print "Making sure correct columns failed: ";
	print boolTest(array_keys($failures2) === array(
		AuthorPeer::LAST_NAME,
		BookPeer::TITLE,
		ReviewPeer::REVIEWED_BY,
	));


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

	print "Making sure complex validation can pass: ";
	print boolTest($ret3 === true);

} catch (Exception $e) {
	die("Error doing validation tests: " . $e->__toString());
}

// Test doCount()
//
try {

	print "\nTesting doCount() functionality\n";
	print "-------------------------------\n\n";

	$c = new Criteria();
	$records = BookPeer::doSelect($c);
	$count = BookPeer::doCount($c);

	print "Making sure correct number of results: ";
	print boolTest(count($records) === $count);

} catch (Exception $e) {
	die("Error deleting book: " . $e->__toString());
}

// Test many-to-many relationships
// ---------------

try {

	print "\nTesting many-to-many relationships\n";
	print "-----------------------------\n\n";

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

	print "Making sure BookClubList 1 was saved: ";
 	print boolTest(!is_null($blc1->getId()));

	// init book club list 2 with 1 book

	$blc2 = new BookClubList();
	$blc2->setGroupLeader("John Foo");
	$blc2->setTheme("Default");

	$brel3 = new BookListRel();
	$brel3->setBook($phoenix);

	$blc2->addBookListRel($brel3);

	$blc2->save();

	print "Making sure BookClubList 2 was saved: ";
 	print boolTest(!is_null($blc2->getId()));

	// re-fetch books and lists from db to be sure that nothing is cached

	$crit = new Criteria();
	$crit->add(BookPeer::ID, $phoenix->getId());
	$phoenix = BookPeer::doSelectOne($crit);
	print "Making sure book 'phoenix' has been re-fetched from db: ";
 	print boolTest(!empty($phoenix));

	$crit = new Criteria();
	$crit->add(BookClubListPeer::ID, $blc1->getId());
	$blc1 = BookClubListPeer::doSelectOne($crit);
	print "Making sure BookClubList 1 has been re-fetched from db: ";
 	print boolTest(!empty($blc1));

	$crit = new Criteria();
	$crit->add(BookClubListPeer::ID, $blc2->getId());
	$blc2 = BookClubListPeer::doSelectOne($crit);
	print "Making sure BookClubList 2 has been re-fetched from db: ";
 	print boolTest(!empty($blc2));

	$relCount = $phoenix->countBookListRels();
	print "Making sure book 'phoenix' has 2 BookListRels: ";
 	print boolTest($relCount == 2);

	$relCount = $blc1->countBookListRels();
	print "Making sure BookClubList 1 has 2 BookListRels: ";
 	print boolTest($relCount == 2);

	$relCount = $blc2->countBookListRels();
	print "Making sure BookClubList 2 has 1 BookListRel: ";
 	print boolTest($relCount == 1);


} catch (Exception $e) {
	die("Error doing many-to-many relationships tests: " . $e->__toString());
}

// Cleanup (tests DELETE)
// ----------------------

try {

	print "\nRemoving books that were just created\n";
	print "-------------------------------------\n\n";

	print "First finding book by PK (=$phoenix_id) .... ";
	try {
		$hp = BookPeer::retrieveByPk($phoenix_id);
	} catch (Exception $e) {
		print "ERROR!\n";
		die("Error retrieving by pk: " . $e->__toString());
	}

	if ($hp) {
		print "FOUND!\n";
	} else {
		print "NOT FOUND :(\n";
		die("Couldn't find just-created book: book_id = $phoenix_id");
	}

	print "Attempting to delete [multi-table] by found pk: ";
	$c = new Criteria();
	$c->add(BookPeer::ID, $hp->getId());
	// The only way for cascading to work currently
	// is to specify the author_id and publisher_id (i.e. the fkeys
	// have to be in the criteria).
	$c->add(AuthorPeer::ID, $hp->getId());
	$c->add(PublisherPeer::ID, $hp->getId());
	$c->setSingleRecord(true);
	BookPeer::doDelete($c);
	print boolTest(true);

	print "Checking to make sure correct records were removed.\n";
	print "\tFrom author table: ";
	$res = AuthorPeer::doSelect(new Criteria());
	print boolTest(count($res) === 3);
	print "\tFrom publisher table: ";
	$res2 = PublisherPeer::doSelect(new Criteria());
	print boolTest(count($res2) === 3);
	print "\tFrom book table: ";
	$res3 = BookPeer::doSelect(new Criteria());
	print boolTest(count($res3) === 3);

	print "Attempting to delete books by complex criteria: ";
	$c = new Criteria();
	$cn = $c->getNewCriterion(BookPeer::ISBN, "043935806X");
	$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0380977427"));
	$cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0140422161"));
	$c->add($cn);
	BookPeer::doDelete($c);
	print boolTest(true);

	print "Attempting to delete book [id = $td_id]: ";
	$td->delete();
	print boolTest(true);

	print "Attempting to delete author [id = $stephenson_id]: ";
	AuthorPeer::doDelete($stephenson_id);
	print boolTest(true);

	print "Attempting to delete author [id = $byron_id]: ";
	AuthorPeer::doDelete($byron_id);
	print boolTest(true);

	print "Attempting to delete author [id = $grass_id]: ";
	$grass->delete();
	print boolTest(true);

	print "Attempting to delete publisher [id = $morrow_id]: ";
	PublisherPeer::doDelete($morrow_id);
	print boolTest(true);

	print "Attempting to delete publisher [id = $penguin_id]: ";
	PublisherPeer::doDelete($penguin_id);
	print boolTest(true);

	print "Attempting to delete publisher [id = $vintage_id]: ";
	$vintage->delete();
	print boolTest(true);

	// These have to be deleted manually also since we have onDelete
	// set to SETNULL in the foreign keys in book. Is this correct?
	print "Attempting to delete author [lastname = 'Rowling']: ";
	$rowling->delete();
	print boolTest(true);

	print "Attempting to delete publisher [lastname = 'Scholastic']: ";
	$scholastic->delete();
	print boolTest(true);

	print "Attempting to delete BookClubList 1: ";
	$blc1->delete();
	print boolTest(true);

	print "Attempting to delete BookClubList 2: ";
	$blc2->delete();
	print boolTest(true);

} catch (Exception $e) {
	die("Error deleting book: " . $e->__toString());
}


// Check again to make sure that tables are empty
// ----------------------------------------------

check_tables_empty();





$timer->stop();
print $timer->display();
