<?php

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\GraphvizGenerateCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixtures;
use Symfony\Component\Console\Application;

class GraphvizGenerateTest extends TestCaseFixtures
{
    public function testCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new GraphvizGenerateCommand();
        $app->add($command);

        $outputDir = __DIR__.'/../../../../graphviztest';

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'graphviz:generate',
            '--schema-dir' => __DIR__ . '/../../../../Fixtures/bookstore',
            '--config-dir' => __DIR__ . '/../../../../Fixtures/bookstore',
            '--output-dir' => $outputDir,
            '--verbose' => true
        ));

        $output = new \Symfony\Component\Console\Output\StreamOutput(fopen("php://temp", 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            rewind($output->getStream());
            echo stream_get_contents($output->getStream());
        }

        $this->assertEquals(0, $result, 'graphviz:generate tests exited successfully');

        $this->assertFileExists($outputDir.'/bookstore.schema.dot');
        $content = file_get_contents($outputDir.'/bookstore.schema.dot');
        $this->assertContains('digraph G {', $content);
    }

}