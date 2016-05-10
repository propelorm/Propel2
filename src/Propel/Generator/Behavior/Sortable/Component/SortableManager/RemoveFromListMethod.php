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
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class RemoveFromListMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}        
";
        if ($useScope) {
            $body .= "
// check if object is already removed
if (\$entity->getScopeValue() === null) {
    throw new PropelException('Object is already removed (has null scope)');
}

// move the object to the end of null scope
\$entity->setScopeValue(null);";
        } else {
            $body .= "
// Keep the list modification query for the save() transaction
\$repository->addSortableQuery([
    'callable'  => 'SortableShiftRank',
    'arguments' => [-1, \$entity->getRank() + 1, null" . ($useScope ? ", \$entity->getScopeValue()" : '') . "]
]);
// remove the object from the list
\$entity->setRank(null);
";
        }

        $this->addMethod('removeFromList')
            ->addSimpleParameter('entity', $this->getObjectClassName())
            ->setDescription('Removes the current object from the list'.($useScope ? ' (moves it to the null scope)' : '').
 'The modifications are not persisted until the object is saved.')
            ->setBody($body)
        ;
    }
}
