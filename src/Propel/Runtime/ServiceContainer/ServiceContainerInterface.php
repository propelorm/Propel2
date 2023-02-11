<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ServiceContainer;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Connection\ConnectionManagerInterface;
use Propel\Runtime\Map\DatabaseMap;
use Propel\Runtime\Util\Profiler;
use Psr\Log\LoggerInterface;

interface ServiceContainerInterface
{
    /**
     * Constant used to request a READ connection (applies to replication).
     *
     * @var string
     */
    public const CONNECTION_READ = 'read';

    /**
     * Constant used to request a WRITE connection (applies to replication).
     *
     * @var string
     */
    public const CONNECTION_WRITE = 'write';

    /**
     * The default DatabaseMap class created by getDatabaseMap()
     *
     * @var string
     */
    public const DEFAULT_DATABASE_MAP_CLASS = DatabaseMap::class;

    /**
     * The name of the default datasource.
     *
     * @var string
     */
    public const DEFAULT_DATASOURCE_NAME = 'default';

    /**
     * The name of the default Profiler class created by getProfiler()
     *
     * @var string
     */
    public const DEFAULT_PROFILER_CLASS = Profiler::class;

    /**
     * @return string
     */
    public function getDefaultDatasource(): string;

    /**
     * Get the adapter for a given datasource.
     *
     * If the adapter does not yet exist, build it using the related adapterClass.
     *
     * @param string|null $name The datasource name
     *
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAdapter(?string $name = null): AdapterInterface;

    /**
     * Get the adapter class for a given datasource.
     *
     * @param string|null $name The datasource name
     *
     * @return string
     */
    public function getAdapterClass(?string $name = null): string;

    /**
     * Get the database map for a given datasource.
     *
     * The database maps are "registered" by the generated map builder classes.
     *
     * @param string|null $name The datasource name
     *
     * @return \Propel\Runtime\Map\DatabaseMap
     */
    public function getDatabaseMap(?string $name = null): DatabaseMap;

    /**
     * @param string $name The datasource name
     *
     * @return \Propel\Runtime\Connection\ConnectionManagerInterface
     */
    public function getConnectionManager(string $name): ConnectionManagerInterface;

    /**
     * @param string $name
     *
     * @return bool true if a connectionManager with $name has been registered
     */
    public function hasConnectionManager(string $name): bool;

    /**
     * Close any associated resource handles.
     *
     * This method frees any database connection handles that have been
     * opened by the getConnection() method.
     *
     * @return void
     */
    public function closeConnections(): void;

    /**
     * Get a connection for a given datasource.
     *
     * If the connection has not been opened, open it using the related
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param string|null $name The datasource name
     * @param string $mode The connection mode (this applies to replication systems).
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getConnection(?string $name = null, string $mode = self::CONNECTION_WRITE): ConnectionInterface;

    /**
     * Get a write connection for a given datasource.
     *
     * If the connection has not been opened, open it using the related
     * connectionSettings. If the connection has already been opened, return it.
     *
     * @param string $name The datasource name that is used to look up the DSN
     *                     from the runtime configuration file. Empty name not allowed.
     *
     * @throws \Propel\Runtime\Adapter\Exception\AdapterException - if connection is not properly configured
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface A database connection
     */
    public function getWriteConnection(string $name): ConnectionInterface;

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
    public function getReadConnection(string $name): ConnectionInterface;

    /**
     * Get a profiler instance.
     *
     * @return \Propel\Runtime\Util\Profiler
     */
    public function getProfiler(): Profiler;

    /**
     * Get a logger for a given datasource, or the default logger.
     *
     * @param string|null $name
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(?string $name = null): LoggerInterface;

    /**
     * Initialize the internal database maps array
     *
     * @param array $databaseNameToTableMapClassNames
     *
     * @return void
     */
    public function initDatabaseMaps(array $databaseNameToTableMapClassNames = []): void;
}
