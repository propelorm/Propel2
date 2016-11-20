<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Bookstore\Author;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\Book;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeAccount;
use Propel\Tests\Bookstore\BookReaderQuery;
use Propel\Tests\Bookstore\BookSummaryQuery;
use Propel\Tests\Bookstore\ReviewQuery;

use Propel\Runtime\ActiveQuery\ModelWith;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for ModelWith.
 *
 * @author François Zaninotto
 */
class ModelWithTest extends TestCaseFixtures
{

    public function testModelNameManyToOne()
    {
        $q = BookQuery::create()
            ->joinAuthor();
        $joins = $q->getJoins();
        $join = $joins['author'];
        $with = new ModelWith($join);
        $this->assertEquals('Propel\Tests\Bookstore\Author', $with->getModelName(), 'A ModelWith computes the model name from the join');
    }

    public function testModelNameOneToMany()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertEquals('Propel\Tests\Bookstore\Book', $with->getModelName(), 'A ModelWith computes the model name from the join');
    }

    public function testModelNameAlias()
    {
        $q = BookQuery::create()
            ->joinAuthor('a');
        $joins = $q->getJoins();
        $join = $joins['a'];
        $with = new ModelWith($join);
        $this->assertEquals('Propel\Tests\Bookstore\Author', $with->getModelName(), 'A ModelWith computes the model name from the join');
    }

    public function testRelationManyToOne()
    {
        $q = BookQuery::create()
            ->joinAuthor();
        $joins = $q->getJoins();
        $join = $joins['author'];
        $with = new ModelWith($join);
        $this->assertEquals($with->getRelationName(), 'author', 'A ModelWith computes the relation name from the join');
        $this->assertFalse($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
    }

    public function testRelationOneToMany()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertEquals($with->getRelationName(), 'books', 'A ModelWith computes the relation name from the join');
        $this->assertTrue($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
    }

    public function testRelationOneToOne()
    {
        $q = BookstoreEmployeeQuery::create()
            ->joinBookstoreEmployeeAccount();
        $joins = $q->getJoins();
        $join = $joins['bookstoreEmployeeAccount'];
        $with = new ModelWith($join);
        $this->assertEquals($with->getRelationName(), 'bookstoreEmployeeAccount', 'A ModelWith computes the relation name from the join');
        $this->assertFalse($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
    }

    public function testIsPrimary()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertTrue($with->isPrimary(), 'A ModelWith initialized from a primary join is primary');

        $q = BookQuery::create()
            ->joinAuthor()
            ->joinReview();
        $joins = $q->getJoins();
        $join = $joins['review'];
        $with = new ModelWith($join);
        $this->assertTrue($with->isPrimary(), 'A ModelWith initialized from a primary join is primary');

        $q = AuthorQuery::create()
            ->join('Propel\Tests\Bookstore\Author.book')
            ->join('book.publisher');
        $joins = $q->getJoins();
        $join = $joins['publisher'];
        $with = new ModelWith($join);
        $this->assertFalse($with->isPrimary(), 'A ModelWith initialized from a non-primary join is not primary');
    }

    public function testGetLeftPhpName()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertNull($with->getLeftName(), 'A ModelWith initialized from a primary join has a null left phpName');

        $q = AuthorQuery::create('a')
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertNull($with->getLeftName(), 'A ModelWith initialized from a primary join with alias has a null left phpName');

        $q = AuthorQuery::create()
            ->joinBook('b');
        $joins = $q->getJoins();
        $join = $joins['b'];
        $with = new ModelWith($join);
        $this->assertNull($with->getLeftName(), 'A ModelWith initialized from a primary join with alias has a null left phpName');

        $q = AuthorQuery::create()
            ->join('Propel\Tests\Bookstore\Author.book')
            ->join('book.publisher');
        $joins = $q->getJoins();
        $join = $joins['publisher'];
        $with = new ModelWith($join);
        $this->assertEquals('book', $with->getLeftName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = ReviewQuery::create()
            ->join('Propel\Tests\Bookstore\Review.book')
            ->join('book.author')
            ->join('book.publisher');
        $joins = $q->getJoins();
        $join = $joins['publisher'];
        $with = new ModelWith($join);
        $this->assertEquals('book', $with->getLeftName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = ReviewQuery::create()
            ->join('Propel\Tests\Bookstore\Review.book')
            ->join('book.bookOpinion')
            ->join('bookOpinion.bookReader');
        $joins = $q->getJoins();
        $join = $joins['bookOpinion'];
        $with = new ModelWith($join);
        $this->assertEquals('book', $with->getLeftName(), 'A ModelWith uses the previous join relation name as left phpName');
        $join = $joins['bookReader'];
        $with = new ModelWith($join);
        $this->assertEquals('bookOpinion', $with->getLeftName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = BookReaderQuery::create()
            ->join('Propel\Tests\Bookstore\BookReader.bookOpinion')
            ->join('bookOpinion.book')
            ->join('book.author');
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertEquals('bookOpinion', $with->getLeftName(), 'A ModelWith uses the previous join relation name as related class');
        $join = $joins['author'];
        $with = new ModelWith($join);
        $this->assertEquals('book', $with->getLeftName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = BookSummaryQuery::create()
            ->join('Propel\Tests\Bookstore\BookSummary.summarizedBook')
            ->join('summarizedBook.author');
        $joins = $q->getJoins();
        $join = $joins['author'];
        $with = new ModelWith($join);
        $this->assertEquals('summarizedBook', $with->getLeftName(), 'A ModelWith uses the previous join relation name as left phpName');
    }

    public function testGetRightPhpName()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertEquals('book', $with->getRightName(), 'A ModelWith initialized from a primary join has a right phpName');

        $q = AuthorQuery::create('a')
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['book'];
        $with = new ModelWith($join);
        $this->assertEquals('book', $with->getRightName(), 'A ModelWith initialized from a primary join with alias has a right phpName');

        $q = AuthorQuery::create()
            ->joinBook('b');
        $joins = $q->getJoins();
        $join = $joins['b'];
        $with = new ModelWith($join);
        $this->assertEquals('b', $with->getRightName(), 'A ModelWith initialized from a primary join with alias uses the alias as right phpName');

        $q = AuthorQuery::create()
            ->join('Propel\Tests\Bookstore\Author.book')
            ->join('book.publisher');
        $joins = $q->getJoins();
        $join = $joins['publisher'];
        $with = new ModelWith($join);
        $this->assertEquals('publisher', $with->getRightName(), 'A ModelWith has a right phpName even when there are previous joins');

        $q = BookSummaryQuery::create()
            ->join('Propel\Tests\Bookstore\BookSummary.summarizedBook');
        $joins = $q->getJoins();
        $join = $joins['summarizedBook'];
        $with = new ModelWith($join);
        $this->assertEquals('summarizedBook', $with->getRightName(), 'A ModelWith uses the relation name rather than the class phpName when it exists');

        $q = BookSummaryQuery::create()
            ->join('Propel\Tests\Bookstore\BookSummary.summarizedBook')
            ->join('summarizedBook.author');
        $joins = $q->getJoins();
        $join = $joins['author'];
        $with = new ModelWith($join);
        $this->assertEquals('author', $with->getRightName(), 'A ModelWith has a right phpName even when there are previous joins with custom relation names');
    }
}
