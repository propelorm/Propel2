<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\ConfigConvertCommand;
use Propel\Generator\Command\InitCommand;
use Propel\Generator\Command\ModelBuildCommand;
use Propel\Generator\Command\SqlBuildCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixtures;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group database
 */
class InitCommandTest extends TestCaseFixtures
{
    /** @var string */
    private $dir;

    /** @var string */
    private $currentDir;

    public function setUp()
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . "/propel_init";
        $filesystem= new Filesystem();
        if ($filesystem->exists($this->dir)) {
            $filesystem->remove($this->dir);
        }
        $filesystem->mkdir($this->dir);

        $this->currentDir = getcwd();
        chdir($this->dir);
    }

    public function testExecute()
    {
        $app = new Application('Propel', Propel::VERSION);
        $app->addCommands([
            new InitCommand(),
            new ModelBuildCommand(),
            new SqlBuildCommand(),
            new ConfigConvertCommand()
        ]);

        $command = $app->find('init');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs($this->getInputsArray());
        $commandTester->execute(['command'  => $command->getName()]);

        $this->assertContains('Propel 2 is ready to be used!', $commandTester->getDisplay());
        $this->assertTrue(file_exists($this->dir . '/schema.xml'), 'Example schema file created.');
        $this->assertTrue(file_exists($this->dir . '/propel.yml'), 'Configuration file created.');
        $this->assertTrue(file_exists($this->dir . '/propel.yml.dist'), 'Dist configuration file created.');
        $this->assertTrue(file_exists($this->dir . '/Model'), 'Model directory created.');
        $this->assertTrue(file_exists($this->dir . '/Model/Init/Command/Namespace/Book.php'), 'Example model classes created.');
        $this->assertTrue(file_exists($this->dir . '/Model/Init/Command/Namespace/Author.php'), 'Example model classes created.');
        $this->assertTrue(file_exists($this->dir . '/generated-conf/config.php'), 'Configuration php file created.');
        $this->assertTrue(file_exists($this->dir . '/generated-sql/default.sql'), 'Sql file from example schema created.');
    }

    public function testExecuteAborted()
    {
        $app = new Application('Propel', Propel::VERSION);
        $app->addCommands([
            new InitCommand(),
            new ModelBuildCommand(),
            new SqlBuildCommand(),
            new ConfigConvertCommand()
        ]);

        $command = $app->find('init');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs($this->getInputsArray('no'));
        $commandTester->execute(['command'  => $command->getName()]);

        $this->assertContains('Process aborted', $commandTester->getDisplay());
    }

    private function getInputsArray($lastAnswer = 'yes')
    {
        $dsn = $this->getConnectionDsn('bookstore', true);

        $dsn = str_replace(':', ';', $dsn);
        $dsnArray = explode(';', $dsn);
        $dsnArray = array_map(function ($element) {
            $pos = strpos($element, '=');
            if (false !== $pos) {
                $element = substr($element, $pos + 1);
            }

            return $element;

        }, $dsnArray);

        $inputs[] = array_shift($dsnArray);
        if ($this->getDriver() !== 'sqlite') {
            $inputs[] = array_shift($dsnArray);
            $inputs[] = null;
        }
        $inputs = array_merge($inputs, [
            $dsnArray[0],
            isset($dsnArray[1]) ? $dsnArray[1] : null,
            isset($dsnArray[2]) ? $dsnArray[2] : null,
            'utf8',
            'no',
            $this->dir,
            $this->dir . '/Model/',
            'Init\\Command\\Namespace',
            'yml',
            $lastAnswer
        ]);

        return $inputs;
    }
}
