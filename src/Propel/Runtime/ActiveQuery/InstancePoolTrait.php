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
use Propel\Runtime\Exception\RuntimeException;

trait InstancePoolTrait
{
    private static $instances = array();

    public static function addInstanceToPool($object, $key = null)
    {
        if (Propel::isInstancePoolingEnabled()) {
            if (null === $key) {
                $key = (string) $object->getId();
            }

            self::$instances[$key] = $object;
        }
    }

    public static function removeInstanceFromPool($value)
    {
        if (Propel::isInstancePoolingEnabled() && null !== $value) {
            if (is_object($value)) {
                $key = (string) $value->getId();
            } elseif (is_scalar($value)) {
                // assume we've been passed a primary key
                $key = (string) $value;
            } else {
                throw new RuntimeException('Invalid value passed to removeInstanceFromPool()');
            }

            unset(self::$instances[$key]);
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
        self::$instances = array();
    }
}
