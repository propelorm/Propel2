<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveQuery;

use Countable;
use Propel\Runtime\Propel;

trait InstancePoolTrait
{
    /**
     * @var object[]
     */
    public static $instances = [];

    /**
     * @param object $object
     * @param string|null $key
     *
     * @return void
     */
    public static function addInstanceToPool($object, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if ($key === null) {
                $key = static::getInstanceKey($object);
            }

            self::$instances[$key] = $object;
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public static function getInstanceKey($value)
    {
        if (!($value instanceof Criteria) && is_object($value)) {
            $pk = $value->getPrimaryKey();
            if (
                ((is_array($pk) || $pk instanceof Countable) && count($pk) > 1)
                || is_object($pk)
            ) {
                $pk = serialize($pk);
            }

            return (string)$pk;
        }

        if (is_scalar($value)) {
            // assume we've been passed a primary key
            return (string)$value;
        }
    }

    /**
     * @param mixed $value
     *
     * @return void
     */
    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && $value !== null) {
            $key = static::getInstanceKey($value);
            if ($key) {
                unset(self::$instances[$key]);
            } else {
                self::clearInstancePool();
            }
        }
    }

    /**
     * @param string $key
     *
     * @return object|null
     */
    public static function getInstanceFromPool($key)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (isset(self::$instances[$key])) {
                return self::$instances[$key];
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public static function clearInstancePool()
    {
        self::$instances = [];
    }

    /**
     * @return void
     */
    public static function clearRelatedInstancePool()
    {
    }
}
