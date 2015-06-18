<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\CrossRelation;
use Propel\Runtime\Session\DependencyGraph;

/**
 * Adds persistDependenciesMethod method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PersistDependenciesMethod extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\Session\Session');

        $body = '
$reader = $this->getPropReader();
';

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            if ($relation->isLocalPrimaryKey()) {
                $body .= "// one-to-one {$relation->getForeignEntity()->getFullClassName()}\n";
            } else {
                $body .= "// many-to-one {$relation->getForeignEntity()->getFullClassName()}\n";
            }

            $body .= "
if (\$relationEntity = \$reader(\$entity, '$relationName')) {
    \$session->persist(\$relationEntity, \$deep);
}
";
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            if ($relation->isLocalPrimaryKey()) {
                $relationName = $this->getRefRelationVarName($relation);
                $body .= "//ref one-to-one {$relation->getEntity()->getFullClassName()}
if (\$relationEntity = \$reader(\$entity, '$relationName')) {
    \$session->persist(\$relationEntity, \$deep);
}
";
            } else {
                //one-to-many not for now
                continue;
            }
        }

        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $varName = $this->getRefRelationCollVarName($crossRelation->getIncomingRelation());

            $to = [];
            foreach ($crossRelation->getRelations() as $relation) {
                $to[] = $relation->getForeignEntity()->getFullClassName();
            }
            $to = implode(', ', $to);

            $body .= "
// cross relation {$crossRelation->getMiddleEntity()->getFullClassName()} (to $to)
if (\$relationEntities = \$reader(\$entity, '$varName')) {
    foreach (\$relationEntities as \$relationEntity) {
        \$session->persist(\$relationEntity, \$deep);
    }
}
";
        }

        $this->getDefinition()->declareUse('Propel\Runtime\Session\DependencyGraph');
        $this->addMethod('persistDependencies')
            ->addSimpleParameter('session', 'Session')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleParameter('deep', 'boolean', false)
            ->setBody($body);
    }
}