<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use Propel\Runtime\ActiveQuery\Criteria;

use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorPeer;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookPeer;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookClubList;
use Propel\Tests\Bookstore\BookClubListPeer;
use Propel\Tests\Bookstore\BookListRel;
use Propel\Tests\Bookstore\BookListRelPeer;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\PublisherPeer;
use Propel\Tests\Bookstore\PublisherQuery;
use Propel\Tests\Bookstore\Media;
use Propel\Tests\Bookstore\MediaPeer;
use Propel\Tests\Bookstore\MediaQuery;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Bookstore\ReviewPeer;
use Propel\Tests\Bookstore\ReviewQuery;

use \DateTime;

/**
 * Tests a functional scenario using the Bookstore model
 *
 * @author Francois Zaninotto
 * @author Hans Lellelid <hans@xmpl.org>
 */
class BookstoreTest extends BookstoreEmptyTestBase
{
    public function testScenario()
    {
        // Add publisher records
        // ---------------------

        try {
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
            $this->assertTrue(true, 'Save Publisher records');
        } catch (Exception $e) {
            $this->fail('Save publisher records');
        }

        // Add author records
        // ------------------

        try {
            $rowling = new Author();
            $rowling->setFirstName("J.K.");
            $rowling->setLastName("Rowling");
            // do not save, will do later to test cascade

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
            $this->assertTrue(true, 'Save Author records');
        } catch (Exception $e) {
            $this->fail('Save Author records');
        }

        // Add book records
        // ----------------

        try {
            $phoenix = new Book();
            $phoenix->setTitle("Harry Potter and the Order of the Phoenix");
            $phoenix->setISBN("043935806X");
            $phoenix->setAuthor($rowling);
            $phoenix->setPublisher($scholastic);
            $phoenix->save();
            $phoenix_id = $phoenix->getId();
            $this->assertFalse($rowling->isNew(), 'saving book also saves related author');
            $this->assertFalse($scholastic->isNew(), 'saving book also saves related publisher');

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
            $this->assertTrue(true, 'Save Book records');
        } catch (Exception $e) {
            $this->fail('Save Author records');
        }

        // Add review records
        // ------------------

        try {
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
            $this->assertTrue(true, 'Save Review records');
        } catch (Exception $e) {
            $this->fail('Save Review records');
        }

        // Perform a "complex" search
        // --------------------------

        $crit = new Criteria();
        $crit->add(BookPeer::TITLE, 'Harry%', Criteria::LIKE);
        $results = BookPeer::doSelect($crit);
        $this->assertEquals(1, count($results));

        $crit2 = new Criteria();
        $crit2->add(BookPeer::ISBN, array("0380977427", "0140422161"), Criteria::IN);
        $results = BookPeer::doSelect($crit2);
        $this->assertEquals(2, count($results));

        // Perform a "limit" search
        // ------------------------

        $crit = new Criteria();
        $crit->setLimit(2);
        $crit->setOffset(1);
        $crit->addAscendingOrderByColumn(BookPeer::TITLE);

        $results = BookPeer::doSelect($crit);
        $this->assertEquals(2, count($results));

        // we ordered on book title, so we expect to get
        $this->assertEquals("Harry Potter and the Order of the Phoenix", $results[0]->getTitle());
        $this->assertEquals("Quicksilver", $results[1]->getTitle());

        // Perform a lookup & update!
        // --------------------------

        // Updating just-created book title
        // First finding book by PK (=$qs_id) ....
        $qs_lookup = BookPeer::retrieveByPk($qs_id);
        $this->assertNotNull($qs_lookup, 'just-created book can be found by pk');

        $new_title = "Quicksilver (".crc32(uniqid(rand())).")";
        // Attempting to update found object
        $qs_lookup->setTitle($new_title);
        $qs_lookup->save();

        // Making sure object was correctly updated: ";
        $qs_lookup2 = BookPeer::retrieveByPk($qs_id);
        $this->assertEquals($new_title, $qs_lookup2->getTitle());

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

        $this->assertEquals(new DateTime('2004-02-29 00:00:00'), $r2->getReviewDate(null), 'ability to fetch DateTime');
        $this->assertEquals($control, $r2->getReviewDate('U'), 'ability to fetch native unix timestamp');
        $this->assertEquals('2-29-2004', $r2->getReviewDate('n-j-Y'), 'ability to use date() formatter');

        // Handle BLOB/CLOB Columns
        // ------------------------

        $blob_path  = __DIR__ . '/../../Fixtures/etc/lob/tin_drum.gif';
        $blob2_path = __DIR__ . '/../../Fixtures/etc/lob/propel.gif';
        $clob_path  = __DIR__ . '/../../Fixtures/etc/lob/tin_drum.txt';

        $m1 = new Media();
        $m1->setBook($phoenix);
        $m1->setCoverImage(file_get_contents($blob_path));
        $m1->setExcerpt(file_get_contents($clob_path));
        $m1->save();
        $m1_id = $m1->getId();

        $m1_lookup = MediaPeer::retrieveByPk($m1_id);

        $this->assertNotNull($m1_lookup, 'Can find just-created media item');
        $this->assertEquals(md5(file_get_contents($blob_path)), md5(stream_get_contents($m1_lookup->getCoverImage())), 'BLOB was correctly updated');
        $this->assertEquals(file_get_contents($clob_path), (string) $m1_lookup->getExcerpt(), 'CLOB was correctly updated');

        // now update the BLOB column and save it & check the results
        $m1_lookup->setCoverImage(file_get_contents($blob2_path));
        $m1_lookup->save();

        $m2_lookup = MediaPeer::retrieveByPk($m1_id);
        $this->assertNotNull($m2_lookup, 'Can find just-created media item');

        $this->assertEquals(md5(file_get_contents($blob2_path)), md5(stream_get_contents($m2_lookup->getCoverImage())), 'BLOB was correctly overwritten');

        // Testing doCount() functionality
        // -------------------------------

        $c = new Criteria();
        $records = BookPeer::doSelect($c);
        $count = BookPeer::doCount($c);

        $this->assertEquals($count, count($records), 'correct number of results');

        // Test many-to-many relationships
        // ---------------

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

        $this->assertNotNull($blc1->getId(), 'BookClubList 1 was saved');

        // init book club list 2 with 1 book

        $blc2 = new BookClubList();
        $blc2->setGroupLeader("John Foo");
        $blc2->setTheme("Default");

        $brel3 = new BookListRel();
        $brel3->setBook($phoenix);

        $blc2->addBookListRel($brel3);

        $blc2->save();

        $this->assertNotNull($blc2->getId(), 'BookClubList 2 was saved');

        // re-fetch books and lists from db to be sure that nothing is cached

        $crit = new Criteria();
        $crit->add(BookPeer::ID, $phoenix->getId());
        $phoenix = BookPeer::doSelectOne($crit);
        $this->assertNotNull($phoenix, "book 'phoenix' has been re-fetched from db");

        $crit = new Criteria();
        $crit->add(BookClubListPeer::ID, $blc1->getId());
        $blc1 = BookClubListPeer::doSelectOne($crit);
        $this->assertNotNull($blc1, 'BookClubList 1 has been re-fetched from db');

        $crit = new Criteria();
        $crit->add(BookClubListPeer::ID, $blc2->getId());
        $blc2 = BookClubListPeer::doSelectOne($crit);
        $this->assertNotNull($blc2, 'BookClubList 2 has been re-fetched from db');

        $relCount = $phoenix->countBookListRels();
        $this->assertEquals(2, $relCount, "book 'phoenix' has 2 BookListRels");

        $relCount = $blc1->countBookListRels();
        $this->assertEquals(2, $relCount, 'BookClubList 1 has 2 BookListRels');

        $relCount = $blc2->countBookListRels();
        $this->assertEquals(1, $relCount, 'BookClubList 2 has 1 BookListRel');

        // Cleanup (tests DELETE)
        // ----------------------

        // Removing books that were just created
        // First finding book by PK (=$phoenix_id) ....
        $hp = BookPeer::retrieveByPk($phoenix_id);
        $this->assertNotNull($hp, 'Could find just-created book');

        // Attempting to delete [multi-table] by found pk
        $c = new Criteria();
        $c->add(BookPeer::ID, $hp->getId());
        // The only way for cascading to work currently
        // is to specify the author_id and publisher_id (i.e. the fkeys
        // have to be in the criteria).
        $c->add(AuthorPeer::ID, $hp->getAuthor()->getId());
        $c->add(PublisherPeer::ID, $hp->getPublisher()->getId());
        $c->setSingleRecord(true);
        BookPeer::doDelete($c);

        // Checking to make sure correct records were removed.
        $this->assertEquals(3, AuthorPeer::doCount(new Criteria()), 'Correct records were removed from author table');
        $this->assertEquals(3, PublisherPeer::doCount(new Criteria()), 'Correct records were removed from publisher table');
        $this->assertEquals(3, BookPeer::doCount(new Criteria()), 'Correct records were removed from book table');

        // Attempting to delete books by complex criteria
        $c = new Criteria();
        $cn = $c->getNewCriterion(BookPeer::ISBN, "043935806X");
        $cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0380977427"));
        $cn->addOr($c->getNewCriterion(BookPeer::ISBN, "0140422161"));
        $c->add($cn);
        BookPeer::doDelete($c);

        // Attempting to delete book [id = $td_id]
        $td->delete();

        // Attempting to delete authors
        AuthorPeer::doDelete($stephenson_id);
        AuthorPeer::doDelete($byron_id);
        $grass->delete();

        // Attempting to delete publishers
        PublisherPeer::doDelete($morrow_id);
        PublisherPeer::doDelete($penguin_id);
        $vintage->delete();

        // These have to be deleted manually also since we have onDelete
        // set to SETNULL in the foreign keys in book. Is this correct?
        $rowling->delete();
        $scholastic->delete();
        $blc1->delete();
        $blc2->delete();

        $this->assertEquals(array(), AuthorPeer::doSelect(new Criteria()), 'no records in [author] table');
        $this->assertEquals(array(), PublisherPeer::doSelect(new Criteria()), 'no records in [publisher] table');
        $this->assertEquals(array(), BookPeer::doSelect(new Criteria()), 'no records in [book] table');
        $this->assertEquals(array(), ReviewPeer::doSelect(new Criteria()), 'no records in [review] table');
        $this->assertEquals(array(), MediaPeer::doSelect(new Criteria()), 'no records in [media] table');
        $this->assertEquals(array(), BookClubListPeer::doSelect(new Criteria()), 'no records in [book_club_list] table');
        $this->assertEquals(array(), BookListRelPeer::doSelect(new Criteria()), 'no records in [book_x_list] table');

    }

    public function testScenarioUsingQuery()
    {
        // Add publisher records
        // ---------------------

        try {
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
            $this->assertTrue(true, 'Save Publisher records');
        } catch (Exception $e) {
            $this->fail('Save publisher records');
        }

        // Add author records
        // ------------------

        try {
            $rowling = new Author();
            $rowling->setFirstName("J.K.");
            $rowling->setLastName("Rowling");
            // do not save, will do later to test cascade

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
            $this->assertTrue(true, 'Save Author records');
        } catch (Exception $e) {
            $this->fail('Save Author records');
        }

        // Add book records
        // ----------------

        try {
            $phoenix = new Book();
            $phoenix->setTitle("Harry Potter and the Order of the Phoenix");
            $phoenix->setISBN("043935806X");
            $phoenix->setAuthor($rowling);
            $phoenix->setPublisher($scholastic);
            $phoenix->save();
            $phoenix_id = $phoenix->getId();
            $this->assertFalse($rowling->isNew(), 'saving book also saves related author');
            $this->assertFalse($scholastic->isNew(), 'saving book also saves related publisher');

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
            $this->assertTrue(true, 'Save Book records');
        } catch (Exception $e) {
            $this->fail('Save Author records');
        }

        // Add review records
        // ------------------

        try {
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
            $this->assertTrue(true, 'Save Review records');
        } catch (Exception $e) {
            $this->fail('Save Review records');
        }

        // Perform a "complex" search
        // --------------------------

        $results = BookQuery::create()
            ->filterByTitle('Harry%')
            ->find();
        $this->assertEquals(1, count($results));

        $results = BookQuery::create()
            ->where('Book.ISBN IN ?', array("0380977427", "0140422161"))
            ->find();
        $this->assertEquals(2, count($results));

        // Perform a "limit" search
        // ------------------------

        $results = BookQuery::create()
            ->limit(2)
            ->offset(1)
            ->orderByTitle()
            ->find();
        $this->assertEquals(2, count($results));
        // we ordered on book title, so we expect to get
        $this->assertEquals("Harry Potter and the Order of the Phoenix", $results[0]->getTitle());
        $this->assertEquals("Quicksilver", $results[1]->getTitle());

        // Perform a lookup & update!
        // --------------------------

        // Updating just-created book title
        // First finding book by PK (=$qs_id) ....
        $qs_lookup = BookQuery::create()->findPk($qs_id);
        $this->assertNotNull($qs_lookup, 'just-created book can be found by pk');

        $new_title = "Quicksilver (".crc32(uniqid(rand())).")";
        // Attempting to update found object
        $qs_lookup->setTitle($new_title);
        $qs_lookup->save();

        // Making sure object was correctly updated: ";
        $qs_lookup2 = BookQuery::create()->findPk($qs_id);
        $this->assertEquals($new_title, $qs_lookup2->getTitle());

        // Test some basic DATE / TIME stuff
        // ---------------------------------

        // that's the control timestamp.
        $control = strtotime('2004-02-29 00:00:00');

        // should be two in the db
        $r = ReviewQuery::create()->findOne();
        $r_id = $r->getId();
        $r->setReviewDate($control);
        $r->save();

        $r2 = ReviewQuery::create()->findPk($r_id);
        $this->assertEquals(new DateTime('2004-02-29 00:00:00'), $r2->getReviewDate(null), 'ability to fetch DateTime');
        $this->assertEquals($control, $r2->getReviewDate('U'), 'ability to fetch native unix timestamp');
        $this->assertEquals('2-29-2004', $r2->getReviewDate('n-j-Y'), 'ability to use date() formatter');

        // Handle BLOB/CLOB Columns
        // ------------------------

        $blob_path  = __DIR__ . '/../../Fixtures/etc/lob/tin_drum.gif';
        $blob2_path = __DIR__ . '/../../Fixtures/etc/lob/propel.gif';
        $clob_path  = __DIR__ . '/../../Fixtures/etc/lob/tin_drum.txt';

        $m1 = new Media();
        $m1->setBook($phoenix);
        $m1->setCoverImage(file_get_contents($blob_path));
        $m1->setExcerpt(file_get_contents($clob_path));
        $m1->save();
        $m1_id = $m1->getId();

        $m1_lookup = MediaQuery::create()->findPk($m1_id);

        $this->assertNotNull($m1_lookup, 'Can find just-created media item');
        $this->assertEquals(md5(file_get_contents($blob_path)), md5(stream_get_contents($m1_lookup->getCoverImage())), 'BLOB was correctly updated');
        $this->assertEquals(file_get_contents($clob_path), (string) $m1_lookup->getExcerpt(), 'CLOB was correctly updated');

        // now update the BLOB column and save it & check the results
        $m1_lookup->setCoverImage(file_get_contents($blob2_path));
        $m1_lookup->save();

        $m2_lookup = MediaQuery::create()->findPk($m1_id);
        $this->assertNotNull($m2_lookup, 'Can find just-created media item');

        $this->assertEquals(md5(file_get_contents($blob2_path)), md5(stream_get_contents($m2_lookup->getCoverImage())), 'BLOB was correctly overwritten');

        // Testing doCount() functionality
        // -------------------------------

        // old way
        $c = new Criteria();
        $records = BookPeer::doSelect($c);
        $count = BookPeer::doCount($c);
        $this->assertEquals($count, count($records), 'correct number of results');

        // new way
        $count = BookQuery::create()->count();
        $this->assertEquals($count, count($records), 'correct number of results');

        // Test many-to-many relationships
        // ---------------

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

        $this->assertNotNull($blc1->getId(), 'BookClubList 1 was saved');

        // init book club list 2 with 1 book

        $blc2 = new BookClubList();
        $blc2->setGroupLeader("John Foo");
        $blc2->setTheme("Default");

        $brel3 = new BookListRel();
        $brel3->setBook($phoenix);

        $blc2->addBookListRel($brel3);

        $blc2->save();

        $this->assertNotNull($blc2->getId(), 'BookClubList 2 was saved');

        // re-fetch books and lists from db to be sure that nothing is cached

        $crit = new Criteria();
        $crit->add(BookPeer::ID, $phoenix->getId());
        $phoenix = BookPeer::doSelectOne($crit);
        $this->assertNotNull($phoenix, "book 'phoenix' has been re-fetched from db");

        $crit = new Criteria();
        $crit->add(BookClubListPeer::ID, $blc1->getId());
        $blc1 = BookClubListPeer::doSelectOne($crit);
        $this->assertNotNull($blc1, 'BookClubList 1 has been re-fetched from db');

        $crit = new Criteria();
        $crit->add(BookClubListPeer::ID, $blc2->getId());
        $blc2 = BookClubListPeer::doSelectOne($crit);
        $this->assertNotNull($blc2, 'BookClubList 2 has been re-fetched from db');

        $relCount = $phoenix->countBookListRels();
        $this->assertEquals(2, $relCount, "book 'phoenix' has 2 BookListRels");

        $relCount = $blc1->countBookListRels();
        $this->assertEquals(2, $relCount, 'BookClubList 1 has 2 BookListRels');

        $relCount = $blc2->countBookListRels();
        $this->assertEquals(1, $relCount, 'BookClubList 2 has 1 BookListRel');

        // Cleanup (tests DELETE)
        // ----------------------

        // Removing books that were just created
        // First finding book by PK (=$phoenix_id) ....
        $hp = BookQuery::create()->findPk($phoenix_id);
        $this->assertNotNull($hp, 'Could find just-created book');

        // Attempting to delete [multi-table] by found pk
        $c = new Criteria();
        $c->add(BookPeer::ID, $hp->getId());
        // The only way for cascading to work currently
        // is to specify the author_id and publisher_id (i.e. the fkeys
        // have to be in the criteria).
        $c->add(AuthorPeer::ID, $hp->getAuthor()->getId());
        $c->add(PublisherPeer::ID, $hp->getPublisher()->getId());
        $c->setSingleRecord(true);
        BookPeer::doDelete($c);

        // Checking to make sure correct records were removed.
        $this->assertEquals(3, AuthorPeer::doCount(new Criteria()), 'Correct records were removed from author table');
        $this->assertEquals(3, PublisherPeer::doCount(new Criteria()), 'Correct records were removed from publisher table');
        $this->assertEquals(3, BookPeer::doCount(new Criteria()), 'Correct records were removed from book table');

        // Attempting to delete books by complex criteria
        BookQuery::create()
            ->filterByISBN("043935806X")
            ->_or()->where('Book.ISBN = ?', "0380977427")
            ->_or()->where('Book.ISBN = ?', "0140422161")
            ->delete();

        // Attempting to delete book [id = $td_id]
        $td->delete();

        // Attempting to delete authors
        AuthorQuery::create()->filterById($stephenson_id)->delete();
        AuthorQuery::create()->filterById($byron_id)->delete();
        $grass->delete();

        // Attempting to delete publishers
        PublisherQuery::create()->filterById($morrow_id)->delete();
        PublisherQuery::create()->filterById($penguin_id)->delete();
        $vintage->delete();

        // These have to be deleted manually also since we have onDelete
        // set to SETNULL in the foreign keys in book. Is this correct?
        $rowling->delete();
        $scholastic->delete();
        $blc1->delete();
        $blc2->delete();

        $this->assertEquals(array(), AuthorPeer::doSelect(new Criteria()), 'no records in [author] table');
        $this->assertEquals(array(), PublisherPeer::doSelect(new Criteria()), 'no records in [publisher] table');
        $this->assertEquals(array(), BookPeer::doSelect(new Criteria()), 'no records in [book] table');
        $this->assertEquals(array(), ReviewPeer::doSelect(new Criteria()), 'no records in [review] table');
        $this->assertEquals(array(), MediaPeer::doSelect(new Criteria()), 'no records in [media] table');
        $this->assertEquals(array(), BookClubListPeer::doSelect(new Criteria()), 'no records in [book_club_list] table');
        $this->assertEquals(array(), BookListRelPeer::doSelect(new Criteria()), 'no records in [book_x_list] table');
    }
}
