<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PreDeleteMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $code = "
foreach (\$event->getEntities() as \$entity) {
    if (!\$entity instanceof {$this->getObjectClassName()}) {
        continue;
    }
    
    \$this->sortableShiftRank(-1, \$entity->getRank() + 1, null".
            ($this->getBehavior()->useScope() ? ", \$entity->getScopeValue()" : '') . ");
}
";

        return $code;
    }
}
