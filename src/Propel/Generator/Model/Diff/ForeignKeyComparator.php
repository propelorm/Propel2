<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Database;
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
     * @param \Propel\Generator\Model\Database $fromDatabase
     * @param \Propel\Generator\Model\ForeignKey $fromFk
     * @param \Propel\Generator\Model\Database $toDatabase
     * @param \Propel\Generator\Model\ForeignKey $toFk
     * @param bool $caseInsensitive Whether the comparison is case insensitive.
     * False by default.
     *
     * @return bool false if the two fks are similar, true if they have differences
     */
    public static function computeDiff(Database $fromDatabase, ForeignKey $fromFk, Database $toDatabase, ForeignKey $toFk, $caseInsensitive = false)
    {
        // Check for differences in local and remote table
        $test = $caseInsensitive ?
            strtolower($fromFk->getTableName()) !== strtolower($toFk->getTableName()) :
            $fromFk->getTableName() !== $toFk->getTableName();

        if ($test) {
            return true;
        }

        $test = $caseInsensitive ?
            strtolower($fromFk->getForeignTableName()) !== strtolower($toFk->getForeignTableName()) :
            $fromFk->getForeignTableName() !== $toFk->getForeignTableName();

        if ($test) {
            return true;
        }

        // compare columns
        $fromFkLocalColumns = $fromFk->getLocalColumns();
        sort($fromFkLocalColumns);
        $toFkLocalColumns = $toFk->getLocalColumns();
        sort($toFkLocalColumns);
        if (array_map('strtolower', $fromFkLocalColumns) !== array_map('strtolower', $toFkLocalColumns)) {
            return true;
        }
        $fromFkForeignColumns = $fromFk->getForeignColumns();
        sort($fromFkForeignColumns);
        $toFkForeignColumns = $toFk->getForeignColumns();
        sort($toFkForeignColumns);
        if (array_map('strtolower', $fromFkForeignColumns) !== array_map('strtolower', $toFkForeignColumns)) {
            return true;
        }

        // compare on
        if ($fromFk->normalizeFKey($fromFk->getOnUpdate(), $fromDatabase->getPlatform()->getDefaultForeignKeyOnUpdateBehavior()) !== $toFk->normalizeFKey($toFk->getOnUpdate(), $toDatabase->getPlatform()->getDefaultForeignKeyOnUpdateBehavior())) {
            return true;
        }
        if ($fromFk->normalizeFKey($fromFk->getOnDelete(), $fromDatabase->getPlatform()->getDefaultForeignKeyOnDeleteBehavior()) !== $toFk->normalizeFKey($toFk->getOnDelete(), $toDatabase->getPlatform()->getDefaultForeignKeyOnDeleteBehavior())) {
            return true;
        }

        // compare skipSql
        return $fromFk->isSkipSql() !== $toFk->isSkipSql();
    }
}
