<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Field;
use Propel\Generator\Model\Relation;

/**
 * Adds getSnapshot method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetSnapshotMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = "
\$reader = \$this->getPropReader();
\$isset = \$this->getPropIsset();
\$snapshot = [];
";

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }

            $fieldName = $field->getName();
            if ($field->isLazyLoad()) {
                $body .= "
if (\$isset(\$entity, '$fieldName')){
    \$snapshot['$fieldName'] = \$this->propertyToSnapshot(\$reader(\$entity, '$fieldName'), '$fieldName');
}";
            } else {
                $body .= "\$snapshot['$fieldName'] = \$this->propertyToSnapshot(\$reader(\$entity, '$fieldName'), '$fieldName');\n";
            }
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $fieldName = $this->getRelationVarName($relation);
            $foreignEntityClass = $relation->getForeignEntity()->getFullClassName();
            $body .= "
if (\$foreignEntity = \$reader(\$entity, '$fieldName')) {
    \$foreignEntityReader = \$this->getConfiguration()->getEntityMap('$foreignEntityClass')->getPropReader();
";
            $emptyBody = '';

            foreach ($relation->getFieldObjectsMapArray() as $map) {
                /** @var Field $localField */
                /** @var Field $foreignField */
                list ($localField, $foreignField) = $map;
                $relationFieldName = $localField->getName();
                $foreignFieldName = $foreignField->getName();

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

                $emptyBody .="
    \$snapshot['$relationFieldName'] = null;";
                $body .= "
    \$snapshot['$relationFieldName'] = \$value;";
            }

            $body .= "
} else {
    $emptyBody
}
";
        }


        $body .= "
return \$snapshot;
";

        $this->addMethod('getSnapshot')
            ->addSimpleParameter('entity', 'object')
            ->setType('array')
            ->setBody($body);
    }
}