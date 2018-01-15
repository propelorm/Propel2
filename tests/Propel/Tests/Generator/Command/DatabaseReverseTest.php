<?php

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\DatabaseReverseCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;
use Symfony\Component\Console\Application;

/**
 * @group database
 */
class DatabaseReverseTest extends TestCaseFixturesDatabase
{
    protected $configDir;
    protected $outputDir;

    public function setUp()
    {
        parent::setUp();
        $this->configDir = __DIR__ . '/../../../../Fixtures/bookstore';
        $this->outputDir = __DIR__ . '/../../../../reversecommand';
    }

    public function testCommandWithoutNamespace()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $currentDir = getcwd();

        chdir(__DIR__ . '/../../../../Fixtures/bookstore');

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'database:reverse',
            '--database-name' => 'reverse-test',
            '--output-dir' => $this->outputDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            'connection' => $this->getConnectionDsn('bookstore-schemas', true)
        ]);

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        chdir($currentDir);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }
        $this->assertEquals(0, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($this->outputDir . '/schema.xml');
        $this->assertEquals('reverse-test', $databaseXml['name']);

        $this->assertGreaterThan(20, $databaseXml->xpath("table"));

        $table = $databaseXml->xpath("table[@name='acct_access_role']");
        $this->assertCount(1, $table);
        $table = $table[0];
        $this->assertEquals('acct_access_role', $table['name']);
        $this->assertEquals('AcctAccessRole', $table['phpName']);
        $this->assertCount(2, $table->xpath('column'));
    }

    public function testCommandWithNamespace()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $currentDir = getcwd();
        $testNamespace = '\ReverseVendor\ReversePackage';

        chdir(__DIR__ . '/../../../../Fixtures/bookstore');

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'database:reverse',
            '--database-name' => 'reverse-test',
            '--output-dir' => $this->outputDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--namespace' => $testNamespace,
            'connection' => $this->getConnectionDsn('bookstore-schemas', true)
        ]);

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        chdir($currentDir);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }
        $this->assertEquals(0, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($this->outputDir . '/schema.xml');
        $this->assertEquals($testNamespace, $databaseXml['namespace']);
    }

    public function testCommandWithConfigDirAndAllParams()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $currentDir = getcwd();
        $testNamespace = '\ReverseVendor\ReversePackage';

        chdir(__DIR__ . '/../../../../Fixtures/bookstore');

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'database:reverse',
            '--config-dir' => $this->configDir,
            '--database-name' => 'reverse-test',
            '--output-dir' => $this->outputDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--namespace' => $testNamespace,
            'connection' => $this->getConnectionDsn('bookstore-schemas', true)
        ]);

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        chdir($currentDir);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }
        $this->assertEquals(0, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($this->outputDir . '/schema.xml');
        $this->assertEquals($testNamespace, $databaseXml['namespace']);
    }

    public function testCommandWithConfigDirAndNoParams()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $currentDir = getcwd();
        $testNamespace = '\ReverseVendor\ReversePackageWithConfDir';

        chdir(__DIR__ . '/../../../../Fixtures/bookstore');

        $input = new \Symfony\Component\Console\Input\ArrayInput([
            'command' => 'database:reverse',
            '--config-dir' => $this->configDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()) . 'Platform'
        ]);

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        chdir($currentDir);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }
        $this->assertEquals(0, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($this->outputDir . '/schema.xml');
        $this->assertEquals($testNamespace, $databaseXml['namespace']);
    }

}
