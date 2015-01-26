<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\Propel;
use Propel\Runtime\Adapter\Pdo\OracleAdapter;
use Propel\Runtime\ActiveQuery\Criteria;

use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author Francois EZaninotto
 */
class OracleAdapterTest extends TestCaseFixtures
{
    public function testApplyLimitSimple()
    {
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $c = new Criteria();
        $c->setDbName('oracle');
        BookTableMap::addSelectColumns($c);
        $c->setLimit(1);
        $params = array();
        $sql = $c->createSelectSql($params);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with the original column names by default');
    }

    public function testApplyLimitDuplicateColumnName()
    {
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $c = new Criteria();
        $c->setDbName('oracle');
        BookTableMap::addSelectColumns($c);
        AuthorTableMap::addSelectColumns($c);
        $c->setLimit(1);
        $params = array();
        $sql = $c->createSelectSql($params);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id AS ORA_COL_ALIAS_0, book.title AS ORA_COL_ALIAS_1, book.isbn AS ORA_COL_ALIAS_2, book.price AS ORA_COL_ALIAS_3, book.publisher_id AS ORA_COL_ALIAS_4, book.author_id AS ORA_COL_ALIAS_5, author.id AS ORA_COL_ALIAS_6, author.first_name AS ORA_COL_ALIAS_7, author.last_name AS ORA_COL_ALIAS_8, author.email AS ORA_COL_ALIAS_9, author.age AS ORA_COL_ALIAS_10 FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
    }

    public function testApplyLimitDuplicateColumnNameWithColumn()
    {
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $c = new Criteria();
        $c->setDbName('oracle');
        BookTableMap::addSelectColumns($c);
        AuthorTableMap::addSelectColumns($c);
        $c->addAsColumn('BOOK_PRICE', BookTableMap::COL_PRICE);
        $c->setLimit(1);
        $params = array();
        $asColumns = $c->getAsColumns();
        $sql = $c->createSelectSql($params);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id AS ORA_COL_ALIAS_0, book.title AS ORA_COL_ALIAS_1, book.isbn AS ORA_COL_ALIAS_2, book.price AS ORA_COL_ALIAS_3, book.publisher_id AS ORA_COL_ALIAS_4, book.author_id AS ORA_COL_ALIAS_5, author.id AS ORA_COL_ALIAS_6, author.first_name AS ORA_COL_ALIAS_7, author.last_name AS ORA_COL_ALIAS_8, author.email AS ORA_COL_ALIAS_9, author.age AS ORA_COL_ALIAS_10, book.price AS BOOK_PRICE FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
        $this->assertEquals($asColumns, $c->getAsColumns(), 'createSelectSql supplementary add alias column');
    }

    public function testCreateSelectSqlPart()
    {
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $db = Propel::getServiceContainer()->getAdapter();
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addAsColumn('book_ID', BookTableMap::COL_ID);
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.id, book.id AS book_ID', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

}
