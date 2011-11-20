<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Tests\Runtime\ServiceContainer;

use Propel\Tests\Helpers\BaseTestCase;

use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\ConnectionPdo;

class StandardServiceContainerTest extends BaseTestCase
{
    /**
     * @var Propel\Runtime\ServiceContainer\StandardServiceContainer
     */
    protected $sc;

    protected function setUp()
    {
        $this->sc = new StandardServiceContainer;
    }

    protected function tearDown()
    {
        $this->sc = null;
    }

    public function testDefaultDatasourceIsDefault()
    {
        $defaultDatasource = $this->sc->getDefaultDatasource();
        $this->assertEquals('default', $defaultDatasource);
    }

    public function testSetDefaultDatasourceUpdatesDefaultDatasource()
    {
        $this->sc->setDefaultDatasource('bookstore');
        $defaultDatasource = $this->sc->getDefaultDatasource();
        $this->assertEquals('bookstore', $defaultDatasource);
    }

    public function testGetAdapterClassUsesDefaultDatasource()
    {
        $this->sc->setAdapterClasses(array('default' => 'bar1', 'foo' => 'bar2'));
        $this->assertEquals('bar1', $this->sc->getAdapterClass());
        $this->sc->setDefaultDatasource('foo');
        $this->assertEquals('bar2', $this->sc->getAdapterClass());
    }

    public function testSetAdapterClassSetsTheAdapterClassForAGivenDatasource()
    {
        $this->sc->setAdapterClass('foo', 'bar');
        $this->assertEquals('bar', $this->sc->getAdapterClass('foo'));
    }

    public function testSetAdapterClassesSetsAdapterClassForAllDatasources()
    {
        $this->sc->setAdapterClasses(array('foo1' => 'bar1', 'foo2' => 'bar2'));
        $this->assertEquals('bar1', $this->sc->getAdapterClass('foo1'));
        $this->assertEquals('bar2', $this->sc->getAdapterClass('foo2'));
    }

    public function testSetAdapterClassesRemovesExistingAdapterClassesForAllDatasources()
    {
        $sc = new TestableServiceContainer;
        $sc->setAdapterClass('foo', 'bar');
        $sc->setAdapterClasses(array('foo1' => 'bar1', 'foo2' => 'bar2'));
        $this->assertEquals(array('foo1' => 'bar1', 'foo2' => 'bar2'), $sc->adapterClasses);
    }

    public function testSetAdapterClassAllowsToReplaceExistingAdapter()
    {
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->sc->setAdapterClass('foo', '\Propel\Runtime\Adapter\Pdo\MysqlAdapter');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapter('foo'));
    }

    public function getAdapterReturnsSetAdapter()
    {
        $adapter = new SqliteAdapter();
        $adapter->foo = 'bar';
        $this->sc->setAdapter('foo', $adapter);
        $this->assertSame($adapter, $this->sc->getAdapter('foo'));
    }

    public function getAdapterCreatesAdapterBasedOnAdapterClass()
    {
        $this->sc->setAdapterClass('foo', '\Propel\Runtime\Adapter\Pdo\MysqlAdapter');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapter('foo'));
    }

    public function testGetAdapterUsesDefaultDatasource()
    {
        $this->sc->setAdapterClasses(array(
            'default' => '\Propel\Runtime\Adapter\Pdo\SqliteAdapter',
            'foo'     => '\Propel\Runtime\Adapter\Pdo\MysqlAdapter'));
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\SqliteAdapter', $this->sc->getAdapter());
        $this->sc->setDefaultDatasource('foo');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapter());
    }

    public function testSetAdapterUpdatesAdapterClass()
    {
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\SqliteAdapter', $this->sc->getAdapterClass('foo'));
    }

    public function testSetAdaptersSetsAllAdapters()
    {
        $this->sc->setAdapters(array(
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter()
        ));
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\SqliteAdapter', $this->sc->getAdapterClass('foo1'));
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapterClass('foo2'));
    }

    public function testSetAdaptersRemovesExistingAdaptersForAllDatasources($value='')
    {
        $sc = new TestableServiceContainer;
        $sc->setAdapter('foo', new SqliteAdapter());
        $sc->setAdapters(array(
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter()
        ));
        $this->assertEquals(array(
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter()
        ), $sc->adapters);
    }

    public function testGetDatabaseMapReturnsADatabaseMap()
    {
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertInstanceOf('\Propel\Runtime\Map\DatabaseMap', $map);
    }

    public function testGetDatabaseMapReturnsADatabaseMapForTheGivenDatasource()
    {
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertEquals('foo', $map->getName());
    }

    public function testGetDatabaseMapReturnsADatabaseMapForTheDefaultDatasourceByDefault()
    {
        $map = $this->sc->getDatabaseMap();
        $this->assertEquals('default', $map->getName());
        $this->sc->setDefaultDatasource('foo');
        $map = $this->sc->getDatabaseMap();
        $this->assertEquals('foo', $map->getName());
    }

    public function testGetDatabaseMapReturnsAlwaysTheSameDatabaseMapForAGivenDatasource()
    {
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertSame($map, $this->sc->getDatabaseMap('foo'));
    }

    public function testGetDatabaseMapReturnsDifferentDatabaseMapForTwoDatasources()
    {
        $map = $this->sc->getDatabaseMap('foo1');
        $this->assertNotSame($map, $this->sc->getDatabaseMap('foo2'));
    }

    public function testGetDatabaseMapUsesDatabaseMapClass()
    {
        $this->sc->setDatabaseMapClass('Propel\Tests\Runtime\ServiceContainer\MyDatabaseMap');
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertInstanceOf('Propel\Tests\Runtime\ServiceContainer\MyDatabaseMap', $map);
    }

    public function testSetDatabaseMapSetsTheDatabaseMapForAGivenDatasource()
    {
        $map = new DatabaseMap('foo');
        $this->sc->setDatabaseMap('foo', $map);
        $this->assertSame($map, $this->sc->getDatabaseMap('foo'));
    }

    public function testSetConnectionManagerSetsTheConnectionManagerForAGivenDatasource()
    {
        $manager1 = new ConnectionManagerSingle();
        $manager2 = new ConnectionManagerSingle();
        $this->sc->setConnectionManager('foo1', $manager1);
        $this->sc->setConnectionManager('foo2', $manager2);
        $this->assertSame($manager1, $this->sc->getConnectionManager('foo1'));
        $this->assertSame($manager2, $this->sc->getConnectionManager('foo2'));
    }

    public function testSetConnectionManagerClosesExistingConnectionMAnagerForTheSameDatasource()
    {
        $manager = new TestableConnectionManagerSingle();
        $manager->setConnection(new ConnectionPdo('sqlite::memory:'));
        $this->assertNotNull($manager->connection);
        $this->sc->setConnectionManager('foo', $manager);
        $this->sc->setConnectionManager('foo', new ConnectionManagerSingle());
        $this->assertNull($manager->connection);
    }

    public function testGetConnectionManagersReturnsConnectionManagersForAllDatasources()
    {
        $manager1 = new ConnectionManagerSingle();
        $manager2 = new ConnectionManagerSingle();
        $this->sc->setConnectionManager('foo1', $manager1);
        $this->sc->setConnectionManager('foo2', $manager2);
        $expected = array(
            'foo1' => $manager1,
            'foo2' => $manager2
        );
        $this->assertEquals($expected, $this->sc->getConnectionManagers());
    }

    public function testCloseConnectionsClosesConnectionsOnAllConnectionManagers()
    {
        $manager1 = new TestableConnectionManagerSingle();
        $manager1->setConnection(new ConnectionPdo('sqlite::memory:'));
        $manager2 = new TestableConnectionManagerSingle();
        $manager2->setConnection(new ConnectionPdo('sqlite::memory:'));
        $this->sc->setConnectionManager('foo1', $manager1);
        $this->sc->setConnectionManager('foo2', $manager2);
        $this->sc->closeConnections();
        $this->assertNull($manager1->connection);
        $this->assertNull($manager2->connection);
    }

    public function testGetConnectionReturnsWriteConnectionByDefault()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection('foo'));
    }

    public function testGetConnectionReturnsWriteConnectionWhenAskedExplicitely()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection('foo', ServiceContainerInterface::CONNECTION_WRITE));
    }

    public function testGetConnectionReturnsReadConnectionWhenAskedExplicitely()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', $this->sc->getConnection('foo', ServiceContainerInterface::CONNECTION_READ));
    }

    public function testGetConnectionReturnsConnectionForDefaultDatasourceByDefault()
    {
        $this->sc->setConnectionManager('default', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('default', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection());
    }

    public function testGetWriteConnectionReturnsWriteConnectionForAGivenDatasource()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getWriteConnection('foo'));
    }

    public function testGetReadConnectionReturnsReadConnectionForAGivenDatasource()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', $this->sc->getReadConnection('foo'));
    }

    public function testSetConnectionAddsAConnectionManagerSingle()
    {
        $this->sc->setConnection('foo', new ConnectionPdo('sqlite::memory:'));
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionManagerSingle', $this->sc->getConnectionManager('foo'));
    }

    public function testSetConnectionAddsAConnectionWhichCanBeRetrivedByGetConnection()
    {
        $con = new ConnectionPdo('sqlite::memory:');
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->sc->setConnection('foo', $con);
        $this->assertSame($con, $this->sc->getConnection('foo'));
    }
}

class TestableServiceContainer extends StandardServiceContainer
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
