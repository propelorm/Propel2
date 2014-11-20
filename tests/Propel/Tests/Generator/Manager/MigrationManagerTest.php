<?php

namespace Propel\Tests\Generator\Manager;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\MigrationManager;
use Propel\Tests\TestCase;

/**
 * @group database
 */
class MigrationManagerTest extends TestCase
{
    /**
     * @return MigrationManager
     */
    private function createMigrationManager(array $migrationTimestamps)
    {
        $generatorConfig = new GeneratorConfig(__DIR__ . '/../../../../Fixtures/migration/');

        $connections = $generatorConfig->getBuildConnections();

        $migrationManager = $this->getMock('Propel\Generator\Manager\MigrationManager', ['getMigrationTimestamps']);
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

    public function testMigrationTableWillBeCreated()
    {
        $migrationManager = $this->createMigrationManager([]);
        $this->assertFalse($migrationManager->migrationTableExists('migration'));

        $migrationManager->createMigrationTable('migration');
        $this->assertTrue($migrationManager->migrationTableExists('migration'));
    }

    public function testGetAllDatabaseVersions()
    {
        $databaseVersions = [1, 2, 3];
        $migrationManager = $this->createMigrationManager([]);
        $datasource = 'migration';
        $migrationManager->createMigrationTable($datasource);

        foreach ($databaseVersions as $version) {
            $migrationManager->updateLatestMigrationTimestamp($datasource, $version);
        }

        $allVersions = $migrationManager->getAllDatabaseVersions();

        $this->assertEquals($databaseVersions, $allVersions[$datasource]);
    }

    public function testGetValidMigrationTimestamps()
    {
        $localTimestamps = [1, 2, 3, 4];
        $databaseTimestamps = [1, 2];
        $expectedMigrationTimestamps = [3, 4];
        $datasource = 'migration';

        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable($datasource);

        foreach ($databaseTimestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp($datasource, $timestamp);
        }

        $migrationTimestamps = $migrationManager->getValidMigrationTimestamps();

        $this->assertEquals($expectedMigrationTimestamps, $migrationTimestamps[$datasource]);
    }

    public function testRemoveMigrationTimestamp()
    {
        $localTimestamps = [1, 2];
        $databaseTimestamps = [1, 2];
        $datasource = 'migration';

        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable($datasource);

        foreach ($databaseTimestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp($datasource, $timestamp);
        }

        $this->assertEquals([], $migrationManager->getValidMigrationTimestamps());

        $migrationManager->removeMigrationTimestamp($datasource, 2);
        $migrationTimestamps = $migrationManager->getValidMigrationTimestamps();

        $this->assertEquals([2], $migrationTimestamps[$datasource]);
    }

    public function testGetAlreadyExecutedTimestamps()
    {
        $timestamps = [1, 2];

        $datasource = 'migration';

        $migrationManager = $this->createMigrationManager($timestamps);
        $migrationManager->createMigrationTable($datasource);

        $this->assertEquals([], $migrationManager->getAlreadyExecutedMigrationTimestamps());

        foreach ($timestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp($datasource, $timestamp);
        }

        $executedTimestamps = $migrationManager->getAlreadyExecutedMigrationTimestamps();

        $this->assertEquals($timestamps, $executedTimestamps[$datasource]);
    }

    public function testIsPending()
    {
        $localTimestamps = [1, 2];

        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);
        $this->assertTrue($migrationManager->hasPendingMigrations());

        $migrationManager->updateLatestMigrationTimestamp('migration', 2);
        $this->assertFalse($migrationManager->hasPendingMigrations());
    }

    public function testGetOldestDatabaseVersion()
    {
        $datasource = 'migration';
        $timestamps = [1, 2];
        $migrationManager = $this->createMigrationManager($timestamps);
        $migrationManager->createMigrationTable($datasource);

        $this->assertNull($migrationManager->getOldestDatabaseVersion());
        foreach ($timestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp($datasource, $timestamp);
        }

        $oldestVersions = $migrationManager->getOldestDatabaseVersion();

        $this->assertEquals(2, $oldestVersions[$datasource]);
    }

    public function testGetFirstUpMigrationTimestamp()
    {
        $datasource = 'migration';
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable($datasource);

        $migrationManager->updateLatestMigrationTimestamp($datasource, 1);
        $migrationTimestamps = $migrationManager->getFirstUpMigrationTimestamp();

        $this->assertEquals(2, $migrationTimestamps[$datasource]);
    }

    public function testGetFirstDownMigrationTimestamp()
    {
        $datasource = 'migration';
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable($datasource);

        $migrationManager->updateLatestMigrationTimestamp($datasource, 1);
        $migrationManager->updateLatestMigrationTimestamp($datasource, 2);

        $firstDownMigrations = $migrationManager->getFirstDownMigrationTimestamp();

        $this->assertEquals(2, $firstDownMigrations[$datasource]);
    }

    public function testGetCommentMigrationManager()
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);

        $body = $migrationManager->getMigrationClassBody("foo", "bar", 4, "migration comment");

        $this->assertContains('public $comment = \'migration comment\';', $body);
    }
}
