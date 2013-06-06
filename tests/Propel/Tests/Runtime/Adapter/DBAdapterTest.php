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
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;
use Propel\Tests\Bookstore\Map\BookTableMap;

/**
 * Tests the DbOracle adapter
 *
 * @see        BookstoreDataPopulator
 * @author Francois EZaninotto
 */
class AbstractAdapterTest extends BookstoreTestBase
{
    public function testTurnSelectColumnsToAliases()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::ID);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_ID', BookTableMap::ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesPreservesAliases()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::ID);
        $c1->addAsColumn('foo', BookTableMap::TITLE);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_ID', BookTableMap::ID);
        $c2->addAsColumn('foo', BookTableMap::TITLE);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesExisting()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::ID);
        $c1->addAsColumn('book_ID', BookTableMap::ID);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_ID_1', BookTableMap::ID);
        $c2->addAsColumn('book_ID', BookTableMap::ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesDuplicate()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectColumn(BookTableMap::ID);
        $c1->addSelectColumn(BookTableMap::ID);
        $db->turnSelectColumnsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsColumn('book_ID', BookTableMap::ID);
        $c2->addAsColumn('book_ID_1', BookTableMap::ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testCreateSelectSqlPart()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::ID);
        $c->addAsColumn('book_ID', BookTableMap::ID);
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.ID, book.ID AS book_ID', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    public function testCreateSelectSqlPartWithFnc()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::ID);
        $c->addAsColumn('book_ID', 'IF(1, '.BookTableMap::ID.', '.BookTableMap::TITLE.')');
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT book.ID, IF(1, book.ID, book.TITLE) AS book_ID', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    public function testCreateSelectSqlPartSelectModifier()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::ID);
        $c->addAsColumn('book_ID', BookTableMap::ID);
        $c->setDistinct();
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause);
        $this->assertEquals('SELECT DISTINCT book.ID, book.ID AS book_ID', $selectSql, 'createSelectSqlPart() includes the select modifiers in the SELECT clause');
        $this->assertEquals(array('book'), $fromClause, 'createSelectSqlPart() adds the tables from the select columns to the from clause');
    }

    public function testCreateSelectSqlPartAliasAll()
    {
        $db = Propel::getServiceContainer()->getAdapter(BookTableMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectColumn(BookTableMap::ID);
        $c->addAsColumn('book_ID', BookTableMap::ID);
        $fromClause = array();
        $selectSql = $db->createSelectSqlPart($c, $fromClause, true);
        $this->assertEquals('SELECT book.ID AS book_ID_1, book.ID AS book_ID', $selectSql, 'createSelectSqlPart() aliases all columns if passed true as last parameter');
        $this->assertEquals(array(), $fromClause, 'createSelectSqlPart() does not add the tables from an all-aliased list of select columns');
    }

}
