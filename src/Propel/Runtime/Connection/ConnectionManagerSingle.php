<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use InvalidArgumentException;
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
    protected $configuration = [];

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface|null
     */
    protected $connection;

    /**
     * @param string $name The datasource name associated to this connection
     *
     * @return void
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
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param \Propel\Runtime\Connection\ConnectionInterface $connection
     *
     * @return void
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->setConfiguration(null);
        $this->connection = $connection;
    }

    /**
     * @param array|null $configuration
     *
     * @return void
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = (array)$configuration;
        $this->closeConnections();
    }

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @throws \InvalidArgumentException
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(?AdapterInterface $adapter = null)
    {
        if ($this->connection === null) {
            if ($adapter === null) {
                throw new InvalidArgumentException('$adapter not given');
            }

            $this->connection = ConnectionFactory::create($this->configuration, $adapter);
            $this->connection->setName($this->getName());
        }

        return $this->connection;
    }

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(?AdapterInterface $adapter = null)
    {
        return $this->getWriteConnection($adapter);
    }

    /**
     * @return void
     */
    public function closeConnections()
    {
        $this->connection = null;
    }
}
