<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    protected TestableAbstractCommand $command;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->command = new TestableAbstractCommand();
    }

    /**
     * @return void
     */
    public function testParseConnectionWithCredentials(): void
    {
        $user = 'root';
        $password = 'H7{“Qj1n>\%28=;P';
        $connectionName = 'bookstore';
        $dsn = 'mysql:host=127.0.0.1;dbname=test';
        $connection = sprintf(
            '%s=%s;user=%s;password=%s',
            $connectionName,
            $dsn,
            $user,
            urlencode($password)
        );
        $result = $this->command->parseConnection($connection);

        $this->assertCount(3, $result);
        $this->assertEquals($connectionName, $result[0]);
        $this->assertEquals($dsn, $result[1], 'DSN should not contain user and password parameters');
        $this->assertArrayHasKey('adapter', $result[2]);
        $this->assertEquals('mysql', $result[2]['adapter']);
        $this->assertArrayHasKey('user', $result[2]);
        $this->assertEquals($user, $result[2]['user']);
        $this->assertArrayHasKey('password', $result[2]);
        $this->assertEquals($password, $result[2]['password']);
    }

    /**
     * @return void
     */
    public function testParseConnectionWithoutCredentials(): void
    {
        $connectionName = 'bookstore';
        $dsn = 'sqlite:/tmp/test.sq3';
        $connection = sprintf(
            '%s=%s',
            $connectionName,
            $dsn
        );
        $result = $this->command->parseConnection($connection);

        $this->assertCount(3, $result);
        $this->assertEquals($connectionName, $result[0]);
        $this->assertEquals($dsn, $result[1], 'DSN should not contain user and password parameters');
        $this->assertArrayHasKey('adapter', $result[2]);
        $this->assertEquals('sqlite', $result[2]['adapter']);
        $this->assertArrayNotHasKey('user', $result[2]);
        $this->assertArrayNotHasKey('password', $result[2]);
    }

    /**
     * @return void
     */
    public function testRecursiveSearch(): void
    {
        $app = new Application();
        $app->add($this->command);

        $tester = new CommandTester($app->find('testable-command'));

        $tester->execute(
            [
                'command' => 'testable-command',
                '--config-dir' => realpath(__DIR__ . '/../../../../Fixtures/recursive'),
                '--recursive' => true,
            ]
        );

        $this->assertEquals('3', $tester->getDisplay());

        $tester->execute(
            [
                'command' => 'testable-command',
                '--config-dir' => realpath(__DIR__ . '/../../../../Fixtures/recursive'),
                '--recursive' => false,
            ]
        );

        $this->assertEquals('1', $tester->getDisplay());
    }
}

class TestableAbstractCommand extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName('testable-command');
    }

    public function parseConnection($connection): array
    {
        return parent::parseConnection($connection);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->getSchemas($input->getOption('config-dir'), $input->getOption('recursive'));

        $output->write(count($result));

        return 0;
    }
}
