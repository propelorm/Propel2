<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Field;

/**
 * Adds populateObject method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PopulateObjectMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');

        $body = "
\$writer = \$this->getPropWriter();
if (\$entity) {
    \$obj = \$entity;
} else {
    \$obj = \$this->getRepository()->createProxy();
}
\$obj->__duringInitializing__ = true;
\$originalValues = [];
";

        $fullColumnNames = $columnNames = $camelNames = $fieldNames = $fieldTypes = [];
        $implementationDetail = [];
        $fieldCount = 0;
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isLazyLoad()) {
                continue;
            }

            $fieldCount++;

            if ($field->isImplementationDetail()) {
                $implementationDetail[$field->getName()] = true;
            }

            $fieldNames[] = $field->getName();
            $fieldTypes[] = $field->getType();
            $camelNames[] = $field->getCamelCaseName();
            $columnNames[] = $field->getColumnName();
            $fullColumnNames[] = $field->getEntity()->getName(). '.' .$field->getColumnName();
        }

        $body .= "
if (EntityMap::TYPE_NUM === \$indexType) {
    //0
";
        foreach ($fieldNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$originalValues['$propName'] = \$this->prepareWritingValue(\$row[\$offset + $idx], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$propName']);";
            }
        }

        $body .= "
} else if (EntityMap::TYPE_COLNAME === \$indexType) {
    //columnName
";
        foreach ($camelNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$originalValues['$propName'] = \$this->prepareWritingValue(\$row[\$offset + $idx], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$propName']);";
            }
        }

        $body .= "
} else if (EntityMap::TYPE_FIELDNAME === \$indexType) {
    //column_name
";
        foreach ($columnNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$originalValues['$propName'] = \$this->prepareWritingValue(\$row[\$offset + $idx], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$propName']);";
            }
        }

        $body .= "
} else if (EntityMap::TYPE_FULLCOLNAME === \$indexType) {
    //book.column_name
";
        foreach ($fullColumnNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $body .= "
    \$originalValues['$propName'] = \$this->prepareWritingValue(\$row[\$offset + $idx], '$propName');";
            if (!isset($implementationDetail[$propName])) {
                $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$propName']);";
            }
        }

        $body .= "
}
";

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $relation->getField();
            $className = $relation->getForeignEntity()->getFullClassName();

            $body .= "
//relation $relationName
\$exist = true;
";
            foreach ($relation->getLocalFieldObjects() as $field) {
                $propName = $field->getName();
                $body .= "\$exist = \$exist && null !== \$originalValues['$propName'];";
            }

            $body .= "
if (\$exist) {
    \$relationProxy = \$this->getConfiguration()->getRepository('$className')->createProxy();
    \$relationProxyWriter = \$this->getConfiguration()->getEntityMap('$className')->getPropWriter();
";

            foreach ($relation->getFieldObjectsMapping() as $mapping) {
                /** @var Field $local */
                $local = $mapping['local'];
                /** @var Field $foreign */
                $foreign = $mapping['foreign'];

                $pkName = $foreign->getName();
                $localName = $local->getName();
                $body .= "
    \$relationProxyWriter(\$relationProxy, '$pkName', \$originalValues['$localName']);";
            }


            $body .= "
    \$writer(\$obj, '$relationName', \$relationProxy);
}
";
        }


        $body .= "
\$this->getRepository()->setLastKnownValues(\$obj, \$originalValues);
\$offset = \$offset + $fieldCount;
unset(\$obj->__duringInitializing__);
return \$obj;
";

        $offsetParameter = new PhpParameter('offset');
        $offsetParameter->setPassedByReference(true);
        $offsetParameter->setType('integer');
        $offsetParameter->setDefaultValue(0);

        $this->addMethod('populateObject')
            ->addSimpleParameter('row', 'array')
            ->addParameter($offsetParameter)
            ->addSimpleParameter('indexType', 'string', PhpConstant::create('EntityMap::TYPE_NUM'))
            ->addSimpleParameter('entity', 'object', null)
            ->setBody($body);
    }
}