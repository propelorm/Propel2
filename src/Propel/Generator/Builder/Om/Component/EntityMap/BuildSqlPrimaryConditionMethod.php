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
 * Adds buildSqlPrimaryCondition method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildSqlPrimaryConditionMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$entityReader = $this->getPropReader();
        ';
        $placeholder = [];

        foreach ($this->getEntity()->getPrimaryKey() as $field) {

            $fieldName = $field->getName();
            $propertyName = $field->getName();
            $placeholder[] = sprintf('%s = ?', $field->getSqlName());

            $body .= "
//$fieldName
\$value = null;";
            if ($field->isImplementationDetail()) {
                $body .= "
\$foreignEntity = null;";

                foreach ($field->getRelations() as $relation) {
                    /** @var Field $foreignField */
                    $foreignField = null;
                    foreach ($relation->getFieldObjectsMapArray() as $mapping) {
                        list($local, $foreign) = $mapping;
                        if ($local === $field) {
                            $foreignField = $foreign;
                        }
                    }

                    $relationEntityName = $relation->getForeignEntity()->getFullClassName();
                    $propertyName = $this->getRelationVarName($relation);
                    $body .= "
if (null === \$foreignEntity) {
    \$foreignEntity = \$entityReader(\$entity, '$propertyName');
    \$foreignEntityReader = \$this->getClassPropReader('$relationEntityName');
    \$value = \$foreignEntityReader(\$foreignEntity, '{$foreignField->getName()}');
}
";
                }
            } else {
                $body .= "
\$value = \$entityReader(\$entity, '$propertyName');
";
            }

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

            if ($field->isLobType()) {
                $body .= "
if (is_resource(\$value)) {
    \$value = stream_get_contents(\$value);
}
";
            }

            $body .= "
\$params[] = \$value;
";
        }


        $placeholder = var_export(implode(' AND ', $placeholder), true);
        $body .= "
return $placeholder;
        ";

        $paramsParam = new PhpParameter('params');
        $paramsParam->setPassedByReference(true);
        $paramsParam->setType('array');

        $this->addMethod('buildSqlPrimaryCondition')
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
