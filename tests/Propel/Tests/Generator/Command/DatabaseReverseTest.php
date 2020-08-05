<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\AbstractCommand;
use Propel\Generator\Command\DatabaseReverseCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @group database
 */
class DatabaseReverseTest extends TestCaseFixturesDatabase
{
    /**
     * @return void
     */
    public function testCommandWithoutNamespace()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $currentDir = getcwd();
        $outputDir = __DIR__ . '/../../../../reversecommand';

        chdir(__DIR__ . '/../../../../Fixtures/bookstore');

        $input = new ArrayInput([
            'command' => 'database:reverse',
            '--database-name' => 'reverse-test',
            '--output-dir' => $outputDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            'connection' => $this->getConnectionDsn('bookstore-schemas', true),
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        chdir($currentDir);

        if (AbstractCommand::CODE_SUCCESS !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }
        $this->assertSame(AbstractCommand::CODE_SUCCESS, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($outputDir . '/schema.xml');
        $this->assertEquals('reverse-test', $databaseXml['name']);

        $this->assertGreaterThan(20, $databaseXml->xpath('table'));

        $table = $databaseXml->xpath("table[@name='acct_access_role']");
        $this->assertCount(1, $table);
        $table = $table[0];
        $this->assertEquals('acct_access_role', $table['name']);
        $this->assertEquals('AcctAccessRole', $table['phpName']);
        $this->assertCount(2, $table->xpath('column'));
    }

    /**
     * @return void
     */
    public function testCommandWithNamespace()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $currentDir = getcwd();
        $outputDir = __DIR__ . '/../../../../reversecommand';
        $testNamespace = '\ReverseVendor\ReversePackage';

        chdir(__DIR__ . '/../../../../Fixtures/bookstore');

        $input = new ArrayInput([
            'command' => 'database:reverse',
            '--database-name' => 'reverse-test',
            '--output-dir' => $outputDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()) . 'Platform',
            '--namespace' => $testNamespace,
            'connection' => $this->getConnectionDsn('bookstore-schemas', true),
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        chdir($currentDir);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }
        $this->assertEquals(0, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($outputDir . '/schema.xml');
        $this->assertEquals($testNamespace, $databaseXml['namespace']);
    }
}
