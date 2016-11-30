<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use Propel\Generator\Command\TestPrepareCommand;
use Propel\Runtime\Configuration;
use Propel\Runtime\Event\SaveEvent;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Application;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Finder\Finder;

/**
 * This test case class is used when the fixtures are needed. It takes care that
 * those files (model classes and -conf.php files) are created.
 *
 * This does not updates database's schema.
 * If you need additional to that also database's tables use TestCaseFixturesDatabase instead.
 */
class TestCaseFixtures extends TestCase
{
    /**
     * If setUp() should also initial database's schema.
     *
     * @var bool
     */
    protected $withDatabaseSchema = false;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Depending on this type we return the correct runninOn* results,
     * also getSql() and getDriver() is based on that.
     *
     * @var ConnectionInterface
     */
    protected $connection;

    public $lastBuildDsn;
    public $lastBuildMode;
    public $lastReadConfigs;

    /**
     * Setup fixture. Needed here because we want to have a realistic code coverage value.
     */
    protected function setUp()
    {
        $dsn = $this->getFixturesConnectionDsn();

        $options = array(
            'command' => 'test:prepare',
            '--vendor' => $this->getDriver(),
            '--dsn' => $dsn,
            '--verbose' => true
        );

        if (!$this->withDatabaseSchema) {
            $options['--exclude-database'] = true;
        }

        $mode = $this->withDatabaseSchema ? 'fixtures-database' : 'fixtures-only';
        $builtMode = $this->getLastBuildMode();

        if ($dsn === $this->getBuiltDsn()) {
            // we have at least the fixtures built

            // when we need a database update ($withDatabaseSchema == true) then we need to check
            // if the last build was a test:prepare with database or not. When yes then skip.
            $skip = true;
            if ($this->withDatabaseSchema && 'fixtures-database' !== $builtMode) {
                //we need new test:prepare call with --exclude-schema disabled
                $skip = false;
            }

            if ($skip) {
                $this->readAllRuntimeConfigs();
                //skip, as we've already created all fixtures for current database connection.
                return;
            }
        }

        $finder = new Finder();
        $finder->files()->name('*.php')->in(__DIR__.'/../../../src/Propel/Generator/Command')->depth(0);

        $app = new Application('Propel', Propel::VERSION);

        foreach ($finder as $file) {
            $ns = '\\Propel\\Generator\\Command';
            $r  = new \ReflectionClass($ns.'\\'.$file->getBasename('.php'));
            if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') && !$r->isAbstract()) {
                $app->add($r->newInstance());
            }
        }
        if (0 !== strpos($dsn, 'sqlite:')) {
            $options['--user'] = getenv('DB_USER') ?: 'root';
        }

        if (false !== getenv('DB_PW')) {
            $options['--password'] = getenv('DB_PW');
        }

        $input = new \Symfony\Component\Console\Input\ArrayInput($options);

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $app->setAutoExit(false);
        if (0 !== $app->run($input, $output)) {
            echo $output->fetch();
            $this->fail('Can not initialize fixtures.');
            return false;
        }


        $builtInfo = __DIR__ . '/../../Fixtures/fixtures_built';
        file_put_contents($builtInfo,
            "$dsn\n$mode\nFixtures has been created. Delete this file to let the test suite regenerate all fixtures."
        );

        $this->lastBuildDsn = $dsn;
        $this->lastBuildMode = $mode;
        $this->lastReadConfigs = '';

        $this->readAllRuntimeConfigs();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->getConfiguration()->reset();
        $this->getConfiguration()->getSession()->reset();
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    protected function getLastBuildMode()
    {
        if ($this->lastBuildMode) {
            return $this->lastBuildMode;
        }

        $builtInfo = __DIR__ . '/../../Fixtures/fixtures_built';
        if (file_exists($builtInfo) && ($h = fopen($builtInfo, 'r'))) {
            fgets($h);
            $secondLine = fgets($h);
            return $this->lastBuildMode = trim($secondLine);
        }
    }

    /**
     * Reads and includes all *-conf.php of Fixtures/ folder.
     */
    protected function readAllRuntimeConfigs()
    {
        if ($this->lastReadConfigs === $this->lastBuildMode.':'.$this->lastBuildDsn) {
            return;
        }

        $finder = new Finder();
        $finder->files()->name('*-conf.php')->in(__DIR__.'/../../Fixtures/');

        foreach ($finder as $file) {
            include($file->getPathname());
        }

        $this->configuration = Configuration::$globalConfiguration;
        $this->lastReadConfigs = $this->lastBuildMode.':'.$this->lastBuildDsn;
    }

    /**
     * Returns the used DNS for building the fixtures.
     *
     * @return string
     */
    protected function getBuiltDsn()
    {
        if ($this->lastBuildDsn) {
            return $this->lastBuildDsn;
        }

        $builtInfo = __DIR__ . '/../../Fixtures/fixtures_built';
        if (file_exists($builtInfo) && ($h = fopen($builtInfo, 'r')) && $firstLine = fgets($h)) {
            return $this->lastBuildDsn = trim($firstLine);
        }
    }

    /**
     * Returns the current connection DSN.
     *
     * @param string $database
     * @param boolean $withCredentials
     * @return string
     */
    protected function getConnectionDsn($database = 'bookstore', $withCredentials = false)
    {
        /** @var $manager \Propel\Runtime\Connection\ConnectionManagerSingle */
        $manager = $this->configuration->getConnectionManager($database);
        $configuration = $manager->getConfiguration();
        $dsn = $configuration['dsn'];

        if ('sqlite' !== substr($dsn, 0, 6) && $withCredentials) {
            $dsn .= ';user=' . $configuration['user'];
            if (isset($configuration['password']) && $configuration['password']) {
                $dsn .= ';password=' . $configuration['password'];
            }
        }

        return $dsn;
    }

    /**
     * Returns the DSN for building the fixtures.
     * They are provided by environment variables.
     *
     * DB, DB_HOSTNAME
     *
     * @return string
     */
    protected function getFixturesConnectionDsn()
    {
        if ('sqlite' === strtolower(getenv('DB'))) {
            $path = __DIR__ . '/../../test.sq3';
            if (!file_exists($path)) {
                touch($path);
            }
            return 'sqlite:' . realpath($path);
        }

        $db = strtolower(getenv('DB'));
        if (!$db || 'agnostic' === $db) {
            $db = 'mysql';
        }

        $dsn = $db . ':host=' . (getenv('DB_HOSTNAME') ?: '127.0.0.1' ) . ';dbname=';
        $dsn .= getenv('DB_NAME') ?: 'test';

        return $dsn;
    }

    /**
     * Returns current database driver.
     *
     * @return string[]
     */
    protected function getDriver()
    {
        $driver = $this->connection ? $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME) : null;

        if (null === $driver && $currentDSN = $this->getBuiltDsn()) {
            $driver = explode(':', $currentDSN)[0];
        }

        $db = strtolower(getenv('DB'));
        if (!$db || 'agnostic' === $db) {
            $db = 'mysql';
        }

        return $db ?: strtolower($driver);
    }
}
