<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

class IntegerType extends AbstractType
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        if (null === $value) {
            return null;
        }

        return (int) $value;
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value;
        }

        if (null === $value) {
            return null;
        }

        return (int) $value;
    }
}