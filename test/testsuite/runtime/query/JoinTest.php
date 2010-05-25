<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'tools/helpers/BaseTestCase.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/query/Criteria.php';

set_include_path(get_include_path() . PATH_SEPARATOR . "fixtures/bookstore/build/classes");		
Propel::init('fixtures/bookstore/build/conf/bookstore-conf.php');

/**
 * Test class for Join.
 *
 * @author     François Zaninotto
 * @version    $Id$
 * @package    runtime.query
 */
class JoinTest extends BaseTestCase 
{
  /**
   * DB adapter saved for later.
   *
   * @var        DBAdapter
   */
  private $savedAdapter;

  protected function setUp()
  {
    parent::setUp();
    $this->savedAdapter = Propel::getDB(null);
    Propel::setDB(null, new DBSQLite());
  }

  protected function tearDown()
  {
    Propel::setDB(null, $this->savedAdapter);
    parent::tearDown();
  }

  public function testEmptyConditions()
  {
    $j = new Join();
    $this->assertEquals(array(), $j->getConditions());
  }
  
  public function testAddCondition()
  {
    $j = new Join();
    $j->addCondition('foo', 'bar');
    $this->assertEquals('=', $j->getOperator());
    $this->assertEquals('foo', $j->getLeftColumn());
    $this->assertEquals('bar', $j->getRightColumn());
  }
  
  public function testGetConditions()
  {
    $j = new Join();
    $j->addCondition('foo', 'bar');
    $expect = array(array('left' => 'foo', 'operator' => '=', 'right' => 'bar'));
    $this->assertEquals($expect, $j->getConditions());
  }
  
  public function testAddConditionWithOperator()
  {
    $j = new Join();
    $j->addCondition('foo', 'bar', '>=');
    $expect = array(array('left' => 'foo', 'operator' => '>=', 'right' => 'bar'));
    $this->assertEquals($expect, $j->getConditions());
  }
  
  public function testAddConditions()
  {
    $j = new Join();
    $j->addCondition('foo', 'bar');
    $j->addCondition('baz', 'bal');
    $expect = array(
      array('left' => 'foo', 'operator' => '=', 'right' => 'bar'),
      array('left' => 'baz', 'operator' => '=', 'right' => 'bal')
    );
    $this->assertEquals(array('=', '='), $j->getOperators());
    $this->assertEquals(array('foo', 'baz'), $j->getLeftColumns());
    $this->assertEquals(array('bar', 'bal'), $j->getRightColumns());
    $this->assertEquals($expect, $j->getConditions());
  }
  
  public function testEmptyJoinType()
  {
    $j = new Join();
    $this->assertNull($j->getJoinType());
  }
  
  public function testSetJoinType()
  {
    $j = new Join();
    $j->setJoinType('foo');
    $this->assertEquals('foo', $j->getJoinType());
  }
  
  public function testSimpleConstructor()
  {
    $j = new Join('foo', 'bar', 'LEFT JOIN');
    $expect = array(array('left' => 'foo', 'operator' => '=', 'right' => 'bar'));
    $this->assertEquals($expect, $j->getConditions());
    $this->assertEquals('LEFT JOIN', $j->getJoinType());
  }
  
  public function testCompositeeConstructor()
  {
    $j = new Join(array('foo1', 'foo2'), array('bar1', 'bar2'), 'LEFT JOIN');
    $expect = array(
      array('left' => 'foo1', 'operator' => '=', 'right' => 'bar1'),
      array('left' => 'foo2', 'operator' => '=', 'right' => 'bar2')
    );
    $this->assertEquals($expect, $j->getConditions());
    $this->assertEquals('LEFT JOIN', $j->getJoinType());
  }
  
  public function testCountConditions()
  {
    $j = new Join();
    $this->assertEquals(0, $j->countConditions());
    $j->addCondition('foo', 'bar');
    $this->assertEquals(1, $j->countConditions());
    $j->addCondition('foo1', 'bar1');
    $this->assertEquals(2, $j->countConditions());

  }

}
