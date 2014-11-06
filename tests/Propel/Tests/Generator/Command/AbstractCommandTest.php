<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\AbstractCommand;
use Propel\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class AbstractCommandTest extends TestCase
{
    protected $command;

    public function setUp()
    {
        $this->command = new TestableAbstractCommand();
    }

    public function testParseConnection()
    {
        $result = $this->command->parseConnection('bookstore=mysql:host=127.0.0.1;dbname=test;user=root');

        $this->assertEquals('bookstore', $result[0]);
        $this->assertEquals('mysql:host=127.0.0.1;dbname=test;user=root', $result[1]);
    }

    public function testRecursiveSearch()
    {
        $app = new Application();
        $app->add($this->command);

        $tester = new CommandTester($app->find('testable-command'));

        $tester->execute(
            array(
                'command' => 'testable-command',
                '--config-dir' =>  realpath(__DIR__ . '/../../../../Fixtures/recursive'),
                '--recursive' => true
            )
        );

        $this->assertEquals('3', $tester->getDisplay());

        $tester->execute(
            array(
                'command' => 'testable-command',
                '--config-dir' =>  realpath(__DIR__ . '/../../../../Fixtures/recursive'),
                '--recursive' => false
            )
        );

        $this->assertEquals('1', $tester->getDisplay());
    }
}

class TestableAbstractCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('testable-command');
    }

    public function parseConnection($connection)
    {
        return parent::parseConnection($connection);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->getSchemas($input->getOption('config-dir'), $input->getOption('recursive'));

        $output->write(count($result));
    }
}