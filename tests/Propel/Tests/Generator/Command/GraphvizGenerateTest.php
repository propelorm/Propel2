<?php

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\GraphvizGenerateCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCase;
use Symfony\Component\Console\Application;

class GraphvizGenerateTest extends TestCase
{
    public function testCommand()
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new GraphvizGenerateCommand();
        $app->add($command);

        $outputDir = __DIR__.'/../../../../graphviztest';

        $input = new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'graphviz:generate',
            '--input-dir' => __DIR__ . '/../../../../Fixtures/bookstore',
            '--output-dir' => $outputDir,
            '--verbose' => true
        ));

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if (0 !== $result) {
            echo $output->fetch();
        }

        $this->assertEquals(0, $result, 'graphviz:generate tests exited successfully');

        $this->assertFileExists($outputDir.'/bookstore.schema.dot');
        $content = file_get_contents($outputDir.'/bookstore.schema.dot');
        $this->assertContains('digraph G {', $content);
    }

}