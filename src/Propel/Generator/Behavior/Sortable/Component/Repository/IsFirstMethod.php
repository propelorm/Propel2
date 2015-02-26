<?php

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IsFirstMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();

        $body = "
\$reader = \$this->getEntityMap()->getPropReader();
return 1 === \$reader(\$entity, {$behavior->getFieldForParameter('rank_field')->getName()});
";

        $this->addMethod('isFirst')
            ->addSimpleParameter('entity', 'object')
            ->setDescription("Check if the object is first in the list, i.e. if it has 1 for rank")
            ->setType("boolean")
            ->setBody($body)
        ;

    }
}