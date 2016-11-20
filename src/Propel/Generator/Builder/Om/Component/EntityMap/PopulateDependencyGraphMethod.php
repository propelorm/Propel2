<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Runtime\Session\DependencyGraph;

/**
 * Adds populateDependencyGraph method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PopulateDependencyGraphMethod extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        $body = '
$reader = $this->getPropReader();
$isset = $this->getPropIsset();
$dependencies = [];
';

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            $body .= "
if (\$isset(\$entity, '$relationName') && \$dep = \$reader(\$entity, '$relationName')) {
    \$dependencies[] = \$dep;
}
            ";
        }
//
//        foreach ($this->getEntity()->getReferrers() as $relation) {
//            $relationName = $this->getRefRelationCollVarName($relation);
//            $body .= "
//if (\$deps = \$reader(\$entity, '$relationName')) {
//    foreach (\$deps as \$dep) {
//        \$dependencies[] = \$dep;
//    }
//}
//            ";
//        }


//        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
//            $relationName = $this->getCrossRelationVarName($crossRelation);
//            $body .= "
//if (\$deps = \$reader(\$entity, '$relationName')) {
//    foreach (\$deps as \$dep) {
//        \$dependencies[] = \$dep;
//    }
//}
//";
//        }

        $body .= '
$dependencyGraph->add($entity, $dependencies);
        ';

        $this->getDefinition()->declareUse('Propel\Runtime\Session\DependencyGraph');
        $this->addMethod('populateDependencyGraph')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleParameter('dependencyGraph', 'DependencyGraph')
            ->setBody($body);
    }
}