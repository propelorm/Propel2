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
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookstoreEmployee;
use Propel\Tests\Bookstore\Essay;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\Map\EssayTableMap;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;

/**
 * Test class for OnDemandFormatter when Criteria uses with().
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class OnDemandFormatterWithTest extends BookstoreEmptyTestBase
{
    protected function assertCorrectHydration1($c, $msg)
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $c->limit(1);
        $books = $c->find($con);
        foreach ($books as $book) {
            break;
        }
        $count = $con->getQueryCount();
        $this->assertEquals($book->getTitle(), 'Don Juan', 'Main object is correctly hydrated ' . $msg);
        $author = $book->getAuthor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ' . $msg);
        $this->assertEquals($author->getLastName(), 'Byron', 'Related object is correctly hydrated ' . $msg);
        $publisher = $book->getPublisher();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query ' . $msg);
        $this->assertEquals($publisher->getName(), 'Penguin', 'Related object is correctly hydrated ' . $msg);
    }

    public function testFindOneWith()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
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
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
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
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
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
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->join('Propel\Tests\Bookstore\Book.Publisher');
        $c->with('Publisher');
        $this->assertCorrectHydration1($c, 'with instance pool');
    }

    public function testFindOneWithEmptyLeftJoin()
    {
        // save a book with no author
        $b = new Book();
        $b->setTitle('Foo');
        $b->setISBN('FA404');
        $b->save();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'Foo');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->limit(1);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $books = $c->find($con);
        foreach ($books as $book) {
            break;
        }
        $count = $con->getQueryCount();
        $author = $book->getAuthor();
        $this->assertNull($author, 'Related object is not hydrated if empty');
    }

    public function testFindOneWithRelationName()
    {
        BookstoreDataPopulator::populate();
        BookstoreEmployeeTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee');
        $c->join('Propel\Tests\Bookstore\BookstoreEmployee.Supervisor s');
        $c->with('s');
        $c->where('s.Name = ?', 'John');
        $c->limit(1);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $emps = $c->find($con);
        foreach ($emps as $emp) {
            break;
        }
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
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->join('Propel\Tests\Bookstore\Essay.FirstAuthor');
        $c->with('FirstAuthor');
        $c->where('Propel\Tests\Bookstore\Essay.Title = ?', 'Foo');
        $c->limit(1);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $essays = $c->find($con);
        foreach ($essays as $essay) {
            break;
        }
        $count = $con->getQueryCount();
        $this->assertEquals($essay->getTitle(), 'Foo', 'Main object is correctly hydrated');
        $firstAuthor = $essay->getFirstAuthor();
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals($firstAuthor->getFirstName(), 'John', 'Related object is correctly hydrated');
        $secondAuthor = $essay->getSecondAuthor();
        $this->assertEquals($count + 1, $con->getQueryCount(), 'with() does not hydrate objects not in with');
    }

    public function testFindOneWithDistantClass()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Review');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->where('Propel\Tests\Bookstore\Review.Recommended = ?', true);
        $c->join('Propel\Tests\Bookstore\Review.Book');
        $c->with('Book');
        $c->join('Book.Author');
        $c->with('Author');
        $c->limit(1);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $reviews = $c->find($con);
        foreach ($reviews as $review) {
            break;
        }
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
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
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
    public function testFindOneWithOneToMany()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->add(BookTableMap::COL_ISBN, '043935806X');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Review');
        $c->with('Review');
        $books = $c->find();
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
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->leftJoinWith('Propel\Tests\Bookstore\Review.Book');
        $c->leftJoinWith('Book.Author');
        // should not raise a notice
        $reviews = $c->find($this->con);
        $this->assertTrue(true);
    }

    public function testFindOneWithColumn()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->filterByTitle('The Tin Drum');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $c->withColumn('Author.LastName', 'AuthorName2');
        $c->limit(1);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $books = $c->find($con);
        foreach ($books as $book) {
            break;
        }
        $this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
        $this->assertEquals('The Tin Drum', $book->getTitle());
        $this->assertEquals('Gunter', $book->getVirtualColumn('AuthorName'), 'ObjectFormatter adds withColumns as virtual columns');
        $this->assertEquals('Grass', $book->getVirtualColumn('AuthorName2'), 'ObjectFormatter correctly hydrates all virtual columns');
        $this->assertEquals('Gunter', $book->getAuthorName(), 'ObjectFormatter adds withColumns as virtual columns');
    }

    public function testFindOneWithClassAndColumn()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
        $c->filterByTitle('The Tin Drum');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $c->withColumn('Author.LastName', 'AuthorName2');
        $c->with('Author');
        $c->limit(1);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $books = $c->find($con);
        foreach ($books as $book) {
            break;
        }
        $this->assertTrue($book instanceof Book, 'withColumn() do not change the resulting model class');
        $this->assertEquals('The Tin Drum', $book->getTitle());
        $this->assertTrue($book->getAuthor() instanceof Author, 'ObjectFormatter correctly hydrates with class');
        $this->assertEquals('Gunter', $book->getAuthor()->getFirstName(), 'ObjectFormatter correctly hydrates with class');
        $this->assertEquals('Gunter', $book->getVirtualColumn('AuthorName'), 'ObjectFormatter adds withColumns as virtual columns');
        $this->assertEquals('Grass', $book->getVirtualColumn('AuthorName2'), 'ObjectFormatter correctly hydrates all virtual columns');
    }
}
