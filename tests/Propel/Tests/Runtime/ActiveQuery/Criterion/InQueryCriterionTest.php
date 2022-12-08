<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\InQueryCriterion;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Test class for IN.
 *
 * @author Moritz Ringler
 * @group database
 */
class InQueryCriterionTest extends TestCaseFixturesDatabase
{
    /**
     * @param string $expected
     * @param \Propel\Runtime\ActiveQuery\Criterion\InQueryCriterion $in
     *
     * @return void
     */
    public function assertCreatedSqlSame(string $expected, InQueryCriterion $in)
    {
        $sql = '';
        $params = [];
        $in->appendPsTo($sql, $params);

        $this->assertSame($expected, $sql);
    }

    /**
     * @return void
     */
    public function testCriterionCreatesInClause()
    {
        $outer = AuthorQuery::create();
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $in = new InQueryCriterion($outer, 'id', null, $inner);

        $expected = 'id IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertCreatedSqlSame($expected, $in);
    }

    /**
     * @return void
     */
    public function testCriterionCreatesNotInClause()
    {
        $outer = AuthorQuery::create();
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $in = new InQueryCriterion($outer, 'id', InQueryCriterion::NOT_IN, $inner);

        $expected = 'id NOT IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertCreatedSqlSame($expected, $in);
    }

    /**
     * @return void
     */
    public function testCreateCriterionFromRelation()
    {
        $outer = AuthorQuery::create();
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL);
        $relation = $outer->getTableMap()->getRelation('Book');
        $in = InQueryCriterion::createForRelation($outer, $relation, null, $inner);

        $expected = 'author.id IN (SELECT book.author_id AS "book.author_id" FROM book WHERE book.price<=:p1)';
        $this->assertCreatedSqlSame($expected, $in);
    }
}
