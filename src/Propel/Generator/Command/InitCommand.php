<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Command;

use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Command\Console\Input\ArrayInput;
use Propel\Generator\Command\Helper\ConsoleHelper;
use Propel\Generator\Command\Helper\ConsoleHelper3;
use Propel\Generator\Command\Helper\ConsoleHelperInterface;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * @author Marc Scholten <marcphilipscholten@gmail.com>
 */
class InitCommand extends AbstractCommand
{
    /**
     * @var string
     */
    private $defaultSchemaDir;

    /**
     * @var string
     */
    private $defaultPhpDir;

    /**
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->defaultSchemaDir = getcwd();
        $this->defaultPhpDir = $this->detectDefaultPhpDir();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('init')
            ->setDescription('Initializes a new project');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consoleHelper = $this->createConsoleHelper($input, $output);
        $options = [];

        $consoleHelper->writeBlock('Propel 2 Initializer');

        $consoleHelper->writeSection('First we need to set up your database connection.');
        $consoleHelper->writeln('');

        $supportedRdbms = [
            'mysql' => 'MySQL',
            'sqlite' => 'SQLite',
            'pgsql' => 'PostgreSQL',
            'oracle' => 'Oracle',
            'sqlsrv' => 'MSSQL (via pdo-sqlsrv)',
            'mssql' => 'MSSQL (via pdo-mssql)',
        ];

        $options['rdbms'] = $consoleHelper->select('Please pick your favorite database management system', $supportedRdbms);

        $consoleHelper->writeln('');

        $connectionAttemptLimit = 10;
        $connectionAttemptCount = 0;
        do {
            if ($connectionAttemptCount >= $connectionAttemptLimit) {
                $consoleHelper->writeln('');
                $consoleHelper->writeSection('Exceeded 10 attempts to connect to database');
                $consoleHelper->writeln('');

                return 1;
            }
            $connectionAttemptCount += 1;
            switch ($options['rdbms']) {
                case 'mysql':
                    $options['dsn'] = $this->initMysql($consoleHelper);

                    break;
                case 'sqlite':
                    $options['dsn'] = $this->initSqlite($consoleHelper);

                    break;
                case 'pgsql':
                    $options['dsn'] = $this->initPgsql($consoleHelper);

                    break;
                default:
                    $options['dsn'] = $this->initDsn($consoleHelper, $options['rdbms']);

                    break;
            }

            $options['user'] = $consoleHelper->askQuestion('Please enter your database user', 'root');
            $options['password'] = $consoleHelper->askHiddenResponse('Please enter your database password');

            $options['charset'] = $consoleHelper->askQuestion('Which charset would you like to use?', 'utf8');
        } while (!$this->testConnection($consoleHelper, $options));

        $consoleHelper->writeSection('The initial step in every Propel project is the "build". During build time, a developer describes the structure of the datamodel in a XML file called the "schema".');
        $consoleHelper->writeSection('From this schema, Propel generates PHP classes, called "model classes", made of object-oriented PHP code optimized for a given RDMBS. The model classes are the primary interface to find and manipulate data in the database in Propel.');
        $consoleHelper->writeSection('The XML schema can also be used to generate SQL code to setup your database. Alternatively, you can generate the schema from an existing database.');
        $consoleHelper->writeln('');

        $isReverseEngineerRequested = $consoleHelper->askConfirmation('Do you have an existing database you want to use with propel?', false);

        $options['schemaDir'] = $consoleHelper->askQuestion('Where do you want to store your schema.xml?', $this->defaultSchemaDir);
        $options['phpDir'] = $consoleHelper->askQuestion('Where do you want propel to save the generated php models?', $this->defaultPhpDir);
        $options['namespace'] = $consoleHelper->askQuestion('Which namespace should the generated php models use?');

        $consoleHelper->writeln('');

        if ($isReverseEngineerRequested) {
            $options['schema'] = $this->reverseEngineerSchema($consoleHelper->getOutput(), $options);
        }

        $consoleHelper->writeSection('Propel asks you to define some data to work properly, for instance: connection parameters, working directories, flags to take decisions and so on. You can pass these data via a configuration file.');
        $consoleHelper->writeSection('The name of the configuration file is <comment>propel</comment>, with one of the supported extensions (yml, xml, json, ini, php). E.g. <comment>propel.yml</comment> or <comment>propel.json</comment>.');
        $consoleHelper->writeln('');

        $options['format'] = $consoleHelper->select('Please enter the format to use for the generated configuration file (yml, xml, json, ini, php)', ['yml', 'xml', 'json', 'ini', 'php'], 'yml');

        $consoleHelper->writeBlock('Propel 2 Initializer - Summary');
        $consoleHelper->writeSection('The Propel 2 Initializer will set up your project with the following settings:');

        $consoleHelper->writeSummary([
            'Path to schema.xml' => $options['schemaDir'] . '/schema.xml',
            'Path to config file' => sprintf('%s/propel.%s', getcwd(), $options['format']),
            'Path to generated php models' => $options['phpDir'],
            'Namespace of generated php models' => $options['namespace'],
        ]);

        $consoleHelper->writeSummary([
            'Database management system' => $options['rdbms'],
            'Charset' => $options['charset'],
            'User' => $options['user'],
        ]);

        $consoleHelper->writeln('');
        $correct = $consoleHelper->askConfirmation('Is everything correct?', false);

        if (!$correct) {
            $consoleHelper->writeln('<error>Process aborted.</error>');

            return static::CODE_ERROR;
        }

        $consoleHelper->writeln('');

        $this->generateProject($consoleHelper->getOutput(), $options);
        $consoleHelper->writeSection('Propel 2 is ready to be used!');

        return static::CODE_SUCCESS;
    }

    /**
     * @return string
     */
    private function detectDefaultPhpDir()
    {
        if (file_exists(getcwd() . '/src/')) {
            $vendors = Finder::create()->directories()->in(getcwd() . '/src/')->depth(1);

            if ($vendors->count() > 1) {
                $iterator = $vendors->getIterator();
                $iterator->next();

                return $iterator->current() . '/Model/';
            }
        }

        return getcwd();
    }

    /**
     * @param \Propel\Generator\Command\Helper\ConsoleHelperInterface $consoleHelper
     *
     * @return string
     */
    private function initMysql(ConsoleHelperInterface $consoleHelper)
    {
        $host = $consoleHelper->askQuestion('Please enter your database host', 'localhost');
        $port = $consoleHelper->askQuestion('Please enter your database port', '3306');
        $database = $consoleHelper->askQuestion('Please enter your database name');

        return sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    /**
     * @param \Propel\Generator\Command\Helper\ConsoleHelperInterface $consoleHelper
     *
     * @return string
     */
    private function initSqlite(ConsoleHelperInterface $consoleHelper)
    {
        $path = $consoleHelper->askQuestion('Where should the sqlite database be stored?', getcwd() . '/my.app.sq3');

        return sprintf('sqlite:%s', $path);
    }

    /**
     * @param \Propel\Generator\Command\Helper\ConsoleHelperInterface $consoleHelper
     *
     * @return string
     */
    private function initPgsql(ConsoleHelperInterface $consoleHelper)
    {
        $host = $consoleHelper->askQuestion('Please enter your database host (without port)', 'localhost');
        $port = $consoleHelper->askQuestion('Please enter your database port', '5432');
        $database = $consoleHelper->askQuestion('Please enter your database name');

        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    /**
     * @param \Propel\Generator\Command\Helper\ConsoleHelperInterface $consoleHelper
     * @param string $rdbms
     *
     * @return mixed
     */
    private function initDsn(ConsoleHelperInterface $consoleHelper, $rdbms)
    {
        switch ($rdbms) {
            case 'oracle':
                $help = 'https://php.net/manual/en/ref.pdo-oci.connection.php#refsect1-ref.pdo-oci.connection-description';

                break;
            case 'sqlsrv':
                $help = 'https://php.net/manual/en/ref.pdo-sqlsrv.connection.php#refsect1-ref.pdo-sqlsrv.connection-description';

                break;
            case 'mssql':
                $help = 'https://php.net/manual/en/ref.pdo-dblib.connection.php#refsect1-ref.pdo-dblib.connection-description';

                break;
            default:
                $help = 'https://php.net/manual/en/pdo.drivers.php';
        }

        return $consoleHelper->askQuestion(sprintf('Please enter the dsn (see <comment>%s</comment>) for your database connection', $help));
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $options
     *
     * @return void
     */
    private function generateProject(OutputInterface $output, array $options)
    {
        $schema = new PropelTemplate();
        $schema->setTemplateFile(__DIR__ . '/templates/schema.xml.php');

        $config = new PropelTemplate();
        $config->setTemplateFile(__DIR__ . '/templates/propel.' . $options['format'] . '.php');

        $distConfig = new PropelTemplate();
        $distConfig->setTemplateFile(__DIR__ . '/templates/propel.' . $options['format'] . '.dist.php');

        if (!isset($options['schema'])) {
            $options['schema'] = $schema->render($options);
        }

        $this->writeFile($output, sprintf('%s/schema.xml', $options['schemaDir']), $options['schema']);
        $this->writeFile($output, sprintf('%s/propel.%s', getcwd(), $options['format']), $config->render($options));
        $this->writeFile($output, sprintf('%s/propel.%s.dist', getcwd(), $options['format']), $distConfig->render($options));

        $this->buildSqlAndModelsAndConvertConfig();
    }

    /**
     * @return void
     */
    private function buildSqlAndModelsAndConvertConfig()
    {
        $this->getApplication()->setAutoExit(false);

        $followupCommands = [
            'sql:build',
            'model:build',
            'config:convert',
        ];

        foreach ($followupCommands as $command) {
            if ($this->getApplication()->run(new ArrayInput([$command])) !== 0) {
                exit(1);
            }
        }

        $this->getApplication()->setAutoExit(true);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $filename
     * @param string $content
     *
     * @return void
     */
    private function writeFile(OutputInterface $output, $filename, $content)
    {
        $this->getFilesystem()->dumpFile($filename, $content);

        $output->writeln(sprintf('<info> + %s</info>', $filename));
    }

    /**
     * @param \Propel\Generator\Command\Helper\ConsoleHelperInterface $consoleHelper
     * @param array $options
     *
     * @return bool
     */
    private function testConnection(ConsoleHelperInterface $consoleHelper, array $options)
    {
        $adapter = AdapterFactory::create($options['rdbms']);

        try {
            ConnectionFactory::create($options, $adapter);

            $consoleHelper->writeBlock('Connected to sql server successful!');

            return true;
        } catch (ConnectionException $e) {
            // get the "real" wrapped exception message
            do {
                $message = $e->getMessage();
            } while (($e = $e->getPrevious()) !== null);

            $consoleHelper->writeBlock('Unable to connect to the specific sql server: ' . $message, 'error');
            $consoleHelper->writeSection('Make sure the specified credentials are correct and try it again.');
            $consoleHelper->writeln('');

            if ($consoleHelper->getOutput()->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $consoleHelper->writeln($e);
            }

            return false;
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $options
     *
     * @return string
     */
    private function reverseEngineerSchema(OutputInterface $output, array $options)
    {
        $outputDir = sys_get_temp_dir();

        $this->getApplication()->setAutoExit(false);
        $fullDsn = sprintf('%s;user=%s;password=%s', $options['dsn'], urlencode($options['user']), urlencode($options['password']));

        $arrInput = [
            'reverse',
            'connection' => $fullDsn,
            '--output-dir' => $outputDir,
        ];

        if (isset($options['namespace'])) {
            $arrInput['--namespace'] = $options['namespace'];
        }

        $input = new ArrayInput($arrInput);
        $result = $this->getApplication()->run($input, $output);

        if ($result === 0) {
            $schema = file_get_contents($outputDir . '/schema.xml');
        } else {
            exit(1);
        }

        $this->getApplication()->setAutoExit(true);

        return $schema;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Propel\Generator\Command\Helper\ConsoleHelperInterface
     */
    protected function createConsoleHelper(InputInterface $input, OutputInterface $output)
    {
        /* Check if it runs in Symfony3 env — than use QuestionHelper, because DialogHelper is absent */
        if (class_exists('\Symfony\Component\Console\Helper\QuestionHelper')) {
            $helper = new ConsoleHelper3($input, $output);
        } else {
            $helper = new ConsoleHelper($input, $output);
        }
        $this->getHelperSet()->set($helper);

        return $helper;
    }
}
