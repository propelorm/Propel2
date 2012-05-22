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
}

class TestableAbstractCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('testable-command');
    }

    public function parseConnection($connection)
    {
        return parent::parseConnection($connection);
    }
}
