<?php


namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getChangeset method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildChangeSetMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = '
$changes = [];
$changed = false;
$reader = $this->getEntityMap()->getPropReader();
$id = spl_object_hash($entity);
if (!$this->hasKnownValues($id)) {
    throw new \Propel\Runtime\Exception\RuntimeException("Can not compute a change set on a unknown entity." . $id);
}
$originValues = $this->getLastKnownValues($id);
';

        foreach ($this->getEntity()->getFields() as $field){
            if ($field->isImplementationDetail()) continue;
            $fieldName = $field->getName();
            $body .= "
if (\$originValues['$fieldName'] !== (\$v = \$reader(\$entity, '$fieldName'))) {
    \$changes['$fieldName'] = \$this->getEntityMap()->prepareReadingValue(\$v, '$fieldName');
    \$changed = true;
}
";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $fieldName = $relation->getField();
            $foreignEntityClass = $relation->getForeignEntity()->getFullClassName();
            $body .= "
if (\$foreignEntity = \$reader(\$entity, '$fieldName')) {
    \$foreignEntityReader = \$this->getConfiguration()->getEntityMap('$foreignEntityClass')->getPropReader();
";
            $emptyBody = '';
            foreach ($relation->getFieldObjectsMapping() as $reference) {
                $relationFieldName = $reference['local']->getName();
                $foreignFieldName = $reference['foreign']->getName();

                $body .= "
    if (\$originValues['$relationFieldName'] !== (\$v = \$foreignEntityReader(\$foreignEntity, '$foreignFieldName'))) {
        \$changed = true;
        \$changes['$relationFieldName'] = \$v;
    }
";

                $emptyBody .= "
    \$originValues['$relationFieldName'] = null;
";

            }

            $body .= "
} else {
    $emptyBody
}
";
        }

        $body .= '
if ($changed) {
    return $changes;
}

return false;';

        $this->addMethod('buildChangeSet')
            ->addSimpleParameter('entity', 'object')
            ->setBody($body)
            ->setType('array|false')
            ->setTypeDescription('Returns false when no changes are detected');
    }
}