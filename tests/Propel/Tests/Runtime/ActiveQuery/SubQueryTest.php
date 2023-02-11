<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Test class for SubQueryTest.
 *
 * @author Francois Zaninotto
 *
 * @group database
 */
class SubQueryTest extends BookstoreTestBase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param \Propel\Runtime\ActiveQuery\Criteria $criteria
     * @param mixed $expectedSql
     * @param mixed $expectedParams
     * @param string $message
     *
     * @return void
     */
    protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
    {
        $params = [];
        $result = $criteria->createSelectSql($params);

        $this->assertEquals($expectedSql, $result, $message);
        $this->assertEquals($expectedParams, $params, $message);
    }

    /**
     * @return void
     */
    public function testSubQueryExplicit()
    {
        $subCriteria = new BookQuery();
        BookTableMap::addSelectColumns($subCriteria);
        $subCriteria->orderByTitle(Criteria::ASC);

        $c = new BookQuery();
        BookTableMap::addSelectColumns($c, 'subCriteriaAlias');
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias', false);
        $c->groupBy('subCriteriaAlias.AuthorId');

        if ($this->isDb('pgsql')) {
            $sql = $this->getSql('SELECT subCriteriaAlias.id, subCriteriaAlias.title, subCriteriaAlias.isbn, subCriteriaAlias.price, subCriteriaAlias.publisher_id, subCriteriaAlias.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book ORDER BY book.title ASC) AS subCriteriaAlias GROUP BY subCriteriaAlias.author_id,subCriteriaAlias.id,subCriteriaAlias.title,subCriteriaAlias.isbn,subCriteriaAlias.price,subCriteriaAlias.publisher_id');
        } else {
            $sql = $this->getSql('SELECT subCriteriaAlias.id, subCriteriaAlias.title, subCriteriaAlias.isbn, subCriteriaAlias.price, subCriteriaAlias.publisher_id, subCriteriaAlias.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book ORDER BY book.title ASC) AS subCriteriaAlias GROUP BY subCriteriaAlias.author_id');
        }

        $params = [];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries successfully');
    }

    /**
     * @return void
     */
    public function testSubQueryWithoutSelect()
    {
        $subCriteria = new BookQuery();
        // no addSelectColumns()

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias');
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql('SELECT subCriteriaAlias.id, subCriteriaAlias.title, subCriteriaAlias.isbn, subCriteriaAlias.price, subCriteriaAlias.publisher_id, subCriteriaAlias.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) AS subCriteriaAlias WHERE subCriteriaAlias.price<:p1');

        $params = [
            ['table' => 'book', 'column' => 'price', 'value' => 20],
        ];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() adds select columns if none given');
    }

    /**
     * @return void
     */
    public function testSubQueryWithoutAlias()
    {
        $subCriteria = new BookQuery();
        $subCriteria->addSelfSelectColumns();

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria); // no alias
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql('SELECT alias_1.id, alias_1.title, alias_1.isbn, alias_1.price, alias_1.publisher_id, alias_1.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) AS alias_1 WHERE alias_1.price<:p1');

        $params = [
            ['table' => 'book', 'column' => 'price', 'value' => 20],
        ];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() forges a unique alias if none is given');
    }

    /**
     * @return void
     */
    public function testSubQueryWithoutAliasAndSelect()
    {
        $subCriteria = new BookQuery();
        // no select

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria); // no alias
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql('SELECT alias_1.id, alias_1.title, alias_1.isbn, alias_1.price, alias_1.publisher_id, alias_1.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) AS alias_1 WHERE alias_1.price<:p1');

        $params = [
            ['table' => 'book', 'column' => 'price', 'value' => 20],
        ];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() forges a unique alias and adds select columns by default');
    }

    /**
     * @return void
     */
    public function testSubQueryWithoutAliasSeveral()
    {
        $c1 = new BookQuery();
        $c1->filterByPrice(10, Criteria::GREATER_THAN);

        $c2 = new BookQuery();
        $c2->filterByPrice(20, Criteria::LESS_THAN);

        $c3 = new BookQuery();
        $c3->addSelectQuery($c1); // no alias
        $c3->addSelectQuery($c2); // no alias
        $c3->filterByTitle('War%', Criteria::LIKE);

        $sql = $this->getSql('SELECT alias_1.id, alias_1.title, alias_1.isbn, alias_1.price, alias_1.publisher_id, alias_1.author_id, alias_2.id, alias_2.title, alias_2.isbn, alias_2.price, alias_2.publisher_id, alias_2.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.price>:p2) AS alias_1, (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.price<:p3) AS alias_2 WHERE alias_2.title LIKE :p1');

        $params = [
            ['table' => 'book', 'column' => 'title', 'value' => 'War%'],
            ['table' => 'book', 'column' => 'price', 'value' => 10],
            ['table' => 'book', 'column' => 'price', 'value' => 20],
        ];

        $this->assertCriteriaTranslation($c3, $sql, $params, 'addSelectQuery() forges a unique alias if none is given');
    }

    /**
     * @return void
     */
    public function testSubQueryWithoutAliasRecursive()
    {
        $c1 = new BookQuery();

        $c2 = new BookQuery();
        $c2->addSelectQuery($c1); // no alias
        $c2->filterByPrice(20, Criteria::LESS_THAN);

        $c3 = new BookQuery();
        $c3->addSelectQuery($c2); // no alias
        $c3->filterByTitle('War%', Criteria::LIKE);

        $sql = $this->getSql('SELECT alias_2.id, alias_2.title, alias_2.isbn, alias_2.price, alias_2.publisher_id, alias_2.author_id FROM (SELECT alias_1.id, alias_1.title, alias_1.isbn, alias_1.price, alias_1.publisher_id, alias_1.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) AS alias_1 WHERE alias_1.price<:p2) AS alias_2 WHERE alias_2.title LIKE :p1');

        $params = [
            ['table' => 'book', 'column' => 'title', 'value' => 'War%'],
            ['table' => 'book', 'column' => 'price', 'value' => 20],
        ];
        $this->assertCriteriaTranslation($c3, $sql, $params, 'addSelectQuery() forges a unique alias if none is given');
    }

    /**
     * @return void
     */
    public function testSubQueryWithJoin()
    {
        $c1 = BookQuery::create()
            ->useAuthorQuery()
                ->filterByLastName('Rowling')
            ->endUse();

        $c2 = new BookQuery();
        $c2->addSelectQuery($c1, 'subQuery');
        $c2->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql('SELECT subQuery.id, subQuery.title, subQuery.isbn, subQuery.price, subQuery.publisher_id, subQuery.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book LEFT JOIN author ON (book.author_id=author.id) WHERE author.last_name=:p2) AS subQuery WHERE subQuery.price<:p1');

        $params = [
            ['table' => 'book', 'column' => 'price', 'value' => 20],
            ['table' => 'author', 'column' => 'last_name', 'value' => 'Rowling'],
        ];
        $this->assertCriteriaTranslation($c2, $sql, $params, 'addSelectQuery() can add a select query with a join');
    }

    /**
     * @return void
     */
    public function testSubQueryParameters()
    {
        $subCriteria = new BookQuery();
        $subCriteria->filterByAuthorId(123);

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias');
        // and use filterByPrice method!
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql('SELECT subCriteriaAlias.id, subCriteriaAlias.title, subCriteriaAlias.isbn, subCriteriaAlias.price, subCriteriaAlias.publisher_id, subCriteriaAlias.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.author_id=:p2) AS subCriteriaAlias WHERE subCriteriaAlias.price<:p1');

        $params = [
            ['table' => 'book', 'column' => 'price', 'value' => 20],
            ['table' => 'book', 'column' => 'author_id', 'value' => 123],
        ];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries successfully');
    }

    /**
     * @return void
     */
    public function testSubQueryRecursive()
    {
        // sort the books (on date, if equal continue with id), filtered by a publisher
        $sortedBookQuery = new BookQuery();
        $sortedBookQuery->addSelfSelectColumns();
        $sortedBookQuery->filterByPublisherId(123);
        $sortedBookQuery->orderByTitle(Criteria::DESC);
        $sortedBookQuery->orderById(Criteria::DESC);

        // group by author, after sorting!
        $latestBookQuery = new BookQuery();
        $latestBookQuery->addSelectQuery($sortedBookQuery, 'sortedBookQuery');
        $latestBookQuery->groupBy('sortedBookQuery.AuthorId');

        // filter from these latest books, find the ones cheaper than 12 euro
        $c = new BookQuery();
        $c->addSelectQuery($latestBookQuery, 'latestBookQuery');
        $c->filterByPrice(12, Criteria::LESS_THAN);

        if ($this->isDb('pgsql')) {
            $sql = $this->getSql('SELECT latestBookQuery.id, latestBookQuery.title, latestBookQuery.isbn, latestBookQuery.price, latestBookQuery.publisher_id, latestBookQuery.author_id ' .
            'FROM (SELECT sortedBookQuery.id, sortedBookQuery.title, sortedBookQuery.isbn, sortedBookQuery.price, sortedBookQuery.publisher_id, sortedBookQuery.author_id FROM ' .
            '(SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book WHERE book.publisher_id=:p2 ORDER BY book.title DESC,book.id DESC) AS sortedBookQuery ' .
            'GROUP BY sortedBookQuery.author_id,sortedBookQuery.id,sortedBookQuery.title,sortedBookQuery.isbn,sortedBookQuery.price,sortedBookQuery.publisher_id) AS latestBookQuery WHERE latestBookQuery.price<:p1');
        } else {
            $sql = $this->getSql('SELECT latestBookQuery.id, latestBookQuery.title, latestBookQuery.isbn, latestBookQuery.price, latestBookQuery.publisher_id, latestBookQuery.author_id ' .
            'FROM (SELECT sortedBookQuery.id, sortedBookQuery.title, sortedBookQuery.isbn, sortedBookQuery.price, sortedBookQuery.publisher_id, sortedBookQuery.author_id ' .
            'FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id ' .
            'FROM book ' .
            'WHERE book.publisher_id=:p2 ' .
            'ORDER BY book.title DESC,book.id DESC) AS sortedBookQuery ' .
            'GROUP BY sortedBookQuery.author_id) AS latestBookQuery ' .
            'WHERE latestBookQuery.price<:p1');
        }

        $params = [
            ['table' => 'book', 'column' => 'price', 'value' => 12],
            ['table' => 'book', 'column' => 'publisher_id', 'value' => 123],
        ];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries successfully');
    }

    /**
     * @return void
     */
    public function testSubQueryWithSelectColumns()
    {
        $subCriteria = new BookQuery();

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'alias1', false);
        $c->select(['alias1.Id']);
        $c->configureSelectColumns();

        $sql = $this->getSql('SELECT alias1.id AS "alias1.Id" FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) AS alias1');

        $params = [];
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() forges a unique alias and adds select columns by default');
    }

    /**
     * @return void
     */
    public function testSubQueryCount()
    {
        $subCriteria = new BookQuery();

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias');
        $c->filterByPrice(20, Criteria::LESS_THAN);
        $nbBooks = $c->count();

        $query = Propel::getConnection()->getLastExecutedQuery();

        $sql = $this->getSql('SELECT COUNT(*) FROM (SELECT subCriteriaAlias.id, subCriteriaAlias.title, subCriteriaAlias.isbn, subCriteriaAlias.price, subCriteriaAlias.publisher_id, subCriteriaAlias.author_id FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) AS subCriteriaAlias WHERE subCriteriaAlias.price<20) propelmatch4cnt');

        $this->assertEquals($sql, $query, 'addSelectQuery() doCount is defined as complexQuery');
    }
}
