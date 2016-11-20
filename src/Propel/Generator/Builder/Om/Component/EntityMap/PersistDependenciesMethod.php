<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;

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
$isset = $this->getPropIsset();
';

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            if ($relation->isLocalPrimaryKey()) {
                $body .= "// one-to-one {$relation->getForeignEntity()->getFullClassName()}\n";
            } else {
                $body .= "// many-to-one {$relation->getForeignEntity()->getFullClassName()}\n";
            }

            $body .= "
if (\$isset(\$entity, '$relationName') && \$relationEntity = \$reader(\$entity, '$relationName')) {
    \$session->persist(\$relationEntity, \$deep);
}
";
        }

        //Prevent add an entity twice
        $alreadyAdded = array();

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $relationName = $this->getRefRelationVarName($relation);

            if ($relation->isLocalPrimaryKey()) {
                $body .= "//ref one-to-one {$relation->getEntity()->getFullClassName()}
if (\$isset(\$entity, '$relationName') && \$relationEntity = \$reader(\$entity, '$relationName')) {
    \$session->persist(\$relationEntity, \$deep);
}
";
            } else {
                //one-to-many
                $relationName = $this->getRefRelationVarName($relation, true);

                if (!in_array($relationName, $alreadyAdded)) {
                    $body .= "
//ref one-to-many {$relation->getEntity()->getFullClassName()}
if (\$isset(\$entity, '$relationName') && \$relationEntities = \$reader(\$entity, '$relationName')) {
    foreach (\$relationEntities as \$relationEntity) {
        \$session->persist(\$relationEntity, \$deep);
    }
}
";
                    $alreadyAdded[] = $relationName;
                }
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
if (\$isset(\$entity, '$varName') && \$relationEntities = \$reader(\$entity, '$varName')) {
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
