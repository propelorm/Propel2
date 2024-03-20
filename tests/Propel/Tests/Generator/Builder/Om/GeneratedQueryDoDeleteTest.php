<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookOpinion;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookReader;
use Propel\Tests\Bookstore\BookReaderQuery;
use Propel\Tests\Bookstore\Bookstore;
use Propel\Tests\Bookstore\BookstoreContest;
use Propel\Tests\Bookstore\BookstoreContestEntry;
use Propel\Tests\Bookstore\BookstoreContestEntryQuery;
use Propel\Tests\Bookstore\Contest;
use Propel\Tests\Bookstore\Customer;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookReaderTableMap;
use Propel\Tests\Bookstore\Map\BookstoreContestTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\PublisherTableMap;
use Propel\Tests\Bookstore\Map\ReaderFavoriteTableMap;
use Propel\Tests\Bookstore\MediaQuery;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\PublisherQuery;
use Propel\Tests\Bookstore\ReaderFavorite;
use Propel\Tests\Bookstore\ReaderFavoriteQuery;
use Propel\Tests\Bookstore\ReviewQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Tests the delete methods of the generated Query classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * query operations.
 *
 * The database is reloaded before every test and flushed after every test. This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class. See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 *
 * @group database
 */
class GeneratedQueryDoDeleteTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    /**
     * Test ability to delete multiple rows via single Criteria object.
     *
     * @return void
     */
    public function testDoDelete_MultiTable()
    {
        $hp = BookQuery::create()->filterByTitle('Harry Potter and the Order of the Phoenix')->findOne();

        // print "Attempting to delete [multi-table] by found pk: ";
        $c = new Criteria();
        $c->add(BookTableMap::COL_ID, $hp->getId());
        // The only way for multi-delete to work currently
        // is to specify the author_id and publisher_id (i.e. the fkeys
        // have to be in the criteria).
        $c->add(AuthorTableMap::COL_ID, $hp->getAuthorId());
        $c->add(PublisherTableMap::COL_ID, $hp->getPublisherId());
        $c->setSingleRecord(true);
        BookTableMap::doDelete($c);

        // check to make sure the right # of records was removed
        $this->assertCount(3, AuthorQuery::create()->find(), 'Expected 3 authors after deleting.');
        $this->assertCount(3, PublisherQuery::create()->find(), 'Expected 3 publishers after deleting.');
        $this->assertCount(3, BookQuery::create()->find(), 'Expected 3 books after deleting.');
    }

    /**
     * Test using a complex criteria to delete multiple rows from a single table.
     *
     * @return void
     */
    public function testDoDelete_ComplexCriteria()
    {
        //print "Attempting to delete books by complex criteria: ";
        $c = new Criteria();
        $cn = $c->getNewCriterion(BookTableMap::COL_ISBN, '043935806X');
        $cn->addOr($c->getNewCriterion(BookTableMap::COL_ISBN, '0380977427'));
        $cn->addOr($c->getNewCriterion(BookTableMap::COL_ISBN, '0140422161'));
        $c->add($cn);
        BookTableMap::doDelete($c);

        // now there should only be one book left; "The Tin Drum"

        $books = BookQuery::create()->find();

        $this->assertEquals(1, count($books), 'Expected 1 book remaining after deleting.');
        $this->assertEquals('The Tin Drum', $books[0]->getTitle(), "Expect the only remaining book to be 'The Tin Drum'");
    }

    /**
     * Test that cascading deletes are happening correctly (whether emulated or native).
     *
     * @return void
     */
    public function testDoDelete_Cascade_Simple()
    {
        // The 'media' table will cascade from book deletes

        // 1) Assert the row exists right now

        $medias = MediaQuery::create()->find();
        $this->assertTrue(count($medias) > 0, "Expected to find at least one row in 'media' table.");
        $media = $medias[0];
        $mediaId = $media->getId();

        // 2) Delete the owning book

        $owningBookId = $media->getBookId();
        BookTableMap::doDelete($owningBookId);

        // 3) Assert that the media row is now also gone

        $obj = MediaQuery::create()->findPk($mediaId);
        $this->assertNull($obj, 'Expect NULL when retrieving on no matching Media.');
    }

    /**
     * Test that cascading deletes are happening correctly for composite pk.
     *
     * @link http://propel.phpdb.org/trac/ticket/544
     *
     * @return void
     */
    public function testDoDelete_Cascade_CompositePK()
    {
        $origBceCount = BookstoreContestEntryQuery::create()->count();

        $cust1 = new Customer();
        $cust1->setName('Cust1');
        $cust1->save();

        $cust2 = new Customer();
        $cust2->setName('Cust2');
        $cust2->save();

        $c1 = new Contest();
        $c1->setName('Contest1');
        $c1->save();

        $c2 = new Contest();
        $c2->setName('Contest2');
        $c2->save();

        $store1 = new Bookstore();
        $store1->setStoreName('Store1');
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
        $bce1->setEntryDate('now');
        $bce1->setCustomer($cust1);
        $bce1->setBookstoreContest($bc1);
        $bce1->save();

        $bce2 = new BookstoreContestEntry();
        $bce2->setEntryDate('now');
        $bce2->setCustomer($cust1);
        $bce2->setBookstoreContest($bc2);
        $bce2->save();

        // Now, if we remove $bc1, we expect *only* bce1 to be no longer valid.

        BookstoreContestTableMap::doDelete($bc1);

        $newCount = BookstoreContestEntryQuery::create()->count();

        $this->assertEquals($origBceCount + 1, $newCount, 'Expected new number of rows in BCE to be orig + 1');

        $bcetest = BookstoreContestEntryQuery::create()->findPk([$store1->getId(), $c1->getId(), $cust1->getId()]);
        $this->assertNull($bcetest, 'Expected BCE for store1 to be cascade deleted.');

        $bcetest2 = BookstoreContestEntryQuery::create()->findPk([$store1->getId(), $c2->getId(), $cust1->getId()]);
        $this->assertNotNull($bcetest2, 'Expected BCE for store2 to NOT be cascade deleted.');
    }

    /**
     * Test that onDelete="SETNULL" is happening correctly (whether emulated or native).
     *
     * @return void
     */
    public function testDoDelete_SetNull()
    {
        // The 'author_id' column in 'book' table will be set to null when author is deleted.

        // 1) Get an arbitrary book
        $book = BookQuery::create()->findOne();
        $bookId = $book->getId();
        $authorId = $book->getAuthorId();
        unset($book);

        // 2) Delete the author for that book
        AuthorTableMap::doDelete($authorId);

        // 3) Assert that the book.author_id column is now NULL

        $book = BookQuery::create()->findPk($bookId);
        $this->assertNull($book->getAuthorId(), 'Expect the book.author_id to be NULL after the author was removed.');
    }

    /**
     * Test deleting a row by passing in the primary key to the doDelete() method.
     *
     * @return void
     */
    public function testDoDelete_ByPK()
    {
        // 1) get an arbitrary book
        $book = BookQuery::create()->findOne();
        $bookId = $book->getId();

        // 2) now delete that book
        BookTableMap::doDelete($bookId);

        // 3) now make sure it's gone
        $obj = BookQuery::create()->findPk($bookId);
        $this->assertNull($obj, 'Expect NULL when retrieving on no matching Book.');
    }

    /**
     * @return void
     */
    public function testDoDelete_ByPks()
    {
        // 1) get all of the books
        $books = BookQuery::create()->find();
        $bookCount = count($books);

        // 2) we have enough books to do this test
        $this->assertGreaterThan(1, $bookCount, 'There are at least two books');

        // 3) select two random books
        $book1 = $books[0];
        $book2 = $books[1];

        // 4) delete the books
        BookTableMap::doDelete([$book1->getId(), $book2->getId()]);

        // 5) we should have two less books than before
        $this->assertEquals($bookCount - 2, BookQuery::create()->count(), 'Two books deleted successfully.');
    }

    /**
     * Test deleting a row by passing the generated object to doDelete().
     *
     * @return void
     */
    public function testDoDelete_ByObj()
    {
        // 1) get an arbitrary book
        $book = BookQuery::create()->findOne();
        $bookId = $book->getId();

        // 2) now delete that book
        BookTableMap::doDelete($book);

        // 3) now make sure it's gone
        $obj = BookQuery::create()->findPk($bookId);
        $this->assertNull($obj, 'Expect NULL when retrieving on no matching Book.');
    }

    /**
     * Test the doDeleteAll() method for single table.
     *
     * @return void
     */
    public function testDoDeleteAll()
    {
        BookTableMap::doDeleteAll();
        $this->assertCount(0, BookQuery::create()->find(), 'Expect all book rows to have been deleted.');
    }

    /**
     * Test the state of the instance pool after a doDeleteAll() call.
     *
     * @return void
     */
    public function testDoDeleteAllInstancePool()
    {
        $review = ReviewQuery::create()->findOne();
        $book = $review->getBook();
        BookTableMap::doDeleteAll();
        $this->assertNull(BookQuery::create()->findPk($book->getId()), 'doDeleteAll invalidates instance pool');
        $this->assertNull(ReviewQuery::create()->findPk($review->getId()), 'doDeleteAll invalidates instance pool of related tables with ON DELETE CASCADE');
    }

    /**
     * Test the doDeleteAll() method when onDelete="CASCADE".
     *
     * @return void
     */
    public function testDoDeleteAll_Cascade()
    {
        BookTableMap::doDeleteAll();
        $this->assertCount(0, MediaQuery::create()->find(), 'Expect all media rows to have been cascade deleted.');
        $this->assertCount(0, ReviewQuery::create()->find(), 'Expect all review rows to have been cascade deleted.');
    }

    /**
     * Test the doDeleteAll() method when onDelete="SETNULL".
     *
     * @return void
     */
    public function testDoDeleteAll_SetNull()
    {
        $c = new Criteria();
        $c->add(BookTableMap::COL_AUTHOR_ID, null, Criteria::NOT_EQUAL);

        // 1) make sure there are some books with valid authors
        $this->assertGreaterThan(0, count(BookQuery::create()->filterByAuthorId(null, Criteria::NOT_EQUAL)->find()) > 0, 'Expect some book.author_id columns that are not NULL.');

        // 2) delete all the authors
        AuthorTableMap::doDeleteAll();

        // 3) now verify that the book.author_id columns are all null
        $this->assertCount(0, BookQuery::create()->filterByAuthorId(null, Criteria::NOT_EQUAL)->find(), 'Expect all book.author_id columns to be NULL.');
    }

    /**
     * @link http://propel.phpdb.org/trac/ticket/519
     *
     * @return void
     */
    public function testDoDeleteCompositePK()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        ReaderFavoriteTableMap::doDeleteAll();
        // Create books with IDs 1 to 3
        // Create readers with IDs 1 and 2

        $this->createBookWithId(1);
        $this->createBookWithId(2);
        $this->createBookWithId(3);
        $this->createReaderWithId(1);
        $this->createReaderWithId(2);

        for ($i = 1; $i <= 3; $i++) {
            for ($j = 1; $j <= 2; $j++) {
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

        $this->assertEquals(6, ReaderFavoriteQuery::create()->count());

        // Now delete 2 of those rows (2 is special in that it is the number of rows
        // being deleted, as well as the number of things in the primary key)
        ReaderFavoriteTableMap::doDelete([[1, 1], [2, 2]]);
        $this->assertEquals(4, ReaderFavoriteQuery::create()->count());

        //Note: these composite PK's are pairs of (BookId, ReaderId)
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([2, 1]));
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([1, 2]));
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([3, 1]));
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([3, 2]));
        $this->assertNull(ReaderFavoriteQuery::create()->findPk([1, 1]));
        $this->assertNull(ReaderFavoriteQuery::create()->findPk([2, 2]));

        //test deletion of a single composite PK
        ReaderFavoriteTableMap::doDelete([3, 1]);
        $this->assertEquals(3, ReaderFavoriteQuery::create()->count());
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([2, 1]));
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([1, 2]));
        $this->assertNotNull(ReaderFavoriteQuery::create()->findPk([3, 2]));
        $this->assertNull(ReaderFavoriteQuery::create()->findPk([1, 1]));
        $this->assertNull(ReaderFavoriteQuery::create()->findPk([2, 2]));
        $this->assertNull(ReaderFavoriteQuery::create()->findPk([3, 1]));

        //test deleting the last three
        ReaderFavoriteTableMap::doDelete([[2, 1], [1, 2], [3, 2]]);
        $this->assertEquals(0, ReaderFavoriteQuery::create()->count());
    }

    /**
     * Test the doInsert() method when passed a Criteria object.
     *
     * @return void
     */
    public function testDoInsert_Criteria()
    {
        $name = 'A Sample Publisher - ' . time();

        $values = new Criteria();
        $values->add(PublisherTableMap::COL_NAME, $name);
        PublisherTableMap::doInsert($values);

        $matches = PublisherQuery::create()->filterByName($name)->find();
        $this->assertCount(1, $matches, 'Expect there to be exactly 1 publisher just-inserted.');
        $this->assertTrue(1 != $matches[0]->getId(), 'Expected to have different ID than one put in values Criteria.');
    }

    /**
     * Test the doInsert() method when passed a generated object.
     *
     * @return void
     */
    public function testDoInsert_Obj()
    {
        $name = 'A Sample Publisher - ' . time();

        $values = new Publisher();
        $values->setName($name);
        PublisherTableMap::doInsert($values);

        $matches = PublisherQuery::create()->filterByName($name)->find();
        $this->assertCount(1, $matches, 'Expect there to be exactly 1 publisher just-inserted.');
        $this->assertTrue(1 != $matches[0]->getId(), 'Expected to have different ID than one put in values Criteria.');
    }

    /**
     * Test passing null values to removeInstanceFromPool().
     *
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testRemoveInstanceFromPool_Null()
    {
        // if it throws an exception, then it's broken.
        try {
            BookTableMap::removeInstanceFromPool(null);
        } catch (Exception $x) {
            $this->fail('Expected to get no exception when removing an instance from the pool.');
        }
    }

    /**
     * @see testDoDeleteCompositePK()
     *
     * @return void
     */
    private function createBookWithId($id)
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $b = BookQuery::create()->findPk($id);
        if (!$b) {
            $b = new Book();
            $b->setTitle("Book$id")->setISBN("BookISBN$id")->save();
            $b1Id = $b->getId();
            $sql = 'UPDATE ' . BookTableMap::TABLE_NAME . ' SET id = ? WHERE id = ?';
            $stmt = $con->prepare($sql);
            $stmt->bindValue(1, $id);
            $stmt->bindValue(2, $b1Id);
            $stmt->execute();
        }
    }

    /**
     * @see testDoDeleteCompositePK()
     *
     * @return void
     */
    private function createReaderWithId($id)
    {
        $con = Propel::getServiceContainer()->getConnection(BookReaderTableMap::DATABASE_NAME);
        $r = BookReaderQuery::create()->findPk($id);
        if (!$r) {
            $r = new BookReader();
            $r->setName('Reader' . $id)->save();
            $r1Id = $r->getId();
            $sql = 'UPDATE ' . BookReaderTableMap::TABLE_NAME . ' SET id = ? WHERE id = ?';
            $stmt = $con->prepare($sql);
            $stmt->bindValue(1, $id);
            $stmt->bindValue(2, $r1Id);
            $stmt->execute();
        }
    }
}
