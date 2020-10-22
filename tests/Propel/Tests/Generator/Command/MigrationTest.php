<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Command;

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
    protected static $output = '/../../../../migrationdiff';

    protected $connectionOption;

    protected $configDir;

    protected $schemaDir;

    protected $outputDir;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->connectionOption = ['migration_command=' . $this->getConnectionDsn('bookstore', true)];
        $this->connectionOption = str_replace('dbname=test', 'dbname=migration', $this->connectionOption);
        $this->configDir = __DIR__ . '/../../../../Fixtures/migration-command';
        $this->schemaDir = __DIR__ . '/../../../../Fixtures/migration-command';
        $this->outputDir = __DIR__ . self::$output;
    }

    /**
     * @return void
     */
    public function testDiffCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationDiffCommand();
        $app->add($command);

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        foreach ($files as $file) {
            unlink($file);
        }

        $input = new ArrayInput([
            'command' => 'migration:diff',
            '--schema-dir' => $this->schemaDir,
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:diff tests exited successfully');

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        $this->assertGreaterThanOrEqual(1, count($files));
        $file = $files[0];

        $content = file_get_contents($file);
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'CREATE TABLE '));
        $this->assertContains('CREATE TABLE ', $content);
    }

    /**
     * @return void
     */
    public function testDiffCommandUsingSuffix()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationDiffCommand();
        $app->add($command);

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        foreach ($files as $file) {
            unlink($file);
        }

        $input = new ArrayInput([
            'command' => 'migration:diff',
            '--schema-dir' => $this->schemaDir,
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--suffix' => 'an_explanatory_filename_suffix',
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:diff tests exited successfully');

        $files = glob($this->outputDir . '/PropelMigration_*_an_explanatory_filename_suffix.php');
        $this->assertGreaterThanOrEqual(1, count($files));
        $file = $files[0];

        $content = file_get_contents($file);
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'CREATE TABLE '));
        $this->assertContains('CREATE TABLE ', $content);
    }

    /**
     * @return void
     */
    public function testUpCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationUpCommand();
        $app->add($command);

        $input = new ArrayInput([
            'command' => 'migration:up',
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        rewind($output->getStream());
        if (0 !== $result) {
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:up tests exited successfully');
        $outputString = stream_get_contents($output->getStream());
        $this->assertContains('Migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testDownCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationDownCommand();
        $app->add($command);

        $input = new ArrayInput([
            'command' => 'migration:down',
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        rewind($output->getStream());
        if (0 !== $result) {
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:down tests exited successfully');
        $outputString = stream_get_contents($output->getStream());
        $this->assertContains('Reverse migration complete.', $outputString);
    }

    /**
     * @return void
     */
    public function testMigrateCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationMigrateCommand();
        $app->add($command);

        $input = new ArrayInput([
            'command' => 'migration:migrate',
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        rewind($output->getStream());
        if (0 !== $result) {
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:down tests exited successfully');
        $outputString = stream_get_contents($output->getStream());
        $this->assertContains('Migration complete.', $outputString);

        //revert this migration change so we have the same database structure as before this test
        $this->testDownCommand();
    }

    /**
     * @return void
     */
    public function testCreateCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationCreateCommand();
        $app->add($command);

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        foreach ($files as $file) {
            unlink($file);
        }

        $input = new ArrayInput([
            'command' => 'migration:create',
            '--schema-dir' => $this->schemaDir,
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:create tests exited successfully');

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        $this->assertGreaterThanOrEqual(1, count($files));
        $file = $files[0];

        $content = file_get_contents($file);
        $this->assertNotContains('CREATE TABLE ', $content);
    }

    /**
     * @return void
     */
    public function testCreateCommandUsingSuffix()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationCreateCommand();
        $app->add($command);

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        foreach ($files as $file) {
            unlink($file);
        }

        $input = new ArrayInput([
            'command' => 'migration:create',
            '--schema-dir' => $this->schemaDir,
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--suffix' => 'an_explanatory_filename_suffix',
            '--verbose' => true,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'migration:create tests exited successfully');

        $files = glob($this->outputDir . '/PropelMigration_*_an_explanatory_filename_suffix.php');
        $this->assertGreaterThanOrEqual(1, count($files));
        $file = $files[0];

        $content = file_get_contents($file);
        $this->assertNotContains('CREATE TABLE ', $content);
    }
}
