<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class IsLastMethod extends BuildComponent
{
    public function process()
    {
        $this->addMethod('isLast')
            ->setDescription('Check if the object is last in the list, i.e. if its rank is the highest rank')
            ->setType('bool')
            ->setBody('return $this->getRepository()->getSortableManager()->isLast($this);')
        ;
    }
}
