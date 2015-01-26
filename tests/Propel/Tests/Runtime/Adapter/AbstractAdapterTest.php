<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter;

use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\Map\BookTableMap;
use Propel\Tests\TestCaseFixtures;

/**
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author Francois EZaninotto
 */
class AbstractAdapterTest extends TestCaseFixtures
{
    public function testTurnSelectColumnsToAliases()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::COL_ID);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_id', BookTableMap::COL_ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesPreservesAliases()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::COL_ID);
        $c1->addAsColumn('foo', BookTableMap::COL_TITLE);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_id', BookTableMap::COL_ID);
        $c2->addAsColumn('foo', BookTableMap::COL_TITLE);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesExisting()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::COL_ID);
        $c1->addAsColumn('book_id', BookTableMap::COL_ID);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_id_1', BookTableMap::COL_ID);
        $c2->addAsColumn('book_id', BookTableMap::COL_ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesDuplicate()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::COL_ID);
        $c1->addSelectColumn(BookTableMap::COL_ID);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_id', BookTableMap::COL_ID);
        $c2->addAsColumn('book_id_1', BookTableMap::COL_ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testCreateSelectSqlPart()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addAsColumn('book_id', BookTableMap::COL_ID);
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.id, book.id AS book_id', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    public function testCreateSelectSqlPartWithFnc()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addAsColumn('book_id', 'IF(1, '.BookTableMap::COL_ID.', '.BookTableMap::COL_TITLE.')');
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.id, IF(1, book.id, book.title) AS book_id', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    public function testCreateSelectSqlPartSelectModifier()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addAsColumn('book_id', BookTableMap::COL_ID);
        $c->setDistinct();
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT DISTINCT book.id, book.id AS book_id', $selectSql, 'createSelectSqlPart() includes the select modifiers in the SELECT clause');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    public function testCreateSelectSqlPartAliasAll()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::COL_ID);
        $c->addAsColumn('book_id', BookTableMap::COL_ID);
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause, true);
        $this->assertEquals('SELECT book.id AS book_id_1, book.id AS book_id', $selectSql, 'createSelectSqlPart() aliases all columns if passed true as last parameter');
        $this->assertEquals(array(), $fromClause, 'createSelectSqlPart() does not add the tables from an all-aliased list of select columns');
    }

}
