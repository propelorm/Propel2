<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\AbstractCommand;
use Propel\Generator\Command\DataDictionaryExportCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixturesDatabase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @group database
 */
class DataDictionaryExportCommandTest extends TestCaseFixturesDatabase
{
    /**
     * @doesNotPerformAssertions
     *
     * @return void
     */
    public function testCommandExecutesWithoutError(): void
    {
        $app = new Application('Propel', Propel::VERSION);
        $command = new DataDictionaryExportCommand();
        $app->add($command);

        $testRoot = __DIR__ . '/../../../..';
        $bookstoreConfigDir = $testRoot . '/Fixtures/bookstore';
        $outputDir = $testRoot . '/tmp/';

        $input = new ArrayInput([
            'command' => 'datadictionary:export',
            '--output-dir' => $outputDir,
            '--schema-dir' => $bookstoreConfigDir,
            '--config-dir' => $bookstoreConfigDir,
        ]);

        $output = new StreamOutput(fopen('php://temp', 'r+'));
        $app->setAutoExit(false);
        $result = $app->run($input, $output);

        if(is_dir($outputDir)) {
            array_map('unlink', glob("$outputDir/*.*"));
            rmdir($outputDir);
        }

        if ($result !== AbstractCommand::CODE_SUCCESS) {
            rewind($output->getStream());
            $message = stream_get_contents($output->getStream());

            $this->fail('Command datadictionary:export failed with error: ' . $message);
        }
    }
}
