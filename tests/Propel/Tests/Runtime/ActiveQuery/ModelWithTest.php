<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\ModelWith;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\BookReaderQuery;
use Propel\Tests\Bookstore\BookstoreEmployeeQuery;
use Propel\Tests\Bookstore\BookSummaryQuery;
use Propel\Tests\Bookstore\ReviewQuery;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for ModelWith.
 *
 * @author François Zaninotto
 */
class ModelWithTest extends TestCaseFixtures
{
    /**
     * @return void
     */
    public function testModelNameManyToOne()
    {
        $q = BookQuery::create()
            ->joinAuthor();
        $joins = $q->getJoins();
        $join = $joins['Author'];
        $with = new ModelWith($join);
        $this->assertEquals('Propel\Tests\Bookstore\Author', $with->getModelName(), 'A ModelWith computes the model name from the join');
    }

    /**
     * @return void
     */
    public function testModelNameOneToMany()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertEquals('Propel\Tests\Bookstore\Book', $with->getModelName(), 'A ModelWith computes the model name from the join');
    }

    /**
     * @return void
     */
    public function testModelNameAlias()
    {
        $q = BookQuery::create()
            ->joinAuthor('a');
        $joins = $q->getJoins();
        $join = $joins['a'];
        $with = new ModelWith($join);
        $this->assertEquals('Propel\Tests\Bookstore\Author', $with->getModelName(), 'A ModelWith computes the model name from the join');
    }

    /**
     * @return void
     */
    public function testRelationManyToOne()
    {
        $q = BookQuery::create()
            ->joinAuthor();
        $joins = $q->getJoins();
        $join = $joins['Author'];
        $with = new ModelWith($join);
        $this->assertEquals($with->getRelationMethod(), 'setAuthor', 'A ModelWith computes the relation method from the join');
        $this->assertEquals($with->getRelationName(), 'Author', 'A ModelWith computes the relation name from the join');
        $this->assertFalse($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
    }

    /**
     * @return void
     */
    public function testRelationOneToMany()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertEquals($with->getRelationMethod(), 'addBook', 'A ModelWith computes the relation method from the join');
        $this->assertEquals($with->getRelationName(), 'Books', 'A ModelWith computes the relation name from the join');
        $this->assertTrue($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
    }

    /**
     * @return void
     */
    public function testRelationOneToOne()
    {
        $q = BookstoreEmployeeQuery::create()
            ->joinBookstoreEmployeeAccount();
        $joins = $q->getJoins();
        $join = $joins['BookstoreEmployeeAccount'];
        $with = new ModelWith($join);
        $this->assertEquals($with->getRelationMethod(), 'setBookstoreEmployeeAccount', 'A ModelWith computes the relation method from the join');
        $this->assertEquals($with->getRelationName(), 'BookstoreEmployeeAccount', 'A ModelWith computes the relation name from the join');
        $this->assertFalse($with->isAdd(), 'A ModelWith computes the relation cardinality from the join');
    }

    /**
     * @return void
     */
    public function testIsPrimary()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertTrue($with->isPrimary(), 'A ModelWith initialized from a primary join is primary');

        $q = BookQuery::create()
            ->joinAuthor()
            ->joinReview();
        $joins = $q->getJoins();
        $join = $joins['Review'];
        $with = new ModelWith($join);
        $this->assertTrue($with->isPrimary(), 'A ModelWith initialized from a primary join is primary');

        $q = AuthorQuery::create()
            ->join('Propel\Tests\Bookstore\Author.Book')
            ->join('Book.Publisher');
        $joins = $q->getJoins();
        $join = $joins['Publisher'];
        $with = new ModelWith($join);
        $this->assertFalse($with->isPrimary(), 'A ModelWith initialized from a non-primary join is not primary');
    }

    /**
     * @return void
     */
    public function testGetLeftPhpName()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertNull($with->getLeftPhpName(), 'A ModelWith initialized from a primary join has a null left phpName');

        $q = AuthorQuery::create('a')
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertNull($with->getLeftPhpName(), 'A ModelWith initialized from a primary join with alias has a null left phpName');

        $q = AuthorQuery::create()
            ->joinBook('b');
        $joins = $q->getJoins();
        $join = $joins['b'];
        $with = new ModelWith($join);
        $this->assertNull($with->getLeftPhpName(), 'A ModelWith initialized from a primary join with alias has a null left phpName');

        $q = AuthorQuery::create()
            ->join('Propel\Tests\Bookstore\Author.Book')
            ->join('Book.Publisher');
        $joins = $q->getJoins();
        $join = $joins['Publisher'];
        $with = new ModelWith($join);
        $this->assertEquals('Book', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = ReviewQuery::create()
            ->join('Propel\Tests\Bookstore\Review.Book')
            ->join('Book.Author')
            ->join('Book.Publisher');
        $joins = $q->getJoins();
        $join = $joins['Publisher'];
        $with = new ModelWith($join);
        $this->assertEquals('Book', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = ReviewQuery::create()
            ->join('Propel\Tests\Bookstore\Review.Book')
            ->join('Book.BookOpinion')
            ->join('BookOpinion.BookReader');
        $joins = $q->getJoins();
        $join = $joins['BookOpinion'];
        $with = new ModelWith($join);
        $this->assertEquals('Book', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as left phpName');
        $join = $joins['BookReader'];
        $with = new ModelWith($join);
        $this->assertEquals('BookOpinion', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = BookReaderQuery::create()
            ->join('Propel\Tests\Bookstore\BookReader.BookOpinion')
            ->join('BookOpinion.Book')
            ->join('Book.Author');
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertEquals('BookOpinion', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as related class');
        $join = $joins['Author'];
        $with = new ModelWith($join);
        $this->assertEquals('Book', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as left phpName');

        $q = BookSummaryQuery::create()
            ->join('Propel\Tests\Bookstore\BookSummary.SummarizedBook')
            ->join('SummarizedBook.Author');
        $joins = $q->getJoins();
        $join = $joins['Author'];
        $with = new ModelWith($join);
        $this->assertEquals('SummarizedBook', $with->getLeftPhpName(), 'A ModelWith uses the previous join relation name as left phpName');
    }

    /**
     * @return void
     */
    public function testGetRightPhpName()
    {
        $q = AuthorQuery::create()
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertEquals('Book', $with->getRightPhpName(), 'A ModelWith initialized from a primary join has a right phpName');

        $q = AuthorQuery::create('a')
            ->joinBook();
        $joins = $q->getJoins();
        $join = $joins['Book'];
        $with = new ModelWith($join);
        $this->assertEquals('Book', $with->getRightPhpName(), 'A ModelWith initialized from a primary join with alias has a right phpName');

        $q = AuthorQuery::create()
            ->joinBook('b');
        $joins = $q->getJoins();
        $join = $joins['b'];
        $with = new ModelWith($join);
        $this->assertEquals('b', $with->getRightPhpName(), 'A ModelWith initialized from a primary join with alias uses the alias as right phpName');

        $q = AuthorQuery::create()
            ->join('Propel\Tests\Bookstore\Author.Book')
            ->join('Book.Publisher');
        $joins = $q->getJoins();
        $join = $joins['Publisher'];
        $with = new ModelWith($join);
        $this->assertEquals('Publisher', $with->getRightPhpName(), 'A ModelWith has a right phpName even when there are previous joins');

        $q = BookSummaryQuery::create()
            ->join('Propel\Tests\Bookstore\BookSummary.SummarizedBook');
        $joins = $q->getJoins();
        $join = $joins['SummarizedBook'];
        $with = new ModelWith($join);
        $this->assertEquals('SummarizedBook', $with->getRightPhpName(), 'A ModelWith uses the relation name rather than the class phpName when it exists');

        $q = BookSummaryQuery::create()
            ->join('Propel\Tests\Bookstore\BookSummary.SummarizedBook')
            ->join('SummarizedBook.Author');
        $joins = $q->getJoins();
        $join = $joins['Author'];
        $with = new ModelWith($join);
        $this->assertEquals('Author', $with->getRightPhpName(), 'A ModelWith has a right phpName even when there are previous joins with custom relation names');
    }
}
