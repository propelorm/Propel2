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
$entityReader = $this->getPropReader();
        ';
        $placeholder = [];

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }
            if (!$this->getEntity()->isAllowPkInsert() && $field->isAutoIncrement()) {
                continue;
            }
            $placeholder[] = '?';
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

            $body .= $this->getTypeCasting($field);

            if ($field->isLobType()) {
                $body .= "
if (is_resource(\$value)) {
    \$value = stream_get_contents(\$value);
}
";
            }

            $body .= "
\$value = \$this->propertyToDatabase(\$value, '{$fieldName}');
\$params['{$fieldName}'] = \$value;
\$outgoingParams[] = \$value;
//end field:$fieldName
";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $className = $this->getClassNameFromEntity($relation->getForeignEntity(), true);
            $propertyName = $this->getRelationVarName($relation);
            $placeholder[] = '?';

            $body .= "
//relation:$propertyName
\$foreignEntityReader = \$this->getClassPropReader('$className');";

            foreach ($relation->getFieldObjectsMapArray() as $map) {
                /** @var Field $localField */
                /** @var Field $foreignField */
                list ($localField, $foreignField) = $map;
                $foreignFieldName = $foreignField->getName();

                $typeCasting = $this->getTypeCasting($foreignField);

                $body .= "
if (\$foreignEntity = \$entityReader(\$entity, '$propertyName')) {
";

                if (isset($foreignField->foreignRelation)) {
                    /** @var Relation $foreignRelation */
                    $foreignRelation = $foreignField->foreignRelation;
                    $relationFieldName = $foreignRelation->getField();
                    $relationEntityName = $foreignRelation->getForeignEntity()->getFullClassName();
                    $body .= "
    \$foreignForeignEntityReader = \$this->getClassPropReader('$relationEntityName');
    \$foreignForeignEntity = \$foreignEntityReader(\$foreignEntity, '{$relationFieldName}');
    \$value = \$foreignForeignEntityReader(\$foreignForeignEntity, '{$foreignField->foreignRelationFieldName}');
                    ";
                } else {
                    $body .= "
    \$value = \$foreignEntityReader(\$foreignEntity, '$foreignFieldName');";
                }

                $body .= "
    $typeCasting
} else {
    \$value = null;
}

if (!isset(\$params['{$localField->getName()}'])) {
    \$params['{$localField->getName()}'] = \$value; //{$localField->getName()}
    \$outgoingParams[] = \$value;
}
";
            }
            $body .= "
//end relation:$propertyName";
        }

        $placeholder = var_export('(' . implode(', ', $placeholder). ')', true);
        $body .= "
return $placeholder;
        ";

        $paramsParam = new PhpParameter('outgoingParams');
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
