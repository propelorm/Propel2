<?php

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds all generic accessor methods getByName()`, `getByPosition()`, and `toArray`.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GenericAccessorMethods extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->addToArray();
        $this->addGetByName();
        $this->addGetByPosition();
    }

    protected function addToArray()
    {
        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();

        $description = "
Exports the object as an array.

You can specify the key type of the array by passing one of the class
type constants. The default key type is the column's EntityMap::$defaultKeyType.
";

        $body = "
if (!(\$alreadyDumpedObjectsWatcher instanceof \\stdClass)) {
    \$alreadyDumpedObjectsWatcher = new \\stdClass;
    \$alreadyDumpedObjectsWatcher->objects = [];
}

if (isset(\$alreadyDumpedObjectsWatcher->objects[spl_object_hash(\$entity)])) {
    return '*RECURSION*';
}

\$alreadyDumpedObjectsWatcher->objects[spl_object_hash(\$entity)] = true;
\$reader = \$this->getPropReader();
\$keys = \$this->getFieldNames(\$keyType);
\$array = [];";

        foreach ($this->getEntity()->getFields() as $num => $field) {
            $propertyName = $field->getName();
            $getter = 'get' . ucfirst($field->getName());

            if ($field->isImplementationDetail()) {
                continue;
            }

            $lazyLoading = $field->isLazyLoad() ? 'true' : 'false';

            $body .= "
//$propertyName
if (\$includeLazyLoadColumns || \$includeLazyLoadColumns === $lazyLoading) {
    if (method_exists(\$entity, '$getter') && is_callable([\$entity, '$getter'])) {
        \$value = \$entity->$getter();
    } else {
        \$value = \$reader(\$entity, '$propertyName');
    }
    \$array[\$keys[$num]] = \$value;
}
        ";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $propertyName = $this->getRelationVarName($relation);
            $body .= "
//relation to {$relation->getForeignEntityName()}
\$relationName = '$propertyName';
\$foreignEntity = \$reader(\$entity, '$propertyName');
\$foreignEntityMap = \$this->getConfiguration()->getEntityMap('{$relation->getForeignEntity()->getFullClassName()}');
\$value = null;
if (\$foreignEntity) {
    \$value = \$foreignEntityMap->toArray(\$foreignEntity, \$keyType, \$includeLazyLoadColumns, \$includeForeignObjects, \$alreadyDumpedObjectsWatcher);
}
if (\$value) {
    {$this->addRelationNameModifier()}
    \$array[\$relationName] = \$value;
}
";
        }

        foreach ($this->getEntity()->getReferrers() as $refRelation) {
            if ($refRelation->isLocalPrimaryKey()) {
                $propertyName = $this->getPKRefRelationVarName($refRelation);
                $toArrayCall = "\$foreignEntityMap->toArray(\$foreignEntity, \$keyType, \$includeLazyLoadColumns, \$includeForeignObjects, \$alreadyDumpedObjectsWatcher)";
                $defaultValue = 'null';
                $typeHint = 'object';
                $relationSetter = $this->addRelationNameModifier();
            } else {
                $propertyName = $this->getRefRelationCollVarName($refRelation);
                $toArrayCall = "\$foreignEntity->toArray(null, false, \$keyType, \$includeLazyLoadColumns, \$alreadyDumpedObjectsWatcher)";
                $defaultValue = '[]';
                $typeHint = 'array|\Propel\Runtime\Collection\ObjectCollection';
                $relationSetter = $this->addRelationNameModifier();
            }

            $body .= "
//ref relation to {$refRelation->getForeignEntityName()}
\$relationName = '$propertyName';
/** @var $typeHint \$foreignEntity */
\$foreignEntity = \$reader(\$entity, '$propertyName');
\$foreignEntityMap = \$this->getConfiguration()->getEntityMap('{$refRelation->getForeignEntity()->getFullClassName()}');
\$value = $defaultValue;
if (\$foreignEntity) {
    \$value = {$toArrayCall};
}
if (\$value) {
    $relationSetter
    \$array[\$relationName] = \$value;
}
";
        }

        $body .= "
return \$array;";

        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $method = $this->addMethod('toArray')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleDescParameter('keyType', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setDescription($description)
            ->setType('array')
            ->setBody($body)
        ;

        $method->addSimpleDescParameter('includeLazyLoadColumns', 'boolean', 'Whether to include lazy loaded columns', true);
        $method->addSimpleDescParameter('includeForeignObjects', 'boolean', 'Whether to include hydrated related objects', false);
        $method->addSimpleParameter('alreadyDumpedObjectsWatcher', 'object', null);
    }


    /**
     * Adds the switch-statement for looking up the array-key name for toArray
     * @see toArray
     */
    protected function addRelationNameModifier()
    {
        return "
    if (EntityMap::TYPE_PHPNAME === \$keyType) {
        \$relationName = ucfirst(\$relationName);
    }
        ";
    }

    protected function addGetByName()
    {
        $body = "
\$pos = \$this->translateFieldName(\$name, \$type, EntityMap::TYPE_NUM);

return \$this->getByPosition(\$entity, \$pos);
";

        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();

        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $this->addMethod('getByName')
            ->setDescription('Retrieves a field from the object by name passed in as a string')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleDescParameter('name', 'string', 'name of the field')
            ->addSimpleDescParameter('type', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_PHPNAME, EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setBody($body);
    }

    protected function addGetByPosition()
    {
        $body = "
\$reader = \$this->getPropReader();
switch (\$pos) {";
        $i = 0;
        foreach ($this->getEntity()->getFields() as $field) {
            $propertyName = $field->getName();
            $getter = 'get' . ucfirst($field->getName());

            $body .= "
    case $i:";

            if (PropelTypes::ENUM === $field->getType()) {
                $body .= "
        \$valueSet = " . $this->getEntityMapClassName() . "::getValueSet(" . $field->getConstantName() . ");
        if (isset(\$valueSet[\$value])) {
            \$value = \$valueSet[\$value];
        }";
            } elseif (PropelTypes::PHP_ARRAY === $field->getType()) {
                $body .= "
        if (!is_array(\$value)) {
            \$v = trim(substr(\$value, 2, -2));
            \$value = \$v ? explode(' | ', \$v) : array();
        }";
            }

            $body .= "
        if (method_exists(\$entity, '$getter') && is_callable([\$entity, '$getter'])) {
            return \$entity->$getter();
        } else {
            return \$reader(\$entity, '$propertyName');
        }
        break;";
            $i++;
        } /* foreach */
        $body .= "
} // switch()

return \$this;";

        $this->addMethod('getByPosition')
            ->setDescription('Retrieves a field from the object by Position as specified in the xml schema. Zero-based')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleDescParameter('pos', 'integer', 'position in xml schema')
            ->setType('$this|' . $this->getEntityMapClassName())
            ->setBody($body);
    }
}