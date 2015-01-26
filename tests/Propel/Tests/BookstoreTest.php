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

use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookClubList;
use Propel\Tests\Bookstore\BookListRelQuery;
use Propel\Tests\Bookstore\BookClubListQuery;
use Propel\Tests\Bookstore\BookListRel;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\PublisherQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\BookClubListTableMap;
use Propel\Tests\Bookstore\Map\PublisherTableMap;
use Propel\Tests\Bookstore\Media;
use Propel\Tests\Bookstore\MediaQuery;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Bookstore\ReviewQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

use \DateTime;

/**
 * Tests a functional scenario using the Bookstore model
 *
 * @author Francois Zaninotto
 * @author Hans Lellelid <hans@xmpl.org>
 *
 * @group database
 */
class BookstoreTest extends BookstoreEmptyTestBase
{
    public function testScenario()
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
        $this->assertTrue(true, 'Save Publisher records');

        // Add author records
        // ------------------

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

        // Add book records
        // ----------------

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
        $this->assertTrue(true, 'Save Review records');

        // Perform a "complex" search
        // --------------------------

        $results = BookQuery::create()->filterByTitle('Harry%', Criteria::LIKE)->find();
        $this->assertEquals(1, count($results));

        $results = BookQuery::create()->filterByISBN(array("0380977427", "0140422161"), Criteria::IN)->find();
        $this->assertEquals(2, count($results));

        // Perform a "limit" search
        // ------------------------

        $results = BookQuery::create()->limit(2)->offset(1)->orderByTitle()->find();
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

        $records = BookQuery::create()->find();
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
        $crit->add(BookTableMap::COL_ID, $phoenix->getId());
        $phoenix = BookQuery::create(null, $crit)->findOne();
        $this->assertNotNull($phoenix, "book 'phoenix' has been re-fetched from db");

        $crit = new Criteria();
        $crit->add(BookClubListTableMap::COL_ID, $blc1->getId());
        $blc1 = BookClubListQuery::create(null, $crit)->findOne();
        $this->assertNotNull($blc1, 'BookClubList 1 has been re-fetched from db');

        $crit = new Criteria();
        $crit->add(BookClubListTableMap::COL_ID, $blc2->getId());
        $blc2 = BookClubListQuery::create(null, $crit)->findOne();
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
        $c->add(BookTableMap::COL_ID, $hp->getId());
        // The only way for cascading to work currently
        // is to specify the author_id and publisher_id (i.e. the fkeys
        // have to be in the criteria).
        $c->add(AuthorTableMap::COL_ID, $hp->getAuthor()->getId());
        $c->add(PublisherTableMap::COL_ID, $hp->getPublisher()->getId());
        $c->setSingleRecord(true);
        BookTableMap::doDelete($c);

        // Checking to make sure correct records were removed.
        $this->assertEquals(3, AuthorQuery::create()->count(), 'Correct records were removed from author table');
        $this->assertEquals(3, PublisherQuery::create()->count(), 'Correct records were removed from publisher table');
        $this->assertEquals(3, BookQuery::create()->count(), 'Correct records were removed from book table');

        // Attempting to delete books by complex criteria
        $c = new Criteria();
        $cn = $c->getNewCriterion(BookTableMap::COL_ISBN, "043935806X");
        $cn->addOr($c->getNewCriterion(BookTableMap::COL_ISBN, "0380977427"));
        $cn->addOr($c->getNewCriterion(BookTableMap::COL_ISBN, "0140422161"));
        $c->add($cn);
        BookTableMap::doDelete($c);

        // Attempting to delete book [id = $td_id]
        $td->delete();

        // Attempting to delete authors
        AuthorTableMap::doDelete($stephenson_id);
        AuthorTableMap::doDelete($byron_id);
        $grass->delete();

        // Attempting to delete publishers
        PublisherTableMap::doDelete($morrow_id);
        PublisherTableMap::doDelete($penguin_id);
        $vintage->delete();

        // These have to be deleted manually also since we have onDelete
        // set to SETNULL in the foreign keys in book. Is this correct?
        $rowling->delete();
        $scholastic->delete();
        $blc1->delete();
        $blc2->delete();

        $this->assertCount(0, AuthorQuery::create()->find(), 'no records in [author] table');
        $this->assertCount(0, PublisherQuery::create()->find(), 'no records in [publisher] table');
        $this->assertCount(0, BookQuery::create()->find(), 'no records in [book] table');
        $this->assertCount(0, ReviewQuery::create()->find(), 'no records in [review] table');
        $this->assertCount(0, MediaQuery::create()->find(), 'no records in [media] table');
        $this->assertCount(0, BookClubListQuery::create()->find(), 'no records in [book_club_list] table');
        $this->assertCount(0, BookListRelQuery::create()->find(), 'no records in [book_x_list] table');
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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

        // Testing count() functionality
        // -------------------------------

        $records = BookQuery::create()->find();
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
        $crit->add(BookTableMap::COL_ID, $phoenix->getId());
        $phoenix = BookQuery::create(null, $crit)->findOne();
        $this->assertNotNull($phoenix, "book 'phoenix' has been re-fetched from db");

        $crit = new Criteria();
        $crit->add(BookClubListTableMap::COL_ID, $blc1->getId());
        $blc1 = BookClubListQuery::create(null, $crit)->findOne();
        $this->assertNotNull($blc1, 'BookClubList 1 has been re-fetched from db');

        $crit = new Criteria();
        $crit->add(BookClubListTableMap::COL_ID, $blc2->getId());
        $blc2 = BookClubListQuery::create(null, $crit)->findOne();
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
        $c->add(BookTableMap::COL_ID, $hp->getId());
        // The only way for cascading to work currently
        // is to specify the author_id and publisher_id (i.e. the fkeys
        // have to be in the criteria).
        $c->add(AuthorTableMap::COL_ID, $hp->getAuthor()->getId());
        $c->add(PublisherTableMap::COL_ID, $hp->getPublisher()->getId());
        $c->setSingleRecord(true);
        BookTableMap::doDelete($c);

        // Checking to make sure correct records were removed.
        $this->assertEquals(3, AuthorQuery::create()->count(), 'Correct records were removed from author table');
        $this->assertEquals(3, PublisherQuery::create()->count(), 'Correct records were removed from publisher table');
        $this->assertEquals(3, BookQuery::create()->count(), 'Correct records were removed from book table');

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

        $this->assertCount(0, AuthorQuery::create()->find(), 'no records in [author] table');
        $this->assertCount(0, PublisherQuery::create()->find(), 'no records in [publisher] table');
        $this->assertCount(0, BookQuery::create()->find(), 'no records in [book] table');
        $this->assertCount(0, ReviewQuery::create()->find(), 'no records in [review] table');
        $this->assertCount(0, MediaQuery::create()->find(), 'no records in [media] table');
        $this->assertCount(0, BookClubListQuery::create()->find(), 'no records in [book_club_list] table');
        $this->assertCount(0, BookListRelQuery::create()->find(), 'no records in [book_x_list] table');
    }
}
