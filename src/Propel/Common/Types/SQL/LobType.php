<?php

namespace Propel\Common\Types\SQL;

use Propel\Common\Types\AbstractType;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

class LobType extends AbstractType
{
    public function convertToPHPValue($value, FieldMap $fieldMap)
    {
        if (is_resource($value)) {
            return $value;
        }

        return $value;
    }

    public function getPHPType(Field $field)
    {
        return 'resource';
    }

    public function snapshotPHPValue($value, FieldMap $fieldMap)
    {
        if (is_resource($value)) {
            rewind($value);
            $value = stream_get_contents($value);
        } else {
            $value = (string) $value;
        }

        return $value;
    }

    public function convertToDatabaseValue($value, FieldMap $fieldMap)
    {
        return $value;
    }
}