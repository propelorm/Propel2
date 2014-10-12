<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds all setter methods for all entity fields. Excludes fields marked as implementationDetail.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PropertySetterMethods extends BuildComponent
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

            $this->addFieldSetter($field);
        }
    }

    /**
     * Adds the setter methods for the field.
     *
     * @param Field $field
     */
    protected function addFieldSetter(Field $field)
    {
        $varName = $field->getName();
        $visibility = $field->getAccessorVisibility();
        $className = $this->getObjectClassName();

        $varType = $field->getPhpType();

        switch (strtoupper($field->getType())) {
            case PropelTypes::DATE:
            case PropelTypes::TIME:
                $dateTimeClass = $this->getBuilder()->getBuildProperty('dateTimeClass');
                if (!$dateTimeClass) {
                    $dateTimeClass = '\DateTime';
                }
                $varType = 'integer|' . $dateTimeClass;

                $body = "\$this->$varName = \\Propel\\Runtime\\Util\\PropelDateTime::newInstance(\$$varName, null, '$dateTimeClass');";
                break;
            default:
                $body = "\$this->$varName = \$$varName;";
        }

        $methodName = 'set' . ucfirst($field->getName());

        $method = $this->addMethod($methodName, $visibility)
            ->setType($className . '|$this')
            ->setDescription("Sets the value of $varName.")
            ->setBody($body);

        if ($field->isNotNull()) {
            $method->addSimpleParameter($varName, $varType);
        } else {
            $method->addSimpleParameter($varName, $varType, null);
        }
    }
}