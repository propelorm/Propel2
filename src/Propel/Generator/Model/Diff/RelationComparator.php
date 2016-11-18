<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Relation;

/**
 * Service class for comparing Relation objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 */
class RelationComparator
{
    /**
     * Compute the difference between two Foreign key objects
     *
     * @param Relation $fromFk
     * @param Relation $toFk
     *
     * @param boolean $caseInsensitive Whether the comparison is case insensitive.
     *                                 False by default.
     *
     * @return boolean false if the two fks are similar, true if they have differences
     */
    public static function computeDiff(Relation $fromFk, Relation $toFk, $caseInsensitive = false)
    {
        // Check for differences in local and remote table
        $test = $caseInsensitive ?
            strtolower($fromFk->getEntityName()) !== strtolower($toFk->getEntityName()) :
            $fromFk->getEntityName() !== $toFk->getEntityName()
        ;

        if ($test) {
            return true;
        }

        $test = $caseInsensitive ?
            strtolower($fromFk->getForeignEntityName()) !== strtolower($toFk->getForeignEntityName()) :
            $fromFk->getForeignEntityName() !== $toFk->getForeignEntityName()
        ;

        if ($test) {
            return true;
        }

        // compare columns
        $fromFkLocalFields = $fromFk->getLocalFields();
        sort($fromFkLocalFields);
        $toFkLocalFields = $toFk->getLocalFields();
        sort($toFkLocalFields);
        if (array_map('strtolower', $fromFkLocalFields) !== array_map('strtolower', $toFkLocalFields)) {
            return true;
        }
        $fromFkForeignFields = $fromFk->getForeignFields();
        sort($fromFkForeignFields);
        $toFkForeignFields = $toFk->getForeignFields();
        sort($toFkForeignFields);
        if (array_map('strtolower', $fromFkForeignFields) !== array_map('strtolower', $toFkForeignFields)) {
            return true;
        }

        // compare on
        if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) !== $toFk->normalizeFKey($toFk->getOnUpdate())) {
            return true;
        }
        if ($fromFk->normalizeFKey($fromFk->getOnDelete()) !== $toFk->normalizeFKey($toFk->getOnDelete())) {
            return true;
        }

        // compare skipSql
        return $fromFk->isSkipSql() !== $toFk->isSkipSql();
    }

}
