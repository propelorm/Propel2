<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

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
}