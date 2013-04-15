<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\Bookstore\AcctAccessRole;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Bookstore;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeAccountTableMap;
use Propel\Tests\Bookstore\BookstoreCashier;
use Propel\Tests\Bookstore\BookstoreManager;
use Propel\Tests\Bookstore\BookOpinion;
use Propel\Tests\Bookstore\BookReader;
use Propel\Tests\Bookstore\BookstoreContest;
use Propel\Tests\Bookstore\Map\BookstoreContestTableMap;
use Propel\Tests\Bookstore\BookstoreContestEntry;
use Propel\Tests\Bookstore\BookstoreContestEntryQuery;
use Propel\Tests\Bookstore\Map\BookstoreContestEntryTableMap;
use Propel\Tests\Bookstore\Contest;
use Propel\Tests\Bookstore\Customer;
use Propel\Tests\Bookstore\ReaderFavorite;
use Propel\Tests\Bookstore\ReaderFavoriteQuery;
use Propel\Tests\Bookstore\Map\ReaderFavoriteTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

/**
 * Tests the generated Query classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * query operations.
 *
 * The database is reloaded before every test and flushed after every test.  This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class.  See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @see        BookstoreDataPopulator
 * @author Hans Lellelid <hans@xmpl.org>
 */
class GeneratedQueryDoSelectTest extends BookstoreEmptyTestBase
{
    protected function setUp()
    {
        $this->markTestSkipped('not used anymore look if all tests are present in Query');
        parent::setUp();
        BookstoreDataPopulator::populate();
    }

    public function testDoSelect()
    {
        $books = BookQuery::create()->doSelect(new Criteria());
        $this->assertEquals(4, count($books), 'doSelect() with an empty Criteria returns all results');
        $book1 = $books[0];

        $c = new Criteria();
        $c->add(BookTableMap::ID, $book1->getId());
        $res = BookQuery::create()->doSelect($c);
        $this->assertEquals(array($book1), $res, 'doSelect() accepts a Criteria object with a condition');

        $c = new Criteria();
        $c->add(BookTableMap::ID, $book1->getId());
        $c->add(BookTableMap::TITLE, $book1->getTitle());
        $res = BookQuery::create()->doSelect($c);
        $this->assertEquals(array($book1), $res, 'doSelect() accepts a Criteria object with several condition');

        $c = new Criteria();
        $c->add(BookTableMap::ID, 'foo');
        $res = BookQuery::create()->doSelect($c);
        $this->assertEquals(array(), $res, 'doSelect() accepts an incorrect Criteria');
    }

    /**
     * Tests performing doSelect() and doSelectJoin() using LIMITs.
     */
    public function testDoSelect_Limit()
    {
        // 1) get the total number of items in a particular table
        $count = BookQuery::create()->count();

        $this->assertTrue($count > 1, "Need more than 1 record in books table to perform this test.");

        $limitcount = $count - 1;

        $lc = new Criteria();
        $lc->setLimit($limitcount);

        $results = BookQuery::create(null, $lc)->find();

        $this->assertEquals($limitcount, count($results), "Expected $limitcount results from BookQuery::doSelect()");

        // re-create it just to avoid side-effects
        $lc2 = new Criteria();
        $lc2->setLimit($limitcount);
        $results2 = BookQuery::create(null, $lc2)->joinWith('Author')->find();

        $this->assertEquals($limitcount, count($results2), "Expected $limitcount results from BookQuery::doSelectJoinAuthor()");

    }

    /**
     * Test the basic functionality of the doSelectJoin*() methods.
     */
    public function testDoSelectJoin()
    {

        BookTableMap::clearInstancePool();

        $c = new Criteria();

        $books = BookQuery::create()->doSelect($c);
        $obj = $books[0];
        // $size = strlen(serialize($obj));

        BookTableMap::clearInstancePool();

        $joinBooks = BookQuery::create()->joinWith('Author')->find();
        $obj2 = $joinBooks[0];
        $obj2Array = $obj2->toArray(TableMap::TYPE_PHPNAME, true, array(), true);
        // $joinSize = strlen(serialize($obj2));

        $this->assertEquals(count($books), count($joinBooks), "Expected to find same number of rows in doSelectJoin*() call as doSelect() call.");

        // $this->assertTrue($joinSize > $size, "Expected a serialized join object to be larger than a non-join object.");

        $this->assertTrue(array_key_exists('Author', $obj2Array));
    }

    public function testObjectInstances()
    {
        $sample = BookQuery::create()->findOne();
        $samplePk = $sample->getPrimaryKey();

        // 1) make sure consecutive calls to retrieveByPK() return the same object.

        $b1 = BookQuery::create()->findPk($samplePk);
        $b2 = BookQuery::create()->findPk($samplePk);

        $sampleval = md5(microtime());

        $this->assertTrue($b1 === $b2, "Expected object instances to match for calls with same retrieveByPK() method signature.");

        // 2) make sure that calls to doSelect also return references to the same objects.
        $allbooks = BookQuery::create()->doSelect(new Criteria());
        foreach ($allbooks as $testb) {
            if ($testb->getPrimaryKey() == $b1->getPrimaryKey()) {
                $this->assertTrue($testb === $b1, "Expected same object instance from doSelect() as from retrieveByPK()");
            }
        }

        // 3) test fetching related objects
        $book = BookQuery::create()->findPk($samplePk);

        $bookauthor = $book->getAuthor();

        $author = AuthorQuery::create()->findPk($bookauthor->getId());

        $this->assertTrue($bookauthor === $author, "Expected same object instance when calling fk object accessor as retrieveByPK()");

        // 4) test a doSelectJoin()
        $morebooks = BookQuery::create()->joinWith('Author')->find();
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

        $this->assertSame($b, BookstoreEmployeeQuery::create()->findPk($empId), "Expected newly saved object to be same instance as pooled.");

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
        $c->add(BookstoreEmployeeTableMap::ID, array($managerId, $empId, $cashierId), Criteria::IN);
        $c->addAscendingOrderByColumn(BookstoreEmployeeTableMap::ID);

        $objects = BookstoreEmployeeQuery::create()->doSelect($c);

        $this->assertEquals(3, count($objects), "Expected 3 objects to be returned.");

        list($o1, $o2, $o3) = $objects;

        $this->assertSame($o1, $manager);
        $this->assertSame($o2, $employee);
        $this->assertSame($o3, $cashier);

        // 2) test a forced reload from database
        BookstoreEmployeeTableMap::clearInstancePool();

        list($o1,$o2,$o3) = BookstoreEmployeeQuery::create()->doSelect($c);

        $this->assertTrue($o1 instanceof BookstoreManager, "Expected BookstoreManager object, got " . get_class($o1));
        $this->assertTrue($o2 instanceof BookstoreEmployee, "Expected BookstoreEmployee object, got " . get_class($o2));
        $this->assertTrue($o3 instanceof BookstoreCashier, "Expected BookstoreCashier object, got " . get_class($o3));

    }

    /**
     * Test hydration of joined rows that contain lazy load columns.
     * @link       http://propel.phpdb.org/trac/ticket/464
     */
    public function testHydrationJoinLazyLoad()
    {
        BookstoreEmployeeAccountTableMap::doDeleteAll();
        BookstoreEmployeeTableMap::doDeleteAll();
        AcctAccessRoleTableMap::doDeleteAll();

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

        $results = BookstoreEmployeeAccountQuery::create()->find();
        $o = $results[0];

        $this->assertEquals('Admin', $o->getAcctAccessRole()->getName());
    }

    /**
     * Testing foreign keys with multiple referrer columns.
     * @link       http://propel.phpdb.org/trac/ticket/606
     */
    public function testMultiColFk()
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);

        ReaderFavoriteTableMap::doDeleteAll();

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

        $c = new Criteria(ReaderFavoriteTableMap::DATABASE_NAME);
        $c->add(ReaderFavoriteTableMap::BOOK_ID, $b1->getId());
        $c->add(ReaderFavoriteTableMap::READER_ID, $r1->getId());

        $results = ReaderFavoriteQuery::create(null, $c)->joinWith('BookOpinion')->find();
        $this->assertEquals(1, count($results), "Expected 1 result");
    }

    /**
     * Testing foreign keys with multiple referrer columns.
     * @link       http://propel.phpdb.org/trac/ticket/606
     */
    public function testMultiColJoin()
    {
        BookstoreContestTableMap::doDeleteAll();
        BookstoreContestEntryTableMap::doDeleteAll();

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
        $c->addJoin(array(BookstoreContestEntryTableMap::BOOKSTORE_ID, BookstoreContestEntryTableMap::CONTEST_ID), array(BookstoreContestTableMap::BOOKSTORE_ID, BookstoreContestTableMap::CONTEST_ID) );

        $results = BookstoreContestEntryQuery::create(null, $c)->find();
        $this->assertEquals(2, count($results) );
        foreach ($results as $result) {
            $this->assertEquals($bs1Id, $result->getBookstoreId() );
            $this->assertEquals($ct1Id, $result->getContestId() );
        }
    }
}
