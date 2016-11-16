<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

class BooleanType extends AbstractType
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value;
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        return $value;
    }
}