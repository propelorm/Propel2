<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds buildSqlBulkInsertPart method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildSqlBulkInsertPartMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$entityReader = $this->getPropReader();
        ';
        $placeholder = [];

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }
            if ($field->isPrimaryKey()) {
                continue;
            }
            $placeholder[] = '?';
            $fieldName = $field->getName();
            $propertyName = $field->getName();

            $body .= "
//$fieldName
\$value = \$entityReader(\$entity, '$propertyName');";

            switch (strtoupper($field->getType())) {
                case PropelTypes::DATE:
                case PropelTypes::TIME:
                    $dateTimeClass = $this->getBuilder()->getBuildProperty('dateTimeClass');
                    if (!$dateTimeClass) {
                        $dateTimeClass = '\DateTime';
                    }

                    $body .= "
if (!(\$value instanceof $dateTimeClass)) {
    \$value = \\Propel\\Runtime\\Util\\PropelDateTime::newInstance(\$value, null, '$dateTimeClass');
}
\$value = \$value->format('Y-m-d H:i:s');";
                    break;
                default:
            }

            $body .= $this->getTypeCasting($field);

            $body .= "
\$params[] = \$value;";
        }


        foreach ($this->getEntity()->getRelations() as $relation) {

            $className = $this->getClassNameFromEntity($relation->getForeignEntity(), true);
            $propertyName = $this->getRelationVarName($relation);
            $placeholder[] = '?';

            $body .= "
//$propertyName
\$foreignEntityReader = \$this->getClassPropReader('$className');";

            foreach ($relation->getForeignFieldObjects() as $foreignField) {
                $foreignFieldName = $foreignField->getName();

                $typeCasting = $this->getTypeCasting($foreignField);

                $body .= "
if (\$foreignEntity = \$entityReader(\$entity, '$propertyName')) {
    \$value = \$foreignEntityReader(\$foreignEntity, '$foreignFieldName');
    $typeCasting
} else {
    \$value = null;
}
\$params[] = \$value;
";
            }
        }

        $placeholder = var_export('(' . implode(', ', $placeholder). ')', true);
        $body .= "
return $placeholder;
        ";

        $paramsParam = new PhpParameter('params');
        $paramsParam->setPassedByReference(true);
        $paramsParam->setType('array');

        $this->addMethod('buildSqlBulkInsertPart')
            ->addSimpleParameter('entity')
            ->addParameter($paramsParam)
            ->setBody($body);
    }

    protected function getTypeCasting(Field $field)
    {
        if ($field->isNumericType()) {
            return "
\$value += 0; //cast to numeric";
        }

        if ($field->isBooleanType()) {
            return "
\$value = \$value ? 1 : 0; //cast to bool";
        }

        return "";
    }
}
