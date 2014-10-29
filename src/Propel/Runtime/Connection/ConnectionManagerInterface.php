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

interface ConnectionManagerInterface
{
    /**
     * @param string $name The datasource name associated to this connection
     */
    public function setName($name);

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName();

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection();

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection();

    public function closeConnections();
}
