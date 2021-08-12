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
    private const MIGRATE_DOWN_AFTERWARDS = true;
    private const SCHEMA_DIR = __DIR__ . '/../../../../Fixtures/migration-command';
    private const OUTPUT_DIR = __DIR__ . '/../../../../migrationdiff';

    /**
     * @return void
     */
    public function testDiffCommandCreatesFiles()
    {
        $this->deleteMigrationFiles();
        $this->runCommandAndAssertSuccess('migration:diff', new MigrationDiffCommand(), ['--schema-dir' => self::SCHEMA_DIR]);
        $this->assertGeneratedFileContainsCreateTableStatement(true, 'PropelMigration_*.php');
    }

    /**
     * @return void
     */
    public function testDiffCommandCreatesSuffixedFiles()
    {
        $this->deleteMigrationFiles();
        $suffix = 'an_explanatory_filename_suffix';
        $this->runCommandAndAssertSuccess('migration:diff', new MigrationDiffCommand(), ['--schema-dir' => self::SCHEMA_DIR, '--suffix' => $suffix]);
        $this->assertGeneratedFileContainsCreateTableStatement(true, "PropelMigration_*_$suffix.php");
    }

    /**
     * @return void
     */
    public function testCreateCommandCreatesFiles()
    {
        $this->deleteMigrationFiles();
        $this->runCommandAndAssertSuccess('migration:create', new MigrationCreateCommand(), ['--schema-dir' => self::SCHEMA_DIR]);
        $this->assertGeneratedFileContainsCreateTableStatement(false, 'PropelMigration_*.php');
    }

    /**
     * @return void
     */
    public function testCreateCommandCreatesSuffixedFiles()
    {
        $this->deleteMigrationFiles();
        $suffix = 'an_explanatory_filename_suffix';
        $this->runCommandAndAssertSuccess('migration:create', new MigrationCreateCommand(), ['--schema-dir' => self::SCHEMA_DIR, '--suffix' => $suffix]);
        $this->assertGeneratedFileContainsCreateTableStatement(false, "PropelMigration_*_$suffix.php");
    }

    /**
     * @return void
     */
    public function testUpCommandPerformsUpMigration()
    {
        $outputString = $this->runCommandAndAssertSuccess('migration:up', new MigrationUpCommand(), [], self::MIGRATE_DOWN_AFTERWARDS);
        $this->assertStringContainsString('Migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testDownCommandPerformsDownMigration()
    {
        $this->migrateUp();
        $outputString = $this->runCommandAndAssertSuccess('migration:down', new MigrationDownCommand());
        $this->assertStringContainsString('Reverse migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testMigrateCommandPerformsUpMigration()
    {
        $outputString = $this->runCommandAndAssertSuccess('migration:migrate', new MigrationMigrateCommand(), [], self::MIGRATE_DOWN_AFTERWARDS);
        $this->assertStringContainsString('Migration complete.', $outputString);
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
     * @return \Symfony\Component\Console\Output\StreamOutput
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
    private function migrateUp()
    {
        $this->runCommand('migration:up', new MigrationUpCommand());
    }

    /**
     * @return void
     */
    private function migrateDown()
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
}
