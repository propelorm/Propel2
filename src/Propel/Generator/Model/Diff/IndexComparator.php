<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Index;

/**
 * Service class for comparing Index objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class IndexComparator
{
    /**
     * Computes the difference between two index objects.
     *
     * @param  Index   $fromIndex
     * @param  Index   $toIndex
     * @param  boolean $caseInsensitive
     * @return boolean
     */
    public static function computeDiff(Index $fromIndex, Index $toIndex, $caseInsensitive = false)
    {
        // Check for removed index columns in $toIndex
        $fromIndexFields = $fromIndex->getFields();
        $max = count($fromIndexFields);
        for ($i = 0; $i < $max; $i++) {
            $indexField = $fromIndexFields[$i];
            if (!$toIndex->hasFieldAtPosition($i, $indexField, $fromIndex->getFieldSize($indexField), $caseInsensitive)) {
                return true;
            }
        }

        // Check for new index columns in $toIndex
        $toIndexFields = $toIndex->getFields();
        $max = count($toIndexFields);
        for ($i = 0; $i < $max; $i++) {
            $indexField = $toIndexFields[$i];
            if (!$fromIndex->hasFieldAtPosition($i, $indexField, $toIndex->getFieldSize($indexField), $caseInsensitive)) {
                return true;
            }
        }

        // Check for difference in unicity
        return $fromIndex->isUnique() !== $toIndex->isUnique();
    }
}
