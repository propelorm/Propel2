<?php

namespace Propel\Tests\Command;

use Propel\Generator\Command\DatabaseReverseCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;
use Symfony\Component\Console\Application;

class DatabaseReverseTest extends TestCase
{
    public function testCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DatabaseReverseCommand();
        $app->add($command);

        $outputDir = __DIR__.'/../../../reversecommand';

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'database:reverse',
            '--database-name' => 'reverse-test',
            '--output-dir' => $outputDir,
            '--verbose' => true,
            '--platform' => ucfirst($this->getDriver()).'Platform',
            'connection' => $this->getConnectionDsn('bookstore-schemas')
        ));

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            echo $output->fetch();
        }
        $this->assertEquals(0, $result, 'database:reverse tests exited successfully');

        $databaseXml = simplexml_load_file($outputDir.'/schema.xml');
        $this->assertEquals('reverse-test', $databaseXml['name']);

        $this->assertGreaterThan(20, $databaseXml->xpath("table"));

        $table = $databaseXml->xpath("table[@name='acct_access_role']");
        $this->assertCount(1, $table);
        $table = $table[0];
        $this->assertEquals('acct_access_role', $table['name']);
        $this->assertEquals('AcctAccessRole', $table['phpName']);
        $this->assertCount(2, $table->xpath('column'));
    }

}