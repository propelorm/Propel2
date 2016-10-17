<?php

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\PropelTypes;

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
        $this->addFromArray();
        $this->addSetByName();
        $this->addSetByPosition();
    }

    protected function addFromArray()
    {
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

        $body = "
\$writer = \$this->getPropWriter();
\$keys = \$this->getFieldNames(\$keyType);";

        foreach ($this->getEntity()->getFields() as $num => $field) {
            $propertyName = $field->getName();
            $setter = 'set' . ucfirst($field->getName());

            $body .= "
//$propertyName
if (isset(\$arr[\$keys[$num]])) {
    \$value = \$arr[\$keys[$num]];
} else {
    \$value = null;
}
if (method_exists(\$entity, '$setter') && is_callable([\$entity, '$setter'])) {
    \$entity->$setter(\$value);
} else {
    \$writer(\$entity, '$propertyName', \$value);
}
        ";
        }

        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $this->addMethod('fromArray')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleDescParameter('arr', 'array')
            ->addSimpleDescParameter('keyType', 'string', "The type of fieldname the \$name is of:
one of the class type constants, EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setDescription($description)
            ->setBody($body)
        ;

    }
    protected function addSetByName()
    {
        $body = "
\$pos = \$this->translateFieldName(\$name, \$type, EntityMap::TYPE_NUM);

return \$this->setByPosition(\$entity, \$pos, \$value);
";

        $defaultKeyType = $this->getBuilder()->getDefaultKeyType();

        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');
        $defaultKeyTypeConstant = new PhpConstant("EntityMap::$defaultKeyType");

        $this->addMethod('setByName')
            ->setDescription('Sets a field from the object by Position as specified in the xml schema. Zero-based')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleDescParameter('name', 'string', 'name of the field')
            ->addSimpleDescParameter('value', 'mixed', 'field value')
            ->addSimpleDescParameter('type', 'string', "The type of fieldname the \$name is of:
one of the class type constants EntityMap::TYPE_CAMELNAME
EntityMap::TYPE_COLNAME, EntityMap::TYPE_FIELDNAME, EntityMap::TYPE_NUM.
Defaults to EntityMap::$defaultKeyType.", $defaultKeyTypeConstant)
            ->setBody($body);
    }

    protected function addSetByPosition()
    {
        $body = "
\$writer = \$this->getPropWriter();
switch (\$pos) {";
        $i = 0;
        foreach ($this->getEntity()->getFields() as $field) {
            $propertyName = $field->getName();
            $setter = 'set' . ucfirst($field->getName());

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
        if (method_exists(\$entity, '$setter') && is_callable([\$entity, '$setter'])) {
            return \$entity->$setter(\$value);
        } else {
            \$writer(\$entity, '$propertyName', \$value);
        }
        break;";
            $i++;
        } /* foreach */
        $body .= "
} // switch()

return \$this;";

        $this->addMethod('setByPosition')
            ->setDescription('Sets a field from the object by Position as specified in the xml schema. Zero-based')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleDescParameter('pos', 'integer', 'position in xml schema')
            ->addSimpleDescParameter('value', 'mixed', 'field value')
            ->setType('$this|' . $this->getEntityMapClassName())
            ->setBody($body);
    }
}