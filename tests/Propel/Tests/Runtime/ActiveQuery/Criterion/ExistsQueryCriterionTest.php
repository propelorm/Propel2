<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\ExistsQueryCriterion;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\TestCaseFixtures;
use Propel\Tests\Bookstore\AuthorQuery;

/**
 * Test class for ExistsQueryCriterion.
 *
 * @author Moritz Ringler
 */
class ExistsQueryCriterionTest extends TestCaseFixtures
{
    /**
     * @return void
     */
    public function testAppendPsToAppendsExistsClause()
    {
        $query = BookQuery::create();
        $exists = new ExistsQueryCriterion(new Criteria(), null, null, $query);

        $params = [];
        $ps = '';
        $exists->appendPsTo($ps, $params);

        $params2 = [];
        $innerQueryStatement = $query->createSelectSql($params2);

        $this->assertEquals("EXISTS ($innerQueryStatement)", $ps);
        $this->assertEquals([], $params);
    }

    /**
     * @return void
     */
    public function testAppendPsToAppendsNotExistsClause()
    {
        $query = BookQuery::create();
        $exists = new ExistsQueryCriterion(new Criteria(), null,  ExistsQueryCriterion::TYPE_NOT_EXISTS, $query,);

        $params = [];
        $ps = '';
        $exists->appendPsTo($ps, $params);

        $params2 = [];
        $innerQueryStatement = $query->createSelectSql($params2);

        $this->assertEquals("NOT EXISTS ($innerQueryStatement)", $ps);
        $this->assertEquals([], $params);
    }

    /**
     * @return void
     */
    public function testBuildsRelationConditionDuringInit()
    {
        $authorQuery = AuthorQuery::create();
        $bookQuery = BookQuery::create();
        $bookRelationMap = $authorQuery->getTableMap()->getRelation('Book');
        ExistsQueryCriterion::createForRelation($authorQuery, $bookRelationMap, null, $bookQuery,);
        $params = [];
        $bookSql = $bookQuery->createSelectSql($params);

        $this->assertEquals('SELECT  FROM book WHERE author.id=book.author_id', $bookSql);
    }

    /**
     * @return void
     */
    public function testSetsSelectOnInsertQuery()
    {
        $authorQuery = AuthorQuery::create();
        $bookQuery = BookQuery::create();
        $bookRelationMap = $authorQuery->getTableMap()->getRelation('Book');
        $exists = ExistsQueryCriterion::createForRelation($authorQuery, $bookRelationMap, null, $bookQuery);

        $params = [];
        $ps = '';
        $exists->appendPsTo($ps, $params);
        $bookSql = $bookQuery->createSelectSql($params);

        $this->assertEquals('SELECT 1 AS existsFlag FROM book WHERE author.id=book.author_id', $bookSql);
    }
}
