<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

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
\$whereCriteria = \$this->createQuery();
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
\$valuesCriteria = \$this->createQuery();
\$valuesCriteria->add({$this->getEntityMapClassName()}::RANK_COL, array('raw' => '{$behavior->getFieldForParameter('rank_field')->getColumnName()} + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

\$whereCriteria->doUpdate(\$valuesCriteria);
\$this->getConfiguration()->getSession()->clearFirstLevelCache();
";

        $method = $this->addMethod('sortableShiftRank')
            ->addSimpleParameter('delta')
            ->addSimpleParameter('first')
            ->addSimpleParameter('last', 'integer', null)
            ->setDescription('Returns the objects in a certain list, from the list scope')
            ->setTypeDescription('The current query, for fluid interface')
            ->setType('$this|' . $this->getRepositoryClassName())
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleParameter('scope', 'mixed', null);
        }
    }
}
