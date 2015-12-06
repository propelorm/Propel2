<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

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
        $methodName = 'get' . ucfirst($field->getName());
        $method = $this->addMethod($methodName, $visibility);

        $body = '';

        if ($field->isTemporalType()) {
                $body .= "
if (\$format && \$this->{$varName} instanceof \\DateTime) {
    return \$this->{$varName}->format(\$format);
}";
            $method->addSimpleParameter('format', 'string', null);
        }


        $body .= "
return \$this->$varName;";

        $method
            ->setType($field->getPhpType())
            ->setDescription("Returns the value of $varName.")
            ->setBody($body);
    }
}