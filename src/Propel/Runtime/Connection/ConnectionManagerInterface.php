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
    public function setName(string $name): void;

    /**
     * @return string The datasource name associated to this connection
     */
    public function getName(): string;

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getWriteConnection(?AdapterInterface $adapter = null): ConnectionInterface;

    /**
     * @param \Propel\Runtime\Adapter\AdapterInterface|null $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection(?AdapterInterface $adapter = null): ConnectionInterface;

    /**
     * @return void
     */
    public function closeConnections(): void;
}
