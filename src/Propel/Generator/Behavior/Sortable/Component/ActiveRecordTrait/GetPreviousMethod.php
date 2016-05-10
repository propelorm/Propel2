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
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class GetPreviousMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addMethod('getPrevious')
            ->setType($this->getObjectClassName())
            ->setDescription('Get the previous item in the list, i.e. the one for which rank is immediately lower')
            ->setBody('return $this->getRepository()->getSortableManager()->getPrevious($this);')
        ;
    }
}
