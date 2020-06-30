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

        $input = ['command' => $command->getName()] + $this->getInputArguments();
        $commandTester->execute($input);

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
        $input = ['command'  => $command->getName()] + $this->getInputArguments('no');
        $commandTester->execute($input);

        $this->assertContains('Process aborted', $commandTester->getDisplay());
    }

    private function getInputArguments($lastAnswer = 'yes')
    {
        $inputs = [
            'connection' => $this->getConnectionDsn('bookstore', true),
            'namespace' => 'Init\\Command\\Namespace',
            'phpModelPath' => $this->dir . '/Model/',
            'schemaPath' => $this->dir,
            'charset' => 'utf8',
            'preexistingDB' => 'no',
            'password' => '',
            'user' => 'root',
            'adapter' => $this->getDriver(),
            'configFormat' => 'yml',
        ];

        return $inputs;
    }
}
