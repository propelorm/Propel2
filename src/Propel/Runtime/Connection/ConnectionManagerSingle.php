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
 * Manager for single connection to a datasource.
 */
class ConnectionManagerSingle implements ConnectionManagerInterface
{
    /**
     * @var string The datasource name associated to this connection
     */
    protected $name;

    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $connection;

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
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(AdapterInterface $adapter = null)
    {
        if (null === $this->connection) {
            $this->connection = ConnectionFactory::create($this->configuration, $adapter);
            $this->connection->setName($this->getName());
        }

        return $this->connection;
    }

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(AdapterInterface $adapter = null)
    {
        return $this->getWriteConnection($adapter);
    }

    public function closeConnections()
    {
        $this->connection = null;
    }
}
