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
use Propel\Generator\Model\Field;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PreUpdateMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $condition = [];
        $behavior = $this->getBehavior();

        foreach ($behavior->getScopes() as $scope) {
            $condition[] = "\$this->getEntityMap()->isFieldModified(\$entity, '$scope')";
        }

        $condition = implode(' OR ', $condition);

        $code = "
foreach (\$event->getEntities() as \$entity) {
    if (!\$entity instanceof {$this->getObjectClassName()}) {
        continue;
    }
            
    // if scope has changed and rank was not modified (if yes, assuming superior action)
    // insert object to the end of new scope and cleanup old one
    if (($condition) && !\$this->getEntityMap()->isFieldModified(\$entity, '{$behavior->getFieldForParameter('rank_field')->getName()}')) { 
        \$this->sortableShiftRank(-1, \$entity->getRank() + 1, null, \$entity->getOldScope());
        \$this->getSortableManager()->insertAtBottom(\$entity);
    }
}
";

        return $code;
    }
}
