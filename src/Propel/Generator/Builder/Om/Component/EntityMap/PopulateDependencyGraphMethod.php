<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
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
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = '
$reader = $this->getPropReader();
$dependencies = [];
';

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            $body .= "
if (\$dep = \$reader(\$entity, '$relationName')) {
    \$dependencies[] = \$dep;
}
            ";
        }

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