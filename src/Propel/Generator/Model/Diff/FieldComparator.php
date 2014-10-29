<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Field;

/**
 * Service class for comparing Field objects.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class FieldComparator
{
    /**
     * Compute and return the difference between two column objects
     *
     * @param  Field             $fromField
     * @param  Field             $toField
     * @return FieldDiff|boolean return false if the two columns are similar
     */
    public static function computeDiff(Field $fromField, Field $toField)
    {
        if ($changedProperties = self::compareFields($fromField, $toField)) {
            if ($fromField->hasPlatform() || $toField->hasPlatform()) {
                $platform = $fromField->hasPlatform() ? $fromField->getPlatform() : $toField->getPlatform();
                if ($platform->getFieldDDL($fromField) == $platform->getFieldDDl($toField)) {
                    return false;
                }
            }
            $columnDiff = new FieldDiff($fromField, $toField);
            $columnDiff->setChangedProperties($changedProperties);

            return $columnDiff;
        }

        return false;
    }

    public static function compareFields(Field $fromField, Field $toField)
    {
        $changedProperties = [];

        // compare column types
        $fromDomain = $fromField->getDomain();
        $toDomain = $toField->getDomain();

        if ($fromDomain->getScale() !== $toDomain->getScale()) {
            $changedProperties['scale'] = [ $fromDomain->getScale(), $toDomain->getScale() ];
        }
        if ($fromDomain->getSize() !== $toDomain->getSize()) {
            $changedProperties['size'] = [ $fromDomain->getSize(), $toDomain->getSize() ];
        }

        if (strtoupper($fromDomain->getSqlType()) !== strtoupper($toDomain->getSqlType())) {
            $changedProperties['sqlType'] = [ $fromDomain->getSqlType(), $toDomain->getSqlType() ];

            if ($fromDomain->getType() !== $toDomain->getType()) {
                $changedProperties['type'] = [ $fromDomain->getType(), $toDomain->getType() ];
            }
        }

        if ($fromField->isNotNull() !== $toField->isNotNull()) {
            $changedProperties['notNull'] = [ $fromField->isNotNull(), $toField->isNotNull() ];
        }

        // compare column default value
        $fromDefaultValue = $fromField->getDefaultValue();
        $toDefaultValue = $toField->getDefaultValue();
        if ($fromDefaultValue && !$toDefaultValue) {
            $changedProperties['defaultValueType'] = [ $fromDefaultValue->getType(), null ];
            $changedProperties['defaultValueValue'] = [ $fromDefaultValue->getValue(), null ];
        } elseif (!$fromDefaultValue && $toDefaultValue) {
            $changedProperties['defaultValueType'] = [ null, $toDefaultValue->getType() ];
            $changedProperties['defaultValueValue'] = [ null, $toDefaultValue->getValue() ];
        } elseif ($fromDefaultValue && $toDefaultValue) {
            if (!$fromDefaultValue->equals($toDefaultValue)) {
                if ($fromDefaultValue->getType() !== $toDefaultValue->getType()) {
                    $changedProperties['defaultValueType'] = [ $fromDefaultValue->getType(), $toDefaultValue->getType() ];
                }
                if ($fromDefaultValue->getValue() !== $toDefaultValue->getValue()) {
                    $changedProperties['defaultValueValue'] = [ $fromDefaultValue->getValue(), $toDefaultValue->getValue() ];
                }
            }
        }

        if ($fromField->isAutoIncrement() !== $toField->isAutoIncrement()) {
            $changedProperties['autoIncrement'] = [ $fromField->isAutoIncrement(), $toField->isAutoIncrement() ];
        }

        return $changedProperties;
    }
}
