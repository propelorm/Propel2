<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Bookstore\BookstoreQuery;
use Propel\Tests\Bookstore\BookstoreSale;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\MediaTableMap;
use Propel\Tests\Bookstore\Map\PublisherTableMap;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Bookstore\MediaQuery;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Bookstore\ReviewQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Tests the generated Object classes.
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
class GeneratedObjectWithFixturesTest extends BookstoreEmptyTestBase
{
    /**
     * Test the reload() method.
     *
     * @return void
     */
    public function testReload()
    {
        BookstoreDataPopulator::populate();
        $a = AuthorQuery::create()->findOne();

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
     *
     * @return void
     */
    public function testReloadDeep()
    {
        BookstoreDataPopulator::populate();

        // arbitrary book
        $b = BookQuery::create()->findOne();

        // arbitrary, different author
        $a = AuthorQuery::create()->filterById($b->getAuthorId(), Criteria::NOT_EQUAL)->findOne();

        $origAuthor = $b->getAuthor();

        $b->setAuthor($a);

        $this->assertNotEquals($origAuthor, $b->getAuthor(), 'Expected just-set object to be different from obj from DB');
        $this->assertTrue($b->isModified());

        $b->reload($deep = true);

        $this->assertEquals($origAuthor, $b->getAuthor(), 'Expected object in DB to be restored');
        $this->assertFalse($a->isModified());
    }

    /**
     * Test deleting an object using the delete() method.
     *
     * @return void
     */
    public function testDelete()
    {
        BookstoreDataPopulator::populate();

        // 1) grab an arbitrary object
        $book = BookQuery::create()->findOne();
        $bookId = $book->getId();

        // 2) delete it
        $book->delete();

        // 3) make sure it can't be save()d now that it's deleted
        try {
            $book->setTitle('Will Fail');
            $book->save();
            $this->fail('Expect an exception to be thrown when attempting to save() a deleted object.');
        } catch (PropelException $e) {
        }

            // 4) make sure that it doesn't exist in db
            $book = BookQuery::create()->findPk($bookId);
        $this->assertNull($book, 'Expect NULL from retrieveByPK on deleted Book.');
    }

    /**
     * Tests new one-to-one functionality.
     *
     * @return void
     */
    public function testOneToOne()
    {
        BookstoreDataPopulator::populate();

        $emp = BookstoreEmployeeQuery::create()->findOne();

        $acct = new BookstoreEmployeeAccount();
        $acct->setBookstoreEmployee($emp);
        $acct->setLogin('testuser');
        $acct->setPassword('testpass');

        $this->assertSame($emp->getBookstoreEmployeeAccount(), $acct, 'Expected same object instance.');
    }

    /**
     * Test the type sensitivity of the returning columns.
     *
     * @return void
     */
    public function testTypeSensitive()
    {
        BookstoreDataPopulator::populate();

        $book = BookQuery::create()->findOne();

        $r = new Review();
        $r->setReviewedBy('testTypeSensitive Tester');
        $r->setReviewDate(time());
        $r->setBook($book);
        $r->setRecommended(true);
        $r->save();

        $id = $r->getId();
        unset($r);

        // clear the instance cache to force reload from database.
        ReviewTableMap::clearInstancePool();
        BookTableMap::clearInstancePool();

        // reload and verify that the types are the same
        $r2 = ReviewQuery::create()->findPk($id);

        $this->assertIsInt($r2->getId(), 'Expected getId() to return an integer.');
        $this->assertIsString($r2->getReviewedBy(), 'Expected getReviewedBy() to return a string.');
        $this->assertIsBool($r2->getRecommended(), 'Expected getRecommended() to return a boolean.');
        $this->assertInstanceOf('\Propel\Tests\Bookstore\Book', $r2->getBook(), 'Expected getBook() to return a Book.');
        $this->assertIsFloat($r2->getBook()->getPrice(), 'Expected Book->getPrice() to return a float.');
        $this->assertInstanceOf('\DateTime', $r2->getReviewDate(null), 'Expected Book->getReviewDate() to return a DateTime.');
    }

    /**
     * This is a test for expected exceptions when saving UNIQUE.
     * See http://propel.phpdb.org/trac/ticket/2
     *
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testSaveUnique()
    {
        // The whole test is in a transaction, but this test needs real transactions
        $this->con->commit();

        $emp = new BookstoreEmployee();
        $emp->setName(md5(microtime()));

        $acct = new BookstoreEmployeeAccount();
        $acct->setBookstoreEmployee($emp);
        $acct->setLogin('foo');
        $acct->setPassword('bar');
        $acct->save();

        // now attempt to create a new acct
        $acct2 = $acct->copy();

        try {
            $acct2->save();
            $this->fail('Expected PropelException in first attempt to save object with duplicate value for UNIQUE constraint.');
        } catch (Exception $x) {
            try {
                // attempt to save it again
                $acct3 = $acct->copy();
                $acct3->save();
                $this->fail('Expected PropelException in second attempt to save object with duplicate value for UNIQUE constraint.');
            } catch (Exception $x) {
                // this is expected.
            }
            // now let's double check that it can succeed if we're not violating the constraint.
            $acct3->setLogin('foo2');
            $acct3->save();
        }

        $this->con->beginTransaction();
    }

    /**
     * Test the BaseObject#equals().
     *
     * @return void
     */
    public function testEquals()
    {
        BookstoreDataPopulator::populate();

        $b = BookQuery::create()->findOne();
        $c = new Book();
        $c->setId($b->getId());
        $this->assertTrue($b->equals($c), 'Expected Book objects to be equal()');

        $a = new Author();
        $a->setId($b->getId());
        $this->assertFalse($b->equals($a), 'Expected Book and Author with same primary key NOT to match.');
    }

    /**
     * @return void
     */
    public function testDefaultFkColVal()
    {
        BookstoreDataPopulator::populate();

        $sale = new BookstoreSale();
        $this->assertEquals(1, $sale->getBookstoreId(), 'Expected BookstoreSale object to have a default bookstore_id of 1.');

        $bookstore = BookstoreQuery::create()->findOne();

        $sale->setBookstore($bookstore);
        $this->assertEquals($bookstore->getId(), $sale->getBookstoreId(), 'Expected FK id to have changed when assigned a valid FK.');

        $sale->setBookstore(null);
        $this->assertEquals(1, $sale->getBookstoreId(), 'Expected BookstoreSale object to have reset to default ID.');

        $sale->setPublisher(null);
        $this->assertEquals(null, $sale->getPublisherId(), 'Expected BookstoreSale object to have reset to NULL publisher ID.');
    }

    /**
     * Test copyInto method.
     *
     * @return void
     */
    public function testCopyInto_Deep()
    {
        BookstoreDataPopulator::populate();

        // Test a "normal" object
        $book = BookQuery::create()->filterByTitle('Harry%', Criteria::LIKE)->findOne();
        $reviews = $book->getReviews();

        $b2 = $book->copy(true);
        $this->assertInstanceOf('\Propel\Tests\Bookstore\Book', $b2);
        $this->assertNull($b2->getId());

        $r2 = $b2->getReviews();

        $this->assertEquals(count($reviews), count($r2));

        // Test a one-to-one object
        $emp = BookstoreEmployeeQuery::create()->findOne();
        $e2 = $emp->copy(true);

        $this->assertInstanceOf('\Propel\Tests\Bookstore\BookstoreEmployee', $e2);
        $this->assertNull($e2->getId());

        $this->assertEquals($emp->getBookstoreEmployeeAccount()->getLogin(), $e2->getBookstoreEmployeeAccount()->getLogin());
    }

    /**
     * Test the toArray() method with new lazyLoad param.
     *
     * @link http://propel.phpdb.org/trac/ticket/527
     *
     * @return void
     */
    public function testToArrayLazyLoad()
    {
        BookstoreDataPopulator::populate();

        $m = MediaQuery::create()
            ->filterByCoverImage(null, Criteria::NOT_EQUAL)
            ->filterByExcerpt(null, Criteria::NOT_EQUAL)
            ->findOne();
        if ($m === null) {
            $this->fail('Test requires at least one media row w/ cover_image and excerpt NOT NULL');
        }

        $arr1 = $m->toArray(TableMap::TYPE_COLNAME);
        $this->assertNotNull($arr1[MediaTableMap::COL_COVER_IMAGE]);
        $this->assertIsResource($arr1[MediaTableMap::COL_COVER_IMAGE]);

        $arr2 = $m->toArray(TableMap::TYPE_COLNAME, false);
        $this->assertNull($arr2[MediaTableMap::COL_COVER_IMAGE]);
        $this->assertNull($arr2[MediaTableMap::COL_EXCERPT]);

        $diffKeys = array_keys(array_diff($arr1, $arr2));

        $expectedDiff = [MediaTableMap::COL_COVER_IMAGE, MediaTableMap::COL_EXCERPT];

        $this->assertEquals($expectedDiff, $diffKeys);
    }

    /**
     * @return void
     */
    public function testToArrayIncludesForeignObjects()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        PublisherTableMap::clearInstancePool();

        $c = new Criteria();
        $c->add(BookTableMap::COL_TITLE, 'Don Juan');
        $books = BookQuery::create(null, $c)->joinWith('Author')->find();
        $book = $books[0];

        $arr1 = $book->toArray(TableMap::TYPE_PHPNAME, false, [], true);
        $expectedKeys = [
            'Id',
            'Title',
            'ISBN',
            'Price',
            'PublisherId',
            'AuthorId',
            'Author',
        ];
        $this->assertEquals($expectedKeys, array_keys($arr1), 'toArray() can return sub arrays for hydrated related objects');
        $this->assertEquals('George', $arr1['Author']['FirstName'], 'toArray() can return sub arrays for hydrated related objects');
    }

    /**
     * @return void
     */
    public function testToArrayIncludesForeignReferrers()
    {
        $a1 = new Author();
        $a1->setFirstName('Leo');
        $a1->setLastName('Tolstoi');
        $arr = $a1->toArray(TableMap::TYPE_PHPNAME, false, [], true);
        $this->assertFalse(array_key_exists('Books', $arr));
        $b1 = new Book();
        $b1->setTitle('War and Peace');
        $b2 = new Book();
        $b2->setTitle('Anna Karenina');
        $a1->addBook($b1);
        $a1->addBook($b2);
        $arr = $a1->toArray(TableMap::TYPE_PHPNAME, false, [], true);
        $this->assertTrue(array_key_exists('Books', $arr));
        $this->assertEquals(2, count($arr['Books']));
        $this->assertEquals('War and Peace', $arr['Books'][0]['Title']);
        $this->assertEquals('Anna Karenina', $arr['Books'][1]['Title']);
        $this->assertEquals(['*RECURSION*'], $arr['Books'][0]['Author']);
    }
}
