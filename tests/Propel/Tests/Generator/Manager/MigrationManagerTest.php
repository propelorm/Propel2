<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Manager;

use PDO;
use PDOException;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\DefaultPlatform;
use Propel\Tests\TestCase;

/**
 * @group database
 */
class MigrationManagerTest extends TestCase
{
    /**
     * @uses \Propel\Generator\Manager\MigrationManager::COL_VERSION
     *
     * @var string
     */
    private const COL_VERSION = 'version';

    /**
     * @uses \Propel\Generator\Manager\MigrationManager::COL_EXECUTION_DATETIME
     *
     * @var string
     */
    private const COL_EXECUTION_DATETIME = 'execution_datetime';

    /**
     * @param list<int> $migrationTimestamps
     *
     * @return \Propel\Generator\Manager\MigrationManager
     */
    private function createMigrationManager(array $migrationTimestamps): MigrationManager
    {
        $generatorConfig = new GeneratorConfig(__DIR__ . '/../../../../Fixtures/migration/');

        $connections = $generatorConfig->getBuildConnections();

        $migrationManager = $this->getMockBuilder(MigrationManager::class)
            ->setMethods(['getMigrationTimestamps'])
            ->getMock();
        $migrationManager->setGeneratorConfig($generatorConfig);
        $migrationManager->setConnections($connections);
        $migrationManager->setMigrationTable('migration');
        $migrationManager
            ->expects($this->any())
            ->method('getMigrationTimestamps')
            ->will($this->returnValue($migrationTimestamps));

        // make sure there is no other table named migration
        $migrationManager->getAdapterConnection('migration')->query('DROP TABLE IF EXISTS migration');

        return $migrationManager;
    }

    /**
     * @return void
     */
    public function testMigrationTableWillBeCreated(): void
    {
        $migrationManager = $this->createMigrationManager([]);
        $this->assertFalse($migrationManager->migrationTableExists('migration'));

        $migrationManager->createMigrationTable('migration');
        $this->assertTrue($migrationManager->migrationTableExists('migration'));
    }

    /**
     * @dataProvider getAllDatabaseVersionsDataProvider
     *
     * @param array<int, string|null> $migrationData
     * @param list<int> $expectedDatabaseVersions
     *
     * @return void
     */
    public function testGetAllDatabaseVersions(array $migrationData, array $expectedDatabaseVersions): void
    {
        $migrationManager = $this->createMigrationManager([]);
        $migrationManager->createMigrationTable('migration');

        $this->addMigrations($migrationManager, $migrationData);

        $this->assertSame($expectedDatabaseVersions, $migrationManager->getAllDatabaseVersions());
    }

    /**
     * @dataProvider getValidMigrationTimestampsDataProvider
     *
     * @param list<int> $localTimestamps
     * @param list<int> $databaseTimestamps
     * @param list<int> $expectedTimestamps
     * @param int|null $expectedVersion
     *
     * @return void
     */
    public function testGetValidMigrationTimestamps(
        array $localTimestamps,
        array $databaseTimestamps,
        array $expectedTimestamps,
        ?int $expectedVersion = null
    ): void {
        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        foreach ($databaseTimestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }

        $this->assertSame($expectedTimestamps, $migrationManager->getValidMigrationTimestamps($expectedVersion));
    }

    /**
     * @return void
     */
    public function testRemoveMigrationTimestamp(): void
    {
        $localTimestamps = [1, 2];
        $databaseTimestamps = [1, 2];

        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        foreach ($databaseTimestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }

        $this->assertEquals([], $migrationManager->getValidMigrationTimestamps());
        $migrationManager->removeMigrationTimestamp('migration', 2);
        $this->assertEquals([2], $migrationManager->getValidMigrationTimestamps());
    }

    /**
     * @dataProvider getAlreadyExecutedTimestampsDataProvider
     *
     * @param list<int> $localTimestamps
     * @param array<int, string|null> $databaseMigrationData
     * @param list<int> $expectedTimestamps
     * @param int|null $expectedVersion
     *
     * @return void
     */
    public function testGetAlreadyExecutedTimestamps(
        array $localTimestamps,
        array $databaseMigrationData,
        array $expectedTimestamps,
        ?int $expectedVersion = null
    ): void {
        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        $this->addMigrations($migrationManager, $databaseMigrationData);

        $this->assertSame($expectedTimestamps, $migrationManager->getAlreadyExecutedMigrationTimestamps($expectedVersion));
    }

    /**
     * @return void
     */
    public function testIsPending(): void
    {
        $localTimestamps = [1, 2];

        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);
        $this->assertTrue($migrationManager->hasPendingMigrations());

        $migrationManager->updateLatestMigrationTimestamp('migration', 2);
        $this->assertFalse($migrationManager->hasPendingMigrations());
    }

    /**
     * @return void
     */
    public function testGetOldestDatabaseVersion(): void
    {
        $timestamps = [1, 2];
        $migrationManager = $this->createMigrationManager($timestamps);
        $migrationManager->createMigrationTable('migration');

        $this->assertNull($migrationManager->getOldestDatabaseVersion());
        foreach ($timestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }
        $this->assertEquals(2, $migrationManager->getOldestDatabaseVersion());
    }

    /**
     * @return void
     */
    public function testGetFirstUpMigrationTimestamp(): void
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);

        $this->assertEquals(2, $migrationManager->getFirstUpMigrationTimestamp());
    }

    /**
     * @return void
     */
    public function testGetFirstDownMigrationTimestamp(): void
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);
        $migrationManager->updateLatestMigrationTimestamp('migration', 2);

        $this->assertEquals(2, $migrationManager->getFirstDownMigrationTimestamp());
    }

    /**
     * @return void
     */
    public function testGetCommentMigrationManager(): void
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);

        $body = $migrationManager->getMigrationClassBody(['foo' => ''], ['foo' => ''], 4, 'migration comment');

        $this->assertStringContainsString('public $comment = \'migration comment\';', $body);
    }

    /**
     * @return void
     */
    public function testBuildVariableNamesFromConnectionNames(): void
    {
        $manager = new class () extends MigrationManager {
            /**
             * @param array<int|string, string> $migrationsUp
             * @param array<string, string> $migrationsDown
             *
             * @return array<string, string>
             */
            public function build(array $migrationsUp, array $migrationsDown): array
            {
                return static::buildConnectionToVariableNameMap($migrationsUp, $migrationsDown);
            }
        };

        $migrationsUp = array_fill_keys(['default', 'with space', '\/', '123'], '');
        $migrationsDown = array_fill_keys(['default', 'connection$', 'connection&', 'connection%'], '');

        $expectedResult = [
            'default' => '$connection_default',
            'with space' => '$connection_withspace',
            '\/' => '$connection_2',
            '123' => '$connection_123',
            'connection$' => '$connection_connection',
            'connection&' => '$connection_connectionI',
            'connection%' => '$connection_connectionII',
        ];
        $result = $manager->build($migrationsUp, $migrationsDown);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return void
     */
    public function testCreateMigrationTableShouldTableWithColumns(): void
    {
        $migrationManager = $this->createMigrationManager([]);
        $migrationManager->createMigrationTable('migration');

        $this->assertTrue($migrationManager->migrationTableExists('migration'));
        $this->assertTrue($this->columnExists($migrationManager, self::COL_VERSION));
        $this->assertTrue($this->columnExists($migrationManager, self::COL_EXECUTION_DATETIME));
    }

    /**
     * @return void
     */
    public function testUpdateLatestMigrationTimestamp(): void
    {
        $expectedVersion = 1;
        $expectedExecutionDatetime = date('Y-m-d H:i:s');

        $migrationManager = $this->createMigrationManager([]);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', $expectedVersion);

        $connection = $migrationManager->getAdapterConnection('migration');
        $sql = sprintf(
            'SELECT %s, %s FROM %s WHERE %s=%s',
            self::COL_VERSION,
            self::COL_EXECUTION_DATETIME,
            $migrationManager->getMigrationTable(),
            self::COL_VERSION,
            $expectedVersion,
        );

        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $migrationData = $stmt->fetch();

        $this->assertSame($expectedVersion, $migrationData[self::COL_VERSION]);
        $this->assertSame($expectedExecutionDatetime, $migrationData[self::COL_EXECUTION_DATETIME]);
    }

    /**
     * @return void
     */
    public function testModifyMigrationTableIfOutdatedShouldNotUpdateTableIfExecutionDatetimeColumnExists(): void
    {
        $migrationManager = $this->createMigrationManager([]);
        $migrationManager->createMigrationTable('migration');

        $platformMock = $this->getMockBuilder(DefaultPlatform::class)
            ->setMethods(['getAddColumnDDL'])
            ->getMock();

        $platformMock->expects($this->never())->method('getAddColumnDDL');

        $migrationManager->modifyMigrationTableIfOutdated(
            $migrationManager->getAdapterConnection('migration'),
            $platformMock,
        );
    }

    /**
     * @return void
     */
    public function testModifyMigrationTableIfOutdatedShouldAddExecutionDatetimeColumn(): void
    {
        $migrationManager = $this->createMigrationManager([]);
        $migrationManager->createMigrationTable('migration');

        $connection = $migrationManager->getAdapterConnection('migration');

        /** @var \Propel\Generator\Platform\DefaultPlatform $platform */
        $platform = $migrationManager->getPlatform('migration');

        $column = new Column(self::COL_EXECUTION_DATETIME);
        $column->setTable(new Table($migrationManager->getMigrationTable()));

        $sql = $platform->getRemoveColumnDDL($column);

        $stmt = $connection->prepare($sql);
        $stmt->execute();

        $migrationManager->modifyMigrationTableIfOutdated($connection, $platform);

        $this->assertTrue($this->columnExists($migrationManager, self::COL_EXECUTION_DATETIME));
    }

    /**
     * @return void
     */
    public function testModifyMigrationTableShouldThrowExceptionIfMigrationTableDoesNotExist(): void
    {
        $migrationManager = $this->createMigrationManager([]);

        $this->expectException(PDOException::class);

        $migrationManager->modifyMigrationTableIfOutdated(
            $migrationManager->getAdapterConnection('migration'),
            $migrationManager->getPlatform('migration'),
        );
    }

    /**
     * @dataProvider isDatabaseVersionsAppliedDataProvider
     *
     * @param list<int> $localTimestamps
     * @param list<int> $databaseTimestamps
     * @param int $version
     * @param bool $expectedIsDatabaseVersionApplied
     *
     * @return void
     */
    public function testIsDatabaseVersionsApplied(
        array $localTimestamps,
        array $databaseTimestamps,
        int $version,
        bool $expectedIsDatabaseVersionApplied
    ): void {
        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        foreach ($databaseTimestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }

        $this->assertSame($expectedIsDatabaseVersionApplied, $migrationManager->isDatabaseVersionApplied($version));
    }

    /**
     * @return array<int, array<int, array<int, mixed>>>
     */
    public function getAllDatabaseVersionsDataProvider(): array
    {
        return [
            [
                [
                    1 => null,
                    2 => null,
                    3 => null,
                ],
                [1, 2, 3],
            ],
            [
                [
                    1 => date('Y-m-d H:i:s'),
                    2 => date('Y-m-d H:i:s', strtotime('-1 day')),
                    3 => date('Y-m-d H:i:s', strtotime('+1 day')),
                ],
                [2, 1, 3],
            ],
            [
                [
                    1 => date('Y-m-d H:i:s'),
                    2 => date('Y-m-d H:i:s', strtotime('-1 day')),
                    3 => date('Y-m-d H:i:s', strtotime('-1 day')),
                ],
                [2, 3, 1],
            ],
            [
                [
                    1 => null,
                    2 => date('Y-m-d H:i:s', strtotime('+1 day')),
                    3 => date('Y-m-d H:i:s'),
                ],
                [1, 3, 2],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<int>|int>>
     */
    public function getValidMigrationTimestampsDataProvider(): array
    {
        return [
            'The method should return full diff if a specific version is not provided.' => [
                [1, 2, 3],
                [1, 2],
                [3],
            ],
            'The method should return full diff if the given version is not found in the intersection.' => [
                [1, 2, 3],
                [1, 2],
                [3],
                4,
            ],
            'The method should cut all values from the diff after the given version.' => [
                [1, 2, 3, 4],
                [1],
                [2, 3],
                3,
            ],
        ];
    }

    /**
     * @return array<string, array<int, array|int>>
     */
    public function getAlreadyExecutedTimestampsDataProvider(): array
    {
        return [
            'The method should return an empty array if no intersection is found.' => [
                [1, 2, 3],
                [],
                [],
            ],
            'The method should return full intersection if a specific version is not provided.' => [
                [1, 2, 3, 4],
                [1 => null, 2 => null, 3 => null],
                [1, 2, 3],
            ],
            'The method should return the intersection according to the order of executed migrations.' => [
                [1, 2, 3, 4],
                [1 => date('Y-m-d H:i:s'), 2 => null, 3 => null],
                [2, 3, 1],
            ],
            'The method should return a full intersection if the given version is not found in the intersection.' => [
                [1, 2, 3, 4],
                [1 => null, 2 => null, 3 => null],
                [1, 2, 3],
                4,
            ],
            'The method should cut all values from the intersection before the given version.' => [
                [1, 2, 3, 4],
                [1 => null, 2 => null, 3 => null],
                [3],
                2,
            ],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function isDatabaseVersionsAppliedDataProvider(): array
    {
        return [
            [
                [1, 2, 3],
                [1, 2],
                4,
                false,
            ],
            [
                [1, 2, 3],
                [1, 2],
                1,
                true,
            ],
        ];
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $migrationManager
     * @param array<int, string|null> $migrationData
     *
     * @return void
     */
    private function addMigrations(MigrationManager $migrationManager, array $migrationData): void
    {
        $platform = $migrationManager->getPlatform('migration');
        $connection = $migrationManager->getAdapterConnection('migration');

        foreach ($migrationData as $version => $executionDatetime) {
            $sql = sprintf(
                'INSERT INTO %s (%s, %s) VALUES (?, ?)',
                $migrationManager->getMigrationTable(),
                $platform->doQuoting(self::COL_VERSION),
                $platform->doQuoting(self::COL_EXECUTION_DATETIME),
            );

            $stmt = $connection->prepare($sql);
            $stmt->bindParam(1, $version, PDO::PARAM_INT);
            $stmt->bindParam(
                2,
                $executionDatetime,
                $executionDatetime === null ? PDO::PARAM_NULL : PDO::PARAM_STR,
            );

            $stmt->execute();
        }
    }

    /**
     * @param \Propel\Generator\Manager\MigrationManager $migrationManager
     * @param string $columnName
     *
     * @return bool
     */
    private function columnExists(MigrationManager $migrationManager, string $columnName): bool
    {
        $connection = $migrationManager->getAdapterConnection('migration');

        $sql = sprintf(
            'SELECT %s FROM %s',
            $columnName,
            $migrationManager->getMigrationTable(),
        );

        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
