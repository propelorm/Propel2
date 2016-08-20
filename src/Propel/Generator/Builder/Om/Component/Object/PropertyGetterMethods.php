<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Field;

/**
 * Adds all getter methods for all entity fields. Excludes fields marked as implementationDetail.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PropertyGetterMethods extends BuildComponent
{
    use NamingTrait;

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
        $methodName = 'get' . $field->getMethodName();

        $method = $this->addMethod($methodName, $visibility);

        $body = '';

        if ($field->isTemporalType()) {
                $body .= "
if (\$format && \$this->{$varName} instanceof \\DateTime) {
    return \$this->{$varName}->format(\$format);
}";
            $method->addSimpleParameter('format', 'string', null);
        } else if ($field->isBooleanType()) {
            $body .= "
//Sometimes the default value is not a boolean
if (!is_bool(\$this->$varName)) {
    \$this->set{$field->getMethodName()}(\$this->$varName);
}
";
        }

        $body .= "
return \$this->$varName;";

        if ($field->isPhpArrayType()) {
            $clo = $field->getName();
            $cloUnserialized = $clo.'_unserialized';
            $body = "
if (null === \$this->$cloUnserialized) {
    \$this->$cloUnserialized = [];
}
if (!\$this->$cloUnserialized && null !== \$this->$clo) {
    \$$cloUnserialized = substr(\$this->$clo, 2, -2);
    \$this->$cloUnserialized = \$$cloUnserialized ? explode(' | ', \$$cloUnserialized) : [];
}

return \$this->$cloUnserialized;
";
        }

        if ($field->isEnumType()) {
            $body = "
if (null === \$this->$varName) {
    return null;
}

//Default values can be strings, so they must be fixed by setter method
if (!is_int(\$this->$varName)) {
    \$this->set{$field->getMethodName()}(\$this->$varName);
}

{$this->getRepositoryAssignment()}
\$valueSet = \$repository->getEntityMap()->getField('{$field->getName()}')->getValueSet();
if (!isset(\$valueSet[\$this->$varName])) {
    throw new PropelException('Unknown stored enum key: ' . \$this->$varName);
}

return \$valueSet[\$this->$varName];
";
        }

        $method
            ->setType($field->getPhpType())
            ->setDescription("Returns the value of $varName.")
            ->setBody($body);
    }
}
