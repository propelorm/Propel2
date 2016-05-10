<?php

namespace Propel\Generator\Behavior\Sortable\Component\SortableManager;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPreviousMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();
        list($methodSignature, $buildScope, $buildScopeVars) = $behavior->generateScopePhp();

        $body = "
{$this->getRepositoryAssignment()}
\$query = \$repository->createQuery();
";

        if ($useScope) {
            $params = $this->parameterToString($methodSignature);

            $body .= "
\$scope = \$entity->getScopeValue();
$buildScopeVars
\$query->filterByRank(\$entity->getRank() - 1, $params);
";
        } else {

            $body .= "
\$query->filterByRank(\$entity->getRank() - 1);
";
        }

        $body .= "

return \$query->findOne();
";

        $this->addMethod('getPrevious')
            ->addSimpleParameter('entity', 'object')
            ->setDescription('Get the previous item in the list, i.e. the one for which rank is immediately lower')
            ->setBody($body)
        ;
    }
}
