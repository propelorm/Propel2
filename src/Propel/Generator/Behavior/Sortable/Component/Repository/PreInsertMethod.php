<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable\Component\Repository;

use Propel\Generator\Behavior\Sortable\SortableBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PreInsertMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var SortableBehavior $behavior */
        $behavior = $this->getBehavior();
        $code = "
foreach (\$event->getEntities() as \$entity) {
    if (!\$entity instanceof {$this->getObjectClassName()}) {
        continue;
    }

    if (!\$this->getEntityMap()->isFieldModified(\$entity, '{$behavior->getFieldForParameter('rank_field')->getName()}')) {
        \$q = \$this->createQuery();";
        if ($behavior->useScope()) {
            if ($behavior->hasMultipleScopes()) {
                $code .= "
        \$maxRank = call_user_func_array([\$q, 'getMaxRank'], \$entity->getScopeValue());";
            } else {
                $code .= "
        \$maxRank = \$q->getMaxRank(\$entity->getScopeValue());";
            }
        } else {
            $code .= "
        \$maxRank = \$q->getMaxRank();";
        }
        $code .= "
        \$entity->setRank(\$maxRank + 1);
    }
}
";

        return $code;
    }
}
