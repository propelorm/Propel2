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
    function getWriteConnection(AdapterInterface $adapter = null);
    function getReadConnection(AdapterInterface $adapter = null);
    function closeConnections();
}
