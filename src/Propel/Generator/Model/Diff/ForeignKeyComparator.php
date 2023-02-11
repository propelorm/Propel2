<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;

/**
 * Service class for comparing ForeignKey objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class ForeignKeyComparator
{
    /**
     * Compute the difference between two Foreign key objects
     *
     * @param \Propel\Generator\Model\ForeignKey $fromFk
     * @param \Propel\Generator\Model\ForeignKey $toFk
     * @param bool $caseInsensitive Whether the comparison is case insensitive.
     * False by default.
     *
     * @return bool false if the two fks are similar, true if they have differences
     */
    public static function computeDiff(ForeignKey $fromFk, ForeignKey $toFk, bool $caseInsensitive = false): bool
    {
        // Check for differences in local and remote table
        $fromDifferentTable = $caseInsensitive ?
            strtolower($fromFk->getTableName()) !== strtolower($toFk->getTableName()) :
            $fromFk->getTableName() !== $toFk->getTableName();

        if ($fromDifferentTable) {
            return true;
        }

        $toDifferentTable = $caseInsensitive ?
            strtolower($fromFk->getForeignTableName() ?? '') !== strtolower($toFk->getForeignTableName() ?? '') :
            $fromFk->getForeignTableName() !== $toFk->getForeignTableName();

        if ($toDifferentTable) {
            return true;
        }

        // compare columns
        if (
            !static::stringArrayEqualsCaseInsensitive($fromFk->getLocalColumns(), $toFk->getLocalColumns())
            || !static::stringArrayEqualsCaseInsensitive($fromFk->getForeignColumns(), $toFk->getForeignColumns())
            || !static::columnTypesEquals($fromFk->getLocalColumnObjects(), $toFk->getLocalColumnObjects())
            || !static::columnTypesEquals($fromFk->getForeignColumnObjects(), $toFk->getForeignColumnObjects())
        ) {
            return true;
        }

        // compare on
        $onUpdateBehaviorInFrom = $fromFk->getOnUpdateWithDefault();
        $onUpdateBehaviorInTo = $toFk->getOnUpdateWithDefault();
        if ($onUpdateBehaviorInFrom !== $onUpdateBehaviorInTo) {
            return true;
        }
        $onDeleteBehaviorInFrom = $fromFk->getOnDeleteWithDefault();
        $onDeleteBehaviorInTo = $toFk->getOnDeleteWithDefault();
        if ($onDeleteBehaviorInFrom !== $onDeleteBehaviorInTo) {
            return true;
        }

        // compare skipSql
        return $fromFk->isSkipSql() !== $toFk->isSkipSql();
    }

    /**
     * @param array $array1
     * @param array $array2
     *
     * @return bool
     */
    protected static function stringArrayEqualsCaseInsensitive(array $array1, array $array2): bool
    {
        sort($array1);
        sort($array2);

        return array_map('strtolower', $array1) === array_map('strtolower', $array2);
    }

    /**
     * @param array $columns1
     * @param array $columns2
     *
     * @return bool
     */
    protected static function columnTypesEquals(array $columns1, array $columns2): bool
    {
        $byNameSorter = fn (Column $column1, Column $column2) => strcmp($column1->getName(), $column2->getName());
        usort($columns1, $byNameSorter);
        usort($columns2, $byNameSorter);

        $toSqlTypeNameMapper = fn (Column $column) => $column->getSqlType();
        $columnTypes1 = array_map($toSqlTypeNameMapper, $columns1);
        $columnTypes2 = array_map($toSqlTypeNameMapper, $columns2);

        return $columnTypes1 === $columnTypes2;
    }
}
