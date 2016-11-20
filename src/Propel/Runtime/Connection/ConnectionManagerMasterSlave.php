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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Manager for master/slave connection to a datasource.
 */
class ConnectionManagerMasterSlave implements ConnectionManagerInterface, LoggerAwareInterface
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

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
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection()
    {
        if (null === $this->writeConnection) {
            $this->writeConnection = ConnectionFactory::create($this->writeConfiguration, $this->adapter);
            $this->writeConnection->setName($this->getName());
            if ($this->logger && $this->writeConnection instanceof LoggerAwareInterface) {
                $this->writeConnection->setLogger($this->logger);
            }
        }

        return $this->writeConnection;
    }

    /**
     * Get a slave connection.
     *
     * If no slave connection exist yet, choose one configuration randomly in the
     * read configuration to open it.
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection()
    {
        if ($this->isForceMasterConnection()) {
            return $this->getWriteConnection();
        }
        if (null === $this->readConnection) {
            if (null === $this->readConfiguration) {
                $this->readConnection = $this->getWriteConnection();
            } else {
                $keys = array_keys($this->readConfiguration);
                $key = $keys[mt_rand(0, count($keys) - 1)];
                $configuration = $this->readConfiguration[$key];
                $this->readConnection = ConnectionFactory::create($configuration, $this->adapter);
                $this->readConnection->setName($this->getName());
                if ($this->logger && $this->readConnection instanceof LoggerAwareInterface) {
                    $this->readConnection->setLogger($this->logger);
                }
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
