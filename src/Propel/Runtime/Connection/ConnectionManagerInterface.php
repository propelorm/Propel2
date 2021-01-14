<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Adapter\AdapterInterface;

interface ConnectionManagerInterface
{
    /**
     * @param string $name The datasource name associated to this connection
     *
     * @return void
     */
    public function setName($name);

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName();

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(?AdapterInterface $adapter = null);

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(?AdapterInterface $adapter = null);

    /**
     * @return void
     */
    public function closeConnections();
}
