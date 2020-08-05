<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder;

use Baz\Map\NamespacedBookListRelTableMap;
use Baz\Map\NamespacedPublisherTableMap;
use Baz\NamespacedBookClub;
use Baz\NamespacedBookClubQuery;
use Baz\NamespacedBookListRelQuery;
use Baz\NamespacedPublisher;
use Baz\NamespacedPublisherQuery;
use Foo\Bar\Map\NamespacedAuthorTableMap;
use Foo\Bar\Map\NamespacedBookTableMap;
use Foo\Bar\NamespacedAuthor;
use Foo\Bar\NamespacedAuthorQuery;
use Foo\Bar\NamespacedBook;
use Foo\Bar\NamespacedBookQuery;
use Foo\Bar\NamespacedBookstoreCashier;
use Foo\Bar\NamespacedBookstoreEmployee;
use Foo\Bar\NamespacedBookstoreEmployeeQuery;
use Foo\Bar\NamespacedBookstoreManager;
use Foo\Bar\NamespacedBookstoreManagerQuery;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Tests for Namespaces in generated classes class
 * Requires a build of the 'namespaced' fixture
 *
 * @group database
 */
class NamespaceTest extends TestCaseFixturesDatabase
{
//    protected function setUp(): void
//    {
//        parent::setUp();
//        Propel::init(__DIR__ . '/../../../../Fixtures/namespaced/build/conf/bookstore_namespaced-conf.php');
//    }
//
//    protected function tearDown(): void
//    {
//        parent::tearDown();
//        Propel::init(dirname(__FILE__) . '/../../../../Fixtures/bookstore/build/conf/bookstore-conf.php');
//    }

    /**
     * @return void
     */
    public function testInsert()
    {
        $book = new NamespacedBook();
        $book->setTitle('foo');
        $book->setISBN('something');
        $book->save();
        $this->assertFalse($book->isNew());

        $publisher = new NamespacedPublisher();
        $publisher->save();
        $this->assertFalse($publisher->isNew());
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        NamespacedBookTableMap::getTableMap();
        $book = new NamespacedBook();
        $book->setTitle('foo');
        $book->setISBN('something');
        $book->save();
        $book->setTitle('bar');
        $book->save();
        $this->assertFalse($book->isNew());
    }

    /**
     * @return void
     */
    public function testRelate()
    {
        $author = new NamespacedAuthor();
        $author->setFirstName('Chuck');
        $author->setLastname('Norris');
        $book = new NamespacedBook();
        $book->setNamespacedAuthor($author);
        $book->setTitle('foo');
        $book->setISBN('something');
        $book->save();
        $this->assertFalse($book->isNew());
        $this->assertFalse($author->isNew());

        $author = new NamespacedAuthor();
        $author->setFirstName('Henning');
        $author->setLastname('Mankell');
        $book = new NamespacedBook();
        $book->setTitle('Mördare utan ansikte');
        $book->setISBN('1234');
        $author->addNamespacedBook($book);
        $author->save();
        $this->assertFalse($book->isNew());
        $this->assertFalse($author->isNew());

        $publisher = new NamespacedPublisher();
        $book = new NamespacedBook();
        $book->setTitle('Där vi en gång gått');
        $book->setISBN('1234');
        $book->setNamespacedPublisher($publisher);
        $book->save();
        $this->assertFalse($book->isNew());
        $this->assertFalse($publisher->isNew());
    }

    /**
     * @return void
     */
    public function testBasicQuery()
    {
        NamespacedBookQuery::create()->deleteAll();
        NamespacedPublisherQuery::create()->deleteAll();
        $noNamespacedBook = NamespacedBookQuery::create()->findOne();
        $this->assertNull($noNamespacedBook);
        $noPublisher = NamespacedPublisherQuery::create()->findOne();
        $this->assertNull($noPublisher);
    }

    /**
     * @return void
     */
    public function testFind()
    {
        NamespacedBookQuery::create()->deleteAll();
        $book = new NamespacedBook();
        $book->setTitle('War And Peace');
        $book->setISBN('1234');
        $book->save();
        $book2 = NamespacedBookQuery::create()->findPk($book->getId());
        $this->assertEquals($book, $book2);
        $book3 = NamespacedBookQuery::create()->findOneByTitle($book->getTitle());
        $this->assertEquals($book, $book3);
    }

    /**
     * @return void
     */
    public function testGetRelatedManyToOne()
    {
        NamespacedBookQuery::create()->deleteAll();
        NamespacedPublisherQuery::create()->deleteAll();
        $publisher = new NamespacedPublisher();
        $book = new NamespacedBook();
        $book->setTitle('Something');
        $book->setISBN('1234');
        $book->setNamespacedPublisher($publisher);
        $book->save();
        NamespacedBookTableMap::clearInstancePool();
        NamespacedPublisherTableMap::clearInstancePool();
        $book2 = NamespacedBookQuery::create()->findPk($book->getId());
        $publisher2 = $book2->getNamespacedPublisher();
        $this->assertEquals($publisher->getId(), $publisher2->getId());
    }

    /**
     * @return void
     */
    public function testGetRelatedOneToMany()
    {
        NamespacedBookQuery::create()->deleteAll();
        NamespacedPublisherQuery::create()->deleteAll();
        $author = new NamespacedAuthor();
        $author->setFirstName('Foo');
        $author->setLastName('Bar');
        $book = new NamespacedBook();
        $book->setTitle('Quux');
        $book->setISBN('1235');
        $book->setNamespacedAuthor($author);
        $book->save();
        NamespacedBookTableMap::clearInstancePool();
        NamespacedAuthorTableMap::clearInstancePool();
        $author2 = NamespacedAuthorQuery::create()->findPk($author->getId());
        $book2 = $author2->getNamespacedBooks()->getFirst();
        $this->assertEquals($book->getId(), $book2->getId());
    }

    /**
     * @return void
     */
    public function testFindWithManyToOne()
    {
        NamespacedBookQuery::create()->deleteAll();
        NamespacedPublisherQuery::create()->deleteAll();
        $publisher = new NamespacedPublisher();
        $book = new NamespacedBook();
        $book->setTitle('asdf');
        $book->setISBN('something');
        $book->setNamespacedPublisher($publisher);
        $book->save();
        NamespacedBookTableMap::clearInstancePool();
        NamespacedPublisherTableMap::clearInstancePool();
        $book2 = NamespacedBookQuery::create()
            ->joinWith('NamespacedPublisher')
            ->findPk($book->getId());
        $publisher2 = $book2->getNamespacedPublisher();
        $this->assertEquals($publisher->getId(), $publisher2->getId());
    }

    /**
     * @return void
     */
    public function testFindWithOneToMany()
    {
        NamespacedBookQuery::create()->deleteAll();
        NamespacedAuthorQuery::create()->deleteAll();
        $author = new NamespacedAuthor();
        $author->setFirstName('Foo');
        $author->setLastName('Bar');
        $book = new NamespacedBook();
        $book->setTitle('asdf');
        $book->setISBN('something');
        $book->setNamespacedAuthor($author);
        $book->save();
        NamespacedBookTableMap::clearInstancePool();
        NamespacedAuthorTableMap::clearInstancePool();
        $author2 = NamespacedAuthorQuery::create()
            ->joinWith('NamespacedBook')
            ->findPk($author->getId());
        $book2 = $author2->getNamespacedBooks()->getFirst();
        $this->assertEquals($book->getId(), $book2->getId());
    }

    /**
     * @return void
     */
    public function testSingleTableInheritance()
    {
        NamespacedBookstoreEmployeeQuery::create()->deleteAll();
        $emp = new NamespacedBookstoreEmployee();
        $emp->setName('Henry');
        $emp->save();
        $man = new NamespacedBookstoreManager();
        $man->setName('John');
        $man->save();
        $cas = new NamespacedBookstoreCashier();
        $cas->setName('William');
        $cas->save();
        $emps = NamespacedBookstoreEmployeeQuery::create()
            ->orderByName()
            ->find();
        $this->assertEquals(3, count($emps));
        $this->assertTrue($emps[0] instanceof NamespacedBookstoreEmployee);
        $this->assertTrue($emps[1] instanceof NamespacedBookstoreManager);
        $this->assertTrue($emps[2] instanceof NamespacedBookstoreCashier);
        $nbMan = NamespacedBookstoreManagerQuery::create()
            ->count();
        $this->assertEquals(1, $nbMan);
    }

    /**
     * @return void
     */
    public function testManyToMany()
    {
        NamespacedBookQuery::create()->deleteAll();
        NamespacedBookClubQuery::create()->deleteAll();
        NamespacedBookListRelQuery::create()->deleteAll();
        $book1 = new NamespacedBook();
        $book1->setTitle('bar');
        $book1->setISBN('1234');
        $book1->save();
        $book2 = new NamespacedBook();
        $book2->setTitle('foo');
        $book2->setISBN('4567');
        $book2->save();
        $bookClub1 = new NamespacedBookClub();
        $bookClub1->addNamespacedBook($book1);
        $bookClub1->addNamespacedBook($book2);
        $bookClub1->setGroupLeader('Someone1');
        $bookClub1->save();
        $bookClub2 = new NamespacedBookClub();
        $bookClub2->addNamespacedBook($book1);
        $bookClub2->setGroupLeader('Someone2');
        $bookClub2->save();
        $this->assertEquals(2, $book1->countNamespacedBookClubs());
        $this->assertEquals(1, $book2->countNamespacedBookClubs());
        $nbRels = NamespacedBookListRelQuery::create()->count();
        $this->assertEquals(3, $nbRels);
        $con = Propel::getServiceContainer()->getConnection(NamespacedBookListRelTableMap::DATABASE_NAME);
        $books = NamespacedBookQuery::create()
            ->orderByTitle()
            ->joinWith('NamespacedBookListRel')
            ->joinWith('NamespacedBookListRel.NamespacedBookClub')
            ->find($con);

        $array = $books->toArray();
        $this->assertCount(2, $array);

        $expected = 'Someone1';
        $this->assertSame($expected, $array[0]['NamespacedBookListRels'][0]['NamespacedBookClub']['GroupLeader']);
    }

    /**
     * @return void
     */
    public function testUseQuery()
    {
        $book = NamespacedBookQuery::create()
            ->useNamespacedPublisherQuery()
                ->filterByName('foo')
            ->endUse()
            ->findOne();
        $this->assertNull($book);
    }
}
