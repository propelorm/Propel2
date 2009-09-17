<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'propel/Propel.php';
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
 * @version    $Id: JoinTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class TableMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $savedAdapter;
  
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

  public function testPrefix()
  {
    // note: the prefix features appear nowhere in ¨Propel as of Propel 1.3
    // and are marked as deprecated in Propel 1.4
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
  
  public function testNormalizeColName()
  {
    $this->assertEquals('', $this->tmap->normalizeColName(''), 'normalizeColName returns an empty string when passed an empty string');
    $this->assertEquals('BAR', $this->tmap->normalizeColName('bar'), 'normalizeColName uppercases the input');
    $this->assertEquals('BAR_BAZ', $this->tmap->normalizeColName('bar_baz'), 'normalizeColName does not mind underscores');
    $this->assertEquals('BAR', $this->tmap->normalizeColName('FOO.BAR'), 'normalizeColName removes table prefix');
    $this->assertEquals('BAR', $this->tmap->normalizeColName('BAR'), 'normalizeColName leaves normalized column names unchanged');
    $this->assertEquals('BAR_BAZ', $this->tmap->normalizeColName('foo.bar_baz'), 'normalizeColName can do all the above at the same time');
  }
  
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
}
