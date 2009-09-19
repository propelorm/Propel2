<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/TableMap.php';

/**
 * Test class for TableMap.
 *
 * @author     François Zaninotto
 * @version    $Id: JoinTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class ColumnMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $savedAdapter;
  
  protected $databaseMap;

  protected function setUp()
  {
    parent::setUp();
    $this->tmap = new TableMap('foo', new DatabaseMap('foodb'));
    $this->columnName = 'bar';
    $this->cmap = new ColumnMap($this->columnName, $this->tmap);
  }

  protected function tearDown()
  {
    // nothing to do for now
    parent::tearDown();
  }

  public function testConstructor()
  {
    $this->assertEquals($this->columnName, $this->cmap->getName(), 'constructor sets the column name');
    $this->assertEquals($this->tmap, $this->cmap->getTable(), 'Constructor sets the table map');
    $this->assertNull($this->cmap->getType(), 'A new column map has no type');
  }
  
  public function testPhpName()
  {
    $this->assertNull($this->cmap->getPhpName(), 'phpName is empty until set');
    $this->cmap->setPhpName('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getPhpName(), 'phpName is set by setPhpName()');
  }
  
  public function testTypee()
  {
    $this->assertNull($this->cmap->getType(), 'type is empty until set');
    $this->cmap->setType('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getType(), 'type is set by setType()');
  }
  /*
  public function testPackage()
  {
    $this->assertNull($this->tmap->getPackage(), 'Package is empty until set');
    $this->tmap->setPackage('barr');
    $this->assertEquals('barr', $this->tmap->getPackage(), 'Package is set by setPackage()');
  }
  
  public function testNormalizeColumnName()
  {
    $this->assertEquals('', TableMap::normalizeColumnName(''), 'normalizeColumnName() returns an empty string when passed an empty string');
    $this->assertEquals('BAR', TableMap::normalizeColumnName('bar'), 'normalizeColumnName() uppercases the input');
    $this->assertEquals('BAR_BAZ', TableMap::normalizeColumnName('bar_baz'), 'normalizeColumnName() does not mind underscores');
    $this->assertEquals('BAR', TableMap::normalizeColumnName('FOO.BAR'), 'normalizeColumnName() removes table prefix');
    $this->assertEquals('BAR', TableMap::normalizeColumnName('BAR'), 'normalizeColumnName() leaves normalized column names unchanged');
    $this->assertEquals('BAR_BAZ', TableMap::normalizeColumnName('foo.bar_baz'), 'normalizeColumnName() can do all the above at the same time');
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
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, true);
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
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, true);
    $expected = array($column1, $column3);
    $this->assertEquals($expected, $this->tmap->getPrimaryKeyColumns(), 'getPrimaryKeyColumns() returns an  array of the table primary keys');
  }
  
  public function testGetPrimaryKeys()
  {
    $this->assertEquals(array(), $this->tmap->getPrimaryKeys(), 'getPrimaryKeys() returns an empty array by default');
    $column1 = $this->tmap->addPrimaryKey('BAR', 'Bar', 'INTEGER');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, true);
    $expected = array('BAR' => $column1, 'BAZZ' => $column3);
    $this->assertEquals($expected, $this->tmap->getPrimaryKeys(), 'getPrimaryKeys() returns an array of the table primary keys');
  }
  
  public function testAddForeignKey()
  {
    $column1 = $this->tmap->addForeignKey('BAR', 'Bar', 'INTEGER', 'Table1', 'column1');
    $this->assertTrue($column1->isForeignKey(), 'Columns added by way of addForeignKey() are foreign keys');
    $column2 = $this->tmap->addColumn('BAZ', 'Baz', 'INTEGER');
    $this->assertFalse($column2->isForeignKey(), 'Columns added by way of addColumn() are not foreign keys by default');
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, false, 'Table1', 'column1');
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
    $column3 = $this->tmap->addColumn('BAZZ', 'Bazz', 'INTEGER', null, null, false, 'Table1', 'column1');
    $expected = array('BAR' => $column1, 'BAZZ' => $column3);
    $this->assertEquals($expected, $this->tmap->getForeignKeys(), 'getForeignKeys() returns an array of the table foreign keys');
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
  */
}
