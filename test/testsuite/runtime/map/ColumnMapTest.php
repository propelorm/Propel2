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
  
  public function testType()
  {
    $this->assertNull($this->cmap->getType(), 'type is empty until set');
    $this->cmap->setType('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getType(), 'type is set by setType()');
  }
  
  public function tesSize()
  {
    $this->assertEquals(0, $this->cmap->getSize(), 'size is empty until set');
    $this->cmap->setSize(123);
    $this->assertEquals(123, $this->cmap->getSize(), 'size is set by setSize()');
  }
  
  public function testPrimaryKey()
  {
    $this->assertFalse($this->cmap->isPrimaryKey(), 'primaryKey is false by default');
    $this->cmap->setPrimaryKey(true);
    $this->assertTrue($this->cmap->isPrimaryKey(), 'primaryKey is set by setPrimaryKey()');
  }
  
  public function testNotNull()
  {
    $this->assertFalse($this->cmap->isNotNull(), 'notNull is false by default');
    $this->cmap->setNotNull(true);
    $this->assertTrue($this->cmap->isNotNull(), 'notNull is set by setPrimaryKey()');
  }
  
  public function testDefaultValue()
  {
    $this->assertNull($this->cmap->getDefaultValue(), 'defaultValue is empty until set');
    $this->cmap->setDefaultValue('FooBar');
    $this->assertEquals('FooBar', $this->cmap->getDefaultValue(), 'defaultValue is set by setDefaultValue()');
  }

}
