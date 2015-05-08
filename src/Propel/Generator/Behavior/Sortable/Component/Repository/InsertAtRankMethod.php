<?php

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class InsertAtRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $useScope = $behavior->useScope();

        $body = "
\$maxRank = \$this->createQuery()->getMaxRankArray(" . ($useScope ? "\$entity->getScopeValue()" : '') . ");
if (\$rank < 1 || \$rank > \$maxRank + 1) {
    throw new PropelException('Invalid rank ' . \$rank);
}
\$reader = \$this->getEntityMap()->getPropReader();
return 1 === \$reader(\$entity, {$behavior->getFieldForParameter('rank_field')->getName()});

// move the object in the list, at the given rank
\$this->{$behavior->getFieldSetter()}(\$rank);
if (\$rank != \$maxRank + 1) {
    // Keep the list modification query for the save() transaction
    \$this->sortableQueries[] = array(
        'callable'  => array(\$this, 'sortableShiftRank'),
        'arguments' => array(1, \$rank, null, " . ($useScope ? "\$this->getScopeValue()" : '') . ")
    );
}

return \$this;
";

        $this->addMethod('insertAtRank')
            ->addSimpleParameter('entity', 'object')
            ->addSimpleParameter('rank', 'number')
            ->setDescription('Insert at specified rank. The modifications are not persisted until the object is saved.')
            ->setType('$this|' . $this->getRepositoryClassName())
            ->setBody($body)
        ;

    }
}