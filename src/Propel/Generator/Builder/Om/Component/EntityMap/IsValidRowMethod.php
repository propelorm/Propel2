<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds isValidRow method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IsValidRowMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\Map\EntityMap');

        $body = "";

        $colName = $phpNames = $camelNames = $fieldNames = [];
        $fieldCount = 0;
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }
            if (!$field->isPrimaryKey()){
                continue;
            }

            $fieldCount++;
            $fieldNames[] = $field->getName();
            $camelNames[] = $field->getCamelCaseName();
            $phpNames[] = $field->getName();
            $colName[] = $field->getEntity()->getName(). '.' .$field->getName();
        }

        $body .= "
if (EntityMap::TYPE_NUM === \$indexType) {
";
        foreach ($fieldNames as $idx => $fieldName) {
            $body .= "
    if (null === \$row[\$offset + $idx]) return false;";
        }

        $body .= "
} else if (EntityMap::TYPE_PHPNAME === \$indexType) {
    //ColumnName
";
        foreach ($phpNames as $idx => $fieldName) {
            $body .= "
    if (null === \$row['$fieldName']) return false;";
        }

        $body .= "
} else if (EntityMap::TYPE_COLNAME === \$indexType) {
    //columnName
";
        foreach ($camelNames as $idx => $fieldName) {
            $body .= "
    if (null === \$row['$fieldName']) return false;";
        }

        $body .= "
} else if (EntityMap::TYPE_FIELDNAME === \$indexType) {
    //column_name
";
        foreach ($fieldNames as $idx => $fieldName) {
            $body .= "
    if (null === \$row['$fieldName']) return false;";
        }

        $body .= "
} else if (EntityMap::TYPE_FULLCOLNAME === \$indexType) {
    //book.column_name
";
        foreach ($colName as $idx => $fieldName) {
            $body .= "
    if (null === \$row['$fieldName']) return false;";
        }

        $body .= "
}
";

        $body .= "
return true;
";

        $offsetParameter = new PhpParameter('offset');
        $offsetParameter->setPassedByReference(true);
        $offsetParameter->setType('integer');
        $offsetParameter->setDefaultValue(0);

        $this->addMethod('isValidRow')
            ->addSimpleParameter('row', 'array')
            ->addSimpleParameter('offset', 'integer', 0)
            ->addSimpleParameter('indexType', 'string', PhpConstant::create('EntityMap::TYPE_NUM'))
            ->setDescription('Checks whether all primary key fields are valid (not null) in $row.')
            ->setBody($body);
    }
}