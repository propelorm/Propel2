<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Runtime\Session\DependencyGraph;

/**
 * Adds persistDependenciesMethod method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class PersistDependenciesMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\Session\Session');

        $body = '
$reader = $this->getPropReader();
';

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = $this->getRelationVarName($relation);
            $body .= "
if (\$relationEntity = \$reader(\$entity, '$relationName')) {
    \$session->persist(\$relationEntity, \$deep);
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