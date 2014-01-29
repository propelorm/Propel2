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
use Propel\Runtime\Propel;
use Symfony\Component\Console\Application;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Finder\Finder;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Depending on this type we return the correct runninOn* results,
     * also getSql() and getDriver() is based on that.
     *
     * @var ConnectionInterface
     */
    protected $con;

    /**
     * Makes the sql compatible with the current database.
     * Means: replaces ` etc.
     *
     * @param  string $sql
     * @param  string $source
     * @param  string $target
     * @return mixed
     */
    protected function getSql($sql, $source = 'mysql', $target = null)
    {
        if (!$target) {
            $target = $this->getDriver();
        }

        if ('sqlite' === $target && 'mysql' === $source) {
            return preg_replace('/`([^`]*)`/', '[$1]', $sql);
        }
        if ('mysql' !== $target && 'mysql' === $source) {
            return str_replace('`', '', $sql);
        }

        return $sql;
    }

	/**
	 * Setup fixture. Needed here because we want to have a realistic code coverage value.
	 */
	protected function setUp()
	{

        $dsn = $this->getFixturesConnectionDsn();

		if ($dsn === $this->getBuiltDsn()) {
            $this->readAllRuntimeConfigs();
            //skip, as we've already created all fixtures for current database connection.
            return;
        }

        $builtInfo = 'tests/Fixtures/fixtures_built';
        file_put_contents($builtInfo,
            "$dsn\nFixtures has been created. Delete this file to let the test suite regenerate all fixtures."
        );

        $finder = new Finder();
        $finder->files()->name('*.php')->in(__DIR__.'/../../../src/Propel/Generator/Command');

        $app = new Application('Propel', Propel::VERSION);

        foreach ($finder as $file) {
            $ns = '\\Propel\\Generator\\Command';
            $r  = new \ReflectionClass($ns.'\\'.$file->getBasename('.php'));
            if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') && !$r->isAbstract()) {
                $app->add($r->newInstance());
            }
        }

        $options = array(
            'command' => 'test:prepare',
            '--vendor' => $this->getDriver(),
            '--dsn' => $dsn,
            '--verbose'
        );

        if (0 !== strpos($dsn, 'sqlite:')) {
            $options['--user'] = getenv('DB_USER') ?: 'root';
        }

        if (false !== getenv('DB_PASSWORD')) {
            $options['--password'] = getenv('DB_PASSWORD');
        }

		$input = new \Symfony\Component\Console\Input\ArrayInput($options);

		$output = new \Symfony\Component\Console\Output\ConsoleOutput();
		$app->setAutoExit(false);
		$app->run($input, $output);

        $this->readAllRuntimeConfigs();
	}

    /**
     * Reads and includes all *-conf.php of Fixtures/ folder.
     */
    protected function readAllRuntimeConfigs()
    {
        $finder = new Finder();
        $finder->files()->name('*-conf.php')->in(__DIR__.'/../../Fixtures/');

        foreach ($finder as $file) {
            include_once($file->getPathname());
        }
    }

    /**
     * Returns the used DNS for building the fixtures.
     *
     * @return string
     */
    protected function getBuiltDsn()
    {
        $builtInfo = 'tests/Fixtures/fixtures_built';
        if (file_exists($builtInfo) && ($h = fopen($builtInfo, 'r')) && $firstLine = fgets($h)) {
            return trim($firstLine);
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
        $serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
        /** @var $manager \Propel\Runtime\Connection\ConnectionManagerSingle */
        $manager = $serviceContainer->getConnectionManager($database);
        $configuration = $manager->getConfiguration();
        $dsn = $configuration['dsn'];

        if ($withCredentials) {
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
            return 'sqlite:' . realpath(__DIR__ . '/../../test.sq3');
        }

        return (strtolower(getenv('DB')) ?: 'mysql') . ':host=' . (getenv('DB_HOSTNAME') ?: '127.0.0.1' ) . ';dbname=test';
    }

    /**
     * Returns true if the current driver in the connection ($this->con) is $db.
     *
     * @param  string $db
     * @return bool
     */
    protected function isDb($db = 'mysql')
    {
        return $this->getDriver() == $db;
    }

    /**
     * @return bool
     */
    protected function runningOnPostgreSQL()
    {
        return $this->isDb('pgsql');
    }

    /**
     * @return bool
     */
    protected function runningOnMySQL()
    {
        return $this->isDb('mysql');
    }

    /**
     * @return bool
     */
    protected function runningOnSQLite()
    {
        return $this->isDb('sqlite');
    }

    /**
     * @return bool
     */
    protected function runningOnOracle()
    {
        return $this->isDb('oracle');
    }

    /**
     * @return bool
     */
    protected function runningOnMSSQL()
    {
        return $this->isDb('mssql');
    }

    /**
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    protected function getPlatform()
    {
        $className = sprintf('\\Propel\\Generator\\Platform\\%sPlatform', ucfirst($this->getDriver()));

        return new $className;
    }

    /**
     * @return \Propel\Generator\Reverse\SchemaParserInterface
     */
    protected function getParser($con)
    {
        $className = sprintf('\\Propel\\Generator\\Reverse\\%sSchemaParser', ucfirst($this->getDriver()));

        $obj =  new $className($con);

        return $obj;
    }

    /**
     * Returns current database driver.
     *
     * @return string[]
     */
    protected function getDriver()
    {
        $driver = $this->con ? $this->con->getAttribute(\PDO::ATTR_DRIVER_NAME) : null;

        if (null === $driver && $currentDSN = $this->getBuiltDsn()) {
            $driver = explode(':', $currentDSN)[0];
        }

        if (null === $driver && getenv('DATABASE')) {
            $driver = getenv('DATABASE');
        }

        return strtolower($driver);
    }
}
