<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

class ArrayType extends AbstractType
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value ? explode(' | ', $value) : [];
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value ? '| ' . implode(' | ', $value) . ' |' : '';
        }

        return $value;
    }
}