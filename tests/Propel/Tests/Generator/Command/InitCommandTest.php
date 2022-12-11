<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Command;

use Propel\Generator\Command\ConfigConvertCommand;
use Propel\Generator\Command\InitCommand;
use Propel\Generator\Command\ModelBuildCommand;
use Propel\Generator\Command\SqlBuildCommand;
use Propel\Runtime\Propel;
use Propel\Tests\TestCaseFixtures;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group database
 */
class InitCommandTest extends TestCaseFixtures
{
    /**
     * @var string
     */
    private $dir;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/propel_init';
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->dir)) {
            $filesystem->remove($this->dir);
        }
        $filesystem->mkdir($this->dir);

        chdir($this->dir);
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        if (!method_exists(CommandTester::class, 'setInputs')) {
            $this->markTestSkipped('Interactive console input was not present in some earlier versions of symfony/console');
        }

        $app = new Application('Propel', Propel::VERSION);
        $app->addCommands([
            new InitCommand(),
            new ModelBuildCommand(),
            new SqlBuildCommand(),
            new ConfigConvertCommand(),
        ]);

        $command = $app->find('init');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs($this->getInputsArray());
        $commandTester->execute(['command' => $command->getName()]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Propel 2 is ready to be used!', $output);
        $this->assertStringContainsString('Successfully wrote PHP configuration in file', $output);
        $this->assertTrue(file_exists($this->dir . '/schema.xml'), 'Example schema file created.');
        $this->assertTrue(file_exists($this->dir . '/propel.yml'), 'Configuration file created.');
        $this->assertTrue(file_exists($this->dir . '/propel.yml.dist'), 'Dist configuration file created.');
        $this->assertTrue(file_exists($this->dir . '/Model'), 'Model directory created.');
        $this->assertTrue(file_exists($this->dir . '/Model/Init/Command/Namespace/Book.php'), 'Example model classes created.');
        $this->assertTrue(file_exists($this->dir . '/Model/Init/Command/Namespace/Author.php'), 'Example model classes created.');
        $this->assertTrue(file_exists($this->dir . '/generated-conf/config.php'), 'Configuration php file created.');
        $this->assertTrue(file_exists($this->dir . '/generated-sql/default.sql'), 'Sql file from example schema created.');
    }

    /**
     * @return void
     */
    public function testExecuteAborted()
    {
        if (!method_exists(CommandTester::class, 'setInputs')) {
            $this->markTestSkipped('Interactive console input was not present in some earlier versions of symphony/console');
        }

        $app = new Application('Propel', Propel::VERSION);
        $app->addCommands([
            new InitCommand(),
            new ModelBuildCommand(),
            new SqlBuildCommand(),
            new ConfigConvertCommand(),
        ]);

        $command = $app->find('init');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs($this->getInputsArray('no'));
        $commandTester->execute(['command' => $command->getName()]);

        $this->assertStringContainsString('Process aborted', $commandTester->getDisplay());
    }

    /**
     * Gets the user input responses to the prompts during init command.
     * 
     * 1. database type
     * 2. host
     * 3. port
     * 4. database name
     * 5. user
     * 6. password
     * 7. charset
     * 8. ...
     * 
     * @param string $lastAnswer
     *
     * @return array
     */
    private function getInputsArray($lastAnswer = 'yes')
    {
        // like mysql:host=$DB_HOSTNAME;port=$DB_PORT;dbname=$DB_NAME;user=$DB_USER;password=$DB_PW
        $dsnData = $this->getParsedDsn();

        $inputs = [];
        if ($dsnData['type']) {
            $inputs[] = $dsnData['type'];
        }

        if ($this->getDriver() !== 'sqlite') {
            $inputs[] = $dsnData['host'] ?? null;
            $inputs[] = $dsnData['port'] ?? null;
        }
        $inputs = array_merge($inputs, [
            $dsnData['dbname'] ?? null,
            $dsnData['user'] ?? null,
            $dsnData['password'] ?? null,
            'utf8',
            'no',
            $this->dir,
            $this->dir . '/Model/',
            'Init\\Command\\Namespace',
            'yml',
            $lastAnswer,
        ]);

        return $inputs;
    }

    /**
     * @return array
     */
    protected function getParsedDsn(): array
    {
        $dsn = $this->getConnectionDsn('bookstore', true);

        $parsedDsn = [];

        $firstColon = strpos($dsn, ':');
        $parsedDsn['type'] = substr($dsn, 0, $firstColon);

        if($parsedDsn['type'] === 'sqlite'){
            return $parsedDsn;
        }

        $namedArgsString = substr($dsn, $firstColon + 1);
        $namedArgsStrings = explode(';', $namedArgsString);
        foreach($namedArgsStrings as $argString){
            [$key, $value] = explode('=', $argString);
            $parsedDsn[$key] = $value;
        }

        return $parsedDsn;
    }
}
