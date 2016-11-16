<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Field;

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
        $body = '';

        if ($field->isTemporalType()) {
            $dateTimeClass = $this->getBuilder()->getBuildProperty('dateTimeClass');
            if (!$dateTimeClass) {
                $dateTimeClass = '\DateTime';
            }
            $varType = 'integer|' . $dateTimeClass;

            $body = "\$$varName = \\Propel\\Runtime\\Util\\PropelDateTime::newInstance(\$$varName, null, '$dateTimeClass');";
        } else if ($field->isFloatingPointNumber()) {
            $body = "
\$$varName = (double)\$$varName;
";
        } else if ($field->isBooleanType()) {
            $body = "
if (\$$varName !== null) {
    if (is_string(\$$varName)) {
        \$$varName = in_array(strtolower(\$$varName), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
    } else {
        \$$varName = (boolean) \$$varName;
    }
}
";
        } else if ($field->isEnumType()) {
            $constName = strtoupper($varName . '_types');

            $body = "
if (\$$varName !== null) {
    if (!in_array(\$$varName, static::$constName, true)) {
        throw new \\InvalidArgumentException(sprintf('Value \"%s\" is not accepted in this enumerated column', \$$varName));
    }
}
";
        }

            $body .= "
\$this->$varName = \$$varName;
";

        $body .= "
return \$this;
";

        $this->getDefinition()->addUseStatement('Propel\Runtime\Exception\PropelException');
        $methodName = 'set' . $field->getMethodName();

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
