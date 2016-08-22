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
        $this->addGetByName();
        $this->addToArray();
        $this->addGetByPosition();
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

    protected function addGetByName()
    {
        $body = "
{$this->getRepositoryAssignment()}
return \$repository->getEntityMap()->getByName(\$this, \$name, \$type);";

        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $this->addMethod('getByName')
            ->setDescription('Retrieves a field from the object by name passed in as a string')
            ->addSimpleDescParameter('name', 'string', 'name of the field')
            ->addSimpleDescParameter('type', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body);
    }

    protected function addToArray()
    {
        $body = "
{$this->getRepositoryAssignment()}
return \$repository->getEntityMap()->toArray(\$this, \$keyType, \$includeLazyLoadColumns, \$includeForeignObjects, \$alreadyDumpedObjectsWatcher);";

        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();

        $description = "
Exports the object as an array.

You can specify the key type of the array by passing one of the class
type constants. The default key type is the column's EntityMap::$defaultKeyType.
";
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $method = $this->addMethod('toArray')
            ->addSimpleDescParameter('keyType', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setDescription($description)
            ->setBody($body)
        ;

        $method->addSimpleDescParameter('includeLazyLoadColumns', 'boolean', 'Whether to include lazy loaded columns', true);
        $method->addSimpleDescParameter('includeForeignObjects', 'boolean', 'Whether to include hydrated related objects', false);
        $method->addSimpleParameter('alreadyDumpedObjectsWatcher', 'object', null);
    }

    protected function addGetByPosition()
    {
        $body = "
{$this->getRepositoryAssignment()}
return \$repository->getEntityMap()->getByPosition(\$this, \$pos);";

        $this->addMethod('getByPosition')
            ->setDescription('Retrieves a field from the object by Position as specified in the xml schema. Zero-based')
            ->addSimpleDescParameter('pos', 'integer', 'position in xml schema')
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body);
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
