<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Adapter\Pdo;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Adapter\Pdo\OracleAdapter;
use Propel\Runtime\Propel;
use Propel\Tests\Bookstore\BookQuery;
use Propel\Tests\Bookstore\Map\AuthorTableMap;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbOracle adapter
 *
 * @see BookstoreDataPopulator
 * @author Francois EZaninotto
 */
class OracleAdapterTest extends TestCaseFixtures
{
    /**
     * @return string
     */
    protected function getDriver()
    {
        return 'oracle';
    }
    
    protected function createOracleSql(Criteria $query): string
    {
        $params = [];
        Propel::getServiceContainer()->setAdapter('oracle', new OracleAdapter());
        $query->setDbName('oracle');
        return $query->createSelectSql($params);
    }

    /**
     * @return void
     */
    public function testApplyLimitSimple()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);
        $c->setLimit(1);
        $sql = $this->createOracleSql($c);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id, book.title, book.isbn, book.price, book.publisher_id, book.author_id FROM book) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with the original column names by default');
    }

    /**
     * @return void
     */
    public function testApplyLimitDuplicateColumnName()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);
        AuthorTableMap::addSelectColumns($c);
        $c->setLimit(1);
        $sql = $this->createOracleSql($c);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id AS ORA_COL_ALIAS_0, book.title AS ORA_COL_ALIAS_1, book.isbn AS ORA_COL_ALIAS_2, book.price AS ORA_COL_ALIAS_3, book.publisher_id AS ORA_COL_ALIAS_4, book.author_id AS ORA_COL_ALIAS_5, author.id AS ORA_COL_ALIAS_6, author.first_name AS ORA_COL_ALIAS_7, author.last_name AS ORA_COL_ALIAS_8, author.email AS ORA_COL_ALIAS_9, author.age AS ORA_COL_ALIAS_10 FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
    }

    /**
     * @return void
     */
    public function testApplyLimitDuplicateColumnNameWithColumn()
    {
        $c = new Criteria();
        BookTableMap::addSelectColumns($c);
        AuthorTableMap::addSelectColumns($c);
        $c->addAsColumn('BOOK_PRICE', BookTableMap::COL_PRICE);
        $c->setLimit(1);
        $asColumns = $c->getAsColumns();
        $sql = $this->createOracleSql($c);
        $this->assertEquals('SELECT B.* FROM (SELECT A.*, rownum AS PROPEL_ROWNUM FROM (SELECT book.id AS ORA_COL_ALIAS_0, book.title AS ORA_COL_ALIAS_1, book.isbn AS ORA_COL_ALIAS_2, book.price AS ORA_COL_ALIAS_3, book.publisher_id AS ORA_COL_ALIAS_4, book.author_id AS ORA_COL_ALIAS_5, author.id AS ORA_COL_ALIAS_6, author.first_name AS ORA_COL_ALIAS_7, author.last_name AS ORA_COL_ALIAS_8, author.email AS ORA_COL_ALIAS_9, author.age AS ORA_COL_ALIAS_10, book.price AS BOOK_PRICE FROM book, author) A ) B WHERE  B.PROPEL_ROWNUM <= 1', $sql, 'applyLimit() creates a subselect with aliased column names when a duplicate column name is found');
        $this->assertEquals($asColumns, $c->getAsColumns(), 'createSelectSql supplementary add alias column');
    }

    /**
     * @return void
     */
    public function testCreateSelectSqlPart()
    {
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addAsColumn('book_ID', BookTableMap::COL_ID);
        
        $adapter = new OracleAdapter();
        $fromClause = [];
        $selectSql = $adapter->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.id, book.id AS book_ID', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(['book'], $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    /**
     * Test `applyLock`
     *
     * @return void
     */
    public function testSimpleLock(): void
    {
        $c = new BookQuery();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->lockForShare();

        $result = $this->createOracleSql($c);

        $expected = 'SELECT book.id FROM book LOCK IN SHARE MODE';

        $this->assertEquals($expected, $result);
    }

    /**
     * Test `applyLock`
     *
     * @return void
     */
    public function testComplexLock(): void
    {
        $c = new BookQuery();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->lockForUpdate([BookTableMap::TABLE_NAME], true);

        $result = $this->createOracleSql($c);

        $expected = 'SELECT book.id FROM book FOR UPDATE';

        $this->assertEquals($expected, $result);
    }
}
