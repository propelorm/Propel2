<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Manager;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Tests\TestCase;

/**
 * @group database
 */
class MigrationManagerTest extends TestCase
{
    /**
     * @return \Propel\Generator\Manager\MigrationManager
     */
    private function createMigrationManager(array $migrationTimestamps)
    {
        $generatorConfig = new GeneratorConfig(__DIR__ . '/../../../../Fixtures/migration/');

        $connections = $generatorConfig->getBuildConnections();

        $migrationManager = $this->getMockBuilder('Propel\Generator\Manager\MigrationManager')
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
    public function testMigrationTableWillBeCreated()
    {
        $migrationManager = $this->createMigrationManager([]);
        $this->assertFalse($migrationManager->migrationTableExists('migration'));

        $migrationManager->createMigrationTable('migration');
        $this->assertTrue($migrationManager->migrationTableExists('migration'));
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testGetFirstUpMigrationTimestamp()
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);
        $migrationManager->createMigrationTable('migration');

        $migrationManager->updateLatestMigrationTimestamp('migration', 1);

        $this->assertEquals(2, $migrationManager->getFirstUpMigrationTimestamp());
    }

    /**
     * @return void
     */
    public function testGetFirstDownMigrationTimestamp()
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
    public function testGetCommentMigrationManager()
    {
        $migrationManager = $this->createMigrationManager([1, 2, 3]);

        $body = $migrationManager->getMigrationClassBody('foo', 'bar', 4, 'migration comment');

        $this->assertStringContainsString('public $comment = \'migration comment\';', $body);
    }
}
