<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\BookOpinion;
use Propel\Tests\Bookstore\BookReader;
use Propel\Tests\Bookstore\Essay;
use Propel\Tests\Bookstore\Publisher;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\Map\EssayTableMap;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Bookstore\Map\BookOpinionTableMap;
use Propel\Tests\Bookstore\Map\BookReaderTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

/**
 * Test class for ObjectFormatter when Criteria uses with().
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ObjectFormatterWithTest extends BookstoreEmptyTestBase
{
    protected function assertCorrectHydration1($c, $msg)
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals($book->getTitle(), 'Don Juan', 'Main object is correctly hydrated ' . $msg);
        $author = $book->getAuthor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ' . $msg);
        $this->assertEquals($author->getLastName(), 'Byron', 'Related object is correctly hydrated ' . $msg);
        $publisher = $book->getPublisher();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ' . $msg);
        $this->assertEquals($publisher->getName(), 'Penguin', 'Related object is correctly hydrated ' . $msg);
    }

    public function testOneToManyRelationHydration()
    {
        BookstoreDataPopulator::populate();
        $bookQuery = BookQuery::create()
            ->leftJoinReview('review')
            ->with('review')
            ->filterByISBN('043935806X'); //Harry Potter

        Propel::disableInstancePooling();
        /** @var Book $books */
        $book = $bookQuery->find()[0];
        $this->assertCount(2, $book->getReviews());

        Propel::enableInstancePooling();
        /** @var Book $books */
        $book = $bookQuery->find()[0];
        $this->assertCount(2, $book->getReviews());
    }

    public function testFindOneWith()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->join('Propel\Tests\Bookstore\Book.Publisher');
        $c->with('Publisher');
        $this->assertCorrectHydration1($c, 'without instance pool');
    }

    public function testFindOneWithAlias()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author a');
        $c->with('a');
        $c->join('Propel\Tests\Bookstore\Book.Publisher p');
        $c->with('p');
        $this->assertCorrectHydration1($c, 'with alias');
    }

    public function testFindOneWithMainAlias()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setModelAlias('b', true);
        $c->orderBy('b.Title');
        $c->join('b.Author a');
        $c->with('a');
        $c->join('b.Publisher p');
        $c->with('p');
        $this->assertCorrectHydration1($c, 'with main alias');
    }

    public function testFindOneWithUsingInstancePool()
    {
        BookstoreDataPopulator::populate();
        // instance pool contains all objects by default, since they were just populated
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->join('Propel\Tests\Bookstore\Book.Publisher');
        $c->with('Publisher');
        $this->assertCorrectHydration1($c, 'with instance pool');
    }

    public function testFindOneWithoutUsingInstancePool()
    {
        BookstoreDataPopulator::populate();
        Propel::disableInstancePooling();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->join('Propel\Tests\Bookstore\Book.Publisher');
        $c->with('Publisher');
        $this->assertCorrectHydration1($c, 'without instance pool');
        Propel::enableInstancePooling();
    }

    public function testFindOneWithEmptyLeftJoin()
    {
        // save a book with no author
        $b = new Book();
        $b->setTitle('Foo');
        $b->setISBN('FA404');
        $b->save();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'Foo');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $count = $con->getQueryCount();
        $author = $book->getAuthor($con);
        $this->assertNull($author, 'Related object is not hydrated if empty');
        $this->assertEquals($count, $con->getQueryCount());
    }

    public function testFindOneWithEmptyLeftJoinOneToMany()
    {
        // non-empty relation
        $a1 = new Author();
        $a1->setFirstName('Foo');
        $a1->setLastName('Bar');
        $b1 = new Book();
        $b1->setTitle('Foo1');
        $b1->setISBN('FA404-1');
        $a1->addBook($b1);
        $b2 = new Book();
        $b2->setTitle('Foo2');
        $b2->setISBN('FA404-2');
        $a1->addBook($b2);
        $a1->save();
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $author = AuthorQuery::create()
            ->filterByFirstName('Foo')
            ->leftJoinWith('Propel\Tests\Bookstore\Author.Book')
            ->find($con)->get(0);
        $count = $con->getQueryCount();
        $books = $author->getBooks(null, $con);
        $this->assertEquals(2, $books->count());
        $this->assertEquals($count, $con->getQueryCount());
        // empty relation
        $a2 = new Author();
        $a2->setFirstName('Bar');
        $a2->setLastName('Bar');
        $a2->save();
        $author = AuthorQuery::create()
            ->filterByFirstName('Bar')
            ->leftJoinWith('Propel\Tests\Bookstore\Author.Book')
            ->find($con)->get(0);
        $count = $con->getQueryCount();
        $books = $author->getBooks(null, $con);
        $this->assertEquals(0, $books->count());
        $this->assertEquals($count, $con->getQueryCount());
    }

    public function testFindOneWithRelationName()
    {
        BookstoreDataPopulator::populate();
        BookstoreEmployeeTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee');
        $c->join('Propel\Tests\Bookstore\BookstoreEmployee.Supervisor s');
        $c->with('s');
        $c->where('s.Name = ?', 'John');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $emp = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals($emp->getName(), 'Pieter', 'Main object is correctly hydrated');
        $sup = $emp->getSupervisor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals($sup->getName(), 'John', 'Related object is correctly hydrated');
    }

    public function testFindOneWithDuplicateRelation()
    {
        EssayTableMap::doDeleteAll();
        $auth1 = new Author();
        $auth1->setFirstName('John');
        $auth1->setLastName('Doe');
        $auth1->save();
        $auth2 = new Author();
        $auth2->setFirstName('Jack');
        $auth2->setLastName('Sparrow');
        $auth2->save();
        $essay = new Essay();
        $essay->setTitle('Foo');
        $essay->setFirstAuthorId($auth1->getId());
        $essay->setSecondAuthorId($auth2->getId());
        $essay->save();
        AuthorTableMap::clearInstancePool();
        EssayTableMap::clearInstancePool();

        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Essay');
        $c->join('Propel\Tests\Bookstore\Essay.FirstAuthor');
        $c->with('FirstAuthor');
        $c->where('Propel\Tests\Bookstore\Essay.Title = ?', 'Foo');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $essay = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals($essay->getTitle(), 'Foo', 'Main object is correctly hydrated');
        $firstAuthor = $essay->getFirstAuthor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals($firstAuthor->getFirstName(), 'John', 'Related object is correctly hydrated');
        $secondAuthor = $essay->getSecondAuthor();
        $this->assertEquals($count + 1, $con->getQueryCount(), 'with() does not hydrate objects not in with');
    }

    public function testFindOneWithEmptyDuplicateRelation()
    {
        EssayTableMap::doDeleteAll();

        $author = new Author();
        $author->setFirstName('Piet');
        $author->setLastName('Sous');
        $author->save();

        AuthorTableMap::clearInstancePool();
        EssayTableMap::clearInstancePool();

        $query = AuthorQuery::create()
            ->useEssayRelatedByFirstAuthorIdQuery()
            ->orderByTitle()
            ->endUse()
            ->with('EssayRelatedByFirstAuthorId');

        $author = $query->find()->get(0); // should not throw a notice
        $this->assertTrue(true);
    }

    public function testFindOneWithDistantClass()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        Propel::enableInstancePooling();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Review');
        $c->where('Propel\Tests\Bookstore\Review.Recommended = ?', true);
        $c->join('Propel\Tests\Bookstore\Review.Book');
        $c->with('Book');
        $c->join('Book.Author');
        $c->with('Author');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $review = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals($review->getReviewedBy(), 'Washington Post', 'Main object is correctly hydrated');
        $book = $review->getBook();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals('Harry Potter and the Order of the Phoenix', $book->getTitle(), 'Related object is correctly hydrated');
        $author = $book->getAuthor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals('J.K.', $author->getFirstName(), 'Related object is correctly hydrated');
    }

    public function testFindOneWithDistantClassRenamedRelation()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        Propel::enableInstancePooling();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookSummary');
        $c->joinWith('Propel\Tests\Bookstore\BookSummary.SummarizedBook');
        $c->joinWith('SummarizedBook.Author');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $summary = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals('Harry Potter does some amazing magic!', $summary->getSummary(), 'Main object is correctly hydrated');
        $book = $summary->getSummarizedBook();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals('Harry Potter and the Order of the Phoenix', $book->getTitle(), 'Related object is correctly hydrated');
        $author = $book->getAuthor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals('J.K.', $author->getFirstName(), 'Related object is correctly hydrated');
    }

    /**
     * @expectedException \Propel\Runtime\Exception\LogicException
     */
    public function testFindOneWithOneToManyAndLimit()
    {
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->add(BookTableMap::COL_ISBN, '043935806X');
        $c->leftJoin('Book.Review');
        $c->with('Review');
        $c->limit(5);
        $books = $c->find();
    }

    public function testFindOneWithOneToMany()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->add(BookTableMap::COL_ISBN, '043935806X');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Review');
        $c->with('Review');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $books = $c->find($con);
        $this->assertEquals(1, count($books), 'with() does not duplicate the main object');
        $book = $books[0];
        $count = $con->getQueryCount();
        $this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Main object is correctly hydrated');
        $reviews = $book->getReviews();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
        try {
            $book->save();
        } catch (Exception $e) {
            $this->fail('with() does not force objects to be new');
        }
    }

    public function testFindOneWithOneToManyCustomOrder()
    {
        $author1 = new Author();
        $author1->setFirstName('AA');
        $author1->setLastName('AZ');
        $author2 = new Author();
        $author2->setFirstName('BB');
        $author2->setLastName('BZ');
        $book1 = new Book();
        $book1->setTitle('Aaa');
        $book1->setISBN('FA404-A');
        $book1->setAuthor($author1);
        $book1->save();
        $book2 = new Book();
        $book2->setTitle('Bbb');
        $book2->setISBN('FA404-B');
        $book2->setAuthor($author2);
        $book2->save();
        $book3 = new Book();
        $book3->setTitle('Ccc');
        $book3->setISBN('FA404-3');
        $book3->setAuthor($author1);
        $book3->save();
        $authors = AuthorQuery::create()
            ->leftJoin('Propel\Tests\Bookstore\Author.Book')
            ->orderBy('Book.Title')
            ->with('Book')
            ->find();
        $this->assertEquals(2, count($authors), 'with() used on a many-to-many doesn\'t change the main object count');
    }

    public function testFindOneWithOneToManyThenManyToOne()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->add(AuthorTableMap::COL_LAST_NAME, 'Rowling');
        $c->leftJoinWith('Propel\Tests\Bookstore\Author.Book');
        $c->leftJoinWith('Book.Review');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $authors = $c->find($con);
        $this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
        $rowling = $authors[0];
        $count = $con->getQueryCount();
        $this->assertEquals($rowling->getFirstName(), 'J.K.', 'Main object is correctly hydrated');
        $books = $rowling->getBooks();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
        $book = $books[0];
        $this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
        $reviews = $book->getReviews();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }

    public function testFindWithLeftJoinWithOneToManyAndNullObject()
    {
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $freud = new Author();
        $freud->setFirstName("Sigmund");
        $freud->setLastName("Freud");
        $freud->save($this->con);
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->add(AuthorTableMap::COL_LAST_NAME, 'Freud');
        $c->leftJoinWith('Propel\Tests\Bookstore\Author.Book');
        $c->leftJoinWith('Book.Review');
        // should not raise a notice
        $authors = $c->find($this->con);
        $this->assertTrue(true);
    }

    public function testFindWithLeftJoinWithManyToOneAndNullObject()
    {
        if (!$this->runningOnSQLite()) {
            $this->markTestSkipped('This test is designed for SQLite as it saves an empty object.');
        }

        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $review = new Review();
        $review->setReviewedBy('Peter');
        $review->setRecommended(true);
        $review->save($this->con);
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Review');
        $c->leftJoinWith('Propel\Tests\Bookstore\Review.Book');
        $c->leftJoinWith('Book.Author');
        // should not raise a notice
        $reviews = $c->find($this->con);
        $this->assertTrue(true);
    }

    public function testFindOneWithOneToManyThenManyToOneUsingJoinRelated()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();

        $con = Propel::getServiceContainer()->getConnection(AuthorTableMap::DATABASE_NAME);
        $authors = AuthorQuery::create()
            ->filterByLastName('Rowling')
            ->joinBook('book')
            ->with('book')
            ->useQuery('book')
            ->joinReview('review')
            ->with('review')
            ->endUse()
            ->find($con);
        $this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
        $rowling = $authors[0];
        $count = $con->getQueryCount();
        $this->assertEquals($rowling->getFirstName(), 'J.K.', 'Main object is correctly hydrated');
        $books = $rowling->getBooks();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
        $book = $books[0];
        $this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
        $reviews = $book->getReviews();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }

    public function testFindOneWithOneToManyThenManyToOneUsingAlias()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->add(AuthorTableMap::COL_LAST_NAME, 'Rowling');
        $c->leftJoinWith('Propel\Tests\Bookstore\Author.Book b');
        $c->leftJoinWith('b.Review r');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $authors = $c->find($con);
        $this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
        $rowling = $authors[0];
        $count = $con->getQueryCount();
        $this->assertEquals($rowling->getFirstName(), 'J.K.', 'Main object is correctly hydrated');
        $books = $rowling->getBooks();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
        $book = $books[0];
        $this->assertEquals($book->getTitle(), 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
        $reviews = $book->getReviews();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }

    public function testFindOneWithColumn()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterByTitle('The Tin Drum');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $c->withColumn('Author.LastName', 'AuthorName2');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
        $this->assertEquals('The Tin Drum', $book->getTitle());
        $this->assertEquals('Gunter', $book->getVirtualColumn('AuthorName'), 'ObjectFormatter adds withColumns as virtual columns');
        $this->assertEquals('Grass', $book->getVirtualColumn('AuthorName2'), 'ObjectFormatter correctly hydrates all virtual columns');
        $this->assertEquals('Gunter', $book->getAuthorName(), 'ObjectFormatter adds withColumns as virtual columns');
    }

    public function testFindOneWithColumnAndAlias()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterByTitle('Harry Potter and the Order of the Phoenix');
        $c->joinWith('Propel\Tests\Bookstore\Book.BookSummary');
        $c->joinWith('Propel\Tests\Bookstore\Book.Review');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->find($con)->get(0);
        $count = $con->getQueryCount();
        $reviews = $book->getReviews();

        //Washington Post
        $this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals('J.K.', $book->getVirtualColumn('AuthorName'), 'ObjectFormatter adds withColumns as virtual columns');
    }

    public function testFindOneWithClassAndColumn()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->filterByTitle('The Tin Drum');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $c->withColumn('Author.LastName', 'AuthorName2');
        $c->with('Author');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
        $this->assertEquals('The Tin Drum', $book->getTitle());
        $this->assertTrue($book->getAuthor() instanceof Author, 'ObjectFormatter correctly hydrates with class');
        $this->assertEquals('Gunter', $book->getAuthor()->getFirstName(), 'ObjectFormatter correctly hydrates with class');
        $this->assertEquals('Gunter', $book->getVirtualColumn('AuthorName'), 'ObjectFormatter adds withColumns as virtual columns');
        $this->assertEquals('Grass', $book->getVirtualColumn('AuthorName2'), 'ObjectFormatter correctly hydrates all virtual columns');
    }

    public function testFindPkWithOneToMany()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = BookQuery::create()
            ->findOneByTitle('Harry Potter and the Order of the Phoenix', $con);
        $pk = $book->getPrimaryKey();
        BookTableMap::clearInstancePool();
        $book = BookQuery::create()
            ->joinWith('Review')
            ->findPk($pk, $con);
        $count = $con->getQueryCount();
        $reviews = $book->getReviews();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ');
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }

    public function testFindOneWithLeftJoinWithOneToManyAndNullObjectsAndWithAdditionalJoins()
    {
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        BookOpinionTableMap::clearInstancePool();
        BookReaderTableMap::clearInstancePool();

        $freud = new Author();
        $freud->setFirstName("Sigmund");
        $freud->setLastName("Freud");
        $freud->save($this->con);

        $publisher = new Publisher();
        $publisher->setName('Psycho Books');
        $publisher->save();

        $book = new Book();
        $book->setAuthor($freud);
        $book->setTitle('Weirdness');
        $book->setIsbn('abc123456');
        $book->setPrice('14.99');
        $book->setPublisher($publisher);
        $book->save();

        $query = BookQuery::create()
            ->filterByTitle('Weirdness')
            ->innerJoinAuthor()
            ->useBookOpinionQuery(null, Criteria::LEFT_JOIN)
            ->leftJoinBookReader()
            ->endUse()
            ->with('Author')
            ->with('BookOpinion')
            ->with('BookReader');

        $books = $query->find($this->con)->get(0);
        $this->assertEquals(0, count($books->getBookOpinions()));
    }
}
