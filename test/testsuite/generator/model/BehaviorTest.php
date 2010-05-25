<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'platform/MysqlPlatform.php';
require_once 'model/Behavior.php';
require_once 'model/Table.php';
require_once 'platform/MysqlPlatform.php';

/**
 * Tests for Behavior class
 *
 * @author     <a href="mailto:mpoeschl@marmot.at>Martin Poeschl</a>
 * @version    $Revision$
 * @package    generator.model
 */
class BehaviorTest extends PHPUnit_Framework_TestCase {

  private $xmlToAppData;
  private $appData;
  
  public function testSetupObject()
  {
    $b = new Behavior();
    $b->loadFromXML(array('name' => 'foo'));
    $this->assertEquals($b->getName(), 'foo', 'setupObject() sets the Behavior name from XML attributes');
  }
  
  public function testName()
  {
    $b = new Behavior();
    $this->assertNull($b->getName(), 'Behavior name is null by default');
    $b->setName('foo');
    $this->assertEquals($b->getName(), 'foo', 'setName() sets the name, and getName() gets it');
  }
  
  public function testTable()
  {
    $b = new Behavior();
    $this->assertNull($b->getTable(), 'Behavior Table is null by default');
    $t = new Table();
    $t->setName('fooTable');
    $b->setTable($t);
    $this->assertEquals($b->getTable(), $t, 'setTable() sets the name, and getTable() gets it');
  }
  
  public function testParameters()
  {
    $b = new Behavior();
    $this->assertEquals($b->getParameters(), array(), 'Behavior parameters is an empty array by default');
    $b->addParameter(array('name' => 'foo', 'value' => 'bar'));
    $this->assertEquals($b->getParameters(), array('foo' => 'bar'), 'addParameter() sets a parameter from an associative array');
    $b->addParameter(array('name' => 'foo2', 'value' => 'bar2'));
    $this->assertEquals($b->getParameters(), array('foo' => 'bar', 'foo2' => 'bar2'), 'addParameter() adds a parameter from an associative array');
    $b->addParameter(array('name' => 'foo', 'value' => 'bar3'));
    $this->assertEquals($b->getParameters(), array('foo' => 'bar3', 'foo2' => 'bar2'), 'addParameter() changes a parameter from an associative array');
    $this->assertEquals($b->getParameter('foo'), 'bar3', 'getParameter() retrieves a parameter value by name');   
    $b->setParameters(array('foo3' => 'bar3', 'foo4' => 'bar4'));
    $this->assertEquals($b->getParameters(), array('foo3' => 'bar3', 'foo4' => 'bar4'), 'setParameters() changes the whole parameter array');
  }
  
  /**
   * test if the tables get the package name from the properties file
   *
   */
  public function testXmlToAppData()
  {
  	include_once 'builder/util/XmlToAppData.php';
    $this->xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
    $this->appData = $this->xmlToAppData->parseFile('fixtures/bookstore/behavior-timestampable-schema.xml');
    $table = $this->appData->getDatabase("bookstore-behavior")->getTable('table1');
    $behaviors = $table->getBehaviors();
    $this->assertEquals(count($behaviors), 1, 'XmlToAppData ads as many behaviors as there are behaviors tags');
    $behavior = $table->getBehavior('timestampable');
    $this->assertEquals($behavior->getTable()->getName(), 'table1', 'XmlToAppData sets the behavior table correctly');
    $this->assertEquals($behavior->getParameters(), array('create_column' => 'created_on', 'update_column' => 'updated_on'), 'XmlToAppData sets the behavior parameters correctly');
  }
  
  public function testMofifyTable()
  {
  	set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
		Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');	
    $tmap = Propel::getDatabaseMap(Table2Peer::DATABASE_NAME)->getTable(Table2Peer::TABLE_NAME);
    $this->assertEquals(count($tmap->getColumns()), 4, 'A behavior can modify its table by implementing modifyTable()');
  }
  
  public function testModifyDatabase()
  {
  	set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
		require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';
		Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');	
    $tmap = Propel::getDatabaseMap(Table3Peer::DATABASE_NAME)->getTable(Table3Peer::TABLE_NAME);
    $this->assertTrue(array_key_exists('do_nothing', $tmap->getBehaviors()), 'A database behavior is automatically copied to all its table');
  }
  
  public function testGetColumnForParameter()
  {
  	$this->xmlToAppData = new XmlToAppData(new MysqlPlatform(), "defaultpackage", null);
    $this->appData = $this->xmlToAppData->parseFile('fixtures/bookstore/behavior-timestampable-schema.xml');
    
    $table = $this->appData->getDatabase("bookstore-behavior")->getTable('table1');
    $behavior = $table->getBehavior('timestampable');
    $this->assertEquals($table->getColumn('created_on'), $behavior->getColumnForParameter('create_column'), 'getColumnForParameter() returns the configured column for behavior based on a parameter name');

  }
}
