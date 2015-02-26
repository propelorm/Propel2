<?php

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetNextMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();
        list($methodSignature, $buildScope, $buildScopeVars) = $behavior->generateScopePhp();

        $body = "
\$reader = \$this->getEntityMap()->getPropReader();
\$query = \$this->createQuery();
";

        if ($useScope) {
            $params = $this->parameterToString($methodSignature);

            $body .= "
\$scope = \$this->getScopeValue(\$entity);
$buildScopeVars
\$query->filterByRank(\$reader(\$entity, '{$behavior->getRankVarName()}') + 1, $params);
";
        } else {

            $body .= "
\$query->filterByRank(\$reader(\$entity, '{$behavior->getRankVarName()}') + 1);
";
        }

        $body .= "

return \$query->findOne(\$con);
}
";

        $this->addMethod('getNext')
            ->addSimpleParameter('entity', 'object')
            ->setDescription('Get the next item in the list, i.e. the one for which rank is immediately higher')
            ->setBody($body)
        ;

    }
}