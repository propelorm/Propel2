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
        $migrationManager->createMigrationTable('migration');

        foreach ($databaseVersions as $version) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $version);
        }

        $this->assertEquals($databaseVersions, $migrationManager->getAllDatabaseVersions());
    }

    public function testGetValidMigrationTimestamps()
    {
        $localTimestamps = [1, 2, 3, 4];
        $databaseTimestamps = [1, 2];
        $expectedMigrationTimestamps = [3, 4];

        $migrationManager = $this->createMigrationManager($localTimestamps);
        $migrationManager->createMigrationTable('migration');

        foreach ($databaseTimestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }

        $this->assertEquals($expectedMigrationTimestamps, $migrationManager->getValidMigrationTimestamps());
    }

    public function testRemoveMigrationTimestamp()
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

    public function testGetAlreadyExecutedTimestamps()
    {
        $timestamps = [1, 2];

        $migrationManager = $this->createMigrationManager($timestamps);
        $migrationManager->createMigrationTable('migration');

        $this->assertEquals([], $migrationManager->getAlreadyExecutedMigrationTimestamps());

        foreach ($timestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }

        $this->assertEquals($timestamps, $migrationManager->getAlreadyExecutedMigrationTimestamps());
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
        $timestamps = [1, 2];
        $migrationManager = $this->createMigrationManager($timestamps);
        $migrationManager->createMigrationTable('migration');

        $this->assertNull($migrationManager->getOldestDatabaseVersion());
        foreach ($timestamps as $timestamp) {
            $migrationManager->updateLatestMigrationTimestamp('migration', $timestamp);
        }
        $this->assertEquals(2, $migrationManager->getOldestDatabaseVersion());
    }

    public function testGetFirstUpMigrationTimestamp()
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);

        $this->assertEquals(2, $migrationManager->getFirstUpMigrationTimestamp());
    }

    public function testGetFirstDownMigrationTimestamp()
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);
        $migrationManager->updateLatestMigrationTimestamp('migration', 2);

        $this->assertEquals(2, $migrationManager->getFirstDownMigrationTimestamp());
    }

    public function testGetCommentMigrationManager()
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);

        $body = $migrationManager->getMigrationClassBody("foo", "bar", 4, "migration comment");

        $this->assertContains('public $comment = \'migration comment\';', $body);
    }
}
