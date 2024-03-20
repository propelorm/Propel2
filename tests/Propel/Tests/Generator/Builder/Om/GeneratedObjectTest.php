<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use DateTime;
use Exception;
use MyNameSpace\TestKeyTypeTable;
use Propel\Generator\Config\QuickGeneratorConfig;
use Propel\Tests\Bookstore\BookClubList;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\AcctAuditLog;
use Propel\Tests\Bookstore\AcctAuditLogQuery;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookOpinion;
use Propel\Tests\Bookstore\BookOpinionQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookReader;
use Propel\Tests\Bookstore\Bookstore;
use Propel\Tests\Bookstore\BookstoreContest;
use Propel\Tests\Bookstore\BookstoreContestEntry;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Bookstore\BookstoreQuery;
use Propel\Tests\Bookstore\BookstoreSale;
use Propel\Tests\Bookstore\BookSummary;
use Propel\Tests\Bookstore\BookSummaryQuery;
use Propel\Tests\Bookstore\Contest;
use Propel\Tests\Bookstore\ContestQuery;
use Propel\Tests\Bookstore\Country;
use Propel\Tests\Bookstore\CountryQuery;
use Propel\Tests\Bookstore\CountryTranslationQuery;
use Propel\Tests\Bookstore\Customer;
use Propel\Tests\Bookstore\CustomerQuery;
use Propel\Tests\Bookstore\Map\AcctAuditLogTableMap;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeAccountTableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\ContestTableMap;
use Propel\Tests\Bookstore\Map\CustomerTableMap;
use Propel\Tests\Bookstore\Map\PublisherTableMap;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\PublisherQuery;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Common\BoxedString;
use Propel\Tests\Helpers\Bookstore\Behavior\TestAuthor;
use Propel\Tests\Helpers\Bookstore\Behavior\TestAuthorDeleteFalse;
use Propel\Tests\Helpers\Bookstore\Behavior\TestAuthorSaveFalse;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use ReflectionMethod;

/**
 * Tests the generated Object classes.
 *
 * This test uses generated Bookstore classes to test the behavior of various
 * object operations. The _idea_ here is to test every possible generated method
 * from Object.tpl; if necessary, bookstore will be expanded to accommodate this.
 *
 * The database is reloaded before every test and flushed after every test. This
 * means that you can always rely on the contents of the databases being the same
 * for each test method in this class. See the BookstoreDataPopulator::populate()
 * method for the exact contents of the database.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 *
 * @group database
 */
class GeneratedObjectTest extends BookstoreTestBase
{
    /**
     * Test saving an object after setting default values for it.
     *
     * @return void
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
        $this->assertTrue($pub->getId() !== null, 'Expect Publisher to have been saved when default value set.');

        // 2) check date/time values
        $review = new Review();
        // note that this is different from how it's represented in schema, but should resolve to same unix timestamp
        $review->setReviewDate('2001-01-01');
        $this->assertTrue($review->isModified(), "Expect Review to have been marked 'modified' after default date/time value set.");
    }

    /**
     * Test isModified() to be false after setting default value second time
     *
     * @return void
     */
    public function testDefaultValueSetTwice()
    {
        $pub = new Publisher();
        $pub->setName('Penguin');
        $pub->save();

        $pubId = $pub->getId();

        PublisherTableMap::clearInstancePool();

        $pub2 = PublisherQuery::create()->findPk($pubId);
        $pub2->setName('Penguin');
        $this->assertFalse($pub2->isModified(), 'Expect Publisher to be not modified after setting default value second time.');
    }

    /**
     * @return void
     */
    public function testHasApplyDefaultValues()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Publisher', 'applyDefaultValues'), 'Tables with default values should have an applyDefaultValues() method');
        $this->assertFalse(method_exists('\Propel\Tests\Bookstore\Book', 'applyDefaultValues'), 'Tables with no default values should not have an applyDefaultValues() method');
    }

    /**
     * Test default return values.
     *
     * @return void
     */
    public function testDefaultValues()
    {
        $r = new Review();
        $this->assertEquals('2001-01-01', $r->getReviewDate('Y-m-d'));

        $this->assertFalse($r->isModified(), 'expected isModified() to be false');

        $acct = new BookstoreEmployeeAccount();
        $this->assertEquals(true, $acct->getEnabled());
        $this->assertFalse($acct->isModified());

        $acct->setLogin('testuser');
        $acct->setPassword('testpass');
        $this->assertTrue($acct->isModified());
    }

    /**
     * Tests the use of default expressions and the reloadOnInsert and reloadOnUpdate attributes.
     *
     * @link http://propel.phpdb.org/trac/ticket/378
     * @link http://propel.phpdb.org/trac/ticket/555
     *
     * @return void
     */
    public function testDefaultExpressions()
    {
        if (Propel::getServiceContainer()->getAdapter(BookstoreEmployeeTableMap::DATABASE_NAME) instanceof SqliteAdapter) {
            $this->markTestSkipped('Cannot test default expressions with SQLite');
        }
        BookstoreEmployeeAccountTableMap::doDeleteAll();

        $b = new Bookstore();
        $b->setStoreName('Foo!');
        $b->save();

        $employee = new BookstoreEmployee();
        $employee->setName('Johnny Walker');

        $acct = new BookstoreEmployeeAccount();
        $acct->setBookstoreEmployee($employee);
        $acct->setLogin('test-login');

        $this->assertNull($acct->getCreated(), 'Expected created column to be NULL.');
        $this->assertNull($acct->getAuthenticator(), 'Expected authenticator column to be NULL.');

        $acct->save();

        $acct = BookstoreEmployeeAccountQuery::create()->findPk($acct->getEmployeeId());

        $this->assertNotNull($acct->getAuthenticator(), 'Expected a valid (non-NULL) authenticator column after save.');
        $this->assertEquals('Password', $acct->getAuthenticator(), "Expected authenticator='Password' after save.");
        $this->assertNotNull($acct->getCreated(), 'Expected a valid date after retrieving saved object.');

        $now = new DateTime('now');
        $this->assertEquals($now->format('Y-m-d'), $acct->getCreated('Y-m-d'));

        $acct->setCreated($now);
        $this->assertEquals($now->format('Y-m-d'), $acct->getCreated('Y-m-d'));

        // Unfortunately we can't really test the conjunction of reloadOnInsert and reloadOnUpdate when using just
        // default values. (At least not in a cross-db way.)
    }

    /**
     * Tests the use of default expressions and the reloadOnInsert attribute.
     *
     * @link http://propel.phpdb.org/trac/ticket/378
     * @link http://propel.phpdb.org/trac/ticket/555
     *
     * @return void
     */
    public function testDefaultExpressions_ReloadOnInsert()
    {
        if (Propel::getServiceContainer()->getAdapter(BookstoreEmployeeTableMap::DATABASE_NAME) instanceof SqliteAdapter) {
            $this->markTestSkipped('Cannot test default date expressions with SQLite');
        }

        // Create a new bookstore, contest, bookstore_contest, and bookstore_contest_entry

        $b = new Bookstore();
        $b->setStoreName('Barnes & Noble');
        $b->save();

        $c = new Contest();
        $c->setName('Bookathon Contest');
        $c->save();

        $bc = new BookstoreContest();
        $bc->setBookstore($b);
        $bc->setContest($c);
        $bc->save();

        $c = new Customer();
        $c->setName('Happy Customer');
        $c->save();

        $bce = new BookstoreContestEntry();
        $bce->setBookstore($b);
        $bce->setBookstoreContest($bc);
        $bce->setCustomer($c);
        $bce->save();

        $this->assertNotNull($bce->getEntryDate(), 'Expected a non-null entry_date after save.');
    }

    /**
     * Tests the overriding reloadOnInsert at runtime.
     *
     * @link http://propel.phpdb.org/trac/ticket/378
     * @link http://propel.phpdb.org/trac/ticket/555
     *
     * @return void
     */
    public function testDefaultExpressions_ReloadOnInsert_Override()
    {
        if (Propel::getServiceContainer()->getAdapter(BookstoreEmployeeTableMap::DATABASE_NAME) instanceof SqliteAdapter) {
            $this->markTestSkipped('Cannot test default date expressions with SQLite');
        }

        // Create a new bookstore, contest, bookstore_contest, and bookstore_contest_entry
        $b = new Bookstore();
        $b->setStoreName('Barnes & Noble');
        $b->save();

        $c = new Contest();
        $c->setName('Bookathon Contest');
        $c->save();

        $bc = new BookstoreContest();
        $bc->setBookstore($b);
        $bc->setContest($c);
        $bc->save();

        $c = new Customer();
        $c->setName('Happy Customer');
        $c->save();

        $bce = new BookstoreContestEntry();
        $bce->setBookstore($b);
        $bce->setBookstoreContest($bc);
        $bce->setCustomer($c);
        $bce->save(null, $skipReload = true);

        $this->assertNull($bce->getEntryDate(), 'Expected a NULL entry_date after save.');
    }

    /**
     * Tests the use of default expressions and the reloadOnUpdate attribute.
     *
     * @link http://propel.phpdb.org/trac/ticket/555
     *
     * @return void
     */
    public function testDefaultExpressions_ReloadOnUpdate()
    {
        $b = new Bookstore();
        $b->setStoreName('Foo!');
        $b->save();

        $sale = new BookstoreSale();
        $sale->setBookstore(BookstoreQuery::create()->findOne());
        $sale->setSaleName('Spring Sale');
        $sale->save();

        // Expect that default values are set, but not default expressions
        $this->assertNull($sale->getDiscount(), 'Expected discount to be NULL.');

        $sale->setSaleName('Winter Clearance');
        $sale->save();
        // Since reloadOnUpdate = true, we expect the discount to be set now.

        $this->assertNotNull($sale->getDiscount(), 'Expected discount to be non-NULL after save.');
    }

    /**
     * Tests the overriding reloadOnUpdate at runtime.
     *
     * @link http://propel.phpdb.org/trac/ticket/378
     * @link http://propel.phpdb.org/trac/ticket/555
     *
     * @return void
     */
    public function testDefaultExpressions_ReloadOnUpdate_Override()
    {
        $b = new Bookstore();
        $b->setStoreName('Foo!');
        $b->save();

        $sale = new BookstoreSale();
        $sale->setBookstore(BookstoreQuery::create()->findOne());
        $sale->setSaleName('Spring Sale');
        $sale->save();

        // Expect that default values are set, but not default expressions
        $this->assertNull($sale->getDiscount(), 'Expected discount to be NULL.');

        $sale->setSaleName('Winter Clearance');
        $sale->save(null, $skipReload = true);

        // Since reloadOnUpdate = true, we expect the discount to be set now.

        $this->assertNull($sale->getDiscount(), 'Expected NULL value for discount after save.');
    }

    /**
     * Testing creating & saving new object & instance pool.
     *
     * @return void
     */
    public function testObjectInstances_New()
    {
        $emp = new BookstoreEmployee();
        $emp->setName(md5(microtime()));
        $emp->save();
        $id = $emp->getId();

        $retrieved = BookstoreEmployeeQuery::create()->findPk($id);
        $this->assertSame($emp, $retrieved, 'Expected same object (from instance pool)');
    }

    /**
     * @return void
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
        $book->setTitle('Book Title');
        $book->setISBN('1234');
        $book->setPublisher($pub1);
        $book->save();

        $this->assertSame($pub1, $book->getPublisher());

        // now change values behind the scenes
        $con = Propel::getServiceContainer()->getConnection(BookstoreEmployeeAccountTableMap::DATABASE_NAME);
        $con->exec('UPDATE ' . BookTableMap::TABLE_NAME . ' SET '
            . ' publisher_id = ' . $pub2->getId()
            . ' WHERE id = ' . $book->getId());

        $book2 = BookQuery::create()->findPk($book->getId());
        $this->assertSame($book, $book2, 'Expected same book object instance');

        $this->assertEquals($pub1->getId(), $book->getPublisherId(), 'Expected book to have OLD publisher id before reload()');

        $book->reload();

        $this->assertEquals($pub2->getId(), $book->getPublisherId(), 'Expected book to have new publisher id');
        $this->assertSame($pub2, $book->getPublisher(), 'Expected book to have new publisher object associated.');

        // Now let's set it back, just to be double sure ...

        $con->exec('UPDATE ' . BookTableMap::TABLE_NAME . ' SET '
            . ' publisher_id = ' . $pub1->getId()
            . ' WHERE id = ' . $book->getId());

        $book->reload();

        $this->assertEquals($pub1->getId(), $book->getPublisherId(), 'Expected book to have old publisher id (again).');
        $this->assertSame($pub1, $book->getPublisher(), 'Expected book to have old publisher object associated (again).');
    }

    /**
     * Test the effect of typecast on primary key values and instance pool retrieval.
     *
     * @return void
     */
    public function testObjectInstancePoolTypecasting()
    {
        $reader = new BookReader();
        $reader->setName('Tester');
        $reader->save();
        $readerId = $reader->getId();

        $book = new Book();
        $book->setTitle('BookTest');
        $book->setISBN('TEST');
        $book->save();
        $bookId = $book->getId();

        $opinion = new BookOpinion();
        $opinion->setBookId((string)$bookId);
        $opinion->setReaderId((string)$readerId);
        $opinion->setRating(5);
        $opinion->setRecommendToFriend(false);
        $opinion->save();

        $opinion2 = BookOpinionQuery::create()->findPk([$bookId, $readerId]);

        $this->assertSame($opinion, $opinion2, 'Expected same object to be retrieved from differently type-casted primary key values.');
    }

    /**
     * Test saving an object and getting correct number of affected rows from save().
     * This includes tests of cascading saves to fk-related objects.
     *
     * @return void
     */
    public function testSaveReturnValues()
    {
        $author = new Author();
        $author->setFirstName('Mark');
        $author->setLastName('Kurlansky');
        // do not save

        $pub = new Publisher();
        $pub->setName('Penguin Books');
        // do not save

        $book = new Book();
        $book->setTitle('Salt: A World History');
        $book->setISBN('0142001619');
        $book->setAuthor($author);
        $book->setPublisher($pub);

        $affected = $book->save();
        $this->assertEquals(3, $affected, 'Expected 3 affected rows when saving book + publisher + author.');

        // change nothing ...
        $affected = $book->save();
        $this->assertEquals(0, $affected, 'Expected 0 affected rows when saving already-saved book.');

        // modify the book (UPDATE)
        $book->setTitle('Salt A World History');
        $affected = $book->save();
        $this->assertEquals(1, $affected, 'Expected 1 affected row when saving modified book.');

        // modify the related author
        $author->setLastName('Kurlanski');
        $affected = $book->save();
        $this->assertEquals(1, $affected, 'Expected 1 affected row when saving book with updated author.');

        // modify both the related author and the book
        $author->setLastName('Kurlansky');
        $book->setTitle('Salt: A World History');
        $affected = $book->save();
        $this->assertEquals(2, $affected, 'Expected 2 affected rows when saving updated book with updated author.');
    }

    /**
     * @return void
     */
    public function testSaveCanInsertNonEmptyObjects()
    {
        $b = new Book();
        $b->setTitle('foo');
        $b->setISBN('FA404');
        $b->save();
        $this->assertFalse($b->isNew());
        $this->assertNotNull($b->getId());
    }

    /**
     * @return void
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
        $this->assertSame(3, $affected); // Why 3 and not 0?
    }

    /**
     * @return void
     */
    public function testIsModifiedIsFalseForNewObjects()
    {
        $a = new Author();
        $this->assertFalse($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsTrueForNewObjectsWithModifications()
    {
        $a = new Author();
        $a->setFirstName('Foo');
        $this->assertTrue($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsFalseForNewObjectsWithNullModifications()
    {
        $a = new Author();
        $a->setFirstName(null);
        $this->assertFalse($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsFalseForObjectsAfterResetModified()
    {
        $a = new Author();
        $a->setFirstName('Foo');
        $a->resetModified();
        $this->assertFalse($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsFalseForSavedObjects()
    {
        $a = new Author();
        $a->setFirstName('Foo');
        $a->setLastName('Bar');
        $a->save();
        $this->assertFalse($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsTrueForSavedObjectsWithModifications()
    {
        $a = new Author();
        $a->setFirstName('Foo');
        $a->setLastName('Bar');
        $a->save();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $this->assertTrue($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsFalseAfterSetToDefaultValueOnNewObject()
    {
        $p = new Publisher();
        $p->setName('Penguin'); // default column value
        $this->assertFalse($p->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsTrueAfterModifyingOnNonDefaultValueOnNewObject()
    {
        $p = new Publisher();
        $p->setName('Puffin Books');
        $this->assertTrue($p->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsTrueAfterSetToDefaultValueOnModifiedObject()
    {
        $p = new Publisher();
        $p->setName('Puffin Books');
        $p->resetModified();
        $p->setName('Penguin'); // default column value
        $this->assertTrue($p->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedIsFalseAfterChangingColumnTypeButNotValue()
    {
        $a = new Author();
        $a->setFirstName('1');
        $a->setAge(25);
        $a->resetModified();

        $a->setAge('25');
        $this->assertFalse($a->isModified());

        $a->setFirstName(1);
        $this->assertFalse($a->isModified());
    }

    /**
     * @return void
     */
    public function testIsModifiedAndNullValues()
    {
        $a = new Author();
        $a->setFirstName('');
        $a->setLastName('Bar');
        $a->setAge(0);
        $a->save();

        $a->setFirstName(null);
        $this->assertTrue($a->isModified(), 'Expected Author to be modified after changing empty string column value to NULL.');

        $a->setAge(null);
        $this->assertTrue($a->isModified(), 'Expected Author to be modified after changing 0-value int column to NULL.');

        $a->setFirstName('');
        $this->assertTrue($a->isModified(), 'Expected Author to be modified after changing NULL column value to empty string.');

        $a->setAge(0);
        $this->assertTrue($a->isModified(), 'Expected Author to be modified after changing NULL column to 0-value int.');
    }

    /**
     * Test checking for non-default values.
     *
     * @see http://propel.phpdb.org/trac/ticket/331
     *
     * @return void
     */
    public function testHasOnlyDefaultValues()
    {
        $emp = new BookstoreEmployee();
        $emp->setName(md5(microtime()));

        $acct2 = new BookstoreEmployeeAccount();

        $acct = new BookstoreEmployeeAccount();
        $acct->setBookstoreEmployee($emp);
        $acct->setLogin('foo');
        $acct->setPassword('bar');
        $acct->save();

        $this->assertFalse($acct->isModified(), 'Expected BookstoreEmployeeAccount NOT to be modified after save().');

        $acct->setEnabled(true);
        $acct->setPassword($acct2->getPassword());

        $this->assertTrue($acct->isModified(), 'Expected BookstoreEmployeeAccount to be modified after setting default values.');

        $this->assertTrue($acct->hasOnlyDefaultValues(), 'Expected BookstoreEmployeeAccount to not have only default values.');

        $acct->setPassword('bar');
        $this->assertFalse($acct->hasOnlyDefaultValues(), 'Expected BookstoreEmployeeAccount to have at one non-default value after setting one value to non-default.');

        // Test a default date/time value
        $r = new Review();
        $r->setReviewDate(new DateTime('now'));
        $this->assertFalse($r->hasOnlyDefaultValues());
    }

    /**
     * @return void
     */
    public function testHasOnlyDefaultValuesObjectType()
    {
        $databaseXml = <<<XML
        <database namespace="ExampleNamespace" package="Things">
            <table name="thing">
                <column name="id" type="integer"/>
                <column
                    name="boxedstring"
                    type="VARCHAR"
                    phpType="\Propel\Tests\Common\BoxedString"
                    default="asdf"
                    required="true"
                />
            </table>
        </database>
XML;
        $builder = new QuickBuilder();
        $builder->setSchema($databaseXml);
        $builder->build();

        $t = new \ExampleNamespace\Thing();
        $this->assertEquals(new BoxedString('asdf'), $t->getBoxedstring());
        $this->assertTrue(
            $t->hasOnlyDefaultValues(),
            'default boxed string should be reported as Object has only default values'
        );
    }

    /**
     * @return void
     */
    public function testCountRefFk()
    {
        $book = new Book();
        $book->setTitle('Test Book');
        $book->setISBN('TT-EE-SS-TT');

        $num = 5;

        for ($i = 2; $i < $num + 2; $i++) {
            $r = new Review();
            $r->setReviewedBy('Hans ' . $num);
            $dt = new DateTime('now');
            $dt->modify('-' . $i . ' weeks');
            $r->setReviewDate($dt);
            $r->setRecommended(($i % 2) == 0);
            $book->addReview($r);
        }

        $this->assertEquals($num, $book->countReviews(), "Expected countReviews to return $num");
        $this->assertEquals($num, count($book->getReviews()), "Expected getReviews to return $num reviews");

        $book->save();

        BookTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();

        $book = BookQuery::create()->findPk($book->getId());
        $this->assertEquals($num, $book->countReviews(), "Expected countReviews() to return $num (after save)");
        $this->assertEquals($num, count($book->getReviews()), "Expected getReviews() to return $num (after save)");

        // Now set different criteria and expect different results
        $c = new Criteria();
        $c->add(ReviewTableMap::COL_RECOMMENDED, false);
        $this->assertEquals(floor($num / 2), $book->countReviews($c), 'Expected ' . floor($num / 2) . ' results from countReviews(recomm=false)');

        // Change Criteria, run again -- expect different.
        $c = new Criteria();
        $c->add(ReviewTableMap::COL_RECOMMENDED, true);
        $this->assertEquals(ceil($num / 2), count($book->getReviews($c)), 'Expected ' . ceil($num / 2) . ' results from getReviews(recomm=true)');

        $this->assertEquals($num, $book->countReviews(), "Expected countReviews to return $num with new empty Criteria");
    }

    /**
     * Test copying when an object has composite primary key.
     *
     * @link http://propel.phpdb.org/trac/ticket/618
     *
     * @return void
     */
    public function testCopy_CompositePK()
    {
        $br = new BookReader();
        $br->setName('TestReader');
        $br->save();
        $br->copy();

        $b = new Book();
        $b->setTitle('TestBook');
        $b->setISBN('XX-XX-XX-XX');
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
        $this->assertEquals(1, count($opinions), 'Expected to have a related BookOpinion after copy()');

        // We DO expect the reader_id to be null
        $this->assertNull($opinions[0]->getReaderId());
        // but we DO NOT expect the book_id to be null
        $this->assertEquals($op->getBookId(), $opinions[0]->getBookId());
    }

    /**
     * @return void
     */
    public function testToArray()
    {
        $b = new Book();
        $b->setTitle('Don Juan');

        $arr1 = $b->toArray();
        $expectedKeys = [
            'Id',
            'Title',
            'ISBN',
            'Price',
            'PublisherId',
            'AuthorId',
        ];
        $this->assertEquals($expectedKeys, array_keys($arr1), 'toArray() returns an associative array with TableMap::TYPE_PHPNAME keys by default');
        $this->assertEquals('Don Juan', $arr1['Title'], 'toArray() returns an associative array representation of the object');
    }

    /**
     * @return void
     */
    public function testToArrayDateTimeAsString()
    {
        $date = new DateTime('2015-01-04T16:00:02Z');

        $review = new Review();
        $review->setReviewDate($date);

        $bookstore = new Bookstore();
        $bookstore->setStoreOpenTime($date);

        $bookClubList = new BookClubList();
        $bookClubList->setCreatedAt($date);

        $this->assertEquals('2015-01-04', $review->toArray()['ReviewDate'], 'toArray() format colums of type DATE as Y-m-d');
        $this->assertEquals('16:00:02.000000', $bookstore->toArray()['StoreOpenTime'], 'toArray() format toArray() colums of type TIME as H:i:s.u');
        $this->assertEquals('2015-01-04 16:00:02.000000', $bookClubList->toArray()['CreatedAt'], 'toArray() format toArray() colums of type TIMESTAMP as Y-m-d H:i:s.u');
    }

    /**
     * @return void
     */
    public function testWithColumn()
    {
        $book = BookQuery::create()->withColumn('Title', 'TitleCopy')->findOne();
        $bookArray = $book->toArray();
        $this->assertEquals($book->getTitleCopy(), $bookArray['TitleCopy']);
    }

    /**
     * @return void
     */
    public function testToArrayKeyType()
    {
        $b = new Book();
        $b->setTitle('Don Juan');

        $arr1 = $b->toArray(TableMap::TYPE_COLNAME);
        $expectedKeys = [
            BookTableMap::COL_ID,
            BookTableMap::COL_TITLE,
            BookTableMap::COL_ISBN,
            BookTableMap::COL_PRICE,
            BookTableMap::COL_PUBLISHER_ID,
            BookTableMap::COL_AUTHOR_ID,
        ];
        $this->assertEquals($expectedKeys, array_keys($arr1), 'toArray() accepts a $keyType parameter to change the result keys');
        $this->assertEquals('Don Juan', $arr1[BookTableMap::COL_TITLE], 'toArray() returns an associative array representation of the object');
    }

    /**
     * @return void
     */
    public function testToArrayKeyTypePreDefined()
    {
        $schema = <<<EOF
<database name="test"  namespace="MyNameSpace">
    <table name="test_key_type_table">
        <column name="id_key_type" required="true" primaryKey="true" autoIncrement="true" type="INTEGER"/>
        <column name="name_key_type" type="VARCHAR"/>
    </table>
</database>
EOF;
        $extraConf['propel']['generator']['objectModel']['defaultKeyType'] = 'camelName';
        $generatorConfig = new QuickGeneratorConfig($extraConf);
        $builder = new QuickBuilder();
        $builder->setSchema($schema);
        $builder->setConfig($generatorConfig);
        $builder->buildClasses();

        $expectedKeys = [
            'idKeyType',
            'nameKeyType',
        ];
        $object = new TestKeyTypeTable();
        $this->assertEquals($expectedKeys, array_keys($object->toArray()), 'toArray() returns an associative array with pre-defined key type in properties.');
    }

    /**
     * Test that setting the auto-increment primary key will result in exception.
     *
     * @return void
     */
    public function testSettingAutoIncrementPK()
    {
        // The whole test is in a transaction, but this test needs real transactions
        $this->con->commit();

        $b = new Bookstore();
        $b->setId(1);
        $b->setStoreName('Test');
        try {
            $b->save();
            $this->fail('Expected setting auto-increment primary key to result in Exception');
        } catch (Exception $x) {
            $this->assertInstanceOf('\Propel\Runtime\Exception\PropelException', $x);
        }

        // ... but we should silently ignore NULL values, since these are really
        // the same as "not set" in PHP world.
        $b = new Bookstore();
        $b->setId(null);
        $b->setStoreName('Test2');
        try {
            $b->save();
        } catch (Exception $x) {
            $this->fail('Expected no exception when setting auto-increment primary key to NULL');
        }
        // success ...

        $this->con->beginTransaction();
    }

    /**
     * Checks whether we are allowed to specify the primary key on a
     * table with allowPkInsert=true set
     *
     * saves the object, gets it from data-source again and then compares
     * them for equality (thus the instance pool is also checked)
     *
     * @return void
     */
    public function testAllowPkInsertOnIdMethodNativeTable()
    {
        CustomerTableMap::doDeleteAll();
        $cu = new Customer();
        $cu->setPrimaryKey(100000);
        $cu->save();

        $this->assertEquals(100000, $cu->getPrimaryKey());

        $cu2 = CustomerQuery::create()->findPk(100000);

        $this->assertSame($cu, $cu2);
    }

    /**
     * Checks if it is allowed to save new, empty objects with a auto increment column
     *
     * @return void
     */
    public function testAllowEmptyWithAutoIncrement()
    {
        $bookreader = new BookReader();
        $bookreader->save();

        $this->assertFalse($bookreader->isNew());
    }

    /**
     * Test foreign key relationships based on references to unique cols but not PK.
     *
     * @link http://propel.phpdb.org/trac/ticket/691
     *
     * @return void
     */
    public function testUniqueFkRel()
    {
        BookstoreEmployeeAccountTableMap::doDeleteAll();

        $employee = new BookstoreEmployee();
        $employee->setName('Johnny Walker');

        $acct = new BookstoreEmployeeAccount();
        $acct->setBookstoreEmployee($employee);
        $acct->setLogin('test-login');
        $acct->save();
        $acctId = $acct->getEmployeeId();

        $al = new AcctAuditLog();
        $al->setBookstoreEmployeeAccount($acct);
        $al->save();
        $alId = $al->getId();

        BookstoreEmployeeTableMap::clearInstancePool();
        BookstoreEmployeeAccountTableMap::clearInstancePool();
        AcctAuditLogTableMap::clearInstancePool();

        $al2 = AcctAuditLogQuery::create()->findPk($alId);
        /** @var \Propel\Tests\Bookstore\AcctAuditLog $al2 */
        $mapacct = $al2->getBookstoreEmployeeAccount();
        $lookupacct = BookstoreEmployeeAccountQuery::create()->findPk($acctId);

        $logs = $lookupacct->getAcctAuditLogs();

        $this->assertTrue(count($logs) == 1, 'Expected 1 audit log result.');
        $this->assertEquals($logs[0]->getId(), $al->getId(), 'Expected returned audit log to match created audit log.');
    }

    /**
     * @return void
     */
    public function testIsPrimaryKeyNull()
    {
        $b = new Book();
        $this->assertTrue($b->isPrimaryKeyNull());
        $b->setPrimaryKey(123);
        $this->assertFalse($b->isPrimaryKeyNull());
        $b->setPrimaryKey(null);
        $this->assertTrue($b->isPrimaryKeyNull());
    }

    /**
     * @return void
     */
    public function testIsPrimaryKeyNullComposite()
    {
        $b = new BookOpinion();
        $this->assertTrue($b->isPrimaryKeyNull());
        $b->setPrimaryKey([123, 456]);
        $this->assertFalse($b->isPrimaryKeyNull());
        $b->setPrimaryKey([123, null]);
        $this->assertFalse($b->isPrimaryKeyNull());
        $b->setPrimaryKey([null, 456]);
        $this->assertFalse($b->isPrimaryKeyNull());
        $b->setPrimaryKey([null, null]);
        $this->assertTrue($b->isPrimaryKeyNull());
    }

    /**
     * @return void
     */
    public function testAddToStringDefault()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Author', '__toString'), 'addPrimaryString() adds a __toString() method even if no column has the primaryString attribute');
        $author = new Author();
        $author->setFirstName('Leo');
        $author->setLastName('Tolstoi');
        $expected = <<<EOF
Id: null
FirstName: Leo
LastName: Tolstoi
Email: null
Age: null

EOF;
        $this->assertEquals($expected, (string)$author, 'addPrimaryString() adds a __toString() method returning the YAML representation of the object where no column is defined as primaryString');
    }

    /**
     * @return void
     */
    public function testAddToStringPrimaryString()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\Book', '__toString'), 'addPrimaryString() adds a __toString() method if a column has the primaryString attribute');
        $book = new Book();
        $book->setTitle('foo');
        $this->assertEquals('foo', (string)$book, 'addPrimaryString() adds a __toString() method returning the value of the the first column where primaryString is true');
    }

    /**
     * @return void
     */
    public function testPreInsert()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $this->assertEquals('PreInsertedFirstname', $author->getFirstName());
    }

    /**
     * @return void
     */
    public function testPreUpdate()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $author->setNew(false);
        $author->save();
        $this->assertEquals('PreUpdatedFirstname', $author->getFirstName());
    }

    /**
     * @return void
     */
    public function testPostInsert()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $this->assertEquals('PostInsertedLastName', $author->getLastName());
    }

    /**
     * @return void
     */
    public function testPostUpdate()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $author->setNew(false);
        $author->save();
        $this->assertEquals('PostUpdatedLastName', $author->getLastName());
    }

    /**
     * @return void
     */
    public function testPreSave()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $this->assertEquals('pre@save.com', $author->getEmail());
    }

    /**
     * @return void
     */
    public function testPreSaveFalse()
    {
        $con = Propel::getServiceContainer()->getConnection(AuthorTableMap::DATABASE_NAME);
        $nbNestedTransactions = $con->getNestedTransactionCount();
        $author = new TestAuthorSaveFalse();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $res = $author->save($con);
        $this->assertEquals(0, $res);
        $this->assertEquals('pre@save.com', $author->getEmail());
        $this->assertNotEquals(115, $author->getAge());
        $this->assertTrue($author->isNew());
        $this->assertEquals($nbNestedTransactions, $con->getNestedTransactionCount());
    }

    /**
     * @return void
     */
    public function testPostSave()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $this->assertEquals(115, $author->getAge());
    }

    /**
     * @return void
     */
    public function testPreDelete()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $author->delete();
        $this->assertEquals('Pre-Deleted', $author->getFirstName());
    }

    /**
     * @return void
     */
    public function testPreDeleteFalse()
    {
        $con = Propel::getServiceContainer()->getConnection(AuthorTableMap::DATABASE_NAME);
        $author = new TestAuthorDeleteFalse();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save($con);
        $author->delete($con);
        $this->assertEquals('Pre-Deleted', $author->getFirstName());
        $this->assertNotEquals('Post-Deleted', $author->getLastName());
        $this->assertFalse($author->isDeleted());
        $this->assertEquals(1, $con->getNestedTransactionCount());
    }

    /**
     * @return void
     */
    public function testPostDelete()
    {
        $author = new TestAuthor();
        $author->setFirstName('bogus');
        $author->setLastName('Lastname');
        $author->save();
        $author->delete();
        $this->assertEquals('Post-Deleted', $author->getLastName());
    }

    /**
     * @return void
     */
    public function testMagicVirtualColumnGetter()
    {
        $book = new Book();
        $book->setVirtualColumn('Foo', 'bar');
        $this->assertEquals('bar', $book->getFoo(), 'generated __call() catches getters for virtual columns');
        $book = new Book();
        $book->setVirtualColumn('foo', 'bar');
        $this->assertEquals('bar', $book->getFoo(), 'generated __call() catches getters for virtual columns starting with a lowercase character');
    }

    /**
     * @return void
     */
    public function testMagicCallUndefined()
    {
        $this->expectException(BadMethodCallException::class);

        $book = new Book();
        $book->fooMethodName();
    }

    public static function conditionsForTestReadOnly()
    {
        return [
            ['reload'],
            ['delete'],
            ['save'],
            ['doSave'],
            ['importFrom'],
        ];
    }

    public static function conditionsForTestVisibility()
    {
        return [
            ['setCode'],
            ['setCapital'],
        ];
    }

    /**
     * @dataProvider conditionsForTestVisibility
     *
     * @return void
     */
    public function testMethodVisibility($method)
    {
        $cv = new Country();
        $reflectionMethod = new ReflectionMethod($cv, $method);

        $this->assertTrue($reflectionMethod->isProtected(), 'readOnly tables end up with no callable `' . $method . '` method in the generated object class');
    }

    /**
     * @dataProvider conditionsForTestReadOnly
     *
     * @return void
     */
    public function testReadOnly($method)
    {
        $cv = new Country();
        $this->assertFalse(method_exists($cv, $method), 'readOnly tables end up with no ' . $method . ' method in the generated object class');
    }

    /**
     * @return void
     */
    public function testReadOnlyRelations()
    {
        //add countries
        CountryTranslationQuery::create()->deleteAll();
        CountryQuery::create()->deleteAll();
        $stmt = $this->con->prepare('INSERT INTO country VALUES (?, ?)');
        $stmt->execute(['fr', 'Paris']);
        $stmt->execute(['us', 'Washington']);
        $stmt->execute(['de', 'Berlin']);

        $stmt = $this->con->prepare('INSERT INTO country_translation (country_code, language_code, label) VALUES (?, ?, ?)');
        $stmt->execute(['fr', 'fr_FR', 'France']);
        $stmt->execute(['us', 'us_US', 'United States America']);
        $stmt->execute(['de', 'de_DE', 'Berlin']);

        $contest = new Contest();
        $contest->setName('Symfony Live 2014');
        $contest->setCountryCode('fr');
        $contest->save();

        ContestTableMap::clearInstancePool();

        $contestDb = ContestQuery::create()
                ->joinWith('Contest.Country')
                ->joinWith('Country.CountryTranslation')
                ->findPk($contest->getId());

        $translations = $contestDb->getCountry()->getCountryTranslations();
        $this->assertCount(1, $translations);
        $this->assertEquals('France', $translations[0]->getLabel());
    }

    /**
     * @return void
     */
    public function testSetterOneToMany()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');

        for ($i = 0; $i < 3; $i++) {
            $b = new Book();
            $b->setTitle('Title ' . $i);
            $b->setISBN($i);

            $coll[] = $b;
        }

        $this->assertEquals(3, $coll->count());

        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks($coll);
        $a->save();

        $this->assertInstanceOf('\Propel\Runtime\Collection\ObjectCollection', $a->getBooks());
        $this->assertEquals(3, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(3, BookQuery::create()->count());

        $coll->shift();
        $this->assertEquals(2, $coll->count());

        $a->setBooks($coll);
        $a->save();

        $this->assertEquals(2, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        //The book is not deleted because his fk is not required
        $this->assertEquals(3, BookQuery::create()->count());

        $newBook = new Book();
        $newBook->setTitle('My New Book');
        $newBook->setIsbn(1234);

        // Kind of new collection
        $coll = clone $coll;
        $coll[] = $newBook;

        $a->setBooks($coll);
        $a->save();

        $this->assertEquals(3, $coll->count());
        $this->assertEquals(3, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(4, BookQuery::create()->count());

        // Add a new object
        $newBook1 = new Book();
        $newBook1->setTitle('My New Book1');
        $newBook1->setIsbn(1256);

        // Existing collection - The fix around reference is tested here.
        $coll[] = $newBook1;

        $a->setBooks($coll);
        $a->save();

        $this->assertEquals(4, $coll->count());
        $this->assertEquals(4, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(5, BookQuery::create()->count());

        // Add the same collection
        $books = $a->getBooks();

        $a->setBooks($books);
        $a->save();

        $this->assertEquals(4, $books->count());
        $this->assertEquals(4, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(5, BookQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testSetterOneToManyWithNoData()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $books = new ObjectCollection();
        $this->assertEquals(0, $books->count());

        // Basic usage
        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks($books);
        $a->save();

        $this->assertEquals(0, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(0, BookQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testSetterOneToManySavesForeignObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $book = new Book();
        $book->setTitle('My Book');
        $book->setISBN('FA404');
        $book->save();

        // Modify it but don't save it
        $book->setTitle('My Title');

        $coll = new ObjectCollection();
        $coll[] = $book;

        BookTableMap::clearInstancePool();
        $book = BookQuery::create()->findPk($book->getPrimaryKey());

        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks($coll);
        $a->save();

        $this->assertEquals(1, $a->getBooks()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(1, BookQuery::create()->count());

        $result = BookQuery::create()
            ->filterById($book->getId())
            ->select('Title')
            ->findOne();
        $this->assertSame('My Title', $result);
    }

    /**
     * @return void
     */
    public function testSetterOneToManyWithNewObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $coll = new ObjectCollection();
        $coll->setModel('\Propel\Tests\Bookstore\Book');

        for ($i = 0; $i < 3; $i++) {
            $b = new Book();
            $b->setTitle('Title ' . $i);
            $b->setISBN($i);

            $coll[] = $b;
        }

        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks($coll);
        $a->save();

        $this->assertEquals(3, $coll->count());
        $this->assertEquals(3, count($a->getBooks()));
        $this->assertSame($coll, $a->getBooks());
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(3, BookQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testSetterOneToManyWithExistingObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        for ($i = 0; $i < 3; $i++) {
            $b = new Book();
            $b->setTitle('Book ' . $i);
            $b->setISBN('FA404-' . $i);
            $b->save();
        }

        BookTableMap::clearInstancePool();
        $books = BookQuery::create()->find();

        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks($books);
        $a->save();

        $this->assertEquals(3, count($a->getBooks()));
        $this->assertEquals(1, AuthorQuery::create()->count());
        $this->assertEquals(3, BookQuery::create()->count());

        $i = 0;
        foreach ($a->getBooks() as $book) {
            $this->assertEquals('Book ' . $i++, $book->getTitle());
        }
    }

    /**
     * @return void
     */
    public function testSetterOneToManyWithEmptyCollection()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks(new ObjectCollection());
        $a->save();

        $this->assertEquals(0, count($a->getBooks()));

        $this->assertEquals(0, BookQuery::create()->count());
        $this->assertEquals(1, AuthorQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testSetterOneToManyReplacesOldObjectsByNewObjects()
    {
        // Ensure no data
        BookQuery::create()->deleteAll();
        AuthorQuery::create()->deleteAll();

        $books = new ObjectCollection();
        foreach (['foo', 'bar'] as $title) {
            $b = new Book();
            $b->setTitle($title);
            $b->setISBN('FA404');

            $books[] = $b;
        }

        $a = new Author();
        $a->setFirstName('Chuck');
        $a->setLastName('Norris');
        $a->setBooks($books);
        $a->save();

        $books = $a->getBooks();
        $this->assertEquals('foo', $books[0]->getTitle());
        $this->assertEquals('bar', $books[1]->getTitle());

        $books = new ObjectCollection();
        foreach (['bam', 'bom'] as $title) {
            $b = new Book();
            $b->setTitle($title);
            $b->setISBN('FA404');

            $books[] = $b;
        }

        $a->setBooks($books);
        $a->save();

        $books = $a->getBooks();
        $this->assertEquals('bam', $books[0]->getTitle());
        $this->assertEquals('bom', $books[1]->getTitle());

        $this->assertEquals(1, AuthorQuery::create()->count());
        // the replaced book are still there because the PK is not required
        $this->assertEquals(4, BookQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testSetterOneToManyWithFkRequired()
    {
        // Ensure no data
        BookSummaryQuery::create()->deleteAll();
        BookQuery::create()->deleteAll();

        $coll = new ObjectCollection();
        $coll->setModel('BookSummary');

        for ($i = 0; $i < 3; $i++) {
            $summary = new BookSummary();
            $summary->setSummary('Was Great!');
            $coll[] = $summary;
        }

        $this->assertEquals(3, $coll->count());

        $b = new Book();
        $b->setTitle('myBook');
        $b->setISBN('01234');
        $b->setBookSummaries($coll);
        $b->save();

        $this->assertInstanceOf('Propel\Runtime\Collection\ObjectCollection', $b->getBookSummaries());
        $this->assertEquals(3, $b->getBookSummaries()->count());
        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(3, BookSummaryQuery::create()->count());

        $coll->shift();
        $this->assertEquals(2, $coll->count());

        $b->setBookSummaries($coll);
        $b->save();

        $this->assertEquals(2, $b->getBookSummaries()->count());
        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(2, BookSummaryQuery::create()->count());

        $newBookSummary = new BookSummary();
        $newBookSummary->setSummary('My summary');

        // Kind of new collection
        $coll = clone $coll;
        $coll[] = $newBookSummary;

        $b->setBookSummaries($coll);
        $b->save();

        $this->assertEquals(3, $coll->count());
        $this->assertEquals(3, $b->getBookSummaries()->count());
        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(3, BookSummaryQuery::create()->count());

        // Add a new object
        $newBookSummary1 = new BookSummary();
        $newBookSummary1->setSummary('My summary 1');

        // Existing collection - The fix around reference is tested here.
        $coll[] = $newBookSummary1;

        $b->setBookSummaries($coll);
        $b->save();

        $this->assertEquals(4, $coll->count());
        $this->assertEquals(4, $b->getBookSummaries()->count());
        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(4, BookSummaryQuery::create()->count());

        // Add the same collection
        $bookSummaries = $b->getBookSummaries();

        $b->setBookSummaries($bookSummaries);
        $b->save();

        $this->assertEquals(4, $coll->count());
        $this->assertEquals(4, $b->getBookSummaries()->count());
        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(4, BookSummaryQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testSetterOneToManyReplacesOldObjectsByNewObjectsWithFkRequired()
    {
        // Ensure no data
        BookSummaryQuery::create()->deleteAll();
        BookQuery::create()->deleteAll();

        $bookSummaries = new ObjectCollection();
        foreach (['foo', 'bar'] as $summary) {
            $s = new BookSummary();
            $s->setSummary($summary);
            $bookSummaries[] = $s;
        }

        $b = new Book();
        $b->setTitle('Hello');
        $b->setISBN('01234');
        $b->setBookSummaries($bookSummaries);
        $b->save();

        $bookSummaries = $b->getBookSummaries();
        $this->assertEquals('foo', $bookSummaries[0]->getSummary());
        $this->assertEquals('bar', $bookSummaries[1]->getSummary());

        $bookSummaries = new ObjectCollection();
        foreach (['bam', 'bom'] as $summary) {
            $s = new BookSummary();
            $s->setSummary($summary);
            $bookSummaries[] = $s;
        }

        $b->setBookSummaries($bookSummaries);
        $b->save();

        $bookSummaries = $b->getBookSummaries();
        $this->assertEquals('bam', $bookSummaries[0]->getSummary());
        $this->assertEquals('bom', $bookSummaries[1]->getSummary());

        $this->assertEquals(1, BookQuery::create()->count());
        $this->assertEquals(2, BookSummaryQuery::create()->count());
    }

    /**
     * @return void
     */
    public function testUnsavedObjectCallingHashCodeIsNotChangingObject()
    {
        $book1 = new Book();
        $book1->setTitle('Foo5');
        $book1->setISBN('1234');

        $author = new Author();
        $author->setFirstName('JAne');
        $author->setLastName('JAne');
        $author->addBook($book1);

        $a = clone $author;
        $a->hashCode();

        $this->assertEquals($author, $a);
    }

    /**
     * @return void
     */
    public function testSavedObjectCallingHashCodeIsNotChangingObject()
    {
        $book1 = new Book();
        $book1->setTitle('Foo5');
        $book1->setISBN('1234');

        $author = new Author();
        $author->setFirstName('JAne');
        $author->setLastName('JAne');
        $author->addBook($book1);
        $author->save();

        $a = clone $author;
        $a->hashCode();

        $this->assertEquals($author, $a);
    }

    /**
     * @return void
     */
    public function testUnsavedObjectCreatesNotSameHashForIdenticalObjects()
    {
        $book1 = new Book();
        $book1->setTitle('Foo5');
        $book1->setISBN('1234');

        $author1 = new Author();
        $author1->setFirstName('JAne');
        $author1->setLastName('JAne');
        $author1->addBook($book1);

        $author2 = new Author();
        $author2->setFirstName('JAne');
        $author2->setLastName('JAne');
        $author2->addBook($book1);

        $this->assertNotEquals($author1->hashCode(), $author2->hashCode());
    }

    /**
     * Primary key should differ
     *
     * @return void
     */
    public function testSavedObjectCreatesDifferentHashForIdenticalObjects()
    {
        $book1 = new Book();
        $book1->setTitle('Foo5');
        $book1->setISBN('1234');

        $author1 = new Author();
        $author1->setFirstName('JAne');
        $author1->setLastName('JAne');
        $author1->addBook($book1);
        $author1->save();

        $author2 = new Author();
        $author2->setFirstName('JAne');
        $author2->setLastName('JAne');
        $author2->addBook($book1);
        $author2->save();

        $this->assertNotEquals($author1->hashCode(), $author2->hashCode());
    }
}
