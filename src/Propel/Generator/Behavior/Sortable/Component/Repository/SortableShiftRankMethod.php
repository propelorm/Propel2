<?php

namespace Propel\Generator\Behavior\SortableBehavior\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class SortableShiftRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        $body = "
\$whereCriteria = \$this->newQuery();
\$criterion = \$whereCriteria->getNewCriterion({$this->getEntityMapClassName()}::RANK_COL, \$first, Criteria::GREATER_EQUAL);
if (null !== \$last) {
    \$criterion->addAnd(\$whereCriteria->getNewCriterion({$this->getEntityMapClassName()}::RANK_COL, \$last, Criteria::LESS_EQUAL));
}
\$whereCriteria->add(\$criterion);";
    if ($useScope) {
        $body .= "
\$whereCriteria->filterByNormalizedListScope(\$scope);";
    }

        $body .= "
\$valuesCriteria = \$this->newQuery();
\$valuesCriteria->add({$this->getEntityMapClassName()}::RANK_COL, array('raw' => {$this->getEntityMapClassName()}::RANK_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

\$whereCriteria->doUpdate(\$valuesCriteria, \$con);
\$this->clearFirstLevelCache();
";

        $method = $this->addMethod('sortableShiftRank')
            ->addSimpleParameter('delta')
            ->addSimpleParameter('first')
            ->addSimpleParameter('last', 'integer', null)
            ->setDescription("Returns the objects in a certain list, from the list scope")
            ->setTypeDescription("The current query, for fluid interface")
            ->setType('$this|' . $this->getQueryClassName())
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleParameter('scope', 'mixed', null);
        }

    }
}