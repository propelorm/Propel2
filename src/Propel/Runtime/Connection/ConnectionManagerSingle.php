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
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Manager for single connection to a datasource.
 */
class ConnectionManagerSingle implements ConnectionManagerInterface
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $connection;

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
     * @param  \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(AdapterInterface $adapter)
    {
        if (null === $this->connection) {
            $this->connection = ConnectionFactory::create($this->configuration, $adapter);
        }

        return $this->connection;
    }

    /**
     * @param  \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(AdapterInterface $adapter)
    {
        return $this->getWriteConnection($adapter);
    }

    public function closeConnections()
    {
        $this->connection = null;
    }
}
