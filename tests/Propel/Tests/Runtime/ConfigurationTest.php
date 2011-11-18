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
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionPdo;

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

    public function testGetDatabaseMapReturnsADatabaseMap()
    {
        $map = Configuration::getInstance()->getDatabaseMap('foo');
        $this->assertInstanceOf('\Propel\Runtime\Map\DatabaseMap', $map);
    }

    public function testGetDatabaseMapReturnsADatabaseMapForTheGivenDatasource()
    {
        $map = Configuration::getInstance()->getDatabaseMap('foo');
        $this->assertEquals('foo', $map->getName());
    }

    public function testGetDatabaseMapReturnsADatabaseMapForTheDefaultDatasourceByDefault()
    {
        $map = Configuration::getInstance()->getDatabaseMap();
        $this->assertEquals('default', $map->getName());
        Configuration::getInstance()->setDefaultDatasource('foo');
        $map = Configuration::getInstance()->getDatabaseMap();
        $this->assertEquals('foo', $map->getName());
    }

    public function testGetDatabaseMapReturnsAlwaysTheSameDatabaseMapForAGivenDatasource()
    {
        $map = Configuration::getInstance()->getDatabaseMap('foo');
        $this->assertSame($map, Configuration::getInstance()->getDatabaseMap('foo'));
    }

    public function testGetDatabaseMapReturnsDifferentDatabaseMapForTwoDatasources()
    {
        $map = Configuration::getInstance()->getDatabaseMap('foo1');
        $this->assertNotSame($map, Configuration::getInstance()->getDatabaseMap('foo2'));
    }

    public function testGetDatabaseMapUsesDatabaseMapClass()
    {
        Configuration::getInstance()->setDatabaseMapClass('Propel\Tests\MyDatabaseMap');
        $map = Configuration::getInstance()->getDatabaseMap('foo');
        $this->assertInstanceOf('Propel\Tests\MyDatabaseMap', $map);
    }

    public function testSetDatabaseMapSetsTheDatabaseMapForAGivenDatasource()
    {
        $map = new DatabaseMap('foo');
        Configuration::getInstance()->setDatabaseMap('foo', $map);
        $this->assertSame($map, Configuration::getInstance()->getDatabaseMap('foo'));
    }

    public function testSetConnectionManagerSetsTheConnectionManagerForAGivenDatasource()
    {
        $manager1 = new ConnectionManagerSingle();
        $manager2 = new ConnectionManagerSingle();
        Configuration::getInstance()->setConnectionManager('foo1', $manager1);
        Configuration::getInstance()->setConnectionManager('foo2', $manager2);
        $this->assertSame($manager1, Configuration::getInstance()->getConnectionManager('foo1'));
        $this->assertSame($manager2, Configuration::getInstance()->getConnectionManager('foo2'));
    }

    public function testSetConnectionManagerClosesExistingConnectionMAnagerForTheSameDatasource()
    {
        $manager = new TestableConnectionManagerSingle();
        $manager->setConnection(new ConnectionPdo('sqlite::memory:'));
        $this->assertNotNull($manager->connection);
        Configuration::getInstance()->setConnectionManager('foo', $manager);
        Configuration::getInstance()->setConnectionManager('foo', new ConnectionManagerSingle());
        $this->assertNull($manager->connection);
    }

    public function testGetConnectionManagersReturnsConnectionManagersForAllDatasources()
    {
        $manager1 = new ConnectionManagerSingle();
        $manager2 = new ConnectionManagerSingle();
        Configuration::getInstance()->setConnectionManager('foo1', $manager1);
        Configuration::getInstance()->setConnectionManager('foo2', $manager2);
        $expected = array(
            'foo1' => $manager1,
            'foo2' => $manager2
        );
        $this->assertEquals($expected, Configuration::getInstance()->getConnectionManagers());
    }

    public function testCloseConnectionsClosesConnectionsOnAllConnectionManagers()
    {
        $manager1 = new TestableConnectionManagerSingle();
        $manager1->setConnection(new ConnectionPdo('sqlite::memory:'));
        $manager2 = new TestableConnectionManagerSingle();
        $manager2->setConnection(new ConnectionPdo('sqlite::memory:'));
        Configuration::getInstance()->setConnectionManager('foo1', $manager1);
        Configuration::getInstance()->setConnectionManager('foo2', $manager2);
        Configuration::getInstance()->closeConnections();
        $this->assertNull($manager1->connection);
        $this->assertNull($manager2->connection);
    }

    public function testGetConnectionReturnsWriteConnectionByDefault()
    {
        Configuration::getInstance()->setConnectionManager('foo', new TestableConnectionManagerSingle());
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', Configuration::getInstance()->getConnection('foo'));
    }

    public function testGetConnectionReturnsWriteConnectionWhenAskedExplicitely()
    {
        Configuration::getInstance()->setConnectionManager('foo', new TestableConnectionManagerSingle());
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', Configuration::getInstance()->getConnection('foo', Configuration::CONNECTION_WRITE));
    }

    public function testGetConnectionReturnsReadConnectionWhenAskedExplicitely()
    {
        Configuration::getInstance()->setConnectionManager('foo', new TestableConnectionManagerSingle());
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', Configuration::getInstance()->getConnection('foo', Configuration::CONNECTION_READ));
    }

    public function testGetConnectionReturnsConnectionForDefaultDatasourceByDefault()
    {
        Configuration::getInstance()->setConnectionManager('default', new TestableConnectionManagerSingle());
        Configuration::getInstance()->setAdapter('default', new SqliteAdapter());
        $this->assertEquals('write', Configuration::getInstance()->getConnection());
    }

    public function testGetWriteConnectionReturnsWriteConnectionForAGivenDatasource()
    {
        Configuration::getInstance()->setConnectionManager('foo', new TestableConnectionManagerSingle());
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', Configuration::getInstance()->getWriteConnection('foo'));
    }

    public function testGetReadConnectionReturnsReadConnectionForAGivenDatasource()
    {
        Configuration::getInstance()->setConnectionManager('foo', new TestableConnectionManagerSingle());
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', Configuration::getInstance()->getReadConnection('foo'));
    }

    public function testSetConnectionAddsAConnectionManagerSingle()
    {
        Configuration::getInstance()->setConnection('foo', new ConnectionPdo('sqlite::memory:'));
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionManagerSingle', Configuration::getInstance()->getConnectionManager('foo'));
    }

    public function testSetConnectionAddsAConnectionWhichCanBeRetrivedByGetConnection()
    {
        $con = new ConnectionPdo('sqlite::memory:');
        Configuration::getInstance()->setAdapter('foo', new SqliteAdapter());
        Configuration::getInstance()->setConnection('foo', $con);
        $this->assertSame($con, Configuration::getInstance()->getConnection('foo'));
    }
}

class TestableConfiguration extends Configuration
{
    public $adapterClasses = array();
    public $adapters = array();
}

class MyDatabaseMap extends DatabaseMap
{
}

class TestableConnectionManagerSingle extends ConnectionManagerSingle
{
    public $connection;

    public function getWriteConnection(AdapterInterface $adapter = null)
    {
        return 'write';
    }

    public function getReadConnection(AdapterInterface $adapter = null)
    {
        return 'read';
    }
}