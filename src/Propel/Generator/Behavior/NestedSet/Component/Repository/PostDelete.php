<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PostDelete extends BuildComponent
{
    use NamingTrait;

    /**
     * @return string
     */
    public function process()
    {
        $code = "
foreach (\$event->getEntities() as \$entity) {
    if (!\$entity instanceof {$this->getObjectClassName()}) {
        continue;
    }

    //remove the entity from the Nested Set Entity Pool
    \$this->removeEntityFromPool(\$entity);

    \$manager = \$this->getNestedManager();

    if (\$manager->isInTree(\$entity)) {
        // fill up the room that was used by the node
        \$this->shiftRLValues(-2, \$entity->getRightValue() + 1, null". ($this->getBehavior()->useScope() ? ", \$entity->getScopeValue()" : "") . ");
    }
}
";

        return $code;
    }
}
