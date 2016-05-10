<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\SortableManager;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class MoveToRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}
if (\$repository->getConfiguration()->getSession()->isNew(\$entity)) {
    throw new PropelException('New objects cannot be moved. Please use insertAtRank() instead');
}
if (\$newRank < 1 || \$newRank > \$repository->createQuery()->getMaxRank(" . ($useScope ? "\$entity->getScopeValue(), " : '') . "\$con)) {
    throw new PropelException('Invalid rank ' . \$newRank);
}

if (null === \$con) {
    \$con = \$repository->getConfiguration()->getConnectionManager({$this->getEntityMapClassName()}::DATABASE_NAME)->getWriteConnection();
}

\$oldRank = \$entity->getRank();
if (\$oldRank == \$newRank) {
    return;
}

\$con->transaction(function () use (\$entity, \$repository, \$oldRank, \$newRank) {
    // shift the objects between the old and the new rank
    \$delta = (\$oldRank < \$newRank) ? -1 : 1;
    \$repository->SortableShiftRank(\$delta, min(\$oldRank, \$newRank), max(\$oldRank, \$newRank)" . ($useScope ? ", \$entity->getScopeValue()" : '') . ");

    // move the object to its new rank
    \$entity->setRank(\$newRank);
    \$repository->save(\$entity);
});
";

        $this->addMethod('moveToRank')
            ->setDescription('Move the object to a new rank, and shifts the rank of the objects in between the old and new rank accordingly')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->addSimpleDescParameter('newRank', 'integer', 'New rank value')
            ->addSimpleParameter('con', 'ConnectionInterface', null)
            ->setBody($body)
        ;
    }
}
