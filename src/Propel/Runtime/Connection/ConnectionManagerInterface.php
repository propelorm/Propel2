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

interface ConnectionManagerInterface
{
    /**
     * @param string $name The datasource name associated to this connection
     */
    function setName($name);

    /**
     * @return string The datasource name associated to this connection
     */
    function getName();

    /**
     * @param  \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    function getWriteConnection(AdapterInterface $adapter = null);

    /**
     * @param  \Propel\Runtime\Adapter\AdapterInterface $adapter
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    function getReadConnection(AdapterInterface $adapter = null);

    function closeConnections();
}