<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Adapter\AdapterInterface;

/**
 * Manager for master/slave connection to a datasource.
 */
class ConnectionManagerMasterSlave implements ConnectionManagerInterface
{

    /**
     * @var array
     */
    protected $writeConfiguration;

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
     * @var boolean Whether a call to getReadConnection() alsways returns a write connection.
     */
    protected $isForceMasterConnection = false;

    /**
     * For replication, whether to always force the use of a master connection.
     *
     * @return     boolean
     */
    public function isForceMasterConnection()
    {
        return $this->isForceMasterConnection;
    }

    /**
     * For replication, set whether to always force the use of a master connection.
     *
     * @param      boolean $isForceMasterConnection
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
     * @param  \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(AdapterInterface $adapter)
    {
        if (null === $this->writeConnection) {
            $this->writeConnection = ConnectionFactory::create($this->writeConfiguration, $adapter);
        }
        return $this->writeConnection;
    }

    /**
     * Get a slave connection.
     * 
     * If no slave connection exist yet, choose one configuration randomly in the 
     * read configuration to open it.
     *
     * @param  \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(AdapterInterface $adapter)
    {
        if ($this->isForceMasterConnection()) {
            return $this->getWriteConnection($adapter);
        }
        if (null === $this->readConnection) {
            if (null === $this->readConfiguration) {
                $this->readConnection = $this->getWriteConnection($adapter);
            } else {
                $configuration = array_rand($this->readConfiguration);
                $this->readConnection = ConnectionFactory::create($configuration, $adapter);
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