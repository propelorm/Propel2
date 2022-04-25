<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Runtime\ServiceContainer;

use Exception;
use Monolog\Logger;
use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\ServiceContainerInterface;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use Propel\Runtime\Util\Profiler;
use Propel\Tests\Helpers\BaseTestCase;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Connection\ConnectionFactory;

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
        $this->sc->initDatabaseMaps([]);
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
    public function testCheckInvalidVersion(): void
    {
        $this->expectException(PropelException::class);
        $this->sc->checkVersion(-1);
    }

    /**
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testCheckValidVersion(): void
    {
        try {
            $this->sc->checkVersion(StandardServiceContainer::CONFIGURATION_VERSION);
        } catch (PropelException $e) {
            $this->fail('The current configuration version should pass a check, but failed with message: ' . $e->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testUninitializedDatabaseMapThrowsException(): void
    {
        $sc = new StandardServiceContainer();
        $this->expectException(PropelException::class);
        $this->expectExceptionMessage('Database map was not initialized. Please check the database loader script included by your conf.');

        $sc->getDatabaseMap('a database name');
    }

    /**
     * @return void
     */
    public function testInitializedDatabaseMapContainsTableMaps(): void
    {
        $sc = new StandardServiceContainer();
        $dbName = 'myBookstore';
        $dbMap = [
            $dbName => [
                '\\Propel\\Tests\\Bookstore\\Map\\AuthorTableMap',
                '\\Propel\\Tests\\Bookstore\\Map\\BookTableMap',
            ],
        ];
        $this->runInitDatabaseMapsOnContainer($sc, $dbMap);
        $dbMap = $sc->getDatabaseMap($dbName);

        $this->assertTrue($dbMap->hasTable('author'));
        $this->assertTrue($dbMap->hasTable('book'));
    }

    /**
     * @return void
     */
    public function testInitializingdDatabaseMapsAccumulates(): void
    {
        $sc = new StandardServiceContainer();
        $dbName = 'myBookstore';
        $dbMap1 = [
            $dbName => [
                '\\Propel\\Tests\\Bookstore\\Map\\AuthorTableMap',
                '\\Propel\\Tests\\Bookstore\\Map\\BookTableMap',
            ],
        ];

        $dbMap2 = [
            $dbName => [
                '\\Propel\\Tests\\Bookstore\\Map\\AuthorTableMap',
                '\\Propel\\Tests\\Bookstore\\Map\\EssayTableMap',
            ],
        ];

        $this->runInitDatabaseMapsOnContainer($sc, $dbMap1);
        $this->runInitDatabaseMapsOnContainer($sc, $dbMap2);
        $dbMap = $sc->getDatabaseMap($dbName);

        $this->assertTrue($dbMap->hasTable('author'));
        $this->assertTrue($dbMap->hasTable('book'));
        $this->assertTrue($dbMap->hasTable('essay'));
    }

    /**
     * @param \Propel\Runtime\ServiceContainer\StandardServiceContainer $sc
     * @param String[][] $dbMap
     *
     * @return void
     */
    private function runInitDatabaseMapsOnContainer(StandardServiceContainer $sc, array $dbMap): void
    {
        $initialServiceContainer = Propel::getServiceContainer();
        try {
            Propel::setServiceContainer($sc);
            $sc->initDatabaseMaps($dbMap);
        } finally {
            Propel::setServiceContainer($initialServiceContainer);
        }
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
        $manager1 = new ConnectionManagerSingle('foo1');
        $manager2 = new ConnectionManagerSingle('foo2');
        $this->sc->setConnectionManager($manager1);
        $this->sc->setConnectionManager($manager2);
        $this->assertSame($manager1, $this->sc->getConnectionManager('foo1'));
        $this->assertSame($manager2, $this->sc->getConnectionManager('foo2'));
    }

    /**
     * @return void
     */
    public function testSetConnectionManagerClosesExistingConnectionManagerForTheSameDatasource()
    {
        $manager = new TestableConnectionManagerSingle('foo');
        $manager->setConnection(new PdoConnection('sqlite::memory:'));
        $this->assertNotNull($manager->getReadConnection());
        $this->sc->setConnectionManager($manager);
        $this->sc->setConnectionManager(new ConnectionManagerSingle('foo'));
        try {
            $manager->getReadConnection();
        } catch (\Error $e) {
            $this->assertTrue(true, 'Throws error');
        }
    }

    /**
     * @return void
     */
    public function testGetConnectionManagersReturnsConnectionManagersForAllDatasources()
    {
        $manager1 = new ConnectionManagerSingle('foo1');
        $manager2 = new ConnectionManagerSingle('foo2');
        $this->sc->setConnectionManager($manager1);
        $this->sc->setConnectionManager($manager2);
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
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('single'));
        $this->assertTrue($this->sc->hasConnectionManager('single'));
        $this->assertFalse($this->sc->hasConnectionManager('single_not_existing'));
    }

    /**
     * @return void
     */
    public function testCloseConnectionsClosesConnectionsOnAllConnectionManagers()
    {
        $manager1 = new TestableConnectionManagerSingle('foo1');
        $manager1->setConnection(new PdoConnection('sqlite::memory:'));
        $manager2 = new TestableConnectionManagerSingle('foo2');
        $manager2->setConnection(new PdoConnection('sqlite::memory:'));
        $this->sc->setConnectionManager($manager1);
        $this->sc->setConnectionManager($manager2);
        $this->sc->closeConnections();

        try {
            $manager1->getReadConnection();
        } catch (\Error $e) {
            $this->assertTrue(true, 'Throws error');
        }

        try {
            $manager2->getReadConnection();
        } catch (\Error $e) {
            $this->assertTrue(true, 'Throws error');
        }
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsWriteConnectionByDefault()
    {
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('foo'));
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection('foo')->getName());
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsWriteConnectionWhenAskedExplicitly()
    {
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('foo'));
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection('foo', ServiceContainerInterface::CONNECTION_WRITE)->getName());
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsReadConnectionWhenAskedExplicitly()
    {
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('foo'));
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', $this->sc->getConnection('foo', ServiceContainerInterface::CONNECTION_READ)->getName());
    }

    /**
     * @return void
     */
    public function testGetConnectionReturnsConnectionForDefaultDatasourceByDefault()
    {
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('default'));
        $this->sc->setAdapter('default', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getConnection()->getName());
    }

    /**
     * @return void
     */
    public function testGetWriteConnectionReturnsWriteConnectionForAGivenDatasource()
    {
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('foo'));
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('write', $this->sc->getWriteConnection('foo')->getName());
    }

    /**
     * @return void
     */
    public function testGetReadConnectionReturnsReadConnectionForAGivenDatasource()
    {
        $this->sc->setConnectionManager(new TestableConnectionManagerSingle('foo'));
        $this->sc->setAdapter('foo', new SqliteAdapter());
        $this->assertEquals('read', $this->sc->getReadConnection('foo')->getName());
    }

    /**
     * @return void
     */
    public function testSetConnectionAddsAConnectionManagerSingle()
    {
        $this->sc->setConnection('foo', new PdoConnection('sqlite::memory:'));
        $this->assertInstanceOf(ConnectionManagerSingle::class, $this->sc->getConnectionManager('foo'));
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
            'slowThreshold' => 0.1,
        ];
        $this->assertEquals($expected, $config);
    }

    /**
     * @return void
     */
    public function testGetProfilerUsesProfilerConfigurationWhenGiven()
    {
        $this->sc->setProfilerConfiguration([
            'slowThreshold' => 22,
        ]);
        $config = $this->sc->getProfiler()->getConfiguration();
        $this->assertEquals(22, $config['slowThreshold']);
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
    
    
    /**
     * @dataProvider debugModeDataProvider
     */
    public function testUseDebugMode(bool $useDebug, ?bool $useProfiler, bool $expectedConnectionMode, bool $expectedProfilerMode)
    {
        $this->sc->useDebugMode($useDebug, $useProfiler);
        $this->assertSame($expectedConnectionMode, ConnectionWrapper::$useDebugMode);
        $this->assertSame($expectedProfilerMode, ConnectionFactory::$useProfilerConnection);
    }
        
    public function debugModeDataProvider(): array
    {
        // use debug , use profile, connection debug, connection profile
        return [
            [false, null, false, false],
            [false, false, false, false],
            [false, true, false, true],
            [true, null, true, true],
            [true, false, true, false],
            [true, false, true, false],
            
        ];
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
    public function getWriteConnection(?AdapterInterface $adapter = null): ConnectionInterface
    {
        $connection = $this->connection ?: $adapter->getConnection(['dsn' => 'sqlite::memory:']);
        $connection->setName('write');

        return $connection;
    }

    public function getReadConnection(?AdapterInterface $adapter = null): ConnectionInterface
    {
        $connection = $this->connection ?: $adapter->getConnection(['dsn' => 'sqlite::memory:']);
        $connection->setName('read');

        return $connection;
    }
}
