<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Runtime\Map\FieldMap;

class VarcharType extends AbstractType
{
    public function convertToPHPValue($value, FieldMap $fieldMap)
    {
        return (string) $value;
    }

    public function snapshotPHPValue($value, FieldMap $fieldMap)
    {
        return (string) $value;
    }

    public function convertToDatabaseValue($value, FieldMap $fieldMap)
    {
        return (string) $value;
    }
}