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
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class RemoveFromListMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body ="
\$this->getRepository()->getSortableManager()->removeFromList(\$this);

return \$this;
";

        $this->addMethod('removeFromList')
            ->setDescription('Removes the current object from the list'.($this->getBehavior()->useScope() ? ' (moves it to the null scope)' : '').
 'The modifications are not persisted until the object is saved.')
            ->setType("\$this|{$this->getObjectClassName()}", 'The current object for fluid interface')
            ->setBody($body)
        ;
    }
}
