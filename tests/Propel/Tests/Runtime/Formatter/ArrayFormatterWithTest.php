<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Formatter;

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Essay;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookstoreEmployeeTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Bookstore\Map\EssayTableMap;
use Propel\Tests\Bookstore\Map\ReviewTableMap;
use Propel\Tests\Bookstore\Review;
use Propel\Tests\Helpers\Bookstore\BookstoreDataPopulator;
use Propel\Tests\Helpers\Bookstore\BookstoreEmptyTestBase;

/**
 * Test class for ArrayFormatter when Criteria uses with().
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class ArrayFormatterWithTest extends BookstoreEmptyTestBase
{
    /**
     * @return void
     */
    protected function assertCorrectHydration1($c, $msg)
    {
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals($book['Title'], 'Don Juan', 'Main object is correctly hydrated ' . $msg);
        $author = $book['Author'];
        $this->assertEquals($author['LastName'], 'Byron', 'Related object is correctly hydrated ' . $msg);
        $publisher = $book['Publisher'];
        $this->assertEquals($publisher['Name'], 'Penguin', 'Related object is correctly hydrated ' . $msg);
    }

    /**
     * @return void
     */
    public function testFindOneWith()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->join('Propel\Tests\Bookstore\Book.Publisher');
        $c->with('Publisher');
        $this->assertCorrectHydration1($c, 'without instance pool');
    }

    /**
     * @return void
     */
    public function testFindOneWithAlias()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author a');
        $c->with('a');
        $c->join('Propel\Tests\Bookstore\Book.Publisher p');
        $c->with('p');
        $this->assertCorrectHydration1($c, 'with alias');
    }

    /**
     * @return void
     */
    public function testFindOneWithMainAlias()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->setModelAlias('b', true);
        $c->orderBy('b.Title');
        $c->join('b.Author a');
        $c->with('a');
        $c->join('b.Publisher p');
        $c->with('p');
        $this->assertCorrectHydration1($c, 'with main alias');
    }

    /**
     * @return void
     */
    public function testFindOneWithUsingInstancePool()
    {
        BookstoreDataPopulator::populate();
        // instance pool contains all objects by default, since they were just populated
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->orderBy('Propel\Tests\Bookstore\Book.Title');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $c->join('Propel\Tests\Bookstore\Book.Publisher');
        $c->with('Publisher');
        $this->assertCorrectHydration1($c, 'with instance pool');
    }

    /**
     * @return void
     */
    public function testFindOneWithEmptyLeftJoin()
    {
        // save a book with no author
        $b = new Book();
        $b->setTitle('Foo');
        $b->setISBN('FA404');
        $b->save();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->where('Propel\Tests\Bookstore\Book.Title = ?', 'Foo');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $count = $con->getQueryCount();
        $author = $book['Author'];
        $this->assertEquals([], $author, 'Related object is not hydrated if empty');
    }

    /**
     * @return void
     */
    public function testFindOneWithRelationName()
    {
        BookstoreDataPopulator::populate();
        BookstoreEmployeeTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\BookstoreEmployee');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->join('Propel\Tests\Bookstore\BookstoreEmployee.Supervisor s');
        $c->with('s');
        $c->where('s.Name = ?', 'John');
        $emp = $c->findOne();
        $this->assertEquals($emp['Name'], 'Pieter', 'Main object is correctly hydrated');
        $sup = $emp['Supervisor'];
        $this->assertEquals($sup['Name'], 'John', 'Related object is correctly hydrated');
    }

    /**
     * @see http://www.propelorm.org/ticket/959
     *
     * @return void
     */
    public function testFindOneWithSameRelatedObject()
    {
        BookTableMap::doDeleteAll();
        AuthorTableMap::doDeleteAll();
        $auth = new Author();
        $auth->setFirstName('John');
        $auth->setLastName('Doe');
        $auth->save();
        $book1 = new Book();
        $book1->setTitle('Hello');
        $book1->setISBN('FA404');
        $book1->setAuthor($auth);
        $book1->save();
        $book2 = new Book();
        $book2->setTitle('World');
        $book2->setISBN('FA404');
        $book2->setAuthor($auth);
        $book2->save();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->with('Author');
        $books = $c->find();

        $this->assertEquals(2, count($books));
        $firstBook = $books[0];
        $this->assertTrue(isset($firstBook['Author']));
        $secondBook = $books[1];
        $this->assertTrue(isset($secondBook['Author']));
    }

    /**
     * @return void
     */
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
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->join('Propel\Tests\Bookstore\Essay.FirstAuthor');
        $c->with('FirstAuthor');
        $c->where('Propel\Tests\Bookstore\Essay.Title = ?', 'Foo');
        $essay = $c->findOne();
        $this->assertEquals($essay['Title'], 'Foo', 'Main object is correctly hydrated');
        $firstAuthor = $essay['FirstAuthor'];
        $this->assertEquals($firstAuthor['FirstName'], 'John', 'Related object is correctly hydrated');
        $this->assertFalse(array_key_exists('SecondAuthor', $essay), 'Only related object specified in with() is hydrated');
    }

    /**
     * @return void
     */
    public function testFindOneWithDistantClass()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Review');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->where('Propel\Tests\Bookstore\Review.Recommended = ?', true);
        $c->join('Propel\Tests\Bookstore\Review.Book');
        $c->with('Book');
        $c->join('Book.Author');
        $c->with('Author');
        $review = $c->findOne();
        $this->assertEquals($review['ReviewedBy'], 'Washington Post', 'Main object is correctly hydrated');
        $book = $review['Book'];
        $this->assertEquals('Harry Potter and the Order of the Phoenix', $book['Title'], 'Related object is correctly hydrated');
        $author = $book['Author'];
        $this->assertEquals('J.K.', $author['FirstName'], 'Related object is correctly hydrated');
    }

    /**
     * @return void
     */
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
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $summary = $c->findOne($con);
        $count = $con->getQueryCount();
        $this->assertEquals('Harry Potter does some amazing magic!', $summary['Summary'], 'Main object is correctly hydrated');
        $book = $summary['SummarizedBook'];
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals('Harry Potter and the Order of the Phoenix', $book['Title'], 'Related object is correctly hydrated');
        $author = $book['Author'];
        $this->assertEquals($count, $con->getQueryCount(), 'with() hydrates the related objects to save a query');
        $this->assertEquals('J.K.', $author['FirstName'], 'Related object is correctly hydrated');
    }

    /**
     * @return void
     */
    public function testFindOneWithOneToManyAndLimit()
    {
        $this->expectException(LogicException::class);

        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->add(BookTableMap::COL_ISBN, '043935806X');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Review');
        $c->with('Review');
        $c->limit(5);
        $books = $c->find();
    }

    /**
     * @return void
     */
    public function testFindOneWithOneToMany()
    {
        BookstoreDataPopulator::populate();

        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->add(BookTableMap::COL_ISBN, '043935806X');
        $c->leftJoin('Propel\Tests\Bookstore\Book.Review');
        $c->with('Review');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $books = $c->find($con);
        $this->assertEquals(1, count($books), 'with() does not duplicate the main object');
        $book = $books[0];
        $this->assertEquals($book['Title'], 'Harry Potter and the Order of the Phoenix', 'Main object is correctly hydrated');
        $this->assertEquals(['Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId', 'Reviews'], array_keys($book), 'with() adds a plural index for the one to many relationship');
        $reviews = $book['Reviews'];
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
        $review1 = $reviews[0];
        $this->assertEquals(['Id', 'ReviewedBy', 'ReviewDate', 'Recommended', 'Status', 'BookId'], array_keys($review1), 'with() Related objects are correctly hydrated');
    }

    /**
     * @return void
     */
    public function testFindOneWithOneToManyCustomOrder()
    {
        $author1 = new Author();
        $author1->setFirstName('AA');
        $author1->setLastName('AZ');
        $author2 = new Author();
        $author2->setFirstName('BB');
        $author2->setLastName('B2');
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
        $book3->setISBN('FA404-C');
        $book3->setAuthor($author1);
        $book3->save();
        $authors = AuthorQuery::create()
            ->setFormatter(ModelCriteria::FORMAT_ARRAY)
            ->leftJoin('Propel\Tests\Bookstore\Author.Book')
            ->orderBy('Book.Title')
            ->with('Book')
            ->find();
        $this->assertEquals(2, count($authors), 'with() used on a many-to-many doesn\'t change the main object count');
    }

    /**
     * @return void
     */
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
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $authors = $c->find($con);
        $this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
        $rowling = $authors[0];
        $this->assertEquals($rowling['FirstName'], 'J.K.', 'Main object is correctly hydrated');
        $books = $rowling['Books'];
        $this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
        $book = $books[0];
        $this->assertEquals($book['Title'], 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
        $reviews = $book['Reviews'];
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }

    /**
     * @return void
     */
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
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $authors = $c->find($con);
        $this->assertEquals(1, count($authors), 'with() does not duplicate the main object');
        $rowling = $authors[0];
        $this->assertEquals($rowling['FirstName'], 'J.K.', 'Main object is correctly hydrated');
        $books = $rowling['Books'];
        $this->assertEquals(1, count($books), 'Related objects are correctly hydrated');
        $book = $books[0];
        $this->assertEquals($book['Title'], 'Harry Potter and the Order of the Phoenix', 'Related object is correctly hydrated');
        $reviews = $book['Reviews'];
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }

    /**
     * @return void
     */
    public function testFindWithLeftJoinWithOneToManyAndNullObject()
    {
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $freud = new Author();
        $freud->setFirstName('Sigmund');
        $freud->setLastName('Freud');
        $freud->save($this->con);
        $c = new ModelCriteria('bookstore', 'Propel\Tests\Bookstore\Author');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->add(AuthorTableMap::COL_LAST_NAME, 'Freud');
        $c->leftJoinWith('Propel\Tests\Bookstore\Author.Book');
        $c->leftJoinWith('Book.Review');
        // should not raise a notice
        $authors = $c->find($this->con);
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
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
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->leftJoinWith('Propel\Tests\Bookstore\Review.Book');
        $c->leftJoinWith('Book.Author');
        // should not raise a notice
        $reviews = $c->find($this->con);
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testFindOneWithColumn()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->filterByTitle('The Tin Drum');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $c->withColumn('Author.LastName', 'AuthorName2');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $this->assertEquals(['Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId', 'AuthorName', 'AuthorName2'], array_keys($book), 'withColumn() do not change the resulting model class');
        $this->assertEquals('The Tin Drum', $book['Title']);
        $this->assertEquals('Gunter', $book['AuthorName'], 'ArrayFormatter adds withColumns as columns');
        $this->assertEquals('Grass', $book['AuthorName2'], 'ArrayFormatter correctly hydrates all as columns');
    }

    /**
     * @return void
     */
    public function testFindOneWithClassAndColumn()
    {
        BookstoreDataPopulator::populate();
        BookTableMap::clearInstancePool();
        AuthorTableMap::clearInstancePool();
        ReviewTableMap::clearInstancePool();
        $c = new ModelCriteria('bookstore', '\Propel\Tests\Bookstore\Book');
        $c->setFormatter(ModelCriteria::FORMAT_ARRAY);
        $c->filterByTitle('The Tin Drum');
        $c->join('Propel\Tests\Bookstore\Book.Author');
        $c->withColumn('Author.FirstName', 'AuthorName');
        $c->withColumn('Author.LastName', 'AuthorName2');
        $c->with('Author');
        $con = Propel::getServiceContainer()->getConnection(BookTableMap::DATABASE_NAME);
        $book = $c->findOne($con);
        $this->assertEquals(['Id', 'Title', 'ISBN', 'Price', 'PublisherId', 'AuthorId', 'Author', 'AuthorName', 'AuthorName2'], array_keys($book), 'withColumn() do not change the resulting model class');
        $this->assertEquals('The Tin Drum', $book['Title']);
        $this->assertEquals('Gunter', $book['Author']['FirstName'], 'ArrayFormatter correctly hydrates withclass and columns');
        $this->assertEquals('Gunter', $book['AuthorName'], 'ArrayFormatter adds withColumns as columns');
        $this->assertEquals('Grass', $book['AuthorName2'], 'ArrayFormatter correctly hydrates all as columns');
    }

    /**
     * @return void
     */
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
            ->setFormatter(ModelCriteria::FORMAT_ARRAY)
            ->joinWith('Review')
            ->findPk($pk, $con);
        $reviews = $book['Reviews'];
        $this->assertEquals(2, count($reviews), 'Related objects are correctly hydrated');
    }
}
