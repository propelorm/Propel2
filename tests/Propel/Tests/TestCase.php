<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Propel\Generator\Platform\PlatformInterface;

class TestCase extends PHPUnitTestCase
{
    /**
     * @return string
     */
    protected function getDriver()
    {
        return 'sqlite';
    }

    /**
     * Makes the sql compatible with the current database.
     * Means: replaces ` etc.
     *
     * @param string $sql
     * @param string $source
     * @param string|null $target
     *
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
        if ('pgsql' === $target && 'mysql' === $source) {
            return preg_replace('/`([^`]*)`/', '"$1"', $sql);
        }
        if ('mysql' !== $target && 'mysql' === $source) {
            return str_replace('`', '', $sql);
        }

        return $sql;
    }

    /**
     * Returns true if the current driver in the connection ($this->con) is $db.
     *
     * @param string $db
     *
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
    protected function getPlatform(): PlatformInterface
    {
        $className = sprintf('\\Propel\\Generator\\Platform\\%sPlatform', ucfirst($this->getDriver()));

        return new $className();
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $con
     *
     * @return \Propel\Generator\Reverse\SchemaParserInterface
     */
    protected function getParser($con)
    {
        $className = sprintf('\\Propel\\Generator\\Reverse\\%sSchemaParser', ucfirst($this->getDriver()));

        $obj = new $className($con);

        return $obj;
    }

    /**
     * Run private or preotected method.
     *
     * @see https://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit
     * 
     * @param object $obj Instance with protected or private methods
     * @param string $name Name of the protected or private method
     * @param array $args Argumens for method
     * @return mixed Result of method call
     */
    public function callMethod(object $obj, string $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
        }
        return $method->invokeArgs($obj, $args);
    }


    /**
     * Set private or preotected property
     * 
     * @param object $obj Instance with protected or private property
     * @param string $name Name of the protected or private property
     * @param mixed $value New value for property
     * @return void
     */
    public function setProperty(object $obj, string $name, $value): void
    {
        $reflection = new \ReflectionClass($obj);
        $property = $reflection->getProperty($name);
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $property->setAccessible(true);
        }
        $property->setValue($obj, $value);
    }
}
