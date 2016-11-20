<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\NamingTool;

/**
 * Adds all generic accessor methods getByName()`, `getByPosition()`, and `toArray`.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GenericAccessorMethods extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        foreach ($this->getBuilder()->getEntity()->getFields() as $field) {
            if ($field->isPhpArrayType()) {
                if ($field->isNamePlural()) {
                    $this->addHasArrayElement($field);
                    $this->addAddArrayElement($field);
                    $this->addRemoveArrayElement($field);
                }
            }
        }
    }

    public function addHasArrayElement(Field $field) {
        $columnType = ($field->isPhpArrayType()) ? 'array' : 'set';
        $singularName = NamingTool::toUpperCamelCase($field->getSingularName());

        $this->addMethod("has$singularName", $field->getAccessorVisibility())
            ->setDescription("Test the presence of a value in the [{$field->getLowercasedName()}] $columnType column value.")
            ->setType('bool')
            ->addSimpleParameter('value')
            ->setBody("return in_array(\$value, \$this->get{$field->getMethodName()}());")
        ;
    }

    /**
     * Adds a push method for an array column.
     * @param Field $field     The current field.
     */
    protected function addAddArrayElement(Field $field)
    {
        $singularName = NamingTool::toUpperCamelCase($field->getSingularName());
        $fieldType = ($field->isPhpArrayType()) ? 'array' : 'set';
        $body = "   
\$currentArray = \$this->get{$field->getMethodName()}();
\$currentArray []= \$value;
\$this->set{$field->getMethodName()}(\$currentArray);
";
        $this->addMethod("add$singularName", $field->getAccessorVisibility())
            ->setDescription("Adds a value to the [{$field->getName()}] $fieldType.")
            ->addSimpleParameter('value')
            ->setBody($body)
        ;
    }

    /**
     * Adds a remove method for an array column.
     * @param Field $field     The current field.
     */
    protected function addRemoveArrayElement(Field $field)
    {
        $singularName = NamingTool::toUpperCamelCase($field->getSingularName());
        $fieldType = ($field->isPhpArrayType()) ? 'array' : 'set';
        $body = "
\$targetArray = array();
foreach (\$this->get{$field->getMethodName()}() as \$element) {
    if (\$element != \$value) {
        \$targetArray []= \$element;
    }
}
\$this->set{$field->getMethodName()}(\$targetArray);
";
        $this->addMethod("remove$singularName", $field->getAccessorVisibility())
            ->setDescription("Removes a value from the [{$field->getName()}] $fieldType field value.")
            ->addSimpleParameter('value')
            ->setBody($body)
        ;
    }
}
