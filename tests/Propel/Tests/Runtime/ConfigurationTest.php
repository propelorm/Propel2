<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\Configuration;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;

class ConfigurationTest extends BaseTestCase
{
    protected function tearDown()
    {
        // reset the singleton
        Configuration::setInstance(null);
    }

    public function testGetInstanceReturnsAConfigurationObject()
    {
        $this->assertInstanceOf('\Propel\Runtime\Configuration', Configuration::getInstance());
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance()
    {
        $conf1 = Configuration::getInstance();
        $conf1->foo = 'bar';
        $conf2 = Configuration::getInstance();
        $this->assertSame($conf1, $conf2);
    }

    public function testSetInstanceOverridesTheExistingInstance()
    {
        $obj = new \ArrayObject(array(1, 2, 3));
        Configuration::setInstance($obj);
        $this->assertSame($obj, Configuration::getInstance());
    }

    public function testSetInstanceNullResetsTheSingleton()
    {
        $obj = new \ArrayObject(array(1, 2, 3));
        Configuration::setInstance($obj);
        Configuration::setInstance(null);
        $this->assertInstanceOf('\Propel\Runtime\Configuration', Configuration::getInstance());
    }

    public function testDefaultDatasourceIsDefault()
    {
        $defaultDatasource = Configuration::getInstance()->getDefaultDatasource();
        $this->assertEquals('default', $defaultDatasource);
    }

    public function testSetDefaultDatasourceUpdatesDefaultDatasource()
    {
        Configuration::getInstance()->setDefaultDatasource('bookstore');
        $defaultDatasource = Configuration::getInstance()->getDefaultDatasource();
        $this->assertEquals('bookstore', $defaultDatasource);
    }

    public function testGetAdapterClassUsesDefaultDatasource()
    {
        Configuration::getInstance()->setAdapterClasses(array('default' => 'bar1', 'foo' => 'bar2'));
        $this->assertEquals('bar1', Configuration::getInstance()->getAdapterClass());
        Configuration::getInstance()->setDefaultDatasource('foo');
        $this->assertEquals('bar2', Configuration::getInstance()->getAdapterClass());
    }

    public function testSetAdapterClassSetsTheAdapterClassForAGivenDatasource()
    {
        Configuration::getInstance()->setAdapterClass('foo', 'bar');
        $this->assertEquals('bar', Configuration::getInstance()->getAdapterClass('foo'));
    }

    public function testSetAdapterClassesSetsAdapterClassForAllDatasources()
    {
        Configuration::getInstance()->setAdapterClasses(array('foo1' => 'bar1', 'foo2' => 'bar2'));
        $this->assertEquals('bar1', Configuration::getInstance()->getAdapterClass('foo1'));
        $this->assertEquals('bar2', Configuration::getInstance()->getAdapterClass('foo2'));
    }

    public function testSetAdapterClassesRemovesExistingAdapterClassesForAllDatasources()
    {
        $configuration = new TestableConfiguration;
        $configuration->setAdapterClass('foo', 'bar');
        $configuration->setAdapterClasses(array('foo1' => 'bar1', 'foo2' => 'bar2'));
        $this->assertEquals(array('foo1' => 'bar1', 'foo2' => 'bar2'), $configuration->adapterClasses);
    }

    public function testSetAdapterClassAllowsToReplaceExistingAdapter()
    {
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        Configuration::getInstance()->setAdapterClass('foo', '\Propel\Runtime\Adapter\Pdo\MysqlAdapter');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', Configuration::getInstance()->getAdapter('foo'));
    }

    public function getAdapterReturnsSetAdapter()
    {
        $adapter = new SqliteAdapter();
        $adapter->foo = 'bar';
        Configuration::getInstance()->setAdapter('foo', $adapter);
        $this->assertSame($adapter, Configuration::getInstance()->getAdapter('foo'));
    }

    public function getAdapterCreatesAdapterBasedOnAdapterClass()
    {
        Configuration::getInstance()->setAdapterClass('foo', '\Propel\Runtime\Adapter\Pdo\MysqlAdapter');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', Configuration::getInstance()->getAdapter('foo'));
    }

    public function testGetAdapterUsesDefaultDatasource()
    {
        Configuration::getInstance()->setAdapterClasses(array(
            'default' => '\Propel\Runtime\Adapter\Pdo\SqliteAdapter',
            'foo'     => '\Propel\Runtime\Adapter\Pdo\MysqlAdapter'));
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\SqliteAdapter', Configuration::getInstance()->getAdapter());
        Configuration::getInstance()->setDefaultDatasource('foo');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', Configuration::getInstance()->getAdapter());
    }

    public function testSetAdapterUpdatesAdapterClass()
    {
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\SqliteAdapter', Configuration::getInstance()->getAdapterClass('foo'));
    }

    public function testSetAdaptersSetsAllAdapters()
    {
        Configuration::getInstance()->setAdapters(array(
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter()
        ));
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\SqliteAdapter', Configuration::getInstance()->getAdapterClass('foo1'));
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\MysqlAdapter', Configuration::getInstance()->getAdapterClass('foo2'));
    }

    public function testSetAdaptersRemovesExistingAdaptersForAllDatasources($value='')
    {
        $configuration = new TestableConfiguration;
        $configuration->setAdapter('foo', new SqliteAdapter());
        $configuration->setAdapters(array(
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter()
        ));
        $this->assertEquals(array(
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter()
        ), $configuration->adapters);
    }
}

class TestableConfiguration extends Configuration
{
    public $adapterClasses = array();
    public $adapters = array();
}