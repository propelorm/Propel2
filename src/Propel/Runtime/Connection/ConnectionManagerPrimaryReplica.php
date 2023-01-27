<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Adapter\AdapterInterface;

/**
 * Manager for primary/replica connection to a datasource.
 */
class ConnectionManagerPrimaryReplica implements ConnectionManagerInterface
{
    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * @var array
     */
    protected $writeConfiguration = [];

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface|null
     */
    protected $writeConnection;

    /**
     * @var array
     */
    protected $readConfiguration = [];

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface|null
     */
    protected $readConnection;

    /**
     * @var bool Whether a call to getReadConnection() always returns a write connection.
     */
    protected $isForcePrimaryConnection = false;

    /**
     * @param string $name The datasource name associated to this connection
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name The datasource name associated to this connection
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * For replication, whether to always force the use of a primary connection.
     *
     * @return bool
     */
    public function isForcePrimaryConnection(): bool
    {
        return $this->isForcePrimaryConnection;
    }

    /**
     * For replication, set whether to always force the use of a primary connection.
     *
     * @param bool $isForceMasterConnection
     *
     * @return void
     */
    public function setForcePrimaryConnection(bool $isForceMasterConnection): void
    {
        $this->isForcePrimaryConnection = $isForceMasterConnection;
    }

    /**
     * Set the configuration for the master (write) connection.
     *
     * <code>
     * $manager->setWriteConfiguration(array(
     *   'dsn' => 'mysql:dbname=test_master',
     *   'user' => 'mywriteuser',
     *   'password' => 'S3cr3t'
     * ));
     * </code>
     *
     * @param array $configuration
     *
     * @return void
     */
    public function setWriteConfiguration(array $configuration): void
    {
        $this->writeConfiguration = $configuration;
        $this->closeConnections();
    }

    /**
     * Set the configuration for the replica (read) connections.
     *
     * <code>
     * $manager->setReadConfiguration(array(
     *   array(
     *     'dsn' => 'mysql:dbname=test_replica1',
     *     'user' => 'myreaduser',
     *     'password' => 'F00baR'
     *   ),
     *   array(
     *     'dsn' => 'mysql:dbname=test_replica2',
     *     'user' => 'myreaduser',
     *     'password' => 'F00baR'
     *   )
     * ));
     * </code>
     *
     * @param array $configuration
     *
     * @return void
     */
    public function setReadConfiguration(array $configuration): void
    {
        $this->readConfiguration = $configuration;
        $this->closeConnections();
    }

    /**
     * Get a master connection.
     *
     * If no master connection exist yet, open it using the write configuration.
     *
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(?AdapterInterface $adapter = null): ConnectionInterface
    {
        if ($this->writeConnection === null) {
            $this->writeConnection = ConnectionFactory::create($this->writeConfiguration, $adapter);
            $this->writeConnection->setName($this->getName());
        }

        return $this->writeConnection;
    }

    /**
     * Get a replica connection.
     *
     * If no replica connection exist yet, choose one configuration randomly in the
     * read configuration to open it.
     *
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(?AdapterInterface $adapter = null): ConnectionInterface
    {
        if ($this->writeConnection && $this->writeConnection->inTransaction()) {
            return $this->writeConnection;
        }

        if ($this->isForcePrimaryConnection()) {
            return $this->getWriteConnection($adapter);
        }
        if ($this->readConnection === null) {
            if (!$this->readConfiguration) {
                $this->readConnection = $this->getWriteConnection($adapter);
            } else {
                $keys = array_keys($this->readConfiguration);
                $key = $keys[mt_rand(0, count($keys) - 1)];
                $configuration = $this->readConfiguration[$key];
                $this->readConnection = ConnectionFactory::create($configuration, $adapter);
                $this->readConnection->setName($this->getName());
            }
        }

        return $this->readConnection;
    }

    /**
     * @return void
     */
    public function closeConnections(): void
    {
        $this->writeConnection = null;
        $this->readConnection = null;
    }
}
