<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

class ObjectType extends AbstractType
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value ? unserialize($value) : null;
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_string($value) && $value) {
            return $value;
        }

        return is_object($value) ? serialize($value) : null;
    }
}