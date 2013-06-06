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

use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author Francois EZaninotto
 */
class OracleAdapterTest extends BookstoreTestBase
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
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.ID, book.TITLE, book.ISBN, book.PRICE, book.PUBLISHER_ID, book.AUTHOR_ID FROM book) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with the original column names by default');
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
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.ID AS ORA_COL_ALIAS_0, book.TITLE AS ORA_COL_ALIAS_1, book.ISBN AS ORA_COL_ALIAS_2, book.PRICE AS ORA_COL_ALIAS_3, book.PUBLISHER_ID AS ORA_COL_ALIAS_4, book.AUTHOR_ID AS ORA_COL_ALIAS_5, author.ID AS ORA_COL_ALIAS_6, author.FIRST_NAME AS ORA_COL_ALIAS_7, author.LAST_NAME AS ORA_COL_ALIAS_8, author.EMAIL AS ORA_COL_ALIAS_9, author.AGE AS ORA_COL_ALIAS_10 FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
    }

    public function testApplyLimitDuplicateColumnNameWithColumn()
    {
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $c = new Criteria();
        $c->setDbName('oracle');
        BookTableMap::addSelectColumns($c);
        AuthorTableMap::addSelectColumns($c);
        $c->addAsColumn('BOOK_PRICE', BookTableMap::PRICE);
        $c->setLimit(1);
        $params = array();
        $asColumns = $c->getAsColumns();
        $sql = $c->createSelectSql($params);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.ID AS ORA_COL_ALIAS_0, book.TITLE AS ORA_COL_ALIAS_1, book.ISBN AS ORA_COL_ALIAS_2, book.PRICE AS ORA_COL_ALIAS_3, book.PUBLISHER_ID AS ORA_COL_ALIAS_4, book.AUTHOR_ID AS ORA_COL_ALIAS_5, author.ID AS ORA_COL_ALIAS_6, author.FIRST_NAME AS ORA_COL_ALIAS_7, author.LAST_NAME AS ORA_COL_ALIAS_8, author.EMAIL AS ORA_COL_ALIAS_9, author.AGE AS ORA_COL_ALIAS_10, book.PRICE AS BOOK_PRICE FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
        $this->assertEquals($asColumns, $c->getAsColumns(), 'createSelectSql supplementary add alias column');
    }

    public function testCreateSelectSqlPart()
    {
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $db = Propel::getServiceContainer()->getAdapter();
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::ID);
        $c->addAsColumn('book_ID', BookTableMap::ID);
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.ID, book.ID AS book_ID', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

}
