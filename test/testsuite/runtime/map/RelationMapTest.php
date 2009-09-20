<?php

require_once 'PHPUnit/Framework/TestCase.php';
include_once 'propel/map/RelationMap.php';
include_once 'propel/map/TableMap.php';

/**
 * Test class for RelationMap.
 *
 * @author     François Zaninotto
 * @version    $Id: RelationMapTest.php 1121 2009-09-14 17:20:11Z francois $
 * @package    runtime.map
 */
class RelationMapTest extends PHPUnit_Framework_TestCase 
{ 
  protected $databaseMap, $relationName, $rmap;

  protected function setUp()
  {
    parent::setUp();
    $this->databaseMap = new DatabaseMap('foodb');
    $this->relationName = 'foo';
    $this->rmap = new RelationMap($this->relationName);
  }

  public function testConstructor()
  {
    $this->assertEquals($this->relationName, $this->rmap->getName(), 'constructor sets the relation name');
  }
  
  public function testLocalTable()
  {
    $this->assertNull($this->rmap->getLocalTable(), 'A new relation has no local table');
    $tmap1 = new TableMap('foo', $this->databaseMap);
    $this->rmap->setLocalTable($tmap1);
    $this->assertEquals($tmap1, $this->rmap->getLocalTable(), 'The local table is set by setLocalTable()');
  }

  public function testForeignTable()
  {
    $this->assertNull($this->rmap->getForeignTable(), 'A new relation has no foreign table');
    $tmap2 = new TableMap('bar', $this->databaseMap);
    $this->rmap->setForeignTable($tmap2);
    $this->assertEquals($tmap2, $this->rmap->getForeignTable(), 'The foreign table is set by setForeignTable()');
  }
  
  public function testType()
  {
    $this->assertNull($this->rmap->getType(), 'A new relation has no type');
    $this->rmap->setType(RelationMap::HAS_MANY);
    $this->assertEquals(RelationMap::HAS_MANY, $this->rmap->getType(), 'The type is set by setType()');
  }
}
