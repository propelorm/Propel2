<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\NestedManager;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class DeleteDescendantsMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $useScope        = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isLeaf(\$node)) {
    // save one query
    return;
}
\$left = \$node->getLeftValue();
\$right = \$node->getRightValue();
";
    if ($useScope) {
        $body .= "
\$scope = \$node->getScopeValue();";
    }
    $body .= "
\$ret = \$repository->createQuery()
    ->descendantsOf(\$node)
    ->delete(\$con);

// fill up the room that was used by descendants
\$repository->shiftRLValues(\$left - \$right + 1, \$right, null" . ($useScope ? ", \$scope" : "") . ", \$con);

// fix the right value for the current node, which is now a leaf
\$node->setRightValue(\$left + 1);

return \$ret;
";
        $this->addMethod('deleteDescendants')
            ->setDescription("Deletes all descendants for the given node.
Instance pooling is wiped out by this command,
so existing {$this->getObjectClassName()} instances are probably invalid (except for the current one)
")
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }
}
