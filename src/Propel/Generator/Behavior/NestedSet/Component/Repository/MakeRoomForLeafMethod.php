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
class MakeRoomForLeafMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
// Update database nodes
\$this->shiftRLValues(2, \$left, null" . ($useScope ? ", \$scope" : "") . ", \$con);

//Update loaded nodes
\$this->updateLoadedNodes(\$prune, \$con);
";
        $method = $this->addMethod('makeRoomForLeaf')
            ->setDescription('Update the tree to allow insertion of a leaf at the specified position')
            ->addSimpleDescParameter('left', 'int', 'Left field value')
            ->setBody($body)
        ;
        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope field value');
        }
        $method
            ->addSimpleDescParameter('prune', $this->getObjectClassName(), 'Object to prune from the shift', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
        ;
    }
}
