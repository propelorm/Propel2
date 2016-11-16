<?php

namespace Propel\Common\Types\SQL;

use gossi\codegen\model\PhpConstant;
use Propel\Common\Types\AbstractType;
use Propel\Common\Types\BuildableFieldTypeInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Field;
use Propel\Runtime\Map\FieldMap;

class EnumType extends AbstractType implements BuildableFieldTypeInterface
{
    public function databaseToProperty($value, FieldMap $fieldMap)
    {
        return $value;
    }

    public function propertyToDatabase($value, FieldMap $fieldMap)
    {
        return $value;
    }

    public function build(AbstractBuilder $builder, Field $field)
    {
        if ($builder instanceof ObjectBuilder) {
            $types = [];

            foreach ($field->getValueSet() as $valueSet) {
                $constName = strtoupper($field->getName() . '_type_' . $valueSet);
                $constName = preg_replace('/[^a-zA-z0-9_]+/', '_', $constName);

                $types[] = 'self::' . $constName;
                $constant = PhpConstant::create($constName, $valueSet);
                $constant->setType('string');
                $builder->getDefinition()->setConstant($constant);
            }

            $all = '[' . implode(', ', $types) . ']';
            $allConstName = strtoupper($field->getName() . '_types');
            $constant = PhpConstant::create($allConstName, $all, true);
            $constant->setType('array|string[]');
            $builder->getDefinition()->setConstant($constant);
        }
    }

}