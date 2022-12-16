<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery\Criterion;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\ExistsCriterion;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\TestCaseFixtures;
use Propel\Tests\Bookstore\AuthorQuery;

/**
 * Test class for ExistsCriterion.
 *
 * @author Moritz Ringler
 */
class ExistsCriterionTest extends TestCaseFixtures
{
    /**
     * @return void
     */
    public function testAppendPsToAppendsExistsClause()
    {
        $query = BookQuery::create();
        $exists = new ExistsCriterion(new Criteria(), $query);

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
        $exists = new ExistsCriterion(new Criteria(), $query, ExistsCriterion::TYPE_NOT_EXISTS);

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
        new ExistsCriterion($authorQuery, $bookQuery, ExistsCriterion::TYPE_EXISTS, $bookRelationMap);
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
        $exists = new ExistsCriterion($authorQuery, $bookQuery, ExistsCriterion::TYPE_EXISTS, $bookRelationMap);

        $params = [];
        $ps = '';
        $exists->appendPsTo($ps, $params);
        $bookSql = $bookQuery->createSelectSql($params);

        $this->assertEquals('SELECT 1 AS existsFlag FROM book WHERE author.id=book.author_id', $bookSql);
    }
}
