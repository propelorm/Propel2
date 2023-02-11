<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Builder\Om;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\ExistsCriterion;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\AcctAuditLogQuery;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookClubListQuery;
use Propel\Tests\Bookstore\BookListRelQuery;
use Propel\Tests\Bookstore\BookOpinionQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeAccountQuery;
use Propel\Tests\Bookstore\BookSummaryQuery;
use Propel\Tests\Bookstore\EssayQuery;
use Propel\Tests\Bookstore\Map\AcctAuditLogTableMap;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookListRelTableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeAccountTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\PublisherTableMap;
use Propel\Tests\Bookstore\Map\RecordLabelTableMap;
use Propel\Tests\Bookstore\Map\ReleasePoolTableMap;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Bookstore\ReaderFavoriteQuery;
use Propel\Tests\Bookstore\RecordLabelQuery;
use Propel\Tests\Bookstore\ReleasePoolQuery;
use Propel\Tests\Bookstore\ReviewQuery;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use ReflectionMethod;

/**
 * Test class for QueryBuilder.
 *
 * @author François Zaninotto
 *
 * @group database
 */
class QueryBuilderTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        include_once(__DIR__ . '/QueryBuilderTestClasses.php');
        include_once(__DIR__ . '/TestableQueryBuilder.php');
    }

    /**
     * @return void
     */
    public function testExtends()
    {
        $q = new BookQuery();
        $this->assertTrue($q instanceof ModelCriteria, 'Model query extends ModelCriteria');
    }

    /**
     * @return void
     */
    public function testConstructor()
    {
        $query = new BookQuery();
        $this->assertEquals('bookstore', $query->getDbName(), 'Constructor sets dabatase name');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $query->getModelName(), 'Constructor sets model name');
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $query = BookQuery::create();
        $this->assertTrue($query instanceof BookQuery, 'create() returns an object of its class');
        $this->assertEquals('bookstore', $query->getDbName(), 'create() sets dabatase name');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $query->getModelName(), 'create() sets model name');
        $query = BookQuery::create('foo');
        $this->assertTrue($query instanceof BookQuery, 'create() returns an object of its class');
        $this->assertEquals($query->getDbName(), 'bookstore', 'create() sets dabatase name');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $query->getModelName(), 'create() sets model name');
        $this->assertEquals('foo', $query->getModelAlias(), 'create() can set the model alias');
    }

    /**
     * @return void
     */
    public function testCreateCustom()
    {
        // see the myBookQuery class definition at the end of this file
        $query = MyCustomBookQuery::create();
        $this->assertTrue($query instanceof MyCustomBookQuery, 'create() returns an object of its class');
        $this->assertTrue($query instanceof BookQuery, 'create() returns an object of its class');
        $this->assertEquals('bookstore', $query->getDbName(), 'create() sets dabatase name');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $query->getModelName(), 'create() sets model name');
        $query = MyCustomBookQuery::create('foo');
        $this->assertTrue($query instanceof MyCustomBookQuery, 'create() returns an object of its class');
        $this->assertEquals('bookstore', $query->getDbName(), 'create() sets dabatase name');
        $this->assertEquals('Propel\Tests\Bookstore\Book', $query->getModelName(), 'create() sets model name');
        $this->assertEquals('foo', $query->getModelAlias(), 'create() can set the model alias');
    }

    /**
     * @return void
     */
    public function testBasePreSelect()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table2Query', 'basePreSelect');
        $this->assertEquals('Propel\Runtime\ActiveQuery\ModelCriteria', $method->getDeclaringClass()->getName(), 'BaseQuery does not override basePreSelect() by default');

        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table3Query', 'basePreSelect');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\Base\Table3Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreSelect() when a behavior is registered');
    }

    /**
     * @return void
     */
    public function testBasePreDelete()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table2Query', 'basePreDelete');
        $this->assertEquals('Propel\Runtime\ActiveQuery\ModelCriteria', $method->getDeclaringClass()->getName(), 'BaseQuery does not override basePreDelete() by default');

        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table3Query', 'basePreDelete');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\Base\Table3Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreDelete() when a behavior is registered');
    }

    /**
     * @return void
     */
    public function testBasePostDelete()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table2Query', 'basePostDelete');
        $this->assertEquals('Propel\Runtime\ActiveQuery\ModelCriteria', $method->getDeclaringClass()->getName(), 'BaseQuery does not override basePostDelete() by default');

        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table3Query', 'basePostDelete');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\Base\Table3Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePostDelete() when a behavior is registered');
    }

    /**
     * @return void
     */
    public function testBasePreUpdate()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table2Query', 'basePreUpdate');
        $this->assertEquals('Propel\Runtime\ActiveQuery\ModelCriteria', $method->getDeclaringClass()->getName(), 'BaseQuery does not override basePreUpdate() by default');

        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table3Query', 'basePreUpdate');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\Base\Table3Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePreUpdate() when a behavior is registered');
    }

    /**
     * @return void
     */
    public function testBasePostUpdate()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table2Query', 'basePostUpdate');
        $this->assertEquals('Propel\Runtime\ActiveQuery\ModelCriteria', $method->getDeclaringClass()->getName(), 'BaseQuery does not override basePostUpdate() by default');

        $method = new ReflectionMethod('\Propel\Tests\Bookstore\Behavior\Table3Query', 'basePostUpdate');
        $this->assertEquals('Propel\Tests\Bookstore\Behavior\Base\Table3Query', $method->getDeclaringClass()->getName(), 'BaseQuery overrides basePostUpdate() when a behavior is registered');
    }

    /**
     * @return void
     */
    public function testQuery()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $q = new BookQuery();
        $book = $q
            ->setModelAlias('b')
            ->where('b.Title like ?', 'Don%')
            ->orderBy('b.ISBN', 'desc')
            ->findOne();
        $this->assertTrue($book instanceof Book);
        $this->assertEquals('Don Juan', $book->getTitle());
    }

    /**
     * @return void
     */
    public function testFindPk()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\BookQuery', 'findPk');
        $this->assertEquals('Propel\Tests\Bookstore\Base\BookQuery', $method->getDeclaringClass()->getName(), 'BaseQuery overrides findPk()');
    }

    /**
     * @return void
     */
    public function testFindPkReturnsCorrectObjectForSimplePrimaryKey()
    {
        $b = new Book();
        $b->setTitle('bar');
        $b->setISBN('FA404');
        $b->save($this->con);
        $count = $this->con->getQueryCount();

        BookTableMap::clearInstancePool();

        $book = BookQuery::create()->findPk($b->getId(), $this->con);
        $this->assertEquals($b, $book);
        $this->assertEquals($count + 1, $this->con->getQueryCount(), 'findPk() issues a database query when instance is not in pool');
    }

    /**
     * @return void
     */
    public function testFindPkUsesInstancePoolingForSimplePrimaryKey()
    {
        $b = new Book();
        $b->setTitle('foo');
        $b->setISBN('FA404');
        $b->save($this->con);
        $count = $this->con->getQueryCount();

        $book = BookQuery::create()->findPk($b->getId(), $this->con);
        $this->assertSame($b, $book);
        $this->assertEquals($count, $this->con->getQueryCount(), 'findPk() does not issue a database query when instance is in pool');
    }

    /**
     * @return void
     */
    public function testFindPkReturnsCorrectObjectForCompositePrimaryKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        foreach ($books as $book) {
            $book->save();
        }

        BookTableMap::clearInstancePool();

        // retrieve the test data
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\BookListRel');
        $bookListRelTest = $c->findOne();
        $pk = $bookListRelTest->getPrimaryKey();

        $q = new BookListRelQuery();
        $bookListRel = $q->findPk($pk);
        $this->assertEquals($bookListRelTest, $bookListRel, 'BaseQuery overrides findPk() for composite primary keysto make it faster');
    }

    /**
     * @return void
     */
    public function testFindPkUsesFindPkSimpleOnEmptyQueries()
    {
        BookQuery::create()->findPk(123, $this->con);
        $expected = 'SELECT id, title, isbn, price, publisher_id, author_id FROM book WHERE id = 123';
        $this->assertEquals($expected, $this->con->getLastExecutedQuery());
    }

    /**
     * @return void
     */
    public function testFindPkSimpleAddsObjectToInstancePool()
    {
        $b = new Book();
        $b->setTitle('foo');
        $b->setISBN('FA404');
        $b->save($this->con);
        BookTableMap::clearInstancePool();

        BookQuery::create()->findPk($b->getId(), $this->con);
        $count = $this->con->getQueryCount();

        $book = BookQuery::create()->findPk($b->getId(), $this->con);
        $this->assertEquals($b, $book);
        $this->assertEquals($count, $this->con->getQueryCount());
    }

    /**
     * @return void
     */
    public function testFindPkUsesFindPkComplexOnNonEmptyQueries()
    {
        BookQuery::create('b')->findPk(123, $this->con);
        $expected = $this->getSql('SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.id=123');
        $this->assertEquals($expected, $this->con->getLastExecutedQuery());
    }

    /**
     * @return void
     */
    public function testFindPkNotUsesInstancePoolingForNonEmptyQueries()
    {
        $b = new Book();
        $b->setTitle('foo');
        $b->setISBN('FA404');
        $b->save($this->con);

        $book = BookQuery::create()->select(['Book.Title', 'Book.ISBN'])->findPk($b->getId(), $this->con);
        $this->assertIsArray($book);

        $book = BookQuery::create()->filterByTitle('bar')->findPk($b->getId(), $this->con);
        $this->assertNull($book);
    }

    /**
     * @return void
     */
    public function testFindPkComplexAddsObjectToInstancePool()
    {
        $b = new Book();
        $b->setTitle('foo');
        $b->setISBN('FA404');
        $b->save($this->con);
        BookTableMap::clearInstancePool();

        BookQuery::create('b')->findPk($b->getId(), $this->con);
        $count = $this->con->getQueryCount();

        $book = BookQuery::create()->findPk($b->getId(), $this->con);
        $this->assertEquals($b, $book);
        $this->assertEquals($count, $this->con->getQueryCount());
    }

    /**
     * @return void
     */
    public function testFindPkCallsPreSelect()
    {
        $q = new MySecondBookQuery();
        $this->assertFalse($q::$preSelectWasCalled);
        $q->findPk(123);
        $this->assertTrue($q::$preSelectWasCalled);
    }

    /**
     * @return void
     */
    public function testFindPks()
    {
        $method = new ReflectionMethod('\Propel\Tests\Bookstore\BookQuery', 'findPks');
        $this->assertEquals('Propel\Tests\Bookstore\Base\BookQuery', $method->getDeclaringClass()->getName(), 'BaseQuery overrides findPks()');
    }

    /**
     * @return void
     */
    public function testFindPksSimpleKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        BookTableMap::clearInstancePool();

        // prepare the test data
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->orderBy('Book.Id', 'desc');
        $testBooks = $c->find();
        $testBook1 = $testBooks->pop();
        $testBook2 = $testBooks->pop();

        $q = new BookQuery();
        $books = $q->findPks([$testBook1->getId(), $testBook2->getId()]);
        $this->assertEquals([$testBook1, $testBook2], $books->getData(), 'BaseQuery overrides findPks() to make it faster');
    }

    /**
     * @return void
     */
    public function testFindPksCompositeKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        foreach ($books as $book) {
            $book->save();
        }

        BookTableMap::clearInstancePool();

        // retrieve the test data
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\BookListRel');
        $bookListRelTest = $c->find();
        $search = [];
        foreach ($bookListRelTest as $obj) {
            $search[] = $obj->getPrimaryKey();
        }

        $q = new BookListRelQuery();
        $objs = $q->findPks($search);
        $this->assertEquals($bookListRelTest->getArrayCopy(), $objs->getArrayCopy(), 'BaseQuery overrides findPks() for composite primary keys to make it work');
    }

    /**
     * @return void
     */
    public function testFilterBy()
    {
        foreach (BookTableMap::getFieldNames(TableMap::TYPE_PHPNAME) as $colName) {
            $filterMethod = 'filterBy' . $colName;
            $this->assertTrue(method_exists('\Propel\Tests\Bookstore\BookQuery', $filterMethod), 'QueryBuilder adds filterByColumn() methods for every column');
            $q = BookQuery::create()->$filterMethod(1);
            $this->assertTrue($q instanceof BookQuery, 'filterByColumn() returns the current query instance');
        }
    }

    /**
     * @return void
     */
    public function testFilterByPrimaryKeySimpleKey()
    {
        $q = BookQuery::create()->filterByPrimaryKey(12);
        $q1 = BookQuery::create()->add(BookTableMap::COL_ID, 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByPrimaryKey() translates to a Criteria::EQUAL in the PK column');

        $q = BookQuery::create()->setModelAlias('b', true)->filterByPrimaryKey(12);
        $q1 = BookQuery::create()->setModelAlias('b', true)->add('b.id', 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByPrimaryKey() uses true table alias if set');
    }

    /**
     * @return void
     */
    public function testFilterByPrimaryKeyCompositeKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        foreach ($books as $book) {
            $book->save();
        }

        BookTableMap::clearInstancePool();

        // retrieve the test data
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\BookListRel');
        $bookListRelTest = $c->findOne();
        $pk = $bookListRelTest->getPrimaryKey();

        $q = new BookListRelQuery();
        $q->filterByPrimaryKey($pk);

        $q1 = BookListRelQuery::create()
            ->add(BookListRelTableMap::COL_BOOK_ID, $pk[0], Criteria::EQUAL)
            ->add(BookListRelTableMap::COL_BOOK_CLUB_LIST_ID, $pk[1], Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByPrimaryKey() translates to a Criteria::EQUAL in the PK columns');
    }

    /**
     * @return void
     */
    public function testFilterByPrimaryKeysSimpleKey()
    {
        $q = BookQuery::create()->filterByPrimaryKeys([10, 11, 12]);
        $q1 = BookQuery::create()->add(BookTableMap::COL_ID, [10, 11, 12], Criteria::IN);
        $this->assertEquals($q1, $q, 'filterByPrimaryKeys() translates to a Criteria::IN on the PK column');

        $q = BookQuery::create()->setModelAlias('b', true)->filterByPrimaryKeys([10, 11, 12]);
        $q1 = BookQuery::create()->setModelAlias('b', true)->add('b.id', [10, 11, 12], Criteria::IN);
        $this->assertEquals($q1, $q, 'filterByPrimaryKeys() uses true table alias if set');
    }

    /**
     * @return void
     */
    public function testFilterByPrimaryKeysCompositeKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        foreach ($books as $book) {
            $book->save();
        }

        BookTableMap::clearInstancePool();

        // retrieve the test data
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\BookListRel');
        $bookListRelTest = $c->find();
        $search = [];
        foreach ($bookListRelTest as $obj) {
            $search[] = $obj->getPrimaryKey();
        }

        $q = new BookListRelQuery();
        $q->filterByPrimaryKeys($search);

        $q1 = BookListRelQuery::create();
        foreach ($search as $key) {
            $cton0 = $q1->getNewCriterion(BookListRelTableMap::COL_BOOK_ID, $key[0], Criteria::EQUAL);
            $cton1 = $q1->getNewCriterion(BookListRelTableMap::COL_BOOK_CLUB_LIST_ID, $key[1], Criteria::EQUAL);
            $cton0->addAnd($cton1);
            $q1->addOr($cton0);
        }
        $this->assertEquals($q1, $q, 'filterByPrimaryKeys() translates to a series of Criteria::EQUAL in the PK columns');

        $q = new BookListRelQuery();
        $q->filterByPrimaryKeys([]);

        $q1 = BookListRelQuery::create();
        $q1->add(null, '1<>1', Criteria::CUSTOM);
        $this->assertEquals($q1, $q, 'filterByPrimaryKeys() translates to an always failing test on empty arrays');
    }

    /**
     * @return void
     */
    public function testFilterByIntegerPk()
    {
        $q = BookQuery::create()->filterById(12);
        $q1 = BookQuery::create()->add(BookTableMap::COL_ID, 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByPkColumn() translates to a Criteria::EQUAL by default');

        $q = BookQuery::create()->filterById(12, Criteria::NOT_EQUAL);
        $q1 = BookQuery::create()->add(BookTableMap::COL_ID, 12, Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByPkColumn() accepts an optional comparison operator');

        $q = BookQuery::create()->setModelAlias('b', true)->filterById(12);
        $q1 = BookQuery::create()->setModelAlias('b', true)->add('b.id', 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByPkColumn() uses true table alias if set');

        $q = BookQuery::create()->filterById([10, 11, 12]);
        $q1 = BookQuery::create()->add(BookTableMap::COL_ID, [10, 11, 12], Criteria::IN);
        $this->assertEquals($q1, $q, 'filterByPkColumn() translates to a Criteria::IN when passed a simple array key');

        $q = BookQuery::create()->filterById([10, 11, 12], Criteria::NOT_IN);
        $q1 = BookQuery::create()->add(BookTableMap::COL_ID, [10, 11, 12], Criteria::NOT_IN);
        $this->assertEquals($q1, $q, 'filterByPkColumn() accepts a comparison when passed a simple array key');
    }

    /**
     * @return void
     */
    public function testFilterByNumber()
    {
        $q = BookQuery::create()->filterByPrice(12);
        $q1 = BookQuery::create()->add(BookTableMap::COL_PRICE, 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::EQUAL by default');

        $q = BookQuery::create()->filterByPrice(12, Criteria::NOT_EQUAL);
        $q1 = BookQuery::create()->add(BookTableMap::COL_PRICE, 12, Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByNumColumn() accepts an optional comparison operator');

        $q = BookQuery::create()->setModelAlias('b', true)->filterByPrice(12);
        $q1 = BookQuery::create()->setModelAlias('b', true)->add('b.price', 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByNumColumn() uses true table alias if set');

        $q = BookQuery::create()->filterByPrice([10, 11, 12]);
        $q1 = BookQuery::create()->add(BookTableMap::COL_PRICE, [10, 11, 12], Criteria::IN);
        $this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::IN when passed a simple array key');

        $q = BookQuery::create()->filterByPrice([10, 11, 12], Criteria::NOT_IN);
        $q1 = BookQuery::create()->add(BookTableMap::COL_PRICE, [10, 11, 12], Criteria::NOT_IN);
        $this->assertEquals($q1, $q, 'filterByNumColumn() accepts a comparison when passed a simple array key');

        $q = BookQuery::create()->filterByPrice(['min' => 10]);
        $q1 = BookQuery::create()->add(BookTableMap::COL_PRICE, 10, Criteria::GREATER_EQUAL);
        $this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::GREATER_EQUAL when passed a \'min\' key');

        $q = BookQuery::create()->filterByPrice(['max' => 12]);
        $q1 = BookQuery::create()->add(BookTableMap::COL_PRICE, 12, Criteria::LESS_EQUAL);
        $this->assertEquals($q1, $q, 'filterByNumColumn() translates to a Criteria::LESS_EQUAL when passed a \'max\' key');

        $q = BookQuery::create()->filterByPrice(['min' => 10, 'max' => 12]);
        $q1 = BookQuery::create()
            ->add(BookTableMap::COL_PRICE, 10, Criteria::GREATER_EQUAL)
            ->addAnd(BookTableMap::COL_PRICE, 12, Criteria::LESS_EQUAL);
        $this->assertEquals($q1, $q, 'filterByNumColumn() translates to a between when passed both a \'min\' and a \'max\' key');
    }

    /**
     * @return void
     */
    public function testFilterByTimestamp()
    {
        $q = BookstoreEmployeeAccountQuery::create()->filterByCreated(12);
        $q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountTableMap::COL_CREATED, 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByDateColumn() translates to a Criteria::EQUAL by default');

        $q = BookstoreEmployeeAccountQuery::create()->filterByCreated(12, Criteria::NOT_EQUAL);
        $q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountTableMap::COL_CREATED, 12, Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByDateColumn() accepts an optional comparison operator');

        $q = BookstoreEmployeeAccountQuery::create()->setModelAlias('b', true)->filterByCreated(12);
        $q1 = BookstoreEmployeeAccountQuery::create()->setModelAlias('b', true)->add('b.created', 12, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByDateColumn() uses true table alias if set');

        $q = BookstoreEmployeeAccountQuery::create()->filterByCreated(['min' => 10]);
        $q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountTableMap::COL_CREATED, 10, Criteria::GREATER_EQUAL);
        $this->assertEquals($q1, $q, 'filterByDateColumn() translates to a Criteria::GREATER_EQUAL when passed a \'min\' key');

        $q = BookstoreEmployeeAccountQuery::create()->filterByCreated(['max' => 12]);
        $q1 = BookstoreEmployeeAccountQuery::create()->add(BookstoreEmployeeAccountTableMap::COL_CREATED, 12, Criteria::LESS_EQUAL);
        $this->assertEquals($q1, $q, 'filterByDateColumn() translates to a Criteria::LESS_EQUAL when passed a \'max\' key');

        $q = BookstoreEmployeeAccountQuery::create()->filterByCreated(['min' => 10, 'max' => 12]);
        $q1 = BookstoreEmployeeAccountQuery::create()
            ->add(BookstoreEmployeeAccountTableMap::COL_CREATED, 10, Criteria::GREATER_EQUAL)
            ->addAnd(BookstoreEmployeeAccountTableMap::COL_CREATED, 12, Criteria::LESS_EQUAL);
        $this->assertEquals($q1, $q, 'filterByDateColumn() translates to a between when passed both a \'min\' and a \'max\' key');
    }

    /**
     * @return void
     */
    public function testFilterByString()
    {
        $q = BookQuery::create()->filterByTitle('foo');
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, 'foo', Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::EQUAL by default');

        $q = BookQuery::create()->filterByTitle('foo', Criteria::NOT_EQUAL);
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, 'foo', Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByStringColumn() accepts an optional comparison operator');

        $q = BookQuery::create()->setModelAlias('b', true)->filterByTitle('foo');
        $q1 = BookQuery::create()->setModelAlias('b', true)->add('b.title', 'foo', Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByStringColumn() uses true table alias if set');

        $q = BookQuery::create()->filterByTitle(['foo', 'bar']);
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, ['foo', 'bar'], Criteria::IN);
        $this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::IN when passed an array');

        $q = BookQuery::create()->filterByTitle(['foo', 'bar'], Criteria::NOT_IN);
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, ['foo', 'bar'], Criteria::NOT_IN);
        $this->assertEquals($q1, $q, 'filterByStringColumn() accepts a comparison when passed an array');

        $q = BookQuery::create()->filterByTitle('foo%', Criteria::LIKE);
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, 'foo%', Criteria::LIKE);
        $this->assertEquals($q1, $q, 'filterByStringColumn() translates to a Criteria::LIKE when passed a string with a % wildcard');

        $q = BookQuery::create()->filterByTitle('foo%', Criteria::NOT_LIKE);
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, 'foo%', Criteria::NOT_LIKE);
        $this->assertEquals($q1, $q, 'filterByStringColumn() accepts a comparison when passed a string with a % wildcard');

        $q = BookQuery::create()->filterByTitle('foo%', Criteria::EQUAL);
        $q1 = BookQuery::create()->add(BookTableMap::COL_TITLE, 'foo%', Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByStringColumn() accepts a comparison when passed a string with a % wildcard');
    }

    /**
     * @return void
     */
    public function testFilterByBoolean()
    {
        $q = ReviewQuery::create()->filterByRecommended(true);
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, true, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a Criteria::EQUAL by default');

        $q = ReviewQuery::create()->filterByRecommended(true, Criteria::NOT_EQUAL);
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, true, Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() accepts an optional comparison operator');

        $q = ReviewQuery::create()->filterByRecommended(false);
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, false, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a Criteria::EQUAL by default');

        $q = ReviewQuery::create()->setModelAlias('b', true)->filterByRecommended(true);
        $q1 = ReviewQuery::create()->setModelAlias('b', true)->add('b.recommended', true, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() uses true table alias if set');

        $q = ReviewQuery::create()->filterByRecommended('true');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, true, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = true when passed a true string');

        $q = ReviewQuery::create()->filterByRecommended('yes');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, true, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = true when passed a true string');

        $q = ReviewQuery::create()->filterByRecommended('1');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, true, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = true when passed a true string');

        $q = ReviewQuery::create()->filterByRecommended('false');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, false, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed a false string');

        $q = ReviewQuery::create()->filterByRecommended('no');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, false, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed a false string');

        $q = ReviewQuery::create()->filterByRecommended('0');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, false, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed a false string');

        $q = ReviewQuery::create()->filterByRecommended('');
        $q1 = ReviewQuery::create()->add(ReviewTableMap::COL_RECOMMENDED, false, Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByBooleanColumn() translates to a = false when passed an empty string');
    }

    /**
     * @return void
     */
    public function testFilterByFk()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\BookQuery', 'filterByAuthor'), 'QueryBuilder adds filterByFk() methods');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\BookQuery', 'filterByPublisher'), 'QueryBuilder adds filterByFk() methods for all fkeys');

        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\EssayQuery', 'filterByFirstAuthor'), 'QueryBuilder adds filterByFk() methods for several fkeys on the same table');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\EssayQuery', 'filterBySecondAuthor'), 'QueryBuilder adds filterByFk() methods for several fkeys on the same table');
    }

    /**
     * @return void
     */
    public function testFilterByFkSimpleKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // prepare the test data
        $testBook = BookQuery::create()
            ->innerJoin('Book.Author') // just in case there are books with no author
            ->findOne();
        $testAuthor = $testBook->getAuthor();

        $book = BookQuery::create()
            ->filterByAuthor($testAuthor)
            ->findOne();
        $this->assertEquals($testBook, $book, 'Generated query handles filterByFk() methods correctly for simple fkeys');

        $q = BookQuery::create()->filterByAuthor($testAuthor);
        $q1 = BookQuery::create()->add(BookTableMap::COL_AUTHOR_ID, $testAuthor->getId(), Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByFk() translates to a Criteria::EQUAL by default');

        $q = BookQuery::create()->filterByAuthor($testAuthor, Criteria::NOT_EQUAL);
        $q1 = BookQuery::create()->add(BookTableMap::COL_AUTHOR_ID, $testAuthor->getId(), Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByFk() accepts an optional comparison operator');
    }

    /**
     * @return void
     */
    public function testFilterByFkCompositeKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        BookstoreDataPopulator::populateOpinionFavorite();

        // prepare the test data
        $testOpinion = BookOpinionQuery::create()
            ->innerJoin('BookOpinion.ReaderFavorite') // just in case there are books with no author
            ->findOne();
        $testFavorite = $testOpinion->getReaderFavorite();

        $favorite = ReaderFavoriteQuery::create()
            ->filterByBookOpinion($testOpinion)
            ->findOne();
        $this->assertEquals($testFavorite, $favorite, 'Generated query handles filterByFk() methods correctly for composite fkeys');
    }

    /**
     * @return void
     */
    public function testFilterByFkObjectCollection()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $authors = AuthorQuery::create()
            ->orderByFirstName()
            ->limit(2)
            ->find($this->con);

        $books = BookQuery::create()
            ->filterByAuthor($authors)
            ->find($this->con);
        $q1 = $this->con->getLastExecutedQuery();

        $books = BookQuery::create()
            ->add(BookTableMap::COL_AUTHOR_ID, $authors->getPrimaryKeys(), Criteria::IN)
            ->find($this->con);
        $q2 = $this->con->getLastExecutedQuery();

        $this->assertEquals($q2, $q1, 'filterByFk() accepts a collection and results to an IN query');
    }

    /**
     * @return void
     */
    public function testFilterByRefFk()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\BookQuery', 'filterByReview'), 'QueryBuilder adds filterByRefFk() methods');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\BookQuery', 'filterByMedia'), 'QueryBuilder adds filterByRefFk() methods for all fkeys');

        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\AuthorQuery', 'filterByEssayRelatedByFirstAuthorId'), 'QueryBuilder adds filterByRefFk() methods for several fkeys on the same table');
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\AuthorQuery', 'filterByEssayRelatedBySecondAuthorId'), 'QueryBuilder adds filterByRefFk() methods for several fkeys on the same table');
    }

    /**
     * @return void
     */
    public function testFilterByRefFkSimpleKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // prepare the test data
        $testBook = BookQuery::create()
            ->innerJoin('Book.Author') // just in case there are books with no author
            ->findOne();
        $testAuthor = $testBook->getAuthor();

        $author = AuthorQuery::create()
            ->filterByBook($testBook)
            ->findOne();
        $this->assertEquals($testAuthor, $author, 'Generated query handles filterByRefFk() methods correctly for simple fkeys');

        $q = AuthorQuery::create()->filterByBook($testBook);
        $q1 = AuthorQuery::create()->add(AuthorTableMap::COL_ID, $testBook->getAuthorId(), Criteria::EQUAL);
        $this->assertEquals($q1, $q, 'filterByRefFk() translates to a Criteria::EQUAL by default');

        $q = AuthorQuery::create()->filterByBook($testBook, Criteria::NOT_EQUAL);
        $q1 = AuthorQuery::create()->add(AuthorTableMap::COL_ID, $testBook->getAuthorId(), Criteria::NOT_EQUAL);
        $this->assertEquals($q1, $q, 'filterByRefFk() accepts an optional comparison operator');
    }

    /**
     * @return void
     */
    public function testFilterByRelationNameCompositePk()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $testLabel = RecordLabelQuery::create()
            ->findOne($this->con);

        $testRelease = ReleasePoolQuery::create()
            ->addJoin(ReleasePoolTableMap::COL_RECORD_LABEL_ID, RecordLabelTableMap::COL_ID)
            ->filterByRecordLabel($testLabel)
            ->find($this->con);
        $q1 = $this->con->getLastExecutedQuery();

        $releasePool = ReleasePoolQuery::create()
            ->addJoin(ReleasePoolTableMap::COL_RECORD_LABEL_ID, RecordLabelTableMap::COL_ID)
            ->add(ReleasePoolTableMap::COL_RECORD_LABEL_ID, $testLabel->getId(), Criteria::EQUAL)
            ->add(ReleasePoolTableMap::COL_RECORD_LABEL_ABBR, $testLabel->getAbbr(), Criteria::EQUAL)
            ->find($this->con);
        $q2 = $this->con->getLastExecutedQuery();

        $this->assertEquals($q2, $q1, 'Generated query handles filterByRefFk() methods correctly for composite fkeys');
        $this->assertEquals($releasePool, $testRelease);
    }

    /**
     * @return void
     */
    public function testFilterUsingCollectionByRelationNameCompositePk()
    {
        $this->expectException(PropelException::class);

        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $testLabel = RecordLabelQuery::create()
            ->limit(2)
            ->find($this->con);

        ReleasePoolQuery::create()
            ->addJoin(ReleasePoolTableMap::COL_RECORD_LABEL_ID, RecordLabelTableMap::COL_ID)
            ->filterByRecordLabel($testLabel)
            ->find($this->con);

        $this->fail('Expected PropelException : filterBy{RelationName}() only accepts arguments of type {RelationName}');
    }

    /**
     * @return void
     */
    public function testFilterByRefNonPrimaryFKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $testBookstoreEmployeeAccount = BookstoreEmployeeAccountQuery::create()
            ->findOne();
        $testAccAuditLog = $testBookstoreEmployeeAccount->getAcctAuditLogs();

        $result = AcctAuditLogQuery::create()
            ->addJoin(AcctAuditLogTableMap::COL_UID, BookstoreEmployeeAccountTableMap::COL_LOGIN)
            ->filterByBookstoreEmployeeAccount($testBookstoreEmployeeAccount)
            ->find($this->con);

        $this->assertEquals($testAccAuditLog, $result, 'Generated query handles filterByRefFk() methods correctly for non primary fkeys');
    }

    /**
     * @return void
     */
    public function testFilterByRefFkCompositeKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        BookstoreDataPopulator::populateOpinionFavorite();

        // prepare the test data
        $testOpinion = BookOpinionQuery::create()
            ->innerJoin('BookOpinion.ReaderFavorite') // just in case there are books with no author
            ->findOne();
        $testFavorite = $testOpinion->getReaderFavorite();

        $opinion = BookOpinionQuery::create()
            ->filterByReaderFavorite($testFavorite)
            ->findOne();
        $this->assertEquals($testOpinion, $opinion, 'Generated query handles filterByRefFk() methods correctly for composite fkeys');
    }

    /**
     * @return void
     */
    public function testFilterByRefFkObjectCollection()
    {
        BookstoreDataPopulator::depopulate($this->con);
        BookstoreDataPopulator::populate($this->con);

        $books = BookQuery::create()
            ->orderByTitle()
            ->limit(2)
            ->find($this->con);

        $authors = AuthorQuery::create()
            ->filterByBook($books)
            ->find($this->con);
        $q1 = $this->con->getLastExecutedQuery();

        $authors = AuthorQuery::create()
            ->addJoin(AuthorTableMap::COL_ID, BookTableMap::COL_AUTHOR_ID, Criteria::LEFT_JOIN)
            ->add(BookTableMap::COL_ID, $books->getPrimaryKeys(), Criteria::IN)
            ->find($this->con);
        $q2 = $this->con->getLastExecutedQuery();

        $this->assertEquals($q2, $q1, 'filterByRefFk() accepts a collection and results to an IN query in the joined table');
    }

    /**
     * @return void
     */
    public function testFilterByCrossFK()
    {
        $this->assertTrue(method_exists('\Propel\Tests\Bookstore\BookQuery', 'filterByBookClubList'), 'Generated query handles filterByCrossRefFK() for many-to-many relationships');
        $this->assertFalse(method_exists('\Propel\Tests\Bookstore\BookQuery', 'filterByBook'), 'Generated query handles filterByCrossRefFK() for many-to-many relationships');
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();
        $blc1 = BookClubListQuery::create()->findOneByGroupLeader('Crazyleggs');
        $nbBooks = BookQuery::create()
            ->filterByBookClubList($blc1)
            ->count();
        $this->assertEquals(2, $nbBooks, 'Generated query handles filterByCrossRefFK() methods correctly');
    }

    /**
     * @return void
     */
    public function testJoinFk()
    {
        $q = BookQuery::create()
            ->joinAuthor();
        $q1 = BookQuery::create()
            ->join('Book.Author', Criteria::LEFT_JOIN);
        $this->assertTrue($q->equals($q1), 'joinFk() translates to a left join on non-required columns');

        $q = BookSummaryQuery::create()
            ->joinSummarizedBook();
        $q1 = BookSummaryQuery::create()
            ->join('BookSummary.SummarizedBook', Criteria::INNER_JOIN);
        $this->assertTrue($q->equals($q1), 'joinFk() translates to an inner join on required columns');

        $q = BookQuery::create()
            ->joinAuthor('a');
        $q1 = BookQuery::create()
            ->join('Book.Author a', Criteria::LEFT_JOIN);
        $this->assertTrue($q->equals($q1), 'joinFk() accepts a relation alias as first parameter');

        $q = BookQuery::create()
            ->joinAuthor('', Criteria::INNER_JOIN);
        $q1 = BookQuery::create()
            ->join('Book.Author', Criteria::INNER_JOIN);
        $this->assertTrue($q->equals($q1), 'joinFk() accepts a join type as second parameter');

        $q = EssayQuery::create()
            ->innerJoinSecondAuthor();
        $q1 = EssayQuery::create()
            ->join('Essay.SecondAuthor', 'INNER JOIN');
        $this->assertTrue($q->equals($q1), 'joinFk() translates to a "INNER JOIN" when this is defined as defaultJoin in the schema');
    }

    /**
     * @return void
     */
    public function testJoinFkAlias()
    {
        $q = BookQuery::create('b')
            ->joinAuthor('a');
        $q1 = BookQuery::create('b')
            ->join('b.Author a', Criteria::LEFT_JOIN);
        $this->assertTrue($q->equals($q1), 'joinFk() works fine with table aliases');

        $q = BookQuery::create()
            ->setModelAlias('b', true)
            ->joinAuthor('a');
        $q1 = BookQuery::create()
            ->setModelAlias('b', true)
            ->join('b.Author a', Criteria::LEFT_JOIN);
        $this->assertTrue($q->equals($q1), 'joinFk() works fine with true table aliases');
    }

    /**
     * @return void
     */
    public function testJoinRefFk()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $q1 = AuthorQuery::create()
            ->join('Author.Book', Criteria::LEFT_JOIN);
        $this->assertTrue($q->equals($q1), 'joinRefFk() translates to a left join on non-required columns');

        $q = BookQuery::create()
            ->joinBookSummary();
        $q1 = BookQuery::create()
            ->join('Book.BookSummary', Criteria::INNER_JOIN);
        $this->assertTrue($q->equals($q1), 'joinRefFk() translates to an inner join on required columns');

        $q = AuthorQuery::create()
            ->joinBook('b');
        $q1 = AuthorQuery::create()
            ->join('Author.Book b', Criteria::LEFT_JOIN);
        $this->assertTrue($q->equals($q1), 'joinRefFk() accepts a relation alias as first parameter');

        $q = AuthorQuery::create()
            ->joinBook('', Criteria::INNER_JOIN);
        $q1 = AuthorQuery::create()
            ->join('Author.Book', Criteria::INNER_JOIN);
        $this->assertTrue($q->equals($q1), 'joinRefFk() accepts a join type as second parameter');

        $q = AuthorQuery::create()
            ->joinEssayRelatedBySecondAuthorId();
        $q1 = AuthorQuery::create()
            ->join('Author.EssayRelatedBySecondAuthorId', Criteria::INNER_JOIN);
        $this->assertTrue($q->equals($q1), 'joinRefFk() translates to a "INNER JOIN" when this is defined as defaultJoin in the schema');
    }

    /**
     * @return void
     */
    public function testUseFkQuerySimple()
    {
        $q = BookQuery::create()
            ->useAuthorQuery()
                ->filterByFirstName('Leo')
            ->endUse();
        $q1 = BookQuery::create()
            ->join('Book.Author', Criteria::LEFT_JOIN)
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() translates to a condition on a left join on non-required columns');

        $q = BookSummaryQuery::create()
            ->useSummarizedBookQuery()
                ->filterByTitle('War And Peace')
            ->endUse();
        $q1 = BookSummaryQuery::create()
            ->join('BookSummary.SummarizedBook', Criteria::INNER_JOIN)
            ->add(BookTableMap::COL_TITLE, 'War And Peace', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() translates to a condition on an inner join on required columns');
    }

    /**
     * @return void
     */
    public function testUseFkQueryWith()
    {
        $q = BookQuery::create()
            ->withAuthorQuery(
                function (AuthorQuery $q) {
                    return $q->filterByFirstName('Leo');
                }
            );
        $q1 = BookQuery::create()
            ->useAuthorQuery()
            ->filterByFirstName('Leo')
            ->endUse();
        $this->assertTrue($q->equals($q1), 'useFkQuery() translates to a condition on a left join on non-required columns');

        $q = BookSummaryQuery::create()
            ->withSummarizedBookQuery(
                function (BookQuery $q) {
                    return $q->filterByTitle('War and Peace');
                }
            );
        $q1 = BookSummaryQuery::create()
            ->useSummarizedBookQuery()
            ->filterByTitle('War and Peace')
            ->endUse();
        $this->assertEquals($q1, $q, 'useFkQuery() translates to a condition on an inner join on required columns');
    }

    /**
     * @return void
     */
    public function testUseFkQueryJoinType()
    {
        $q = BookQuery::create()
            ->useAuthorQuery(null, Criteria::LEFT_JOIN)
                ->filterByFirstName('Leo')
            ->endUse();
        $q1 = BookQuery::create()
            ->join('Book.Author', Criteria::LEFT_JOIN)
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() accepts a join type as second parameter');
    }

    /**
     * @return void
     */
    public function testUseFkQueryAlias()
    {
        $q = BookQuery::create()
            ->useAuthorQuery('a')
                ->filterByFirstName('Leo')
            ->endUse();
        $join = new ModelJoin();
        $join->setJoinType(Criteria::LEFT_JOIN);
        $join->setTableMap(AuthorTableMap::getTableMap());
        $join->setRelationMap(BookTableMap::getTableMap()->getRelation('Author'), null, 'a');
        $join->setRelationAlias('a');
        $q1 = BookQuery::create()
            ->addAlias('a', AuthorTableMap::TABLE_NAME)
            ->addJoinObject($join, 'a')
            ->add('a.first_name', 'Leo', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() uses the first argument as a table alias');
    }

    /**
     * @return void
     */
    public function testUseFkQueryMixed()
    {
        $q = BookQuery::create()
            ->useAuthorQuery()
                ->filterByFirstName('Leo')
            ->endUse()
            ->filterByTitle('War And Peace');
        $q1 = BookQuery::create()
            ->join('Book.Author', Criteria::LEFT_JOIN)
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL)
            ->add(BookTableMap::COL_TITLE, 'War And Peace', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() allows combining conditions on main and related query');
    }

    /**
     * @return void
     */
    public function testUseFkQueryTwice()
    {
        $q = BookQuery::create()
            ->useAuthorQuery()
                ->filterByFirstName('Leo')
            ->endUse()
            ->useAuthorQuery()
                ->filterByLastName('Tolstoi')
            ->endUse();
        $q1 = BookQuery::create()
            ->join('Book.Author', Criteria::LEFT_JOIN)
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL)
            ->add(AuthorTableMap::COL_LAST_NAME, 'Tolstoi', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() called twice on the same relation does not create two joins');
    }

    /**
     * @return void
     */
    public function testUseFkQueryTwiceTwoAliases()
    {
        $q = BookQuery::create()
            ->useAuthorQuery('a')
                ->filterByFirstName('Leo')
            ->endUse()
            ->useAuthorQuery('b')
                ->filterByLastName('Tolstoi')
            ->endUse();
        $join1 = new ModelJoin();
        $join1->setJoinType(Criteria::LEFT_JOIN);
        $join1->setTableMap(AuthorTableMap::getTableMap());
        $join1->setRelationMap(BookTableMap::getTableMap()->getRelation('Author'), null, 'a');
        $join1->setRelationAlias('a');
        $join2 = new ModelJoin();
        $join2->setJoinType(Criteria::LEFT_JOIN);
        $join2->setTableMap(AuthorTableMap::getTableMap());
        $join2->setRelationMap(BookTableMap::getTableMap()->getRelation('Author'), null, 'b');
        $join2->setRelationAlias('b');
        $q1 = BookQuery::create()
            ->addAlias('a', AuthorTableMap::TABLE_NAME)
            ->addJoinObject($join1, 'a')
            ->add('a.first_name', 'Leo', Criteria::EQUAL)
            ->addAlias('b', AuthorTableMap::TABLE_NAME)
            ->addJoinObject($join2, 'b')
            ->add('b.last_name', 'Tolstoi', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() called twice on the same relation with two aliases creates two joins');
    }

    /**
     * @return void
     */
    public function testUseFkQueryNested()
    {
        $q = ReviewQuery::create()
            ->useBookQuery()
                ->useAuthorQuery()
                    ->filterByFirstName('Leo')
                ->endUse()
            ->endUse();
        $q1 = ReviewQuery::create()
            ->join('Review.Book', Criteria::LEFT_JOIN)
            ->join('Book.Author', Criteria::LEFT_JOIN)
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL);
        // embedded queries create joins that keep a relation to the parent
        // as this is not testable, we need to use another testing technique
        $params = [];
        $result = $q->createSelectSql($params);
        $expectedParams = [];
        $expectedResult = $q1->createSelectSql($expectedParams);
        $this->assertEquals($expectedParams, $params, 'useFkQuery() called nested creates two joins');
        $this->assertEquals($expectedResult, $result, 'useFkQuery() called nested creates two joins');
    }

    /**
     * @return void
     */
    public function testUseFkQueryTwoRelations()
    {
        $q = BookQuery::create()
            ->useAuthorQuery()
                ->filterByFirstName('Leo')
            ->endUse()
            ->usePublisherQuery()
                ->filterByName('Penguin')
            ->endUse();
        $q1 = BookQuery::create()
            ->join('\Propel\Tests\Bookstore\Book.Author', Criteria::LEFT_JOIN)
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL)
            ->join('\Propel\Tests\Bookstore\Book.Publisher', Criteria::LEFT_JOIN)
            ->add(PublisherTableMap::COL_NAME, 'Penguin', Criteria::EQUAL);
        $this->assertTrue($q->equals($q1), 'useFkQuery() called twice on two relations creates two joins');
    }

    /**
     * @return void
     */
    public function testUseFkQueryNoAliasThenWith()
    {
        $con = Propel::getServiceContainer()->getReadConnection(BookTableMap::DATABASE_NAME);
        $books = BookQuery::create()
            ->useAuthorQuery()
                ->filterByFirstName('Leo')
            ->endUse()
            ->with('Author')
            ->find($con);
        $q1 = $con->getLastExecutedQuery();
        $books = BookQuery::create()
            ->leftJoinWithAuthor()
            ->add(AuthorTableMap::COL_FIRST_NAME, 'Leo', Criteria::EQUAL)
            ->find($con);
        $q2 = $con->getLastExecutedQuery();
        $this->assertEquals($q1, $q2, 'with() can be used after a call to useFkQuery() with no alias');
    }

    /**
     * @return void
     */
    public function testUseRelationExistsQuery()
    {
        $expected = BookQuery::create()
        ->useExistsQuery('Author')
        ->filterByFirstName('Leo')
        ->endUse();
        $actual = BookQuery::create()
        ->useAuthorExistsQuery()
        ->filterByFirstName('Leo')
        ->endUse();

        $this->assertEquals($expected, $actual, 'useExistsQuery() is available and calls correct parent method');
    }

    /**
     * @return void
     */
    public function testUseRelationNotExistsQuery()
    {
        $expected = BookQuery::create()
        ->useExistsQuery('Author', null, null, ExistsCriterion::TYPE_NOT_EXISTS)
        ->filterByFirstName('Leo')
        ->endUse();
        $actual = BookQuery::create()
        ->useAuthorNotExistsQuery()
        ->filterByFirstName('Leo')
        ->endUse();

        $this->assertEquals($expected, $actual, 'useNotExistsQuery() is available and calls correct parent method');
    }

    /**
     * @return void
     */
    public function testUseRelationExistsQueryWithCustomQueryClass()
    {
        $query = BookQuery::create()->useAuthorExistsQuery(null, BookClubListQuery::class, false);
        $this->assertInstanceOf(BookClubListQuery::class, $query, 'useExistsQuery() passes on given query class');
    }

    /**
     * @return void
     */
    public function testUseRelationNotExistsQueryWithCustomQueryClass()
    {
        $query = BookQuery::create()->useAuthorNotExistsQuery(null, BookClubListQuery::class);
        $this->assertInstanceOf(BookClubListQuery::class, $query, 'useNotExistsQuery() passes on given query class');
    }

    /**
     * @return void
     */
    public function testPrune()
    {
        $q = BookQuery::create()->prune();
        $this->assertTrue($q instanceof BookQuery, 'prune() returns the current Query object');
    }

    /**
     * @return void
     */
    public function testPruneSimpleKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $nbBooks = BookQuery::create()->prune()->count();
        $this->assertEquals(4, $nbBooks, 'prune() does nothing when passed a null object');

        $testBook = BookQuery::create()->findOne();
        $nbBooks = BookQuery::create()->prune($testBook)->count();
        $this->assertEquals(3, $nbBooks, 'prune() removes an object from the result');
    }

    /**
     * @return void
     */
    public function testPruneCompositeKey()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        // save all books to make sure related objects are also saved - BookstoreDataPopulator keeps some unsaved
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $books = $c->find();
        foreach ($books as $book) {
            $book->save();
        }

        BookTableMap::clearInstancePool();

        $nbBookListRel = BookListRelQuery::create()->prune()->count();
        $this->assertEquals(2, $nbBookListRel, 'prune() does nothing when passed a null object');

        $testBookListRel = BookListRelQuery::create()->findOne();
        $nbBookListRel = BookListRelQuery::create()->prune($testBookListRel)->count();
        $this->assertEquals(1, $nbBookListRel, 'prune() removes an object from the result');
    }

    /**
     * @return void
     */
    public function testFindPkSimpleThrowsExceptionWhenTableIsAbstract(): void
    {
        $databaseXml = '
<database>
    <table name="my_table" abstract="true">
        <column name="id" type="integer" primaryKey="true"/>
    </table>
</database>
';
        $script = TestableQueryBuilder::forTableFromXml($databaseXml, 'my_table')->buildScript('addFindPkSimple');
        $throwStatement = 'throw new PropelException(\'MyTable is declared abstract, you cannot query it.\');';
        $msg = 'Query class for abstract table should throw exception when calling findPkSimple()';
        $this->assertStringContainsString($throwStatement, $script, $msg);
    }

    /**
     * @return void
     */
    public function testFindPkSimpleThrowsNoExceptionWhenTableIsAbstractWithInheritance(): void
    {
        $databaseXml = '
<database>
    <table name="my_table" abstract="true">
        <column name="id" type="integer" primaryKey="true"/>
        <column name="class_key" type="integer" inheritance="single">
            <inheritance key="1" class="class1"/>
            <inheritance key="2" class="class2" extends="my_table"/>
        </column>
    </table>
</database>
';
        $script = TestableQueryBuilder::forTableFromXml($databaseXml, 'my_table')->buildScript('addFindPkSimple');
        $throwStatement = 'throw new PropelException(\'MyTable is declared abstract, you cannot query it.\');';
        $msg = 'Query class for abstract table should not have abstract findPkSimple() method if table uses inheritance';
        $this->assertStringNotContainsString($throwStatement, $script, $msg);
    }
}
