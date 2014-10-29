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
 * Manager for single connection to a datasource.
 */
class ConnectionManagerSingle implements ConnectionManagerInterface, LoggerAwareInterface
{
    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * @var array
     */
    protected $configuration = array();

    protected $logger;

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $connection;

    protected $adapter;

    function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
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

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->setConfiguration(null);
        $this->connection = $connection;
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        $this->closeConnections();
    }

    /**
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection()
    {
        if (null === $this->connection) {
            $this->connection = ConnectionFactory::create($this->configuration, $this->adapter);
            if ($this->connection instanceof LoggerAwareInterface) {
                $this->connection->setLogger($this->logger);
            }
            $this->connection->setName($this->getName());
        }

        return $this->connection;
    }

    /**
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection()
    {
        return $this->getWriteConnection($this->adapter);
    }

    public function closeConnections()
    {
        $this->connection = null;
    }
}
