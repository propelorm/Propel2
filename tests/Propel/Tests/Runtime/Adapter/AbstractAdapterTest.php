<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Runtime\Adapter;

use Propel\Runtime\Configuration;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Tests\Bookstore\Map\BookEntityMap;
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
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectField(BookEntityMap::FIELD_ID);
        $db->turnSelectFieldsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesPreservesAliases()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectField(BookEntityMap::FIELD_ID);
        $c1->addAsField('foo', BookEntityMap::FIELD_TITLE);
        $db->turnSelectFieldsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $c2->addAsField('foo', BookEntityMap::FIELD_TITLE);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesExisting()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectField(BookEntityMap::FIELD_ID);
        $c1->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $db->turnSelectFieldsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsField('Propel_Tests_Bookstore_Book_id_1', BookEntityMap::FIELD_ID);
        $c2->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testTurnSelectColumnsToAliasesDuplicate()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c1 = new Criteria();
        $c1->addSelectField(BookEntityMap::FIELD_ID);
        $c1->addSelectField(BookEntityMap::FIELD_ID);
        $db->turnSelectFieldsToAliases($c1);

        $c2 = new Criteria();
        $c2->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $c2->addAsField('Propel_Tests_Bookstore_Book_id_1', BookEntityMap::FIELD_ID);
        $this->assertTrue($c1->equals($c2));
    }

    public function testCreateSelectSqlPart()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectField(BookEntityMap::FIELD_ID);
        $c->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $selectSql = $db->createSelectSqlPart($c);
        $this->assertEquals('SELECT Propel\Tests\Bookstore\Book.id, Propel\Tests\Bookstore\Book.id AS Propel_Tests_Bookstore_Book_id', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
    }

    public function testCreateSelectSqlPartWithFnc()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectField(BookEntityMap::FIELD_ID);
        $c->addAsField('Propel_Tests_Bookstore_Book_id', 'IF(1, '.BookEntityMap::FIELD_ID.', '.BookEntityMap::FIELD_TITLE.')');
        $selectSql = $db->createSelectSqlPart($c);
        $this->assertEquals('SELECT Propel\Tests\Bookstore\Book.id, IF(1, Propel\Tests\Bookstore\Book.id, Propel\Tests\Bookstore\Book.title) AS Propel_Tests_Bookstore_Book_id', $selectSql, 'createSelectSqlPart() returns a SQL SELECT clause with both select and as columns');
    }

    public function testCreateSelectSqlPartSelectModifier()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectField(BookEntityMap::FIELD_ID);
        $c->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $c->setDistinct();
        $selectSql = $db->createSelectSqlPart($c);
        $this->assertEquals('SELECT DISTINCT Propel\Tests\Bookstore\Book.id, Propel\Tests\Bookstore\Book.id AS Propel_Tests_Bookstore_Book_id', $selectSql, 'createSelectSqlPart() includes the select modifiers in the SELECT clause');
    }

    public function testCreateSelectSqlPartAliasAll()
    {
        $db = Configuration::getCurrentConfiguration()->getAdapter(BookEntityMap::DATABASE_NAME);
        $c = new Criteria();
        $c->addSelectField(BookEntityMap::FIELD_ID);
        $c->addAsField('Propel_Tests_Bookstore_Book_id', BookEntityMap::FIELD_ID);
        $selectSql = $db->createSelectSqlPart($c, true);
        $this->assertEquals('SELECT Propel\Tests\Bookstore\Book.id AS Propel_Tests_Bookstore_Book_id_1, Propel\Tests\Bookstore\Book.id AS Propel_Tests_Bookstore_Book_id', $selectSql, 'createSelectSqlPart() aliases all columns if passed true as last parameter');
    }

}
