<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

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
        $proxyClass = $this->getProxyClassName();
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');

        $body = "
\$writer = \$this->getPropWriter();
\$obj = new $proxyClass;
\$originalValues = [];
";

        $colNames = $columnNames = $camelNames = $fieldNames = $fieldTypes = [];
        $fieldCount = 0;
        foreach ($this->getEntity()->getFields() as $field) {
            $fieldCount++;

            if ($field->isImplementationDetail()) {
                continue;
            }

            $fieldNames[] = $field->getName();
            $fieldTypes[] = $field->getType();
            $camelNames[] = $field->getCamelCaseName();
            $columnNames[] = $field->getColumnName();
            $colNames[] = $field->getEntity()->getName(). '.' .$field->getName();
        }

        $body .= "
if (EntityMap::TYPE_NUM === \$indexType) {
";
        foreach ($fieldNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $name = $fieldNames[$idx];
            $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$fieldName'] = \$this->prepareWritingValue(\$row[\$offset + $idx], '$name'));";
        }

        $body .= "
} else if (EntityMap::TYPE_CAMELNAME === \$indexType) {
    //columnName
";
        foreach ($camelNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $name = $fieldNames[$idx];
            $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$fieldName'] = \$this->prepareWritingValue(\$row['$fieldName'], '$name'));";
        }

        $body .= "
} else if (EntityMap::TYPE_FIELDNAME === \$indexType) {
    //column_name
";
        foreach ($fieldNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $name = $fieldNames[$idx];
            $fieldName = $columnNames[$idx];
            $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$fieldName'] = \$this->prepareWritingValue(\$row['$fieldName'], '$name'));";
        }

        $body .= "
} else if (EntityMap::TYPE_COLNAME === \$indexType) {
    //book.column_name
";
        foreach ($colNames as $idx => $fieldName) {
            $propName = $fieldNames[$idx];
            $name = $fieldNames[$idx];
            $fieldName = $colNames[$idx];
            $body .= "
    \$writer(\$obj, '$propName', \$originalValues['$fieldName'] = \$this->prepareWritingValue(\$row['$fieldName'], '$name'));";
        }

        $body .= "
}
";

        $body .= "
\$this->getRepository()->setLastKnownValues(\$obj, \$originalValues);
\$offset = \$offset + $fieldCount;
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
            ->setBody($body);
    }
}