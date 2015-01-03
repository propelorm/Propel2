<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Map;

use Propel\Runtime\Exception\PropelException;

trait EntityMapTrait
{
    /**
     * Returns an array of field names.
     *
     * @param  string          $type The type of fieldnames to return:
     *                               One of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_COLNAME
     *                               EntityMap::TYPE_FULLCOLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM
     * @return array           A list of field names
     * @throws PropelException
     */
    public static function getFieldNames($type = EntityMap::TYPE_PHPNAME)
    {
        if (!array_key_exists($type, static::$fieldNames)) {
            throw new PropelException('Method getFieldNames() expects the parameter \$type to be one of the class constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_COLNAME, EntityMap::TYPE_FULLCOLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM. ' . $type . ' was given.');
        }

        return static::$fieldNames[$type];
    }

    /**
     * Translates a fieldname to another type
     *
     * @param  string          $name     field name
     * @param  string          $fromType One of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_COLNAME
     *                                   EntityMap::TYPE_FULLCOLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM
     * @param  string          $toType   One of the class type constants
     * @return string          translated name of the field.
     * @throws PropelException - if the specified name could not be found in the fieldname mappings.
     */
    public static function translateFieldName($name, $fromType, $toType)
    {
        $toNames = static::getFieldNames($toType);
        $key = isset(static::$fieldKeys[$fromType][$name]) ? static::$fieldKeys[$fromType][$name] : null;
        if (null === $key) {
            throw new PropelException("'$name' could not be found in the field names of type '$fromType'. These are: " . print_r(static::$fieldKeys[$fromType], true));
        }

        return $toNames[$key];
    }

    public static function translateFieldNames($row, $fromType, $toType)
    {
        $toNames = static::getFieldNames($toType);
        $newRow = array();
        foreach ($row as $name => $field) {
            if ($key = static::$fieldKeys[$fromType][$name]) {
                $newRow[$toNames[$key]] = $field;
            } else {
                $newRow[$name] = $field;
            }
        }

        return $newRow;
    }

    /**
     * Convenience method which changes entity.field to alias.field.
     *
     * Using this method you can maintain SQL abstraction while using field aliases.
     * <code>
     *        $c->addAlias("alias1", EntityEntityMap::TABLE_NAME);
     *        $c->addJoin(EntityEntityMap::alias("alias1", EntityEntityMap::PRIMARY_KEY_COLUMN), EntityEntityMap::PRIMARY_KEY_COLUMN);
     * </code>
     * @param  string $alias  The alias for the current entity.
     * @param  string $field The field name for current entity. (i.e. BookEntityMap::COLUMN_NAME).
     * @return string
     */
    public static function alias($alias, $field)
    {
        return str_replace(static::TABLE_NAME.'.', $alias.'.', $field);
    }
}
