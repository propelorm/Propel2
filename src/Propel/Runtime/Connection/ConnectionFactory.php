<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\Connection;

use Propel\Runtime\Adapter\AdapterInterface;
use Propel\Runtime\Adapter\Exception\AdapterException;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Propel\Runtime\Exception\InvalidArgumentException;

class ConnectionFactory
{
    public const DEFAULT_CONNECTION_CLASS = '\Propel\Runtime\Connection\ConnectionWrapper';

    /**
     * Open a database connection based on a configuration.
     *
     * @param array $configuration
     * @param \Propel\Runtime\Adapter\AdapterInterface $adapter
     * @param string $defaultConnectionClass
     *
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     * @throws Exception\ConnectionException
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public static function create(array $configuration, AdapterInterface $adapter, $defaultConnectionClass = self::DEFAULT_CONNECTION_CLASS)
    {
        if (isset($configuration['classname'])) {
            $connectionClass = $configuration['classname'];
        } else {
            $connectionClass = $defaultConnectionClass;
        }
        try {
            $adapterConnection = $adapter->getConnection($configuration);
        } catch (AdapterException $e) {
            throw new ConnectionException('Unable to open connection', 0, $e);
        }
        /** @var \Propel\Runtime\Connection\ConnectionInterface $connection */
        $connection = new $connectionClass($adapterConnection);

        // load any connection options from the config file
        // connection attributes are those PDO flags that have to be set on the initialized connection
        if (isset($configuration['attributes']) && is_array($configuration['attributes'])) {
            foreach ($configuration['attributes'] as $option => $value) {
                if (is_string($value) && strpos($value, '::') !== false) {
                    if (!defined($value)) {
                        throw new InvalidArgumentException(sprintf('Invalid class constant specified "%s" while processing connection attributes for datasource "%s"', $value, $connection->getName()));
                    }
                    $value = constant($value);
                }
                $connection->setAttribute($option, $value);
            }
        }

        return $connection;
    }
}
