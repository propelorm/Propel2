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
use Propel\Runtime\Configuration;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PostSave extends BuildComponent
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

    //Add the entity to the Nested Set EntityPool
    \$this->addEntityToPool(\$entity);
}
";
        
        return $code;
    }
}
