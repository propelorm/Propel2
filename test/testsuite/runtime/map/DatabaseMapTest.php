<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/ColumnMap.php';
include_once 'propel/map/TableMap.php';
include_once 'propel/map/DatabaseMap.php';

class FakeTableBuilder implements MapBuilder
{
  public function doBuild()
  {
  }
  
  public function isBuilt()
  {
    return true;
  }

  public function getDatabaseMap()
  {
  }
}

class TestDatabaseBuilder
{
  protected static $dmap = null;
  protected static $tmap = null;
  public static function getDmap()
  {
    if (is_null(self::$dmap)) {
        self::$dmap = new DatabaseMap('foodb');
    }
    return self::$dmap;
  }
  public static function setTmap($tmap)
  {
    self::$tmap = $tmap;
  }
  public static function getTmap()
  {
    return self::$tmap;
  }
    
}

class TestTableBuilder implements MapBuilder
{ 
	private $dbMap;
	public function isBuilt()
	{
		return ($this->dbMap !== null);
	}
	public function getDatabaseMap()
	{
		return $this->dbMap;
	}
	
	public function doBuild()
  {
    $this->dbMap = TestDatabaseBuilder::getDmap();

		$tMap = $this->dbMap->addTable('foo2');
		TestDatabaseBuilder::setTmap($tMap);
  }
}

/**
 * Test class for DatabaseMap.
 *
 * @author     François Zaninotto
 * @version    $Id: ColumnMapTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class DatabaseMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $databaseMap;

  protected function setUp()
  {
    parent::setUp();
    $this->databaseName = 'foodb';
    $this->databaseMap = TestDatabaseBuilder::getDmap();
  }

  protected function tearDown()
  {
    // nothing to do for now
    parent::tearDown();
  }

  public function testConstructor()
  {
    $this->assertEquals($this->databaseName, $this->databaseMap->getName(), 'constructor sets the table name');
  }

  public function testAddTable()
  {
    $this->assertFalse($this->databaseMap->hasTable('foo'), 'tables are empty by default');
    try
    {
      $this->databaseMap->getTable('foo');
      $this->fail('getTable() throws an exception when called on an inexistent table');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getTable() throws an exception when called on an inexistent table');
    }
    $tmap = $this->databaseMap->addTable('foo');
    $this->assertFalse($this->databaseMap->hasTable('foo'), 'hasTable() returns false as long as the table has no builder');
    $this->databaseMap->addTableBuilder('foo', new FakeTableBuilder());
    $this->assertTrue($this->databaseMap->hasTable('foo'), 'hasTable() returns true when the table has a builder');
    $this->assertEquals($tmap, $this->databaseMap->getTable('foo'), 'getTable() returns a table by name when it was built');
  }
  
  public function testAddTableBuilder()
  {
    $this->assertFalse($this->databaseMap->hasTable('foo2'), 'tables are empty by default');
    try
    {
      $this->databaseMap->getTable('foo2');
      $this->fail('getTable() throws an exception when called on a table with no builder');
    } catch(PropelException $e) {
      $this->assertTrue(true, 'getTable() throws an exception when called on a table with no builder');
    }
    $tmap = $this->databaseMap->addTableBuilder('foo2', new TestTableBuilder());
    $this->assertTrue($this->databaseMap->hasTable('foo2'), 'hasTable() returns true as long as the table has a builder');
    $this->assertEquals($this->databaseMap->getTable('foo2'), TestDatabaseBuilder::getTmap(), 'getTable() builds the table if it was not built before');
  }
}
