<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Adds getChangeset method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildChangeSetMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$changes = [];
$changed = false;
$reader = $this->getPropReader();
$isset = $this->getPropIsset();
$id = spl_object_hash($entity);
$originValues = $this->getLastKnownValues($id);
';

        foreach ($this->getEntity()->getFields() as $field){
            if ($field->isImplementationDetail()) continue;
            if ($field->isRelation()) continue;
            $fieldName = $field->getName();

            $body .= "
// field {$field->getName()}
\$different = null;
";

            if ($field->isLazyLoad()) {
                //if not set in originValues and not loaded in $entity then there's no need to compare those and
                //execute with it extra queries
                $lazyLoaded = '_' . $fieldName . '_loaded';

                $body .= "
\$lazyLastLoaded = isset(\$originValues['$fieldName']);
\$lazyNowLoaded = \$isset(\$entity, '$lazyLoaded');
if (false === \$lazyLastLoaded && false === \$lazyNowLoaded) {
    //both, initial population and lifetime value have not been set,
    //so there can't be any difference.
    \$different = false;
}
";
            }

            $body .= "
if (null === \$different) {
    \$currentValue = \$this->propertyToSnapshot(\$reader(\$entity, '$fieldName'), '$fieldName');
    if (!isset(\$originValues['$fieldName'])) {
        \$lastValue = null;
    } else {
        \$lastValue = \$originValues['$fieldName'];
    }
    \$different = \$lastValue !== \$currentValue;
}
if (\$different) {
    \$changes['$fieldName'] = \$this->propertyToDatabase(\$reader(\$entity, '$fieldName'), '$fieldName');
    \$changed = true;
}
";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $fieldName = $this->getRelationVarName($relation);
            $foreignEntityClass = $relation->getForeignEntity()->getFullClassName();
            $body .= "
// relation {$relation->getField()}
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
    if (null !== \$originValues['$relationFieldName']) {
        \$changed = true;
        \$changes['$relationFieldName'] = null;
    }
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