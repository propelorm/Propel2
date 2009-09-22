<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/TableMap.php';

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
  
  public function normalizeColName($name)
  {
    return parent::normalizeColName($name);
  }
}

/**
 * Test class for TableMap.
 *
 * @author     François Zaninotto
 * @version    $Id: TableMapTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class TableMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $databaseMap;

  protected function setUp()
  {
    parent::setUp();
    $this->databaseMap = new DatabaseMap('foodb');
    $this->tableName = 'foo';
    $this->tmap = new TestableTableMap($this->tableName, $this->databaseMap);
  }

  protected function tearDown()
  {
    // nothing to do for now
    parent::tearDown();
  }

  public function testConstructor()
  {
    $this->assertEquals($this->tableName, $this->tmap->getName(), 'constructor sets the table name');
    $this->assertEquals($this->databaseMap, $this->tmap->getDatabaseMap(), 'Constructor sets the database map');
    $this->assertEquals(array(), $this->tmap->getColumns(), 'A new table map has no columns');
  }
  
  public function testPhpName()
  {
    $this->assertNull($this->tmap->getPhpName(), 'phpName is empty until set');
    $this->tmap->setPhpName('FooBar');
    $this->assertEquals('FooBar', $this->tmap->getPhpName(), 'phpName is set by getPhpName()');
  }
  
  public function testClassName()
  {
    $this->assertNull($this->tmap->getClassName(), 'ClassName is empty until set');
    $this->tmap->setClassName('FooBarClass');
    $this->assertEquals('FooBarClass', $this->tmap->getClassName(), 'ClassName is set by setClassName()');
  }
  
  public function testPackage()
  {
    $this->assertNull($this->tmap->getPackage(), 'Package is empty until set');
    $this->tmap->setPackage('barr');
    $this->assertEquals('barr', $this->tmap->getPackage(), 'Package is set by setPackage()');
  }
  
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
  
  public function testGetColumn()
  {
    $column = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
    $this->assertEquals($column, $this->tmap->getColumn('BAR'), 'getColumn returns a ColumnMap according to a column mame');
    try
    {
      $this->tmap->getColumn('FOO');
      $this->fail('getColumn throws an exception when called on an inexistent column');
    } catch(PropelException $e) {}
    $this->assertEquals($column, $this->tmap->getColumn('foo.bar'), 'getColumn accepts a denormalized column name');
    try
    {
      $this->tmap->getColumn('foo.bar', false);
      $this->fail('getColumn accepts a $normalize parameter to skip name normalization');
    } catch(PropelException $e) {}
  }
  
  public function testGetColumns()
  {
    $this->assertEquals(array(), $this->tmap->getColumns(), 'getColumns returns an empty array when no columns were added');
    $column1 = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
    $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
    $this->assertEquals(array('BAR' => $column1, 'BAZ' => $column2), $this->tmap->getColumns(), 'getColumns returns the columns indexed by name');
  }
  
  public function testAddPrimaryKey()
  {
    $column1 = $this->tmap->addPrimaryKey('BAR', 'Bar', 'INTEGER');
    $this->assertTrue($column1->isPrimaryKey(), 'Columns added by way of addPrimaryKey() are primary keys');
    $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
    $this->assertFalse($column2->isPrimaryKey(), 'Columns added by way of addColumn() are not primary keys by default');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, null, true);
    $this->assertTrue($column3->isPrimaryKey(), 'Columns added by way of addColumn() can be defined as primary keys');
    $column4 = $this->tmap->addForeignKey('BAZZZ', 'Bazzz', 'INTEGER', 'Table1', 'column1');
    $this->assertFalse($column4->isPrimaryKey(), 'Columns added by way of addForeignKey() are not primary keys');
    $column5 = $this->tmap->addForeignPrimaryKey('BAZZZZ', 'Bazzzz', 'INTEGER', 'table1', 'column1');
    $this->assertTrue($column5->isPrimaryKey(), 'Columns added by way of addForeignPrimaryKey() are primary keys');
  }
  
  public function testGetPrimaryKeyColumns()
  {
    $this->assertEquals(array(), $this->tmap->getPrimaryKeyColumns(), 'getPrimaryKeyColumns() returns an empty array by default');
    $column1 = $this->tmap->addPrimaryKey('BAR', 'Bar', 'INTEGER');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, null, true);
    $expected = array($column1, $column3);
    $this->assertEquals($expected, $this->tmap->getPrimaryKeyColumns(), 'getPrimaryKeyColumns() returns an  array of the table primary keys');
  }
  
  public function testGetPrimaryKeys()
  {
    $this->assertEquals(array(), $this->tmap->getPrimaryKeys(), 'getPrimaryKeys() returns an empty array by default');
    $column1 = $this->tmap->addPrimaryKey('BAR', 'Bar', 'INTEGER');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, null, true);
    $expected = array('BAR' => $column1, 'BAZZ' => $column3);
    $this->assertEquals($expected, $this->tmap->getPrimaryKeys(), 'getPrimaryKeys() returns an array of the table primary keys');
  }
  
  public function testAddForeignKey()
  {
    $column1 = $this->tmap->addForeignKey('BAR', 'Bar', 'INTEGER', 'Table1', 'column1');
    $this->assertTrue($column1->isForeignKey(), 'Columns added by way of addForeignKey() are foreign keys');
    $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
    $this->assertFalse($column2->isForeignKey(), 'Columns added by way of addColumn() are not foreign keys by default');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, null, false, 'Table1', 'column1');
    $this->assertTrue($column3->isForeignKey(), 'Columns added by way of addColumn() can be defined as foreign keys');
    $column4 = $this->tmap->addPrimaryKey('BAZZZ', 'Bazzz', 'INTEGER');
    $this->assertFalse($column4->isForeignKey(), 'Columns added by way of addPrimaryKey() are not foreign keys');
    $column5 = $this->tmap->addForeignPrimaryKey('BAZZZZ', 'Bazzzz', 'INTEGER', 'table1', 'column1');
    $this->assertTrue($column5->isForeignKey(), 'Columns added by way of addForeignPrimaryKey() are foreign keys');
  }
  
  public function testGetForeignKeys()
  {
    $this->assertEquals(array(), $this->tmap->getForeignKeys(), 'getForeignKeys() returns an empty array by default');
    $column1 = $this->tmap->addForeignKey('BAR', 'Bar', 'INTEGER', 'Table1', 'column1');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, null, false, 'Table1', 'column1');
    $expected = array('BAR' => $column1, 'BAZZ' => $column3);
    $this->assertEquals($expected, $this->tmap->getForeignKeys(), 'getForeignKeys() returns an array of the table foreign keys');
  }
  
  public function testLazyLoadRelations()
  {
    try {
      $this->tmap->getRelation('Bar');
      $this->fail('getRelation() throws an exception when called on a table with no relation builder');    
    } catch (PropelException $e) {
      $this->assertTrue(true, 'getRelation() throws an exception when called on a table with no relation builder');
    }
    $this->tmap->setRelationsBuilder('get_class');
    $this->assertEquals(array(), $this->tmap->getRelations(), 'Adding a builder allows relations to be initialized');
    try {
      $this->tmap->getRelation('Bar');
      $this->fail('getRelation() throws an exception when called on an inexistent relation');    
    } catch (PropelException $e) {
      $this->assertTrue(true, 'getRelation() throws an exception when called on  an inexistent relation');
    }
    $foreigntmap = $this->databaseMap->addTable('bar');
    $foreigntmap->setPhpName('Bar');
    $rmap1 = $this->tmap->addRelation('Bar', 'Bar', RelationMap::MANY_TO_ONE);
    $rmap2 = $this->tmap->getRelation('Bar');
    $this->assertEquals($rmap1, $rmap2, 'getRelation() returns the relations set by setRelation()');
  }
  
  public function buildRelations($tmap)
  {
    $this->rmap1 = $tmap->addRelation('Bar', 'Bar', RelationMap::MANY_TO_ONE);
    $this->rmap2 = $tmap->addRelation('Bazz', 'Baz', RelationMap::ONE_TO_MANY);
  }
  
  public function testAddRelation()
  {
    $foreigntmap1 = $this->databaseMap->addTable('bar');
    $foreigntmap1->setPhpName('Bar');
    $foreigntmap2 = $this->databaseMap->addTable('baz');
    $foreigntmap2->setPhpName('Baz');
    $this->tmap->setRelationsBuilder(array($this, 'buildRelations'));
    $this->tmap->getRelations();
    // now on to the test
    $this->assertEquals($this->rmap1->getLocalTable(), $this->tmap, 'adding a relation with HAS_ONE sets the local table to the current table');    
    $this->assertEquals($this->rmap1->getForeignTable(), $foreigntmap1, 'adding a relation with HAS_ONE sets the foreign table according to the name given');
    $this->assertEquals(RelationMap::MANY_TO_ONE, $this->rmap1->getType(), 'adding a relation with HAS_ONE sets the foreign table type accordingly');

    $this->assertEquals($this->rmap2->getForeignTable(), $this->tmap, 'adding a relation with HAS_MANY sets the foreign table to the current table');    
    $this->assertEquals($this->rmap2->getLocalTable(), $foreigntmap2, 'adding a relation with HAS_MANY sets the local table according to the name given');
    $this->assertEquals(RelationMap::ONE_TO_MANY, $this->rmap2->getType(), 'adding a relation with HAS_MANY sets the foreign table type accordingly');
    
    $expectedRelations = array('Bar' => $this->rmap1, 'Bazz' => $this->rmap2);
    $this->assertEquals($expectedRelations, $this->tmap->getRelations(), 'getRelations() returns an associative array of all the relations');
  }

  // deprecated method
  public function testNormalizeColName()
  {
    $this->assertEquals('', $this->tmap->normalizeColName(''), 'normalizeColName returns an empty string when passed an empty string');
    $this->assertEquals('BAR', $this->tmap->normalizeColName('bar'), 'normalizeColName uppercases the input');
    $this->assertEquals('BAR_BAZ', $this->tmap->normalizeColName('bar_baz'), 'normalizeColName does not mind underscores');
    $this->assertEquals('BAR', $this->tmap->normalizeColName('FOO.BAR'), 'normalizeColName removes table prefix');
    $this->assertEquals('BAR', $this->tmap->normalizeColName('BAR'), 'normalizeColName leaves normalized column names unchanged');
    $this->assertEquals('BAR_BAZ', $this->tmap->normalizeColName('foo.bar_baz'), 'normalizeColName can do all the above at the same time');
  }
  
  // deprecated method
  public function testContainsColumn()
  {
    $this->assertFalse($this->tmap->containsColumn('BAR'), 'containsColumn returns false when the column is not in the table map');
    $column = $this->tmap->addColumn('BAR', 'Bar', 'INTEGER');
    $this->assertTrue($this->tmap->containsColumn('BAR'), 'containsColumn returns true when the column is in the table map');
    $this->assertTrue($this->tmap->containsColumn('foo.bar'), 'containsColumn accepts a denormalized column name');
    $this->assertFalse($this->tmap->containsColumn('foo.bar', false), 'containsColumn accepts a $normalize parameter to skip name normalization');
    $this->assertTrue($this->tmap->containsColumn('BAR', false), 'containsColumn accepts a $normalize parameter to skip name normalization');
    $this->assertTrue($this->tmap->containsColumn($column), 'containsColumn accepts a ColumnMap object as parameter');
  }
  
  // deprecated methods
  public function testPrefix()
  {
    $tmap = $this->tmap;
    $this->assertNull($tmap->getPrefix(), 'prefix is empty until set');
    $this->assertFalse($tmap->hasPrefix('barbaz'), 'hasPrefix returns false when prefix is not set');
    $this->tmap->setPrefix('bar');
    $this->assertEquals('bar', $tmap->getPrefix(), 'prefix is set by setPrefix()');
    $this->assertTrue($tmap->hasPrefix('barbaz'), 'hasPrefix returns true when prefix is set and found in string');
    $this->assertFalse($tmap->hasPrefix('baz'), 'hasPrefix returns false when prefix is set and not found in string');
    $this->assertFalse($tmap->hasPrefix('bazbar'), 'hasPrefix returns false when prefix is set and not found anywhere in string'); 
    $this->assertEquals('baz', $tmap->removePrefix('barbaz'), 'removePrefix returns string without prefix if found at the beginning');
    $this->assertEquals('bazbaz', $tmap->removePrefix('bazbaz'), 'removePrefix returns original string when prefix is not found');
    $this->assertEquals('bazbar', $tmap->removePrefix('bazbar'), 'removePrefix returns original string when prefix is not found at the beginning');
  }
}
