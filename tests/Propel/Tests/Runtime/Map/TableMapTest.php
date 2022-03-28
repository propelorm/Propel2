<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\Map;

use Exception;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Map\ColumnMap;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Map\Exception\ColumnNotFoundException;
use Propel\Runtime\Map\Exception\RelationNotFoundException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Tests\TestCase;

/**
 * Test class for TableMap.
 *
 * @author François Zaninotto
 * @version $Id$
 */
class TableMapTest extends TestCase
{
    protected $databaseMap;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseMap = new DatabaseMap('foodb');
        $this->tableName = 'foo';
        $this->tmap = new TableMap($this->tableName, $this->databaseMap);
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
        $this->assertEquals([], $this->tmap->getColumns(), 'A new table map has no columns');
        $this->assertEquals($this->tableName, $this->tmap->getName(), 'constructor can set the table name');
        $this->assertEquals($this->databaseMap, $this->tmap->getDatabaseMap(), 'Constructor can set the database map');
        try {
            $tmap = new TableMap();
            $this->assertTrue(true, 'A table map can be instantiated with no parameters');
        } catch (Exception $e) {
            $this->fail('A table map can be instantiated with no parameters');
        }
    }

    /**
     * @return void
     */
    public function testProperties()
    {
        $tmap = new TableMap();
        $properties = ['name', 'phpName', 'className', 'package'];
        foreach ($properties as $property) {
            $getter = 'get' . ucfirst($property);
            $setter = 'set' . ucfirst($property);
            $this->assertNull($tmap->$getter(), "A new relation has no $property");
            $tmap->$setter('foo_value');
            $this->assertEquals('foo_value', $tmap->$getter(), "The $property is set by setType()");
        }
    }

    /**
     * @return void
     */
    public function testHasColumn()
    {
        $this->assertFalse($this->tmap->hasColumn('BAR'), 'hascolumn() returns false when the column is not in the table map');
        $column = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
        $this->assertTrue($this->tmap->hasColumn('BAR'), 'hascolumn() returns true when the column is in the table map');
        $this->assertTrue($this->tmap->hasColumn('foo.bar'), 'hascolumn() accepts a denormalized column name');
        $this->assertFalse($this->tmap->hasColumn('foo.bar', false), 'hascolumn() accepts a $normalize parameter to skip name normalization');
        $this->assertTrue($this->tmap->hasColumn('BAR', false), 'hascolumn() accepts a $normalize parameter to skip name normalization');
        $this->assertTrue($this->tmap->hasColumn($column), 'hascolumn() accepts a ColumnMap object as parameter');
    }

    /**
     * @return void
     */
    public function testGetColumn()
    {
        $column = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
        $this->assertEquals($column, $this->tmap->getColumn('BAR'), 'getColumn returns a ColumnMap according to a column name');
        try {
            $this->tmap->getColumn('FOO');
            $this->fail('getColumn throws an exception when called on an inexistent column');
        } catch (ColumnNotFoundException $e) {
        }
            $this->assertEquals($column, $this->tmap->getColumn('foo.bar'), 'getColumn accepts a denormalized column name');
        try {
            $this->tmap->getColumn('foo.bar', false);
            $this->fail('getColumn accepts a $normalize parameter to skip name normalization');
        } catch (ColumnNotFoundException $e) {
        }
    }

    /**
     * @return void
     */
    public function testGetColumnByPhpName()
    {
        $column = $this->tmap->addColumn('BAR_BAZ', 'BarBaz', 'INTEGER');
        $this->assertEquals($column, $this->tmap->getColumnByPhpName('BarBaz'), 'getColumnByPhpName() returns a ColumnMap according to a column phpName');
        try {
            $this->tmap->getColumn('Foo');
            $this->fail('getColumnByPhpName() throws an exception when called on an inexistent column');
        } catch (ColumnNotFoundException $e) {
        }
    }

    /**
     * @return void
     */
    public function testGetColumns()
    {
        $this->assertEquals([], $this->tmap->getColumns(), 'getColumns returns an empty array when no columns were added');
        $column1 = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
        $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
        $this->assertEquals(['BAR' => $column1, 'BAZ' => $column2], $this->tmap->getColumns(), 'getColumns returns the columns indexed by name');
    }

    /**
     * @return void
     */
    public function testFindColumnsByName()
    {
        $this->assertNull($this->tmap->findColumnByName('LeName'), 'findColumnByName() should return null on empty map');
        $column = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
        $this->assertEquals($column, $this->tmap->findColumnByName('BAR'), 'findColumnByName() should find column if regular name matches');
        $this->assertEquals($column, $this->tmap->findColumnByName('Bar'), 'findColumnByName() should find column if phpName matches');
        $this->assertEquals($column, $this->tmap->findColumnByName('table.bar'), 'findColumnByName() should try normalizing input name');
    }

    /**
     * @return void
     */
    public function testAddPrimaryKey()
    {
        $column1 = $this->tmap->addPrimaryKey('BAR', 'Bar', 'INTEGER');
        $this->assertTrue($column1->isPrimaryKey(), 'Columns added by way of addPrimaryKey() are primary keys');
        $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
        $this->assertFalse($column2->isPrimaryKey(), 'Columns added by way of addColumn() are not primary keys by default');
        $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', false, null, null, true);
        $this->assertTrue($column3->isPrimaryKey(), 'Columns added by way of addColumn() can be defined as primary keys');
        $column4 = $this->tmap->addForeignKey('BAZZZ', 'Bazzz', 'INTEGER', 'Table1', 'column1');
        $this->assertFalse($column4->isPrimaryKey(), 'Columns added by way of addForeignKey() are not primary keys');
        $column5 = $this->tmap->addForeignPrimaryKey('BAZZZZ', 'Bazzzz', 'INTEGER', 'table1', 'column1');
        $this->assertTrue($column5->isPrimaryKey(), 'Columns added by way of addForeignPrimaryKey() are primary keys');
    }

    /**
     * @return void
     */
    public function testGetPrimaryKeys()
    {
        $this->assertEquals([], $this->tmap->getPrimaryKeys(), 'getPrimaryKeys() returns an empty array by default');
        $column1 = $this->tmap->addPrimaryKey('BAR', 'Bar', 'INTEGER');
        $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', false, null, null, true);
        $expected = ['BAR' => $column1, 'BAZZ' => $column3];
        $this->assertEquals($expected, $this->tmap->getPrimaryKeys(), 'getPrimaryKeys() returns an array of the table primary keys');
    }

    /**
     * @return void
     */
    public function testAddForeignKey()
    {
        $column1 = $this->tmap->addForeignKey('BAR', 'Bar', 'INTEGER', 'Table1', 'column1');
        $this->assertTrue($column1->isForeignKey(), 'Columns added by way of addForeignKey() are foreign keys');
        $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
        $this->assertFalse($column2->isForeignKey(), 'Columns added by way of addColumn() are not foreign keys by default');
        $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', false, null, null, false, 'Table1', 'column1');
        $this->assertTrue($column3->isForeignKey(), 'Columns added by way of addColumn() can be defined as foreign keys');
        $column4 = $this->tmap->addPrimaryKey('BAZZZ', 'Bazzz', 'INTEGER');
        $this->assertFalse($column4->isForeignKey(), 'Columns added by way of addPrimaryKey() are not foreign keys');
        $column5 = $this->tmap->addForeignPrimaryKey('BAZZZZ', 'Bazzzz', 'INTEGER', 'table1', 'column1');
        $this->assertTrue($column5->isForeignKey(), 'Columns added by way of addForeignPrimaryKey() are foreign keys');
    }

    /**
     * @return void
     */
    public function testGetForeignKeys()
    {
        $this->assertEquals([], $this->tmap->getForeignKeys(), 'getForeignKeys() returns an empty array by default');
        $column1 = $this->tmap->addForeignKey('BAR', 'Bar', 'INTEGER', 'Table1', 'column1');
        $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', false, null, null, false, 'Table1', 'column1');
        $expected = ['BAR' => $column1, 'BAZZ' => $column3];
        $this->assertEquals($expected, $this->tmap->getForeignKeys(), 'getForeignKeys() returns an array of the table foreign keys');
    }

    /**
     * @return void
     */
    public function testLoadWrongRelations()
    {
        $this->expectException(RelationNotFoundException::class);

        $this->tmap->getRelation('Bar');
    }

    /**
     * @return void
     */
    public function testLazyLoadRelations()
    {
        $foreigntmap = new BarTableMap();
        $this->databaseMap->addTableObject($foreigntmap);
        $localtmap = new FooTableMap();
        $this->databaseMap->addTableObject($localtmap);
        $rmap = $localtmap->getRelation('Bar');
        $this->assertEquals($rmap, $localtmap->rmap, 'getRelation() returns the relations lazy loaded by buildRelations()');
    }

    /**
     * @return void
     */
    public function testAddRelation()
    {
        $foreigntmap1 = new TableMap('bar');
        $foreigntmap1->setClassName('Bar');
        $this->databaseMap->addTableObject($foreigntmap1);
        $foreigntmap2 = new TableMap('baz');
        $foreigntmap2->setClassName('Baz');
        $this->databaseMap->addTableObject($foreigntmap2);
        $this->rmap1 = $this->tmap->addRelation('Bar', 'Bar', RelationMap::MANY_TO_ONE);
        $this->rmap2 = $this->tmap->addRelation('Bazz', 'Baz', RelationMap::ONE_TO_MANY);
        $this->tmap->getRelations();
        // now on to the test
        $this->assertEquals($this->rmap1->getLocalTable(), $this->tmap, 'adding a relation with HAS_ONE sets the local table to the current table');
        $this->assertEquals($this->rmap1->getForeignTable(), $foreigntmap1, 'adding a relation with HAS_ONE sets the foreign table according to the name given');
        $this->assertEquals(RelationMap::MANY_TO_ONE, $this->rmap1->getType(), 'adding a relation with HAS_ONE sets the foreign table type accordingly');

        $this->assertEquals($this->rmap2->getForeignTable(), $this->tmap, 'adding a relation with HAS_MANY sets the foreign table to the current table');
        $this->assertEquals($this->rmap2->getLocalTable(), $foreigntmap2, 'adding a relation with HAS_MANY sets the local table according to the name given');
        $this->assertEquals(RelationMap::ONE_TO_MANY, $this->rmap2->getType(), 'adding a relation with HAS_MANY sets the foreign table type accordingly');

        $expectedRelations = ['Bar' => $this->rmap1, 'Bazz' => $this->rmap2];
        $this->assertEquals($expectedRelations, $this->tmap->getRelations(), 'getRelations() returns an associative array of all the relations');
    }

    /**
     * @return void
     */
    public function testPrimaryStringAddColumn()
    {
        $this->assertFalse($this->tmap->hasPrimaryStringColumn(), 'hasPrimaryStringColumn() returns false while none set.');
        $this->assertNull($this->tmap->getPrimaryStringColumn(), 'getPrimaryStringColumn() returns null while none set.');

        $column = $this->tmap->addColumn('FOO', 'Foo', 'VARCHAR');
        $this->assertFalse($this->tmap->hasPrimaryStringColumn(), 'hasPrimaryStringColumn() returns false when no pkStr column is set.');
        $this->assertNull($this->tmap->getPrimaryStringColumn(), 'getPrimaryStringColumn() returns null when no pkStr column is set.');

        $column = $this->tmap->addColumn('PKSTR', 'pkStr', 'VARCHAR');
        $column->setPrimaryString(true);
        $this->assertTrue($this->tmap->hasPrimaryStringColumn(), 'hasPrimaryStringColumn() returns true after adding pkStr column.');
        $this->assertEquals($column, $this->tmap->getPrimaryStringColumn(), 'getPrimaryStringColumn() returns correct column.');
    }

    /**
     * @return void
     */
    public function testPrimaryStringAddConfiguredColumn()
    {
        $this->assertFalse($this->tmap->hasPrimaryStringColumn(), 'hasPrimaryStringColumn() returns false while none set.');

        $column = new ColumnMap('BAR', $this->tmap, 'Bar', 'VARCHAR');
        $column->setPrimaryString(true);
        $this->tmap->addConfiguredColumn($column);

        $this->assertTrue($this->tmap->hasPrimaryStringColumn(), 'hasPrimaryStringColumn() returns true after adding pkStr column.');
        $this->assertEquals($column, $this->tmap->getPrimaryStringColumn(), 'getPrimaryStringColumn() returns correct column.');
    }

    /**
     * @return void
     */
    public function testGetCollectionClassNameReturnsObjectCollection()
    {
        $this->assertEquals(ObjectCollection::class, $this->tmap->getCollectionClassName());
    }

    /**
     * @return void
     */
    public function testGetCollectionClassNameReturnsCustomCollection()
    {
        $classWithCollection = __NAMESPACE__ . '\ExtendingTest';
        $this->tmap->setClassName($classWithCollection);
        $this->assertEquals(ExtendingTestCollection::class, $this->tmap->getCollectionClassName());
    }

    /**
     * @return void
     */
    public function testGetCollectionClassNameReturnsOnlyCollections()
    {
        $classWithUnrelatedCollection = __NAMESPACE__ . '\NonExtendingTest';
        $this->tmap->setClassName($classWithUnrelatedCollection);
        $this->assertEquals(ObjectCollection::class, $this->tmap->getCollectionClassName());
    }
}

class ExtendingTestCollection extends ObjectCollection
{
}

class NonExtendingTestCollection
{
}

class TestableTableMap extends TableMap
{
    public function hasPrefix($data)
    {
        return parent::hasPrefix($data);
    }

    public function removePrefix($data)
    {
        return parent::removePrefix($data);
    }
}

class FooTableMap extends TableMap
{
    public $rmap;

    /**
     * @return void
     */
    public function buildRelations(): void
    {
        $this->rmap = $this->addRelation('Bar', 'Bar', RelationMap::MANY_TO_ONE);
    }
}

class BarTableMap extends TableMap
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->setName('bar');
        $this->setClassName('Bar');
    }
}
