<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ServiceContainer;

use Monolog\Logger;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Runtime\Util\Profiler;
use Propel\Tests\Helpers\BaseTestCase;

class StandardServiceContainerTest extends BaseTestCase
{
    /**
     * @var \Propel\Runtime\ServiceContainer\StandardServiceContainer
     */
    protected $sc;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sc = new StandardServiceContainer();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->sc = null;
    }

    /**
     * @return void
     */
    public function testDefaultDatasourceIsDefault()
    {
        $defaultDatasource = $this->sc->getDefaultDatasource();
        $this->assertEquals('default', $defaultDatasource);
    }

    /**
     * @return void
     */
    public function testSetDefaultDatasourceUpdatesDefaultDatasource()
    {
        $this->sc->setDefaultDatasource('bookstore');
        $defaultDatasource = $this->sc->getDefaultDatasource();
        $this->assertEquals('bookstore', $defaultDatasource);
    }

    /**
     * @return void
     */
    public function testGetAdapterClassUsesDefaultDatasource()
    {
        $this->sc->setAdapterClasses(['default' => 'bar1', 'foo' => 'bar2']);
        $this->assertEquals('bar1', $this->sc->getAdapterClass());
        $this->sc->setDefaultDatasource('foo');
        $this->assertEquals('bar2', $this->sc->getAdapterClass());
    }

    /**
     * @return void
     */
    public function testSetAdapterClassSetsTheAdapterClassForAGivenDatasource()
    {
        $this->sc->setAdapterClass('foo', 'bar');
        $this->assertEquals('bar', $this->sc->getAdapterClass('foo'));
    }

    /**
     * @return void
     */
    public function testSetAdapterClassesSetsAdapterClassForAllDatasources()
    {
        $this->sc->setAdapterClasses(['foo1' => 'bar1', 'foo2' => 'bar2']);
        $this->assertEquals('bar1', $this->sc->getAdapterClass('foo1'));
        $this->assertEquals('bar2', $this->sc->getAdapterClass('foo2'));
    }

    /**
     * @return void
     */
    public function testSetAdapterClassesRemovesExistingAdapterClassesForAllDatasources()
    {
        $sc = new TestableServiceContainer();
        $sc->setAdapterClass('foo', 'bar');
        $sc->setAdapterClasses(['foo1' => 'bar1', 'foo2' => 'bar2']);
        $this->assertEquals(['foo1' => 'bar1', 'foo2' => 'bar2'], $sc->adapterClasses);
    }

    /**
     * @return void
     */
    public function testSetAdapterClassAllowsToReplaceExistingAdapter()
    {
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->sc->setAdapterClass('foo', '\Propel\Runtime\Adapter\Pdo\MysqlAdapter');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapter('foo'));
    }

    /**
     * @return void
     */
    public function getAdapterReturnsSetAdapter()
    {
        $adapter = new SqliteAdapter();
        $adapter->foo = 'bar';
        $this->sc->setAdapter('foo', $adapter);
        $this->assertSame($adapter, $this->sc->getAdapter('foo'));
    }

    /**
     * @return void
     */
    public function getAdapterCreatesAdapterBasedOnAdapterClass()
    {
        $this->sc->setAdapterClass('foo', '\Propel\Runtime\Adapter\Pdo\MysqlAdapter');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapter('foo'));
    }

    /**
     * @return void
     */
    public function testGetAdapterUsesDefaultDatasource()
    {
        $this->sc->setAdapterClasses([
            'default' => '\Propel\Runtime\Adapter\Pdo\SqliteAdapter',
            'foo' => '\Propel\Runtime\Adapter\Pdo\MysqlAdapter']);
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\SqliteAdapter', $this->sc->getAdapter());
        $this->sc->setDefaultDatasource('foo');
        $this->assertInstanceof('\Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapter());
    }

    /**
     * @return void
     */
    public function testSetAdapterUpdatesAdapterClass()
    {
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\SqliteAdapter', $this->sc->getAdapterClass('foo'));
    }

    /**
     * @return void
     */
    public function testSetAdaptersSetsAllAdapters()
    {
        $this->sc->setAdapters([
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter(),
        ]);
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\SqliteAdapter', $this->sc->getAdapterClass('foo1'));
        $this->assertEquals('Propel\Runtime\Adapter\Pdo\MysqlAdapter', $this->sc->getAdapterClass('foo2'));
    }

    /**
     * @return void
     */
    public function testSetAdaptersRemovesExistingAdaptersForAllDatasources($value = '')
    {
        $sc = new TestableServiceContainer();
        $sc->setAdapter('foo', new SqliteAdapter());
        $sc->setAdapters([
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter(),
        ]);
        $this->assertEquals([
            'foo1' => new SqliteAdapter(),
            'foo2' => new MysqlAdapter(),
        ], $sc->adapters);
    }

    /**
     * @return void
     */
    public function testCheckInvalidVersion()
    {
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->setMethods(['warning'])
            ->setConstructorArgs(['mylogger'])
            ->getMock();
        $logger->expects($this->once())->method('warning');

        $this->sc->setLogger('defaultLogger', $logger);
        $this->sc->checkVersion('1.0.0-invalid');
    }

    /**
     * @return void
     */
    public function testCheckValidVersion()
    {
        $logger = $this->getMockBuilder('Monolog\Logger')
            ->setMethods(['warning'])
            ->setConstructorArgs(['mylogger'])
            ->getMock();
        $logger->expects($this->never())->method('warning');

        $this->sc->setLogger('defaultLogger', $logger);
        $this->sc->checkVersion(Propel::VERSION);
    }

    /**
     * @return void
     */
    public function testGetDatabaseMapReturnsADatabaseMap()
    {
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertInstanceOf('\Propel\Runtime\Map\DatabaseMap', $map);
    }

    /**
     * @return void
     */
    public function testGetDatabaseMapReturnsADatabaseMapForTheGivenDatasource()
    {
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertEquals('foo', $map->getName());
    }

    /**
     * @return void
     */
    public function testGetDatabaseMapReturnsADatabaseMapForTheDefaultDatasourceByDefault()
    {
        $map = $this->sc->getDatabaseMap();
        $this->assertEquals('default', $map->getName());
        $this->sc->setDefaultDatasource('foo');
        $map = $this->sc->getDatabaseMap();
        $this->assertEquals('foo', $map->getName());
    }

    /**
     * @return void
     */
    public function testGetDatabaseMapReturnsAlwaysTheSameDatabaseMapForAGivenDatasource()
    {
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertSame($map, $this->sc->getDatabaseMap('foo'));
    }

    /**
     * @return void
     */
    public function testGetDatabaseMapReturnsDifferentDatabaseMapForTwoDatasources()
    {
        $map = $this->sc->getDatabaseMap('foo1');
        $this->assertNotSame($map, $this->sc->getDatabaseMap('foo2'));
    }

    /**
     * @return void
     */
    public function testGetDatabaseMapUsesDatabaseMapClass()
    {
        $this->sc->setDatabaseMapClass('Propel\Tests\Runtime\ServiceContainer\MyDatabaseMap');
        $map = $this->sc->getDatabaseMap('foo');
        $this->assertInstanceOf('Propel\Tests\Runtime\ServiceContainer\MyDatabaseMap', $map);
    }

    /**
     * @return void
     */
    public function testSetDatabaseMapSetsTheDatabaseMapForAGivenDatasource()
    {
        $map = new DatabaseMap('foo');
        $this->sc->setDatabaseMap('foo', $map);
        $this->assertSame($map, $this->sc->getDatabaseMap('foo'));
    }

    /**
     * @return void
     */
    public function testSetConnectionManagerSetsTheConnectionManagerForAGivenDatasource()
    {
        $manager1 = new ConnectionManagerSingle();
        $manager2 = new ConnectionManagerSingle();
        $this->sc->setConnectionManager('foo1', $manager1);
        $this->sc->setConnectionManager('foo2', $manager2);
        $this->assertSame($manager1, $this->sc->getConnectionManager('foo1'));
        $this->assertSame($manager2, $this->sc->getConnectionManager('foo2'));
    }

    /**
     * @return void
     */
    public function testSetConnectionManagerClosesExistingConnectionManagerForTheSameDatasource()
    {
        $manager = new TestableConnectionManagerSingle();
        $manager->setConnection(new PdoConnection('sqlite::memory:'));
        $this->assertNotNull($manager->connection);
        $this->sc->setConnectionManager('foo', $manager);
        $this->sc->setConnectionManager('foo', new ConnectionManagerSingle());
        $this->assertNull($manager->connection);
    }

    /**
     * @return void
     */
    public function testGetConnectionManagersReturnsConnectionManagersForAllDatasources()
    {
        $manager1 = new ConnectionManagerSingle();
        $manager2 = new ConnectionManagerSingle();
        $this->sc->setConnectionManager('foo1', $manager1);
        $this->sc->setConnectionManager('foo2', $manager2);
        $expected = [
            'foo1' => $manager1,
            'foo2' => $manager2,
        ];
        $this->assertEquals($expected, $this->sc->getConnectionManagers());
    }

    /**
     * @return void
     */
    public function testGetConnectionManagerWithUnknownDatasource()
    {
        $this->expectException(RuntimeException::class);

        $this->sc->getConnectionManager('unknown');
    }

    /**
     * @return void
     */
    public function testHasConnectionManager()
    {
        $this->sc->setConnectionManager('single', new TestableConnectionManagerSingle());
        $this->assertTrue($this->sc->hasConnectionManager('single'));
        $this->assertFalse($this->sc->hasConnectionManager('single_not_existing'));
    }

    /**
     * @return void
     */
    public function testCloseConnectionsClosesConnectionsOnAllConnectionManagers()
    {
        $manager1 = new TestableConnectionManagerSingle();
        $manager1->setConnection(new PdoConnection('sqlite::memory:'));
        $manager2 = new TestableConnectionManagerSingle();
        $manager2->setConnection(new PdoConnection('sqlite::memory:'));
        $this->sc->setConnectionManager('foo1', $manager1);
        $this->sc->setConnectionManager('foo2', $manager2);
        $this->sc->closeConnections();
        $this->assertNull($manager1->connection);
        $this->assertNull($manager2->connection);
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsWriteConnectionByDefault()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection('foo'));
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsWriteConnectionWhenAskedExplicitly()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection('foo', ServiceContainerInterface::CONNECTION_WRITE));
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsReadConnectionWhenAskedExplicitly()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', $this->sc->getConnection('foo', ServiceContainerInterface::CONNECTION_READ));
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsConnectionForDefaultDatasourceByDefault()
    {
        $this->sc->setConnectionManager('default', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('default', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionReturnsWriteConnectionForAGivenDatasource()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getWriteConnection('foo'));
    }

    /**
     * @return void
     */
    public function testGetReadConnectionReturnsReadConnectionForAGivenDatasource()
    {
        $this->sc->setConnectionManager('foo', new TestableConnectionManagerSingle());
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', $this->sc->getReadConnection('foo'));
    }

    /**
     * @return void
     */
    public function testSetConnectionAddsAConnectionManagerSingle()
    {
        $this->sc->setConnection('foo', new PdoConnection('sqlite::memory:'));
        $this->assertInstanceOf('Propel\Runtime\Connection\ConnectionManagerSingle', $this->sc->getConnectionManager('foo'));
    }

    /**
     * @return void
     */
    public function testSetConnectionAddsAConnectionWhichCanBeRetrievedByGetConnection()
    {
        $con = new PdoConnection('sqlite::memory:');
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->sc->setConnection('foo', $con);
        $this->assertSame($con, $this->sc->getConnection('foo'));
    }

    /**
     * @return void
     */
    public function testGetProfilerReturnsAProfiler()
    {
        $profiler = $this->sc->getProfiler();
        $this->assertInstanceOf('\Propel\Runtime\Util\Profiler', $profiler);
    }

    /**
     * @return void
     */
    public function testGetProfilerUsesProfilerClass()
    {
        $this->sc->setProfilerClass('\Propel\Tests\Runtime\ServiceContainer\MyProfiler');
        $profiler = $this->sc->getProfiler();
        $this->assertInstanceOf('\Propel\Tests\Runtime\ServiceContainer\MyProfiler', $profiler);
    }

    /**
     * @return void
     */
    public function testGetProfilerUsesDefaultConfiguration()
    {
        $config = $this->sc->getProfiler()->getConfiguration();
        $expected = [
            'details' => [
                'time' => [
                    'name' => 'Time',
                    'precision' => 3,
                    'pad' => 8,
                ],
                'mem' => [
                    'name' => 'Memory',
                    'precision' => 3,
                    'pad' => 8,
                ],
                'memDelta' => [
                    'name' => 'Memory Delta',
                    'precision' => 3,
                    'pad' => 8,
                ],
                'memPeak' => [
                    'name' => 'Memory Peak',
                    'precision' => 3,
                    'pad' => 8,
                ],
            ],
            'innerGlue' => ': ',
            'outerGlue' => ' | ',
            'slowTreshold' => 0.1,
        ];
        $this->assertEquals($expected, $config);
    }

    /**
     * @return void
     */
    public function testGetProfilerUsesProfilerConfigurationWhenGiven()
    {
        $this->sc->setProfilerConfiguration([
            'slowTreshold' => 22,
        ]);
        $config = $this->sc->getProfiler()->getConfiguration();
        $this->assertEquals(22, $config['slowTreshold']);
    }

    /**
     * @return void
     */
    public function testGetLoggerReturnsNullLoggerByDefault()
    {
        $this->assertInstanceOf('Psr\Log\NullLogger', $this->sc->getLogger());
    }

    /**
     * @return void
     */
    public function testGetLoggerReturnsNullLoggerForConnectionNamesByDefault()
    {
        $this->assertInstanceOf('Psr\Log\NullLogger', $this->sc->getLogger('book'));
    }

    /**
     * @return void
     */
    public function testGetLoggerReturnsLoggerPreviouslySet()
    {
        $logger = new Logger('book');
        $this->sc->setLogger('book', $logger);
        $this->assertEquals($logger, $this->sc->getLogger('book'));
    }

    /**
     * @return void
     */
    public function testGetLoggerWithNoArgumentReturnsDefaultLogger()
    {
        $logger = new Logger('defaultLogger');
        $this->sc->setLogger('defaultLogger', $logger);
        $this->assertEquals($logger, $this->sc->getLogger());
    }

    /**
     * @return void
     */
    public function testGetLoggerWithAnArgumentReturnsLoggerSetOnThatArgument()
    {
        $logger1 = new Logger('defaultLogger');
        $this->sc->setLogger('defaultLogger', $logger1);
        $logger2 = new Logger('book');
        $this->sc->setLogger('book', $logger2);
        $this->assertEquals($logger2, $this->sc->getLogger('book'));
    }

    /**
     * @return void
     */
    public function testGetLoggerWithAnArgumentReturnsDefaultLoggerOnFallback()
    {
        $logger = new Logger('defaultLogger');
        $this->sc->setLogger('defaultLogger', $logger);
        $this->assertEquals($logger, $this->sc->getLogger('book'));
    }

    /**
     * @return void
     */
    public function testGetLoggerLazyLoadsLoggerFromConfiguration()
    {
        $this->sc->setLoggerConfiguration('defaultLogger', [
            'type' => 'stream',
            'path' => 'php://stderr',
        ]);
        $logger = $this->sc->getLogger();
        $this->assertInstanceOf('\Monolog\Logger', $logger);
        $handler = $logger->popHandler();
        $this->assertInstanceOf('\Monolog\Handler\StreamHandler', $handler);
    }
}

class TestableServiceContainer extends StandardServiceContainer
{
    public $adapterClasses = [];

    public $adapters = [];
}

class MyDatabaseMap extends DatabaseMap
{
}

class MyProfiler extends Profiler
{
}

class TestableConnectionManagerSingle extends ConnectionManagerSingle
{
    public $connection;

    public function getWriteConnection(?AdapterInterface $adapter = null)
    {
        return 'write';
    }

    public function getReadConnection(?AdapterInterface $adapter = null)
    {
        return 'read';
    }
}
