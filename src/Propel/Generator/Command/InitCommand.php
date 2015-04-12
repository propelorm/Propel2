<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Command;

use Propel\Generator\Builder\Util\PropelTemplate;
use Propel\Generator\Command\Helper\DialogHelper;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * @author Marc Scholten <marcphilipscholten@gmail.com>
 */
class InitCommand extends AbstractCommand
{
    private $defaultSchemaDir;
    private $defaultPhpDir;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->defaultSchemaDir = getcwd();
        $this->defaultPhpDir = $this->detectDefaultPhpDir();
    }


    protected function configure()
    {
        parent::configure();

        $this
            ->setName('init')
            ->setDescription('Initializes a new project')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getHelperSet()->set(new DialogHelper());

        /** @var $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');

        $options = [];

        $dialog->writeBlock($output, 'Propel 2 Initializer');

        $dialog->writeSection($output, 'First we need to set up your database connection.');
        $output->writeln('');

        $supportedRdbms = [
            'mysql' => 'MySQL',
            'sqlite' => 'SQLite',
            'pgsql' => 'PostgreSQL',
            'oracle' => 'Oracle',
            'sqlsrv' => 'MSSQL (via pdo-sqlsrv)',
            'mssql' => 'MSSQL (via pdo-mssql)'
        ];

        $options['rdbms'] = $dialog->select($output, 'Please pick your favorite database management system', $supportedRdbms);

        $output->writeln('');

        do {
            switch ($options['rdbms']) {
                case 'mysql':
                    $options['dsn'] = $this->initMysql($output, $dialog);
                    break;
                case 'sqlite':
                    $options['dsn'] = $this->initSqlite($output, $dialog);
                    break;
                case 'pgsql':
                    $options['dsn'] = $this->initPgsql($output, $dialog);
                    break;
                default:
                    $options['dsn'] = $this->initDsn($output, $dialog, $options['rdbms']);
                    break;
            }


            $options['user'] = $dialog->ask($output, 'Please enter your database user', 'root');
            $options['password'] = $dialog->askHiddenResponse($output, 'Please enter your database password');

            $options['charset'] = $dialog->ask($output, 'Which charset would you like to use?', 'utf8');
        } while (!$this->testConnection($output, $dialog, $options));

        $dialog->writeSection($output, 'The initial step in every Propel project is the "build". During build time, a developer describes the structure of the datamodel in a XML file called the "schema".');
        $dialog->writeSection($output, 'From this schema, Propel generates PHP classes, called "model classes", made of object-oriented PHP code optimized for a given RDMBS. The model classes are the primary interface to find and manipulate data in the database in Propel.');
        $dialog->writeSection($output, 'The XML schema can also be used to generate SQL code to setup your database. Alternatively, you can generate the schema from an existing database.');
        $output->writeln('');

        if ($dialog->askConfirmation($output, 'Do you have an existing database you want to use with propel?', false)) {
            $options['schema'] = $this->reverseEngineerSchema($output, $options);
        }

        $options['schemaDir'] = $dialog->ask($output, 'Where do you want to store your schema.xml?', $this->defaultSchemaDir);
        $options['phpDir'] = $dialog->ask($output, 'Where do you want propel to save the generated php models?', $this->defaultPhpDir);
        $options['namespace'] = $dialog->ask($output, 'Which namespace should the generated php models use?');

        $dialog->writeSection($output, 'Propel asks you to define some data to work properly, for instance: connection parameters, working directories, flags to take decisions and so on. You can pass these data via a configuration file.');
        $dialog->writeSection($output, 'The name of the configuration file is <comment>propel</comment>, with one of the supported extensions (yml, xml, json, ini, php). E.g. <comment>propel.yml</comment> or <comment>propel.json</comment>.');
        $output->writeln('');

        $options['format'] = $dialog->askAndValidate($output, 'Please enter the format to use for the generated configuration file (yml, xml, json, ini, php)', [$this, 'validateFormat'], false, 'yml');

        $dialog->writeBlock($output, 'Propel 2 Initializer - Summary');
        $dialog->writeSection($output, 'The Propel 2 Initializer will set up your project with the following settings:');

        $dialog->writeSummary($output, [
            'Path to schema.xml' => $options['schemaDir'] . '/schema.xml',
            'Path to config file' => sprintf('%s/propel.%s', getcwd(), $options['format']),
            'Path to generated php models' => $options['phpDir'],
            'Namespace of generated php models' => $options['namespace'],
        ]);

        $dialog->writeSummary($output, [
            'Database management system' => $options['rdbms'],
            'Charset' => $options['charset'],
            'User' => $options['user'],
        ]);

        $output->writeln('');

        if (!$dialog->askConfirmation($output, 'Is everything correct?')) {
            $output->writeln('<error>Process aborted.</error>');
            return 1;
        }

        $output->writeln('');

        $this->generateProject($output, $options);

        $dialog->writeSection($output, sprintf('Propel 2 is ready to be used!'));
    }

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

    private function initMysql(OutputInterface $output, DialogHelper $dialog)
    {
        $host = $dialog->ask($output, 'Please enter your database host', 'localhost');
        $database = $dialog->ask($output, 'Please enter your database name');

        return sprintf('mysql:host=%s;dbname=%s', $host, $database);
    }

    private function initSqlite(OutputInterface $output, DialogHelper $dialog)
    {
        $path = $dialog->ask($output, 'Where should the sqlite database be stored?', getcwd() . '/my.app.sq3');

        return sprintf('sqlite:', $path);
    }

    private function initPgsql(OutputInterface $output, DialogHelper $dialog)
    {
        $host = $dialog->ask($output, 'Please enter your database host (without port)', 'localhost');
        $port = $dialog->ask($output, 'Please enter your database port', '5432');
        $database = $dialog->ask($output, 'Please enter your database name');

        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    private function initDsn(OutputInterface $output, DialogHelper $dialog, $rdbms)
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

        return $dialog->ask($output, sprintf('Please enter the dsn (see <comment>%s</comment>) for your database connection', $help));
    }

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
    }

    private function writeFile(OutputInterface $output, $filename, $content)
    {
        $this->getFilesystem()->dumpFile($filename, $content);

        $output->writeln(sprintf('<info> + %s</info>', $filename));
    }

    public function validateFormat($format)
    {
        $format = strtolower($format);

        if ($format === 'yaml') {
            $format = 'yml';
        }

        $validFormats = ['php', 'ini', 'yml', 'xml', 'json'];
        if (!in_array($format, $validFormats)) {
            throw new \InvalidArgumentException(sprintf('The specified format "%s" is invalid. Use one of %s',
                $format,
                implode(', ', $validFormats)
            ));
        }

        return $format;
    }

    private function testConnection(OutputInterface $output, DialogHelper $dialog, array $options)
    {
        $adapter = AdapterFactory::create($options['rdbms']);

        try {
            ConnectionFactory::create($options, $adapter);

            $dialog->writeBlock($output, 'Connected to sql server successful!');
            return true;
        } catch (ConnectionException $e) {
            // get the "real" wrapped exception message
            do {
                $message = $e->getMessage();
            } while (($e = $e->getPrevious()) !== null);

            $dialog->writeBlock($output, 'Unable to connect to the specific sql server: ' . $message, 'error');
            $dialog->writeSection($output, 'Make sure the specified credentials are correct and try it again.');
            $output->writeln('');

            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln($e);
            }

            return false;
        }
    }

    private function reverseEngineerSchema(OutputInterface $output, array $options)
    {
        $outputDir = sys_get_temp_dir();

        $this->getApplication()->setAutoExit(false);
        if (0 === $this->getApplication()->run(new StringInput(sprintf('reverse %s --output-dir %s', escapeshellarg($options['dsn']), $outputDir)), $output)) {
            $schema = file_get_contents($outputDir . '/schema.xml');
        } else {
            exit(1);
        }

        $this->getApplication()->setAutoExit(true);

        return $schema;
    }
}
