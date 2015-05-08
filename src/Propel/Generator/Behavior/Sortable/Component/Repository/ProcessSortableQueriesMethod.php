<?php

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ProcessSortableQueriesMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
foreach (\$this->sortableQueries as \$query) {
    \$query['arguments'][] = \$con;
    call_user_func_array(\$query['callable'], \$query['arguments']);
}
\$this->sortableQueries = array();

return \$this;
";

        $this->addMethod('processSortableQueries')
            ->addSimpleParameter('con', null, null)
            ->setDescription('Execute queries that were saved to be run inside the save transaction.')
            ->setTypeDescription('The current repository, for fluid interface')
            ->setType('$this|' . $this->getRepositoryClassName())
            ->setBody($body)
        ;
    }
}