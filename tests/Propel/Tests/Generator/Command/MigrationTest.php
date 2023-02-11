<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\AbstractCommand;
use Propel\Generator\Command\MigrationCreateCommand;
use Propel\Generator\Command\MigrationDiffCommand;
use Propel\Generator\Command\MigrationDownCommand;
use Propel\Generator\Command\MigrationMigrateCommand;
use Propel\Generator\Command\MigrationStatusCommand;
use Propel\Generator\Command\MigrationUpCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @group database
 */
class MigrationTest extends TestCaseFixturesDatabase
{
    /**
     * @var bool
     */
    private const MIGRATE_DOWN_AFTERWARDS = true;

    /**
     * @var string
     */
    private const SCHEMA_DIR = __DIR__ . '/../../../../Fixtures/migration-command';

    /**
     * @var string
     */
    private const OUTPUT_DIR = __DIR__ . '/../../../../migrationdiff';

    /**
     * @var string
     */
    private const SCHEMA_DIR_MIGRATE_TO_VERSION = __DIR__ . '/../../../../Fixtures/migrate-to-version';

    /**
     * @see \Propel\Generator\Command\MigrationMigrateCommand::COMMAND_OPTION_MIGRATE_TO_VERSION
     *
     * @var string
     */
    private const COMMAND_OPTION_MIGRATE_TO_VERSION = '--migrate-to-version';

    /**
     * @see \Propel\Generator\Command\MigrationStatusCommand::COMMAND_OPTION_LAST_VERSION
     *
     * @var string
     */
    private const COMMAND_OPTION_LAST_VERSION = '--last-version';

    /**
     * @uses \Propel\Generator\Manager\MigrationManager::COL_VERSION
     *
     * @var string
     */
    private const COL_VERSION = 'version';

    /**
     * @var string
     */
    private const MIGRATION_TABLE = 'propel_migration';

    /**
     * @return void
     */
    public function testDiffCommandCreatesFiles(): void
    {
        $this->deleteMigrationFiles();
        $this->runCommandAndAssertSuccess('migration:diff', new MigrationDiffCommand(), ['--schema-dir' => self::SCHEMA_DIR]);
        $this->assertGeneratedFileContainsCreateTableStatement(true, 'PropelMigration_*.php');
    }

    /**
     * @return void
     */
    public function testDiffCommandCreatesSuffixedFiles(): void
    {
        $this->deleteMigrationFiles();
        $suffix = 'an_explanatory_filename_suffix';
        $this->runCommandAndAssertSuccess('migration:diff', new MigrationDiffCommand(), ['--schema-dir' => self::SCHEMA_DIR, '--suffix' => $suffix]);
        $this->assertGeneratedFileContainsCreateTableStatement(true, "PropelMigration_*_$suffix.php");
    }

    /**
     * @return void
     */
    public function testCreateCommandCreatesFiles(): void
    {
        $this->deleteMigrationFiles();
        $this->runCommandAndAssertSuccess('migration:create', new MigrationCreateCommand(), ['--schema-dir' => self::SCHEMA_DIR]);
        $this->assertGeneratedFileContainsCreateTableStatement(false, 'PropelMigration_*.php');
    }

    /**
     * @return void
     */
    public function testCreateCommandCreatesSuffixedFiles(): void
    {
        $this->deleteMigrationFiles();
        $suffix = 'an_explanatory_filename_suffix';
        $this->runCommandAndAssertSuccess('migration:create', new MigrationCreateCommand(), ['--schema-dir' => self::SCHEMA_DIR, '--suffix' => $suffix]);
        $this->assertGeneratedFileContainsCreateTableStatement(false, "PropelMigration_*_$suffix.php");
    }

    /**
     * @return void
     */
    public function testUpCommandPerformsUpMigration(): void
    {
        $outputString = $this->runCommandAndAssertSuccess('migration:up', new MigrationUpCommand(), [], self::MIGRATE_DOWN_AFTERWARDS);
        $this->assertStringContainsString('Migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testDownCommandPerformsDownMigration(): void
    {
        $this->migrateUp();
        $outputString = $this->runCommandAndAssertSuccess('migration:down', new MigrationDownCommand());
        $this->assertStringContainsString('Reverse migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testMigrateCommandPerformsUpMigration(): void
    {
        $outputString = $this->runCommandAndAssertSuccess('migration:migrate', new MigrationMigrateCommand(), [], self::MIGRATE_DOWN_AFTERWARDS);
        $this->assertStringContainsString('Migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testMigrateCommandShouldMigrateToTheLastVersionIfTheGivenVersionIsNotExists(): void
    {
        $outputString = $this->runCommandAndAssertSuccess(
            'migration:migrate',
            new MigrationMigrateCommand(),
            [self::COMMAND_OPTION_MIGRATE_TO_VERSION => 0],
            self::MIGRATE_DOWN_AFTERWARDS,
        );

        $this->assertStringContainsString('Migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testMigrateCommandShouldDoNothingIfGivenVersionIsTheLastAppliedVersion(): void
    {
        $this->setUpMigrateToVersion();

        $migrationVersions = $this->getMigrationVersions();
        $expectedVersion = $migrationVersions[array_key_last($migrationVersions)];

        $outputString = $this->runCommandAndAssertSuccess(
            'migration:migrate',
            new MigrationMigrateCommand(),
            [self::COMMAND_OPTION_MIGRATE_TO_VERSION => $expectedVersion],
        );

        $this->assertIsCurrentVersion($expectedVersion);
        $this->assertStringContainsString(
            sprintf('Already at version %s.', $expectedVersion),
            $outputString,
        );

        $this->tearDownMigrateToVersion($migrationVersions);
    }

    /**
     * @return void
     */
    public function testMigrateCommandShouldRollbackToTheGivenVersionIfItIsLowerThanTheCurrentVersion(): void
    {
        $this->setUpMigrateToVersion();

        $migrationVersions = $this->getMigrationVersions();
        $expectedVersion = $migrationVersions[array_key_first($migrationVersions)];

        $outputString = $this->runCommandAndAssertSuccess(
            'migration:migrate',
            new MigrationMigrateCommand(),
            [self::COMMAND_OPTION_MIGRATE_TO_VERSION => $expectedVersion],
        );

        $this->assertIsCurrentVersion($expectedVersion);
        $this->assertStringContainsString(
            sprintf('Successfully rollback to migration version %s.', $expectedVersion),
            $outputString,
        );

        $this->tearDownMigrateToVersion($migrationVersions);
    }

    /**
     * @return void
     */
    public function testMigrateCommandShouldMigrateToTheGivenVersionIfItIsHigherThanTheCurrentVersion(): void
    {
        $this->setUpMigrateToVersion();

        $migrationVersions = $this->getMigrationVersions();
        $this->migrateDown();

        $expectedVersion = $migrationVersions[array_key_last($migrationVersions)];

        $outputString = $this->runCommandAndAssertSuccess(
            'migration:migrate',
            new MigrationMigrateCommand(),
            [self::COMMAND_OPTION_MIGRATE_TO_VERSION => $expectedVersion],
        );

        $this->assertIsCurrentVersion($expectedVersion);
        $this->assertStringContainsString('Migration complete. No further migration to execute.', $outputString);

        $this->tearDownMigrateToVersion($migrationVersions);
    }

    /**
     * @return void
     */
    public function testMigrationStatusCommandShouldReturnEmptyWhenOptionIsProvidedAndMigrationsWereNotExecuted(): void
    {
        $outputString = $this->runCommandAndAssertSuccess(
            'migration:status',
            new MigrationStatusCommand(),
            [self::COMMAND_OPTION_LAST_VERSION => true],
        );

        $this->assertEmpty(trim(str_replace('\n', '', $outputString)));
    }

    /**
     * @return void
     */
    public function testMigrationStatusCommandShouldReturnTheLastMigrationVersionWhenOptionIsProvided(): void
    {
        $this->setUpMigrateToVersion();

        $migrationVersions = $this->getMigrationVersions();

        $outputString = $this->runCommandAndAssertSuccess(
            'migration:status',
            new MigrationStatusCommand(),
            [self::COMMAND_OPTION_LAST_VERSION => true],
        );

        $this->tearDownMigrateToVersion($this->getMigrationVersions());

        $this->assertSame((string)array_pop($migrationVersions), trim(str_replace('\n', '', $outputString)));
    }

    /**
     * @return void
     */
    public function testMigrationStatusCommandShouldNotReturnTheLastMigrationVersionWhenOptionIsNotProvided(): void
    {
        $this->setUpMigrateToVersion();
        $this->migrateDown();

        $outputString = $this->runCommandAndAssertSuccess('migration:status', new MigrationStatusCommand());

        $this->tearDownMigrateToVersion($this->getMigrationVersions());

        $this->assertStringContainsString('Checking Database Versions', $outputString);
    }

    /**
     * @return void
     */
    private function deleteMigrationFiles(): void
    {
        $files = glob(self::OUTPUT_DIR . DIRECTORY_SEPARATOR . 'PropelMigration_*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Runs the supplied command and returns its output.
     *
     * @param string $commandName
     * @param \Propel\Generator\Command\AbstractCommand $commandInstance
     * @param array $additionalArguments
     * @param bool $migrateDownAfterwards
     *
     * @return string
     */
    private function runCommandAndAssertSuccess(
        string $commandName,
        AbstractCommand $commandInstance,
        array $additionalArguments = [],
        bool $migrateDownAfterwards = false
    ): string {
        $outputCapturer = new StreamOutput(fopen('php://temp', 'r+'));
        $exitCode = $this->runCommand($commandName, $commandInstance, $additionalArguments, $outputCapturer);

        if ($migrateDownAfterwards) {
            $this->migrateDown();
        }

        $streamedOutput = $outputCapturer->getStream();
        rewind($streamedOutput);
        $outputString = stream_get_contents($streamedOutput);

        $msg = "$commandName should exit successfully, but failed with message '$outputString'";
        $this->assertEquals(0, $exitCode, $msg);

        return $outputString;
    }

    /**
     * @return void
     */
    private function migrateUp(): void
    {
        $this->runCommand('migration:up', new MigrationUpCommand());
    }

    /**
     * @return void
     */
    private function migrateDown(): void
    {
        $this->runCommand('migration:down', new MigrationDownCommand());
    }

    /**
     * Create application and run it
     *
     * @param string $commandName
     * @param \Propel\Generator\Command\AbstractCommand $commandInstance
     * @param array $additionalArguments
     * @param \Symfony\Component\Console\Output\StreamOutput|null $outputCapturer
     *
     * @return int
     */
    private function runCommand(
        string $commandName,
        AbstractCommand $commandInstance,
        array $additionalArguments = [],
        ?StreamOutput $outputCapturer = null
    ): int {
        $applicationInputArguments = $this->buildApplicationInputArguments($commandName, $additionalArguments);

        if ($outputCapturer === null) {
            $outputCapturer = new StreamOutput(fopen('php://temp', 'r+'));
        }

        $app = new Application('Propel', Propel::VERSION);
        $app->add($commandInstance);
        $app->setAutoExit(false);

        return $app->run($applicationInputArguments, $outputCapturer);
    }

    /**
     * @param string $commandName
     * @param array $additionalArguments
     *
     * @return \Symfony\Component\Console\Input\ArrayInput
     */
    private function buildApplicationInputArguments(string $commandName, array $additionalArguments): ArrayInput
    {
        $additionalArguments['command'] = $commandName;

        $dsn = $this->getConnectionDsn('bookstore', true);
        $connectionOption = ['migration_command=' . $dsn];

        $defaultAppArguments = [
            '--config-dir' => self::SCHEMA_DIR,
            '--output-dir' => self::OUTPUT_DIR,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $connectionOption,
            '--verbose' => true,
        ];
        $args = array_merge($additionalArguments, $defaultAppArguments);

        return new ArrayInput($args);
    }

    /**
     * @param bool $containsCreateTable
     * @param string $fileGlobPattern
     *
     * @return void
     */
    private function assertGeneratedFileContainsCreateTableStatement(bool $containsCreateTable, string $fileGlobPattern): void
    {
        $files = glob(self::OUTPUT_DIR . DIRECTORY_SEPARATOR . $fileGlobPattern);
        $this->assertCount(1, $files, 'Exactly one file should have been created');

        $file = $files[0];
        $content = file_get_contents($file);
        if ($containsCreateTable) {
            // unfortunatelly, the number of CREATE TABLE statements differs when running the tests alone or as part of the suite
            $this->assertStringContainsString('CREATE TABLE ', $content);
        } else {
            $this->assertStringNotContainsString('CREATE TABLE ', $content);
        }
    }

    /**
     * @param int $version
     *
     * @return void
     */
    private function assertIsCurrentVersion(int $version): void
    {
        $sql = sprintf('SELECT %s FROM %s', self::COL_VERSION, self::MIGRATION_TABLE);

        $stmt = Propel::getServiceContainer()->getConnection()->prepare($sql);
        $stmt->execute();

        $versions = $stmt->fetchAll();
        $lastVersion = array_pop($versions)[self::COL_VERSION];

        $this->assertSame($version, (int)$lastVersion);
    }

    /**
     * @return void
     */
    private function setUpMigrateToVersion(): void
    {
        $this->deleteMigrationFiles();

        /** @var array<string> $versionDirectories */
        $versionDirectories = glob(
            sprintf(
                '%s%s*',
                self::SCHEMA_DIR_MIGRATE_TO_VERSION,
                DIRECTORY_SEPARATOR,
            ),
            GLOB_ONLYDIR,
        );

        foreach ($versionDirectories as $versionDirectory) {
            $this->runCommand('migration:diff', new MigrationDiffCommand(), ['--schema-dir' => $versionDirectory]);
            $this->migrateUp();
            sleep(1);
        }
    }

    /**
     * @param list<int> $migrationVersions
     *
     * @return void
     */
    private function tearDownMigrateToVersion(array $migrationVersions): void
    {
        foreach ($migrationVersions as $migrationVersion) {
            $this->migrateDown();
        }

        $this->deleteMigrationFiles();
    }

    /**
     * @return list<int>
     */
    private function getMigrationVersions(): array
    {
        $migrationFiles = scandir(sprintf('%s%s', self::OUTPUT_DIR, DIRECTORY_SEPARATOR));

        $migrationVersions = [];
        foreach ($migrationFiles as $migrationFile) {
            if (preg_match('/^PropelMigration_(\d+).*\.php$/', $migrationFile, $matches)) {
                $migrationVersions[] = (int)$matches[1];
            }
        }

        return $migrationVersions;
    }
}
