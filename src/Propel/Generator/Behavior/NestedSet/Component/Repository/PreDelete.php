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
class PreDelete extends BuildComponent
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

    \$manager = \$this->getNestedManager();

    if (\$manager->isRoot(\$entity)) {
        throw new PropelException('Deletion of a root node is disabled for nested sets. Use "
            . $this->getQueryClassName() . "::deleteTree(" . ($this->getBehavior()->useScope() ? '$scope' : '') . ") instead to delete an entire tree');
    }
    if (\$manager->isInTree(\$entity)) {
        \$manager->deleteDescendants(\$entity);
    }
}
";

        return $code;
    }
}
