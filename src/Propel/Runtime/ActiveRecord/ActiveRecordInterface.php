<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Runtime\ActiveRecord;

/**
 * This ActiveRecord interface helps to find Propel Object
 *
 * @author jaugustin
 *
 * @method array toArray(string $keyType = \Propel\Runtime\Map\TableMap::TYPE_FIELDNAME, bool $includeLazyLoadColumns = true, array $alreadyDumpedObjects = [], bool $includeForeignObjects = false)
 * @method void initRelation(string $relationName)
 * @method self setVirtualColumn(string $name, $value)
 * @method int hydrate(array $row, int $startcol = 0, bool $rehydrate = false, string $indexType = \Propel\Runtime\Map\TableMap::TYPE_NUM)
 * @method mixed getPrimaryKey()
 */
interface ActiveRecordInterface
{
    /**
     * Returns true if the primary key for this object is null.
     *
     * @return bool
     */
    public function isPrimaryKeyNull(): bool;
}
