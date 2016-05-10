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
class InsertAtBottomMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}
\$entity->setRank(\$repository->createQuery()->getMaxRank(" . ($useScope ? "\$entity->getScopeValue()" : '') . ") + 1);
";

        $this->addMethod('insertAtBottom')
            ->addSimpleParameter('entity', 'object')
            ->setDescription('Insert in the last rank. The modifications are not persisted until the object is saved.')
            ->setBody($body)
        ;
    }
}
