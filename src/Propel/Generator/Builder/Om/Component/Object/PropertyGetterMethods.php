<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Model\Field;

/**
 * Adds all getter methods for all entity fields. Excludes fields marked as implementationDetail.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PropertyGetterMethods extends BuildComponent
{
    public function process()
    {
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                // it's a implementation detail, we don't need to expose it to the domain model.
                continue;
            }
            if ($field->isSkipCodeGeneration()){
                continue;
            }

            $this->addFieldGetter($field);
        }
    }

    /**
     * Adds the getter methods for the field.
     *
     * @param Field $field
     */
    protected function addFieldGetter(Field $field)
    {
        $varName = $field->getName();
        $visibility = $field->getAccessorVisibility();

        $body = "
        return \$this->$varName;";

        $methodName = 'get' . ucfirst($field->getName());
        $this->addMethod($methodName, $visibility)
            ->setType($field->getPhpType())
            ->setDescription("Returns the value of $varName.")
            ->setBody($body);
    }
}