<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Column;

/**
 * Service class for comparing Column objects.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class ColumnComparator
{
    /**
     * Compute and return the difference between two column objects
     *
     * @param Column $fromColumn
     * @param Column $toColumn
     * @return ColumnDiff|boolean return false if the two columns are similar
     */
    static public function computeDiff(Column $fromColumn, Column $toColumn)
    {
        if ($changedProperties = self::compareColumns($fromColumn, $toColumn)) {
            if ($fromColumn->hasPlatform() || $toColumn->hasPlatform()) {
                $platform = $fromColumn->hasPlatform() ? $fromColumn->getPlatform() : $toColumn->getPlatform();
                if ($platform->getColumnDDL($fromColumn) == $platform->getColumnDDl($toColumn)) {
                    return false;
                }
            }
            $columnDiff = new ColumnDiff();
            $columnDiff->setFromColumn($fromColumn);
            $columnDiff->setToColumn($toColumn);
            $columnDiff->setChangedProperties($changedProperties);

            return $columnDiff;
        }

        return false;
    }

    static function compareColumns(Column $fromColumn, Column $toColumn)
    {
        $changedProperties = array();

        // compare column types
        $fromDomain = $fromColumn->getDomain();
        $toDomain = $toColumn->getDomain();
        if ($fromDomain->getType() !== $toDomain->getType()) {
            $changedProperties['type'] = array($fromDomain->getType(), $toDomain->getType());
        }
        if ($fromDomain->getScale() !== $toDomain->getScale()) {
            $changedProperties['scale'] = array($fromDomain->getScale(), $toDomain->getScale());
        }
        if ($fromDomain->getSize() !== $toDomain->getSize()) {
            $changedProperties['size'] = array($fromDomain->getSize(), $toDomain->getSize());
        }
        if (strtoupper($fromDomain->getSqlType()) !== strtoupper($toDomain->getSqlType())) {
            $changedProperties['sqlType'] = array($fromDomain->getSqlType(), $toDomain->getSqlType());
        }
        if ($fromColumn->isNotNull() !== $toColumn->isNotNull()) {
            $changedProperties['notNull'] = array($fromColumn->isNotNull(), $toColumn->isNotNull());
        }

        // compare column default value
        $fromDefaultValue = $fromColumn->getDefaultValue();
        $toDefaultValue = $toColumn->getDefaultValue();
        if ($fromDefaultValue && !$toDefaultValue) {
            $changedProperties['defaultValueType'] = array($fromDefaultValue->getType(), null);
            $changedProperties['defaultValueValue'] = array($fromDefaultValue->getValue(), null);
        } elseif (!$fromDefaultValue && $toDefaultValue) {
            $changedProperties['defaultValueType'] = array(null, $toDefaultValue->getType());
            $changedProperties['defaultValueValue'] = array(null, $toDefaultValue->getValue());
        } elseif ($fromDefaultValue && $toDefaultValue) {
            if (!$fromDefaultValue->equals($toDefaultValue)) {
                if ($fromDefaultValue->getType() !== $toDefaultValue->getType()) {
                    $changedProperties['defaultValueType'] = array($fromDefaultValue->getType(), $toDefaultValue->getType());
                }
                if ($fromDefaultValue->getValue() !== $toDefaultValue->getValue()) {
                    $changedProperties['defaultValueValue'] = array($fromDefaultValue->getValue(), $toDefaultValue->getValue());
                }
            }
        }

        if ($fromColumn->isAutoIncrement() !== $toColumn->isAutoIncrement()) {
            $changedProperties['autoIncrement'] = array($fromColumn->isAutoIncrement(), $toColumn->isAutoIncrement());
        }

        return $changedProperties;
    }
}
