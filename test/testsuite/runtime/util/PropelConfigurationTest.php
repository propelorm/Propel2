<?php
/*
 *  $Id: $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'propel/util/PropelConfiguration.php';

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
