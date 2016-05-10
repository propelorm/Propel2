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

/**
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class IsFirstMethod extends BuildComponent
{
    public function process()
    {
        $this->addMethod('isFirst')
            ->addSimpleParameter('entity', 'object')
            ->setDescription('Check if the object is first in the list, i.e. if it has 1 for rank')
            ->setType('bool')
            ->setBody('return 1 === $entity->getRank();')
        ;
    }
}
