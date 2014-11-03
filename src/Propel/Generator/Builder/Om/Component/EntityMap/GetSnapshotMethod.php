<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getSnapshot method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetSnapshotMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$reader = \$this->getPropReader();
\$snapshot = [];
";

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) continue;

            $fieldName = $field->getName();
            $body .= "\$snapshot['$fieldName'] = \$this->prepareReadingValue(\$reader(\$entity, '$fieldName'), '$fieldName');\n";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $fieldName = $relation->getField();
            $foreignEntityClass = $relation->getForeignEntity()->getFullClassName();
            $body .= "
if (\$v = \$reader(\$entity, '$fieldName')) {
    \$foreignEntityReader = \$this->getConfiguration()->getEntityMap('$foreignEntityClass')->getPropReader();
";
            $emptyBody = '';

            foreach ($relation->getFieldObjectsMapping() as $reference) {
                $relationFieldName = $reference['local']->getName();
                $foreignFieldName = $reference['foreign']->getName();
                $emptyBody .="
    \$snapshot['$relationFieldName'] = null;";
                $body .= "
    \$snapshot['$relationFieldName'] = \$foreignEntityReader(\$v, '$foreignFieldName');";
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