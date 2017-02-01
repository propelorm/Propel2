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

trait TableMapTrait
{
    /**
     * Returns an array of field names.
     *
     * @param  string          $type The type of fieldnames to return:
     *                               One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                               TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     * @return array           A list of field names
     * @throws PropelException
     */
    public static function getFieldNames($type = TableMap::TYPE_PHPNAME)
    {
        if (!array_key_exists($type, static::$fieldNames)) {
            throw new PropelException('Method getFieldNames() expects the parameter \$type to be one of the class constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM. ' . $type . ' was given.');
        }

        return static::$fieldNames[$type];
    }

    /**
     * Translates a fieldname to another type
     *
     * @param  string          $name     field name
     * @param  string          $fromType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                                   TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
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
        $newRow = [];
        foreach ($row as $name => $field) {
            if (isset(static::$fieldKeys[$fromType][$name])) {
                $newRow[$toNames[static::$fieldKeys[$fromType][$name]]] = $field;
            } else {
                $newRow[$name] = $field;
            }
        }

        return $newRow;
    }

    /**
     * Convenience method which changes table.column to alias.column.
     *
     * Using this method you can maintain SQL abstraction while using column aliases.
     * <code>
     *        $c->addAlias("alias1", TableTableMap::TABLE_NAME);
     *        $c->addJoin(TableTableMap::alias("alias1", TableTableMap::PRIMARY_KEY_COLUMN), TableTableMap::PRIMARY_KEY_COLUMN);
     * </code>
     * @param  string $alias  The alias for the current table.
     * @param  string $column The column name for current table. (i.e. BookTableMap::COLUMN_NAME).
     * @return string
     */
    public static function alias($alias, $column)
    {
        return str_replace(static::TABLE_NAME.'.', $alias.'.', $column);
    }
}
