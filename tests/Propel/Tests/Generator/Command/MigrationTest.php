<?php

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\MigrationDiffCommand;
use Propel\Generator\Command\MigrationDownCommand;
use Propel\Generator\Command\MigrationMigrateCommand;
use Propel\Generator\Command\MigrationUpCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;
use Symfony\Component\Console\Application;

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

    public function setUp()
    {
        parent::setUp();
        $this->connectionOption =  ['migration_command=' . $this->getConnectionDsn('bookstore', true)];
        $this->connectionOption = str_replace('dbname=test', 'dbname=migration', $this->connectionOption);
        $this->configDir = __DIR__ . '/../../../../Fixtures/migration-command';
        $this->schemaDir = __DIR__ . '/../../../../Fixtures/migration-command';
        $this->outputDir = __DIR__ . self::$output;
    }

    public function testDiffCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationDiffCommand();
        $app->add($command);

        $files = glob($this->outputDir . '/PropelMigration_*.php');
        foreach ($files as $file) {
            unlink($file);
        }

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'migration:diff',
            '--schema-dir' => $this->schemaDir,
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true
        ));

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
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
        $this->assertGreaterThanOrEqual(2, substr_count($content, "CREATE TABLE "));
        $this->assertContains('CREATE TABLE ', $content);
    }

    public function testUpCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationUpCommand();
        $app->add($command);

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'migration:up',
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true
        ));

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
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

    public function testDownCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationDownCommand();
        $app->add($command);

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'migration:down',
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true
        ));

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
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

    public function testMigrateCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new MigrationMigrateCommand();
        $app->add($command);

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'migration:migrate',
            '--config-dir' => $this->configDir,
            '--output-dir' => $this->outputDir,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--connection' => $this->connectionOption,
            '--verbose' => true
        ));

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
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

}
