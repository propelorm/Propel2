<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Model\Relation;

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
$params = [];
$placeholder = [];
$entityReader = $this->getPropReader();
        ';

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }
            if (!$this->getEntity()->isAllowPkInsert() && $field->isAutoIncrement()) {
                continue;
            }
            $fieldName = $field->getName();
            $propertyName = $field->getName();

            $body .= "
//field:$fieldName
\$value = \$entityReader(\$entity, '$propertyName');";

            switch (strtoupper($field->getType())) {
                case PropelTypes::DATE:
                case PropelTypes::TIME:
                case PropelTypes::TIMESTAMP:
                    $dateTimeClass = $this->getBuilder()->getBuildProperty('dateTimeClass');
                    if (!$dateTimeClass) {
                        $dateTimeClass = '\DateTime';
                    }

                    $body .= "
if (!(\$value instanceof $dateTimeClass)) {
    \$value = \\Propel\\Runtime\\Util\\PropelDateTime::newInstance(\$value, null, '$dateTimeClass');
}
if (null !== \$value) {
    \$value = \$value->format('Y-m-d H:i:s');
}";
                    break;
                default:
            }

            if ($field->isLobType()) {
                $body .= "
if (is_resource(\$value)) {
    \$value = stream_get_contents(\$value);
}
";
            }

            $default = 'NULL';
            if ($field->getDefaultValue() && $field->getDefaultValue()->isExpression()) {
                $default = $field->getDefaultValue()->getValue();
            }

            $default = var_export($default, true);

            $body .= "
\$value = \$this->propertyToDatabase(\$value, '{$fieldName}');
if (null !== \$value) {
    \$params['{$fieldName}'] = \$value;
    \$outgoingParams[] = \$value;
    \$placeholder[] = '?';
} else {
    \$placeholder[] = $default;
}
//end field:$fieldName
";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $className = $relation->getForeignEntity()->getFullClassName();
            $propertyName = $this->getRelationVarName($relation);

            $body .= "
//relation:$propertyName
\$foreignEntityReader = \$this->getClassPropReader('$className');
\$foreignEntity = \$entityReader(\$entity, '$propertyName');
";
            foreach ($relation->getFieldObjectsMapArray() as $map) {
                /** @var Field $localField */
                /** @var Field $foreignField */
                list ($localField, $foreignField) = $map;
                $foreignFieldName = $foreignField->getName();

                $body .= "
\$value = null;
if (\$foreignEntity) {
    \$value = \$foreignEntityReader(\$foreignEntity, '{$foreignFieldName}');
}

if (!isset(\$params['{$localField->getName()}'])) {
    \$params['{$localField->getName()}'] = \$value; //{$localField->getName()}
    \$outgoingParams[] = \$value;
    \$placeholder[] = '?';
}
";

//                if (isset($foreignField->foreignRelation)) {
//                    /** @var Relation $foreignRelation */
//                    $foreignRelation = $foreignField->foreignRelation;
//                    $relationFieldName = $foreignRelation->getField();
//                    $relationEntityName = $foreignRelation->getForeignEntity()->getFullClassName();
//                    $body .= "
//    \$foreignForeignEntity = \$foreignEntityReader(\$foreignEntity, '{$foreignFieldName}');
//    \$value = \$foreignForeignEntityReader(\$foreignForeignEntity, '{$foreignField->foreignRelationFieldName}');
//                    ";
//                } else {
//                    $body .= "
//    \$value = \$foreignEntityReader(\$foreignEntity, '$foreignFieldName');";
//                }
            }

//            $body .= "
//    $typeCasting
//} else {
//    \$value = null;
//}
//
//";
            $body .= "
//end relation:$propertyName";
        }

        $body .= "
return '(' . implode(',', \$placeholder) . ')';
        ";

        $paramsParam = new PhpParameter('outgoingParams');
        $paramsParam->setPassedByReference(true);
        $paramsParam->setType('array');

        $this->addMethod('buildSqlBulkInsertPart')
            ->addSimpleParameter('entity')
            ->addParameter($paramsParam)
            ->setBody($body);
    }
}
