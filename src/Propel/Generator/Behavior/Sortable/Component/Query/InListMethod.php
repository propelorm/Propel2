<?php

namespace Propel\Generator\Behavior\Sortable\Component\Query;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class InListMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        list($methodSignature, $buildScope) = $behavior->generateScopePhp();

        $body = "
$buildScope
\$this->filterByNormalizedListScope(\$scope, 'addUsingAlias');

return \$this;
";

        $this->addMethod('inList')
            ->setParameters($methodSignature)
            ->setDescription("Returns the objects in a certain list, from the list scope")
            ->setTypeDescription("The current query, for fluid interface")
            ->setType('$this|' . $this->getQueryClassName())
            ->setBody($body)
        ;

    }
}