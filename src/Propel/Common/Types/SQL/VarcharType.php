<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

class VarcharType extends AbstractType
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return null === $value ? null : (string) $value;
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value;
        }

        return null === $value ? null : (string) $value;
    }
}