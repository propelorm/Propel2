<?php

namespace Propel\Common\Types\SQL;

use gossi\codegen\model\PhpConstant;
use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

class ArrayType extends AbstractType implements BuildableFieldTypeInterface
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value ? explode(' | ', $value) : [];
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        if (is_array($value)) {
            return $value ? '| ' . implode(' | ', $value) . ' |' : null;
        }

        return $value;
    }

    public function build(AbstractBuilder $builder, Field $field)
    {
        if ($builder instanceof ObjectBuilder) {
            $property = $builder->getDefinition()->getProperty($field->getName());

            if (!$property->hasValue()) {
                $property->setValue(PhpConstant::create('[]'));
            }
        }
    }
}