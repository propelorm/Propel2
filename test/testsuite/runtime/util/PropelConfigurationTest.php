<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once dirname(__FILE__) . '/../../../../runtime/lib/config/PropelConfiguration.php';

/**
 * Test for PropelConfiguration class
 *
 * @author     Francois Zaninotto
 * @package    runtime.util
 */
class PropelConfigurationTest extends PHPUnit_Framework_TestCase
{
	protected $testArray = array('foo' => array('fooo' => 'bar', 'fi' => array('fooooo' => 'bara')), 'baz' => 'bar2');
	
  public function testConstruct()
  {
  	$conf = new PropelConfiguration($this->testArray);
  	$this->assertEquals($this->testArray, $conf->getParameters(), 'constructor sets values from an associative array');
  }
  
  public function testGetParameters()
  {
  	$conf = new PropelConfiguration($this->testArray);
  	$expected = array('foo.fooo' => 'bar', 'foo.fi.fooooo' => 'bara', 'baz' => 'bar2');
  	$this->assertEquals($expected, $conf->getParameters(PropelConfiguration::TYPE_ARRAY_FLAT), 'getParameters can return a flat array');  	
  }

  public function testGetParameter()
  {
  	$conf = new PropelConfiguration($this->testArray);
  	$this->assertEquals('bar', $conf->getParameter('foo.fooo'), 'getParameter accepts a flat key');  	
  	$this->assertEquals('bara', $conf->getParameter('foo.fi.fooooo'), 'getParameter accepts a flat key');  	
  	$this->assertEquals('bar2', $conf->getParameter('baz'), 'getParameter accepts a flat key');  	
  }

	public function testGetParameterDefault()
	{
  	$conf = new PropelConfiguration($this->testArray);
  	$this->assertEquals('bar', $conf->getParameter('foo.fooo'), 'getParameter accepts a flat key');  	
		$this->assertEquals('', $conf->getParameter('foo.fooo2'), 'getParameter returns null for nonexistent keys');  	
		$this->assertEquals('babar', $conf->getParameter('foo.fooo3', 'babar'), 'getParameter accepts a default value');  	
	} 
	 
  public function testSetParameter()
  {
  	$conf = new PropelConfiguration(array());
  	$conf->setParameter('foo.fooo', 'bar');
  	$conf->setParameter('foo.fi.fooooo', 'bara');
  	$conf->setParameter('baz', 'bar2');
  	$this->assertEquals($this->testArray, $conf->getParameters(), 'setParameter accepts a flat array');
  }
  
  public function testArrayAccess()
  {
    $conf = new PropelConfiguration($this->testArray);
    $expected = array('fooo' => 'bar', 'fi' => array('fooooo' => 'bara'));
  	$this->assertEquals($expected, $conf['foo'], 'PropelConfiguration implements ArrayAccess for OffsetGet');
  	$this->assertEquals('bar', $conf['foo']['fooo'], 'Array access allows deep access');
  }
}
