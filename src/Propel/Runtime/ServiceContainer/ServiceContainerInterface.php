<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ServiceContainer;

use Propel\Runtime\Util\Profiler;
use Psr\Log\LoggerInterface;

interface ServiceContainerInterface
{
    /**
     * Constant used to request a READ connection (applies to replication).
     */
    const CONNECTION_READ = 'read';

    /**
     * Constant used to request a WRITE connection (applies to replication).
     */
    const CONNECTION_WRITE = 'write';

    /**
     * The default DatabaseMap class created by getDatabaseMap()
     */
    const DEFAULT_DATABASE_MAP_CLASS = '\Propel\Runtime\Map\DatabaseMap';

    /**
     * The name of the default datasource.
     */
    const DEFAULT_DATASOURCE_NAME = 'default';

    /**
     * The name of the default Profiler class created by getProfiler()
     */
    const DEFAULT_PROFILER_CLASS = '\Propel\Runtime\Util\Profiler';

    /**
     * @return string
     */
    public function getDefaultDatasource();

    /**
     * Get the adapter for a given datasource.
     *
     * If the adapter does not yet exist, build it using the related adapterClass.
     *
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAdapter($name = null);

    /**
     * Get the adapter class for a given datasource.
     *
     * @param string $name The datasource name
     *
     * @return string
     */
    public function getAdapterClass($name = null);

    /**
     * Get the database map for a given datasource.
     *
     * The database maps are "registered" by the generated map builder classes.
     *
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Map\DatabaseMap
     */
    public function getDatabaseMap($name = null);

    /**
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Connection\ConnectionManagerInterface
     */
    public function getConnectionManager($name);

    /**
     * @param string $name
     *
     * @return boolean true if a connectionManager with $name has been registered
     */
    public function hasConnectionManager($name);

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     */
    public function closeConnections();

    /**
     * Get a connection for a given datasource.
     *
     * If the connection has not been opened, open it using the related
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param string $name The datasource name
     * @param string $mode The connection mode (this applies to replication systems).
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getConnection($name = null, $mode = self::CONNECTION_WRITE);

    /**
     * Get a write connection for a given datasource.
     *
     * If the connection has not been opened, open it using the related
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param string $name The datasource name that is used to look up the DSN
     *                     from the runtime configuration file. Empty name not allowed.
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     *
     * @throws \Propel\Runtime\Adapter\Exception\AdapterException - if connection is not properly configured
     */
    public function getWriteConnection($name);

    /**
     * Get a read connection for a given datasource.
     *
     * If the slave connection has not been opened, open it using a random read connection
     * setting for the related datasource. If no read connection setting exist, return the master
     * connection. If the slave connection has already been opened, return it.
     *
     * @param string $name The datasource name that is used to look up the DSN
     *                     from the runtime configuration file. Empty name not allowed.
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getReadConnection($name);

    /**
     * Get a profiler instance.
     *
     * @return Profiler
     */
    public function getProfiler();

    /**
     * Get a logger for a given datasource, or the default logger.
     *
     * @param  string          $name
     * @return LoggerInterface
     */
    public function getLogger($name = 'defaultLogger');
}
