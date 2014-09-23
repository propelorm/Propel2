<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */
namespace Propel\Tests\Generator\Command;


use Propel\Generator\Command\SqlInsertCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Propel\Tests\TestCaseFixturesDatabase;

/**
 * @author Julien Ferrier <ferjul17@gmail.com>
 */
class SqlInsertCommandTest extends TestCaseFixturesDatabase {

    public function testCommand() {

        $command = new SqlInsertCommand();
        $input = new ArrayInput(array());
        $input->bind($command->getDefinition());

        chdir(__DIR__.'/../../../../Fixtures/sql-insert-command/');

        $class = new \ReflectionClass($command);
        $method = $class->getMethod('getGeneratorConfig');
        $method->setAccessible(true);

        $result = $method->invokeArgs($command, array(array(),$input));

        $this->assertInstanceOf('\Propel\Generator\Config\GeneratorConfig', $result);

    }

} 