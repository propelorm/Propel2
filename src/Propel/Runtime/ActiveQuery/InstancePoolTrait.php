<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\ActiveQuery;

use Propel\Runtime\Propel;

trait InstancePoolTrait
{
    public static $instances = [];

    public static function addInstanceToPool($object, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = static::getInstanceKey($object);
            }

            self::$instances[$key] = $object;
        }
    }

    public static function getInstanceKey($value)
    {
        if (!($value instanceof Criteria) && is_object($value)) {
            if (count($pk = $value->getPrimaryKey()) > 1 || is_object($value->getPrimaryKey())) {
                $pk = serialize($pk);
            }

            return (string) $pk;
        } elseif (is_scalar($value)) {
            // assume we've been passed a primary key
            return (string) $value;
        }
    }

    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            $key = static::getInstanceKey($value);
            if ($key) {
                unset(self::$instances[$key]);
            } else {
                self::clearInstancePool();
            }
        }
    }

    public static function getInstanceFromPool($key)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (isset(self::$instances[$key])) {
                return self::$instances[$key];
            }
        }

        return null;
    }

    public static function clearInstancePool()
    {
        self::$instances = [];
    }

    public static function clearRelatedInstancePool()
    {
    }
}
