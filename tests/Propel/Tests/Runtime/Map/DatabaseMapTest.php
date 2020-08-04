<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Map;

use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\Exception\ColumnNotFoundException;
use Propel\Runtime\Map\Exception\TableNotFoundException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixtures;

/**
 * Test class for DatabaseMap.
 *
 * @author François Zaninotto
 */
class DatabaseMapTest extends TestCaseFixtures
{
    protected $databaseMap;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseName = 'foodb';
        $this->databaseMap = TestDatabaseBuilder::getDmap();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        // nothing to do for now
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testConstructor()
    {
        $this->assertEquals($this->databaseName, $this->databaseMap->getName(), 'constructor sets the table name');
    }

    /**
     * @return void
     */
    public function testAddTable()
    {
        $this->assertFalse($this->databaseMap->hasTable('foo'), 'tables are empty by default');
        try {
            $this->databaseMap->getTable('foo');
            $this->fail('getTable() throws an exception when called on an inexistent table');
        } catch (TableNotFoundException $e) {
            $this->assertTrue(true, 'getTable() throws an exception when called on an inexistent table');
        }
        $tmap = $this->databaseMap->addTable('foo');
        $this->assertTrue($this->databaseMap->hasTable('foo'), 'hasTable() returns true when the table was added by way of addTable()');
        $this->assertEquals($tmap, $this->databaseMap->getTable('foo'), 'getTable() returns a table by name when the table was added by way of addTable()');
    }

    /**
     * @return void
     */
    public function testAddTableObject()
    {
        $this->assertFalse($this->databaseMap->hasTable('foo2'), 'tables are empty by default');
        try {
            $this->databaseMap->getTable('foo2');
            $this->fail('getTable() throws an exception when called on a table with no builder');
        } catch (TableNotFoundException $e) {
            $this->assertTrue(true, 'getTable() throws an exception when called on a table with no builder');
        }
        $tmap = new TableMap('foo2');
        $this->databaseMap->addTableObject($tmap);
        $this->assertTrue($this->databaseMap->hasTable('foo2'), 'hasTable() returns true when the table was added by way of addTableObject()');
        $this->assertEquals($tmap, $this->databaseMap->getTable('foo2'), 'getTable() returns a table by name when the table was added by way of addTableObject()');
    }

    /**
     * @return void
     */
    public function testAddTableFromMapClass()
    {
        $table1 = $this->databaseMap->addTableFromMapClass('\Propel\Tests\Runtime\Map\BazTableMap');
        try {
            $table2 = $this->databaseMap->getTable('baz');
            $this->assertEquals($table1, $table2, 'addTableFromMapClass() adds a table from a map class');
        } catch (PropelException $e) {
            $this->fail('addTableFromMapClass() adds a table from a map class');
        }
    }

    /**
     * @return void
     */
    public function testGetColumn()
    {
        try {
            $this->databaseMap->getColumn('foo.BAR');
            $this->fail('getColumn() throws an exception when called on column of an inexistent table');
        } catch (ColumnNotFoundException $e) {
            $this->assertTrue(true, 'getColumn() throws an exception when called on column of an inexistent table');
        }
        $tmap = $this->databaseMap->addTable('foo');
        try {
            $this->databaseMap->getColumn('foo.BAR');
            $this->fail('getColumn() throws an exception when called on an inexistent column of an existent table');
        } catch (ColumnNotFoundException $e) {
            $this->assertTrue(true, 'getColumn() throws an exception when called on an inexistent column of an existent table');
        }
        $column = $tmap->addColumn('BAR', 'Bar', 'INTEGER');
        $this->assertEquals($column, $this->databaseMap->getColumn('foo.BAR'), 'getColumn() returns a ColumnMap object based on a fully qualified name');
    }

    /**
     * @return void
     */
    public function testGetTableByPhpName()
    {
        try {
            $this->databaseMap->getTableByPhpName('Foo1');
            $this->fail('getTableByPhpName() throws an exception when called on an inexistent table');
        } catch (TableNotFoundException $e) {
            $this->assertTrue(true, 'getTableByPhpName() throws an exception when called on an inexistent table');
        }
        $tmap = $this->databaseMap->addTable('foo1');
        try {
            $this->databaseMap->getTableByPhpName('Foo1');
            $this->fail('getTableByPhpName() throws an exception when called on a table with no phpName');
        } catch (TableNotFoundException $e) {
            $this->assertTrue(true, 'getTableByPhpName() throws an exception when called on a table with no phpName');
        }
        $tmap2 = new TableMap('foo2');
        $tmap2->setClassName('Foo2');
        $this->databaseMap->addTableObject($tmap2);
        $this->assertEquals($tmap2, $this->databaseMap->getTableByPhpName('Foo2'), 'getTableByPhpName() returns tableMap when phpName was set by way of TableMap::setPhpName()');
    }

    /**
     * @return void
     */
    public function testGetTableByPhpNameNotLoaded()
    {
        $this->assertEquals('book', Propel::getServiceContainer()->getDatabaseMap('bookstore')->getTableByPhpName('Propel\Tests\Bookstore\Book')->getName(), 'getTableByPhpName() can autoload a TableMap when the class is generated and autoloaded');
    }
}

class TestDatabaseBuilder
{
    protected static $dmap = null;

    protected static $tmap = null;

    public static function getDmap()
    {
        if ((self::$dmap === null)) {
            self::$dmap = new DatabaseMap('foodb');
        }

        return self::$dmap;
    }

    /**
     * @return void
     */
    public static function setTmap($tmap)
    {
        self::$tmap = $tmap;
    }

    public static function getTmap()
    {
        return self::$tmap;
    }
}

class BazTableMap extends TableMap
{
    /**
     * @return void
     */
    public function initialize()
    {
        $this->setName('baz');
        $this->setPhpName('Baz');
    }
}
