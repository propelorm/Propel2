<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Model\Field;

/**
 * Adds all fields as class properties.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Properties extends BuildComponent
{
    use ComponentHelperTrait;

    public function process()
    {
        $entity = $this->getEntity();

        if (!$entity->isAlias()) {
            $this->addFieldAttributes();
        }
    }

    /**
     * Adds variables that store field values.
     */
    protected function addFieldAttributes()
    {
        $entity = $this->getEntity();

        foreach ($entity->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                // it's a implementation detail, we don't need to expose it to the domain model.
                continue;
            }

            if ($field->isSkipCodeGeneration()){
                continue;
            }

            $this->addFieldAttribute($field);
        }
    }

    /**
     * Adds comment about the attribute (variable) that stores field values.
     *
     * @param Field $field
     */
    protected function addFieldAttribute(Field $field)
    {
        if ($field->isTemporalType()) {
            $cpType = $this->getBuilder()->getBuildProperty('dateTimeClass');
            if (!$cpType) {
                $cpType = '\DateTime';
            }
        } else {
            $cpType = $field->getPhpType();
        }
        $clo = $field->getName();

        $description[] = "The value for the $clo field.";

        $defaultValue = null;
        if ($field->getDefaultValue()) {
            if ($field->getDefaultValue()->isExpression()) {
                $expression = $field->getDefaultValue()->getValue();
                $description[] = "Note: this field has a database default value of: (expression) $expression";
            } else {
                $defaultValue = $field->getDefaultValue()->getValue();
                if ($field->isPhpArrayType()) {
                    $defaultValue = $this->getDefaultValueString($field);
                    $defaultValue = substr($defaultValue, 1, -1);
                }
                $description[] = "Note: this field has a database default value of: '$defaultValue'";
            }
        }

        $this->addProperty($clo)
            ->setType($cpType)
            ->setDescription($description)
            ->setValue($defaultValue)
        ;

        if ($field->isPhpArrayType()) {
            $this->addProperty($clo.'_unserialized')
                ->setType('array')
                ->setDescription("The effective array $clo")
            ;
        }
    }
}
