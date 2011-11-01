<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\config;

use Propel\Runtime\Config\Configuration;
use Propel\Runtime\Config\ConfigurationIterator;

/**
 * Test for Configuration class
 *
 * @author     Francois Zaninotto
 * @package    runtime.config
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    static public function configurationProvider()
    {
        $initialConf = array(
            'foo' => 'bar0',
            'foo1' => array(
                'foo2' => 'bar1',
            ),
            'a' => array(
                'b' => array(
                    'c' => 'bar2',
                )
            )
        );
        $c = new Configuration($initialConf);

        return array(array($c));
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testConstructorArrayAccess($c)
    {
        $this->assertEquals('bar0', $c['foo']);
        $this->assertEquals('bar1', $c['foo1']['foo2']);
        $this->assertEquals('bar2', $c['a']['b']['c']);
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testConstructorFlastAccess($c)
    {
        $this->assertEquals('bar0', $c->getParameter('foo'));
        $this->assertEquals('bar1', $c->getParameter('foo1.foo2'));
        $this->assertEquals('bar2', $c->getParameter('a.b.c'));
    }

    public function testArrayAccess()
    {
        $c = new Configuration();
        $this->assertFalse(isset($c[1]));
        $c[1] = 2;
        $this->assertTrue(isset($c[1]));
        $this->assertEquals(2, $c[1]);
        unset($c[1]);
        $this->assertFalse(isset($c[1]));
    }

    public function testNullValue()
    {
        $c = new Configuration();
        $c[1] = null;
        $this->assertTrue(isset($c[1]));
    }

    public function testSetParameterSimpleKey()
    {
        $c = new Configuration();
        $c->setParameter('foo', 'bar');
        $this->assertEquals('bar', $c['foo']);
        $this->assertEquals('bar', $c->getParameter('foo'));
    }

    public function testSetParameterSimpleKeyArrayValue()
    {
        $c = new Configuration();
        $c->setParameter('foo', array('bar1' => 'baz1'));
        $this->assertEquals(array('bar1' => 'baz1'), $c['foo']);
        $this->assertNull($c->getParameter('foo'));
        $this->assertEquals('baz1', $c->getParameter('foo.bar1'));
    }

    public function testSetParameterNamespacedKey()
    {
        $c = new Configuration();
        $c->setParameter('foo1.foo2', 'bar');
        $this->assertEquals('bar', $c['foo1']['foo2']);
        $this->assertEquals('bar', $c->getParameter('foo1.foo2'));
    }

    public function testSetParameterNamespacedKeyArrayValue()
    {
        $c = new Configuration();
        $c->setParameter('foo1.foo2', array('bar1' => 'baz1'));
        $this->assertEquals(array('bar1' => 'baz1'), $c['foo1']['foo2']);
        $this->assertNull($c->getParameter('foo1.foo2'));
        $this->assertEquals('baz1', $c->getParameter('foo1.foo2.bar1'));
    }

    public function testSetParameterMultiNamespacedKey()
    {
        $c = new Configuration();
        $c->setParameter('a.b.c', 'bar');
        $this->assertEquals('bar', $c['a']['b']['c']);
        $this->assertEquals('bar', $c->getParameter('a.b.c'));
    }

    public function testSetParameterMultiNamespacedKeyArrayValue()
    {
        $c = new Configuration();
        $c->setParameter('a.b.c', array('bar1' => 'baz1'));
        $this->assertEquals(array('bar1' => 'baz1'), $c['a']['b']['c']);
        $this->assertNull($c->getParameter('a.b.c'));
        $this->assertEquals('baz1', $c->getParameter('a.b.c.bar1'));
    }

    public function testGetParameterSimpleKey()
    {
        $c = new Configuration();
        $c['foo'] = 'bar';
        $this->assertEquals('bar', $c->getParameter('foo'));
    }

    public function testGetParameterSimpleKeyArrayValue()
    {
        $c = new Configuration();
        $c['foo'] = array('bar1' => 'baz1');
        $this->assertNull($c->getParameter('foo'));
        $this->assertEquals('baz1', $c->getParameter('foo.bar1'));
    }

    public function testGetParameterNamespacedKey()
    {
        $c = new Configuration();
        $c['foo1'] = array('foo2' => 'bar');
        $this->assertEquals('bar', $c->getParameter('foo1.foo2'));
    }

    public function testGetParameterNamespacedKeyArrayValue()
    {
        $c = new Configuration();
        $c['foo1'] = array('foo2' => array('bar1' => 'baz1'));
        $this->assertNull($c->getParameter('foo1.foo2'));
        $this->assertEquals('baz1', $c->getParameter('foo1.foo2.bar1'));
    }

    public function testGetParameterMultiNamespacedKey()
    {
        $c = new Configuration();
        $c['a'] = array('b' => array('c' => 'bar'));
        $this->assertEquals('bar', $c->getParameter('a.b.c'));
    }

    public function testGetParameterMultiNamespacedKeyArrayValue()
    {
        $c = new Configuration();
        $c['a'] = array('b' => array('c' => array('bar1' => 'baz1')));
        $this->assertNull($c->getParameter('a.b.c'));
        $this->assertEquals('baz1', $c->getParameter('a.b.c.bar1'));
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testGetParameters($c)
    {
        $expected = array(
            'foo' => 'bar0',
            'foo1' => array(
                'foo2' => 'bar1',
            ),
            'a' => array(
                'b' => array(
                    'c' => 'bar2',
                )
            )
        );
        $this->assertEquals($expected, $c->getParameters());
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testGetFlattenedParameters($c)
    {
        $expected = array(
            'foo'       => 'bar0',
            'foo1.foo2' => 'bar1',
            'a.b.c'     => 'bar2',
        );
        $this->assertEquals($expected, $c->getFlattenedParameters());
    }
}
