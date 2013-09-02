<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Depending on this type we return the correct runninOn* results,
     * also getSql() is based on that.
     *
     * If $adapterClass is not available, the adapter type is extracted of this
     * connection.
     *
     * @var ConnectionInterface
     */
    protected $con;

    /**
     *
     * Depending on this type we return the correct runninOn* results,
     * also getSql() is based on that.
     *
     * @var string
     */
    protected $adapterClass = '';

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
     * @return string[]
     */
    protected function getDriver()
    {
        return $this->adapterClass
            ? $this->adapterClass
            :($this->con ? $this->con->getAttribute(\PDO::ATTR_DRIVER_NAME) : 'sqlite');
    }
}
