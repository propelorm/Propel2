<?php

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class InsertAtBottomMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        $body = "
\$this->{$behavior->getFieldSetter()}(\$this->createQuery()->getMaxRankArray(" . ($useScope ? "\$entity->getScopeValue()" : '') . ") + 1);

return \$this;
";

        $this->addMethod('insertAtBottom')
            ->addSimpleParameter('entity', 'object')
            ->setDescription('Insert in the last rank. The modifications are not persisted until the object is saved.')
            ->setType('$this|' . $this->getRepositoryClassName())
            ->setBody($body)
        ;

    }
}