<?php

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IsLastMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        $body = "
\$reader = \$this->getEntityMap()->getPropReader();
\$last = \$this->createQuery()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue()" : '') . ")
return \$last === \$reader(\$entity, {$behavior->getFieldForParameter('rank_field')->getName()});
";

        $this->addMethod('isLast')
            ->addSimpleParameter('entity', 'object')
            ->setDescription("Check if the object is last in the list, i.e. if its rank is the highest rank")
            ->setType("boolean")
            ->setBody($body)
        ;

    }
}