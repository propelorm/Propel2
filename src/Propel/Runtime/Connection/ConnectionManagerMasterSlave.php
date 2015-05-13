<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Adapter\AdapterInterface;

/**
 * Manager for master/slave connection to a datasource.
 */
class ConnectionManagerMasterSlave implements ConnectionManagerInterface
{
    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * @var array
     */
    protected $writeConfiguration = array();

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $writeConnection;

    /**
     * @var array
     */
    protected $readConfiguration;

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $readConnection;

    /**
     * @var boolean Whether a call to getReadConnection() always returns a write connection.
     */
    protected $isForceMasterConnection = false;

    /**
     * @param string $name The datasource name associated to this connection
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * For replication, whether to always force the use of a master connection.
     *
     * @return boolean
     */
    public function isForceMasterConnection()
    {
        return $this->isForceMasterConnection;
    }

    /**
     * For replication, set whether to always force the use of a master connection.
     *
     * @param boolean $isForceMasterConnection
     */
    public function setForceMasterConnection($isForceMasterConnection)
    {
        $this->isForceMasterConnection = (bool) $isForceMasterConnection;
    }

    /**
     * Set the configuration for the master (write) connection.
     *
     * <code>
     * $manager->setWriteConfiguration(array(
     *   'dsn'      => 'mysql:dbname=test_master',
     *   'user'     => 'mywriteuser',
     *   'password' => 'S3cr3t'
     * ));
     * </code>
     *
     * @param array $configuration
     */
    public function setWriteConfiguration($configuration)
    {
        $this->writeConfiguration = $configuration;
        $this->closeConnections();
    }

    /**
     * Set the configuration for the slave (read) connections.
     *
     * <code>
     * $manager->setReadConfiguration(array(
     *   array(
     *     'dsn'      => 'mysql:dbname=test_slave1',
     *     'user'     => 'myreaduser',
     *     'password' => 'F00baR'
     *   ),
     *   array(
     *     'dsn'      => 'mysql:dbname=test_slave2',
     *     'user'     => 'myreaduser',
     *     'password' => 'F00baR'
     *   )
     * ));
     * </code>
     *
     * @param array $configuration
     */
    public function setReadConfiguration($configuration)
    {
        $this->readConfiguration = $configuration;
        $this->closeConnections();
    }

    /**
     * Get a master connection.
     *
     * If no master connection exist yet, open it using the write configuration.
     *
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(AdapterInterface $adapter = null)
    {
        if (null === $this->writeConnection) {
            $this->writeConnection = ConnectionFactory::create($this->writeConfiguration, $adapter);
            $this->writeConnection->setName($this->getName());
        }

        return $this->writeConnection;
    }

    /**
     * Get a slave connection.
     *
     * If no slave connection exist yet, choose one configuration randomly in the
     * read configuration to open it.
     *
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(AdapterInterface $adapter = null)
    {
        if ($this->writeConnection && $this->writeConnection->inTransaction()) {
            return $this->writeConnection;
        }

        if ($this->isForceMasterConnection()) {
            return $this->getWriteConnection($adapter);
        }
        if (null === $this->readConnection) {
            if (null === $this->readConfiguration) {
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

    public function closeConnections()
    {
        $this->writeConnection = null;
        $this->readConnection = null;
    }
}
