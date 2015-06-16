<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Behavior\AutoAddPk;

use Propel\Tests\Bookstore\Behavior\Entity6;
use Propel\Tests\Bookstore\Behavior\Entity8;
use Propel\Tests\Bookstore\Behavior\Entity8Query;
use Propel\Tests\Bookstore\Behavior\Map\Entity6EntityMap;
use Propel\Tests\Bookstore\Behavior\Map\Entity7EntityMap;
use Propel\Tests\Bookstore\Behavior\Map\Entity8EntityMap;
use Propel\Tests\Helpers\Bookstore\BookstoreTestBase;

/**
 * Tests for AutoAddPkBehavior class
 *
 * @author François Zaninotto
 *
 * @group database
 */
class AutoAddPkBehaviorTest extends BookstoreTestBase
{
    public function testDefault()
    {
        $table6 = $this->getConfiguration()->getEntityMap(Entity6EntityMap::ENTITY_CLASS);
        $this->assertEquals(count($table6->getFields()), 2, 'auto_add_pk adds one column by default');
        $pks = $table6->getPrimaryKeys();
        $this->assertEquals(count($pks), 1, 'auto_add_pk adds a simple primary key by default');
        $pk = array_pop($pks);
        $this->assertEquals($pk->getName(), 'id', 'auto_add_pk adds an id column by default');
        $this->assertEquals($pk->getType(), 'INTEGER', 'auto_add_pk adds an integer column by default');
        $this->assertTrue($pk->isPrimaryKey(), 'auto_add_pk adds a primary key column by default');
        $this->assertTrue($table6->isUseIdGenerator(), 'auto_add_pk adds an autoIncrement column by default');
    }

    public function testNoTrigger()
    {
        $table7 = $this->getConfiguration()->getEntityMap(Entity7EntityMap::ENTITY_CLASS);
        $this->assertEquals(count($table7->getFields()), 2, 'auto_add_pk does not add a column when the table already has a primary key');
        $this->assertFalse(method_exists('Table7', 'getId'), 'auto_add_pk does not add an id column when the table already has a primary key');
        $pks = $table7->getPrimaryKeys();
        $pk = array_pop($pks);
        $this->assertEquals($pk->getName(), 'foo', 'auto_add_pk does not change an existing primary key');
    }

    public function testParameters()
    {
        $table8 = $this->getConfiguration()->getEntityMap(Entity8EntityMap::ENTITY_CLASS);
        $this->assertEquals(count($table8->getFields()), 3, 'auto_add_pk adds one column with custom parameters');
        $pks = $table8->getPrimaryKeys();
        $pk = array_pop($pks);
        $this->assertEquals($pk->getName(), 'identifier', 'auto_add_pk accepts customization of pk column name');
        $this->assertEquals($pk->getType(), 'BIGINT', 'auto_add_pk accepts customization of pk column type');
        $this->assertTrue($pk->isPrimaryKey(), 'auto_add_pk adds a primary key column with custom parameters');
        $this->assertFalse($table8->isUseIdGenerator(), 'auto_add_pk accepts customization of pk column autoIncrement');
    }

    public function testForeignKey()
    {
        Entity8Query::create()->deleteAll();
        $t6 = new Entity6();
        $t6->setTitle('foo');
        $t6->save();
        $t8 = new Entity8();
        $t8->setIdentifier(1);
        $t8->setFoo($t6);
        $t8->save();
        $this->assertEquals($t8->getFoo()->getId(), $t6->getId(), 'Auto added pkeys can be used in relations');
        $t8->delete();
        $t6->delete();
    }
}
