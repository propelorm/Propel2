<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\ActiveQuery;

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\BookTableMap;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Test class for SubQueryTest.
 *
 * @author Francois Zaninotto
 * @version    $Id$
 */
class SubQueryTest extends BookstoreTestBase
{
    protected function assertCriteriaTranslation($criteria, $expectedSql, $expectedParams, $message = '')
    {
        $params = array();
        $result = $criteria->createSelectSql($params);

        $this->assertEquals($expectedSql, $result, $message);
        $this->assertEquals($expectedParams, $params, $message);
    }

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
            $sql = $this->getSql("SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM book ORDER BY book.TITLE ASC) AS subCriteriaAlias GROUP BY subCriteriaAlias.AUTHOR_ID,subCriteriaAlias.ID,subCriteriaAlias.TITLE,subCriteriaAlias.ISBN,subCriteriaAlias.PRICE,subCriteriaAlias.PUBLISHER_ID");
        } else {
            $sql = $this->getSql("SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` ORDER BY book.TITLE ASC) AS subCriteriaAlias GROUP BY subCriteriaAlias.AUTHOR_ID");
        }

        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries successfully');
    }

    public function testSubQueryWithoutSelect()
    {
        $subCriteria = new BookQuery();
        // no addSelectColumns()

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias');
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql("SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book`) AS subCriteriaAlias WHERE subCriteriaAlias.PRICE<:p1");

        $params = array(
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() adds select columns if none given');
    }

    public function testSubQueryWithoutAlias()
    {
        $subCriteria = new BookQuery();
        $subCriteria->addSelfSelectColumns();

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria); // no alias
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql("SELECT alias_1.ID, alias_1.TITLE, alias_1.ISBN, alias_1.PRICE, alias_1.PUBLISHER_ID, alias_1.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book`) AS alias_1 WHERE alias_1.PRICE<:p1");

        $params = array(
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() forges a unique alias if none is given');
    }

    public function testSubQueryWithoutAliasAndSelect()
    {
        $subCriteria = new BookQuery();
        // no select

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria); // no alias
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql("SELECT alias_1.ID, alias_1.TITLE, alias_1.ISBN, alias_1.PRICE, alias_1.PUBLISHER_ID, alias_1.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book`) AS alias_1 WHERE alias_1.PRICE<:p1");

        $params = array(
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() forges a unique alias and adds select columns by default');
    }

    public function testSubQueryWithoutAliasSeveral()
    {
        $c1 = new BookQuery();
        $c1->filterByPrice(10, Criteria::GREATER_THAN);

        $c2 = new BookQuery();
        $c2->filterByPrice(20, Criteria::LESS_THAN);

        $c3 = new BookQuery();
        $c3->addSelectQuery($c1); // no alias
        $c3->addSelectQuery($c2); // no alias
        $c3->filterByTitle('War%');

        $sql = $this->getSql("SELECT alias_1.ID, alias_1.TITLE, alias_1.ISBN, alias_1.PRICE, alias_1.PUBLISHER_ID, alias_1.AUTHOR_ID, alias_2.ID, alias_2.TITLE, alias_2.ISBN, alias_2.PRICE, alias_2.PUBLISHER_ID, alias_2.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.PRICE>:p2) AS alias_1, (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.PRICE<:p3) AS alias_2 WHERE alias_2.TITLE LIKE :p1");

        $params = array(
            array('table' => 'book', 'column' => 'TITLE', 'value' => 'War%'),
            array('table' => 'book', 'column' => 'PRICE', 'value' => 10),
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
        );
        $this->assertCriteriaTranslation($c3, $sql, $params, 'addSelectQuery() forges a unique alias if none is given');
    }

    public function testSubQueryWithoutAliasRecursive()
    {
        $c1 = new BookQuery();

        $c2 = new BookQuery();
        $c2->addSelectQuery($c1); // no alias
        $c2->filterByPrice(20, Criteria::LESS_THAN);

        $c3 = new BookQuery();
        $c3->addSelectQuery($c2); // no alias
        $c3->filterByTitle('War%');

        $sql = $this->getSql("SELECT alias_2.ID, alias_2.TITLE, alias_2.ISBN, alias_2.PRICE, alias_2.PUBLISHER_ID, alias_2.AUTHOR_ID FROM (SELECT alias_1.ID, alias_1.TITLE, alias_1.ISBN, alias_1.PRICE, alias_1.PUBLISHER_ID, alias_1.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book`) AS alias_1 WHERE alias_1.PRICE<:p2) AS alias_2 WHERE alias_2.TITLE LIKE :p1");

        $params = array(
            array('table' => 'book', 'column' => 'TITLE', 'value' => 'War%'),
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
        );
        $this->assertCriteriaTranslation($c3, $sql, $params, 'addSelectQuery() forges a unique alias if none is given');
    }

    public function testSubQueryWithJoin()
    {
        $c1 = BookQuery::create()
            ->useAuthorQuery()
                ->filterByLastName('Rowling')
            ->endUse();

        $c2 = new BookQuery();
        $c2->addSelectQuery($c1, 'subQuery');
        $c2->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql("SELECT subQuery.ID, subQuery.TITLE, subQuery.ISBN, subQuery.PRICE, subQuery.PUBLISHER_ID, subQuery.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` LEFT JOIN `author` ON (book.AUTHOR_ID=author.ID) WHERE author.LAST_NAME=:p2) AS subQuery WHERE subQuery.PRICE<:p1");

        $params = array(
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
            array('table' => 'author', 'column' => 'LAST_NAME', 'value' => 'Rowling'),
        );
        $this->assertCriteriaTranslation($c2, $sql, $params, 'addSelectQuery() can add a select query with a join');
    }

    public function testSubQueryParameters()
    {
        $subCriteria = new BookQuery();
        $subCriteria->filterByAuthorId(123);

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias');
        // and use filterByPrice method!
        $c->filterByPrice(20, Criteria::LESS_THAN);

        $sql = $this->getSql("SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book` WHERE book.AUTHOR_ID=:p2) AS subCriteriaAlias WHERE subCriteriaAlias.PRICE<:p1");

        $params = array(
            array('table' => 'book', 'column' => 'PRICE', 'value' => 20),
            array('table' => 'book', 'column' => 'AUTHOR_ID', 'value' => 123),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries successfully');
    }

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
            $sql = $this->getSql("SELECT latestBookQuery.ID, latestBookQuery.TITLE, latestBookQuery.ISBN, latestBookQuery.PRICE, latestBookQuery.PUBLISHER_ID, latestBookQuery.AUTHOR_ID ".
            "FROM (SELECT sortedBookQuery.ID, sortedBookQuery.TITLE, sortedBookQuery.ISBN, sortedBookQuery.PRICE, sortedBookQuery.PUBLISHER_ID, sortedBookQuery.AUTHOR_ID FROM ".
            "(SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM book WHERE book.PUBLISHER_ID=:p2 ORDER BY book.TITLE DESC,book.ID DESC) AS sortedBookQuery ".
            "GROUP BY sortedBookQuery.AUTHOR_ID,sortedBookQuery.ID,sortedBookQuery.TITLE,sortedBookQuery.ISBN,sortedBookQuery.PRICE,sortedBookQuery.PUBLISHER_ID) AS latestBookQuery WHERE latestBookQuery.PRICE<:p1");
        } else {
            $sql = $this->getSql("SELECT latestBookQuery.ID, latestBookQuery.TITLE, latestBookQuery.ISBN, latestBookQuery.PRICE, latestBookQuery.PUBLISHER_ID, latestBookQuery.AUTHOR_ID ".
            "FROM (SELECT sortedBookQuery.ID, sortedBookQuery.TITLE, sortedBookQuery.ISBN, sortedBookQuery.PRICE, sortedBookQuery.PUBLISHER_ID, sortedBookQuery.AUTHOR_ID ".
            "FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID ".
            "FROM `book` ".
            "WHERE book.PUBLISHER_ID=:p2 ".
            "ORDER BY book.TITLE DESC,book.ID DESC) AS sortedBookQuery ".
            "GROUP BY sortedBookQuery.AUTHOR_ID) AS latestBookQuery ".
            "WHERE latestBookQuery.PRICE<:p1");
        }

        $params = array(
            array('table' => 'book', 'column' => 'PRICE', 'value' => 12),
            array('table' => 'book', 'column' => 'PUBLISHER_ID', 'value' => 123),
        );
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSubQueryCriteriaInFrom() combines two queries successfully');
    }

    public function testSubQueryWithSelectColumns()
    {
        $subCriteria = new BookQuery();

        $c = new TestableBookQuery();
        $c->addSelectQuery($subCriteria, 'alias1', false);
        $c->select(array('alias1.Id'));
        $c->configureSelectColumns();

        $sql = $this->getSql("SELECT alias1.ID AS \"alias1.Id\" FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book`) AS alias1");

        $params = array();
        $this->assertCriteriaTranslation($c, $sql, $params, 'addSelectQuery() forges a unique alias and adds select columns by default');
    }

    public function testSubQueryCount()
    {
        $subCriteria = new BookQuery();

        $c = new BookQuery();
        $c->addSelectQuery($subCriteria, 'subCriteriaAlias');
        $c->filterByPrice(20, Criteria::LESS_THAN);
        $nbBooks = $c->count();

        $query = Propel::getConnection()->getLastExecutedQuery();

        $sql = $this->getSql("SELECT COUNT(*) FROM (SELECT subCriteriaAlias.ID, subCriteriaAlias.TITLE, subCriteriaAlias.ISBN, subCriteriaAlias.PRICE, subCriteriaAlias.PUBLISHER_ID, subCriteriaAlias.AUTHOR_ID FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM `book`) AS subCriteriaAlias WHERE subCriteriaAlias.PRICE<20) propelmatch4cnt");

        $this->assertEquals($sql, $query, 'addSelectQuery() doCount is defined as complexQuery');
    }
}

class TestableBookQuery extends BookQuery
{
    public function configureSelectColumns()
    {
        return parent::configureSelectColumns();
    }
}
