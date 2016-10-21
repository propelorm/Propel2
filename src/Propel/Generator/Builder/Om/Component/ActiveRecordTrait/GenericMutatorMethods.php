<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds all generic mutator methods setByName()`, `setByPosition()`, and `fromArray`.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GenericMutatorMethods extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addSetByName();
        $this->addFromArray();
        $this->addSetByPosition();
    }

    protected function addSetByName()
    {
        $body = "
{$this->getRepositoryAssignment()}
return \$repository->getEntityMap()->setByName(\$this, \$name, \$value, \$type);";

        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $this->addMethod('setByName')
            ->setDescription('Sets a field from the object by Position as specified in the xml schema. Zero-based')
            ->addSimpleDescParameter('name', 'string', 'name of the field')
            ->addSimpleDescParameter('value', 'mixed', 'field value')
            ->addSimpleDescParameter('type', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body);
    }

    protected function addFromArray()
    {
        $body = "
{$this->getRepositoryAssignment()}
return \$repository->getEntityMap()->fromArray(\$this, \$arr, \$keyType);";

        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();

        $description = "
Populates the object using an array.
This is particularly useful when populating an object from one of the
request arrays (e.g. \$_POST).  This method goes through the field
names and sets all values through its setter or directly into the property.

You can specify the key type of the array by additionally passing one
of the class type constants EntityMap::TYPE_CAMELNAME,
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
The default key type is the column's EntityMap::$defaultKeyType.
";
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $this->addMethod('fromArray')
            ->addSimpleDescParameter('arr', 'array')
            ->addSimpleDescParameter('keyType', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setDescription($description)
            ->setBody($body)
        ;
    }

    protected function addSetByPosition()
    {
        $body = "
{$this->getRepositoryAssignment()}
return \$repository->getEntityMap()->setByPosition(\$this, \$pos, \$value);";

        $this->addMethod('setByPosition')
            ->setDescription('Sets a field from the object by Position as specified in the xml schema. Zero-based')
            ->addSimpleDescParameter('pos', 'integer', 'position in xml schema')
            ->addSimpleDescParameter('value', 'mixed', 'field value')
            ->setType('$this|' . $this->getObjectClassName())
            ->setBody($body);
    }
}