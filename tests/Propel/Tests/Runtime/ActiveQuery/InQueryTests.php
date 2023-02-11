<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\AuthorQuery;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * Test class for In.
 */
class InQueryTests extends TestCaseFixturesDatabase
{
    /**
     * @param string $expected
     * @param \Propel\Runtime\ActiveQuery\Criteria $query
     *
     * @return void
     */
    public function assertQuerySqlSame(string $expected, Criteria $query)
    {
        $params = [];
        $sql = $query->createSelectSql($params);

        $this->assertSame($expected, $sql);
    }

    /**
     * @return void
     */
    public function testCreateInFromFilterBy()
    {
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $query = AuthorQuery::create()->filterBy('Id', $inner, Criteria::IN);

        $expected = 'SELECT  FROM author WHERE author.id IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testCreateNotInFromFilterBy()
    {
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $query = AuthorQuery::create()->filterBy('Id', $inner, Criteria::NOT_IN);

        $expected = 'SELECT  FROM author WHERE author.id NOT IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testInIsDefaultOperatorForQueryInputInFilterBy()
    {
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $query = AuthorQuery::create()->filterBy('Id', $inner);

        $expected = 'SELECT  FROM author WHERE author.id IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testCreateInFromFilterByMagicMethod()
    {
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $query = AuthorQuery::create()->filterById($inner, Criteria::IN);

        $expected = 'SELECT  FROM author WHERE author.id IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testCreateNotInFromFilterByMagicMethod()
    {
        $inner = BookQuery::create()->filterByPrice(15, Criteria::LESS_EQUAL)->select('author_id');
        $query = AuthorQuery::create()->filterById($inner, Criteria::NOT_IN);

        $expected = 'SELECT  FROM author WHERE author.id NOT IN (SELECT book.author_id AS "author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testUseInQuery()
    {
        $query = AuthorQuery::create()
            ->useInQuery('Book')
            ->filterByPrice(15, Criteria::LESS_EQUAL)
            ->endUse();

        $expected = 'SELECT  FROM author WHERE author.id IN (SELECT book.author_id AS "book.author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testUseNotInQuery()
    {
        $query = AuthorQuery::create()
            ->useNotInQuery('Book')
            ->filterByPrice(15, Criteria::LESS_EQUAL)
            ->endUse();

        $expected = 'SELECT  FROM author WHERE author.id NOT IN (SELECT book.author_id AS "book.author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testUseInRelationQuery()
    {
        $query = AuthorQuery::create()
            ->useInBookQuery()
            ->filterByPrice(15, Criteria::LESS_EQUAL)
            ->endUse();

        $expected = 'SELECT  FROM author WHERE author.id IN (SELECT book.author_id AS "book.author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }

    /**
     * @return void
     */
    public function testUseNotInRelationQuery()
    {
        $query = AuthorQuery::create()
            ->useNotInBookQuery()
            ->filterByPrice(15, Criteria::LESS_EQUAL)
            ->endUse();

        $expected = 'SELECT  FROM author WHERE author.id NOT IN (SELECT book.author_id AS "book.author_id" FROM book WHERE book.price<=:p1)';
        $this->assertQuerySqlSame($expected, $query);
    }
}
