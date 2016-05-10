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
class InsertAtRankMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}
\$maxRank = \$repository->createQuery()->getMaxRank(" . ($useScope ? "\$entity->getScopeValue()" : '') . ");
if (\$rank < 1 || \$rank > \$maxRank + 1) {
    throw new PropelException('Invalid rank ' . \$rank);
}

// move the object in the list, at the given rank
\$entity->setRank(\$rank);
if (\$rank != \$maxRank + 1) {
    // Keep the list modification query for the save() transaction
    \$repository->addSortableQuery([
        'callable'  => 'sortableShiftRank',
        'arguments' => [1, \$rank, null, " . ($useScope ? "\$entity->getScopeValue()" : '') . "]
    ]);
}
";

        $this->addMethod('insertAtRank')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->addSimpleParameter('rank', 'int')
            ->setDescription('Insert at specified rank. The modifications are not persisted until the object is saved.')
            ->setBody($body)
        ;
    }
}
