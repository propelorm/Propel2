<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\ColumnToQueryOperatorCriterion;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\TestCaseFixtures;

/**
 * @author Moritz Ringler
 * @group database
 */
class ColumnToQueryOperatorCriterionTest extends TestCaseFixtures
{
    /**
     * @param string $expected
     * @param \Propel\Runtime\ActiveQuery\Criterion\ColumnToQueryOperatorCriterion $in
     *
     * @return void
     */
    public function assertCreatedSqlSame(string $expected, ColumnToQueryOperatorCriterion $in)
    {
        $sql = '';
        $params = [];
        $in->appendPsTo($sql, $params);

        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider operatorDataProvider
     * @return void
     */
    public function testCriterionCreatesClauseForOperator(string $operator)
    {
        $outer = AuthorQuery::create();
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $filter = new ColumnToQueryOperatorCriterion($outer, 'id', $operator, $inner);

        $operatorLiteral = trim($operator);
        $expected = "id $operatorLiteral (SELECT book.author_id AS \"author_id\" FROM book WHERE book.price<=:p1)";
        $this->assertCreatedSqlSame($expected, $filter);
    }

    /**
     * @dataProvider operatorDataProvider
     * @return void
     */
    public function testCriterionCreateForRelation(string $operator)
    {
        $outer = AuthorQuery::create();
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL);
        $relation = $outer->getTableMap()->getRelation('Book');
        $in = ColumnToQueryOperatorCriterion::createForRelation($outer, $relation, $operator, $inner);

        $operatorLiteral = trim($operator);
        $expected = "author.id $operatorLiteral (SELECT book.author_id AS \"book.author_id\" FROM book WHERE book.price<=:p1)";
        $this->assertCreatedSqlSame($expected, $in);
    }

    public function operatorDataProvider(): array
    {
        return [
            [' untrimmed operator '],
            ['trimmed operator'],
            [' left padded operator'],
            ['right padded operator '],
        ];
    }
}
