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
use Propel\Runtime\Propel;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Movers extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addMoveToFirstChildOf();
        $this->addMoveToLastChildOf();
        $this->addMoveToPrevSiblingOf();
        $this->addMoveToNextSiblingOf();
        $this->addMoveSubtreeTo();
    }

    protected function addMoveToFirstChildOf()
    {
        $body = "
if (!\$this->isInTree(\$node)) {
    throw new PropelException('A {$this->getObjectClassName()} object must be already in the tree to be moved. Use the insertAsFirstChildOf() instead.');
}

if (\$this->isDescendantOf(\$parent, \$node)) {
    throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
}

\$this->moveSubtreeTo(\$node, \$parent->getLeftValue() + 1, \$parent->getLevel() - \$node->getLevel() + 1" . ($this->getBehavior()->useScope() ? ", \$parent->getScopeValue()" : "") . ", \$con);
";
        $this->addMethod('moveToFirstChildOf')
            ->setDescription('Moves current node and its subtree to be the first child of $parent
The modifications in the current object and the tree are immediate')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('parent', $this->getObjectClassName(), 'Propel object for parent node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveToLastChildOf()
    {
        $body = "
if (!\$this->isInTree(\$node)) {
    throw new PropelException('A {$this->getObjectClassName()} object must be already in the tree to be moved. Use the insertAsLastChildOf() instead.');
}

if (\$this->isDescendantOf(\$parent, \$node)) {
    throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
}

\$this->moveSubtreeTo(\$node, \$parent->getRightValue(), \$parent->getLevel() - \$node->getLevel() + 1" . ($this->getBehavior()->useScope() ? ", \$parent->getScopeValue()" : "") . ", \$con);
";

        $this->addMethod('moveToLastChildOf')
            ->setDescription('Moves current node and its subtree to be the last child of $parent
The modifications in the current object and the tree are immediate')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('parent', $this->getObjectClassName(), 'Propel object for parent node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveToPrevSiblingOf()
    {
        $body = "
if (!\$this->isInTree(\$node)) {
    throw new PropelException('A {$this->getObjectClassName()} object must be already in the tree to be moved. Use the insertAsPrevSiblingOf() instead.');
}
if (\$this->isRoot(\$sibling)) {
    throw new PropelException('Cannot move to previous sibling of a root node.');
}

if (\$this->isDescendantOf(\$sibling, \$node)) {
    throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
}

\$this->moveSubtreeTo(\$node, \$sibling->getLeftValue(), \$sibling->getLevel() - \$node->getLevel()" . ($this->getBehavior()->useScope() ? ", \$sibling->getScopeValue()" : "") . ", \$con);
";

        $this->addMethod('moveToPrevSiblingOf')
            ->setDescription('Moves current node and its subtree to be the previous sibling of $sibling
The modifications in the current object and the tree are immediate')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('sibling', $this->getObjectClassName(), 'Propel object for sibling node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveToNextSiblingOf()
    {
        $body = "
if (!\$this->isInTree(\$node)) {
    throw new PropelException('A {$this->getObjectClassName()} object must be already in the tree to be moved. Use the insertAsNextSiblingOf() instead.');
}
if (\$this->isRoot(\$sibling)) {
    throw new PropelException('Cannot move to next sibling of a root node.');
}

if (\$this->isDescendantOf(\$sibling, \$node)) {
    throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
}

\$this->moveSubtreeTo(\$node, \$sibling->getRightValue() + 1, \$sibling->getLevel() - \$node->getLevel()" . ($this->getBehavior()->useScope() ? ", \$sibling->getScopeValue()" : "") . ", \$con);
";

        $this->addMethod('moveToNextSiblingOf')
            ->setDescription('Moves current node and its subtree to be the next sibling of $sibling
The modifications in the current object and the tree are immediate')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('sibling', $this->getObjectClassName(), 'Propel object for sibling node')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addMoveSubtreeTo()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}

\$left  = \$node->getLeftValue();
\$right = \$node->getRightValue();";

        if ($useScope) {
            $body .= "
\$scope = \$node->getScopeValue();

if (\$targetScope === null) {
    \$targetScope = \$scope;
}
";
        }

        $body .= "
\$treeSize = \$right - \$left +1;

if (null === \$con) {
    \$con = \$repository->getConfiguration()->getConnectionManager({$this->getEntityMapClassName()}::DATABASE_NAME)->getWriteConnection();
}

\$con->transaction(function () use (\$con, \$treeSize, \$destLeft, \$left, \$right, \$levelDelta" . ($useScope ? ", \$scope, \$targetScope" : "") . ", \$repository) {
    \$preventDefault = false;

    // make room next to the target for the subtree
    \$repository->shiftRLValues(\$treeSize, \$destLeft, null" . ($useScope ? ", \$targetScope" : "") . ", \$con);

";

    if ($useScope) {
        $body .= "
    if (\$targetScope != \$scope) {

        //move subtree to < 0, so the items are out of scope.
        \$repository->shiftRLValues(-\$right, \$left, \$right" . ($useScope ? ", \$scope" : "") . ", \$con);

        //update scopes
        \$repository->setNegativeScope(\$targetScope, \$con);

        //update levels
        \$repository->shiftLevel(\$levelDelta, \$left - \$right, 0" . ($useScope ? ", \$targetScope" : "") . ", \$con);

        //move the subtree to the target
        \$repository->shiftRLValues((\$right - \$left) + \$destLeft, \$left - \$right, 0" . ($useScope ? ", \$targetScope" : "") . ", \$con);


        \$preventDefault = true;
    }
";
    }

    $body .= "

    if (!\$preventDefault) {


        if (\$left >= \$destLeft) { // src was shifted too?
            \$left += \$treeSize;
            \$right += \$treeSize;
        }

        if (\$levelDelta) {
            // update the levels of the subtree
            \$repository->shiftLevel(\$levelDelta, \$left, \$right" . ($useScope ? ", \$scope" : "") . ", \$con);
        }

        // move the subtree to the target
        \$repository->shiftRLValues(\$destLeft - \$left, \$left, \$right" . ($useScope ? ", \$scope" : "") . ", \$con);
    }

    // remove the empty room at the previous location of the subtree
    \$repository->shiftRLValues(-\$treeSize, \$right + 1, null" . ($useScope ? ", \$scope" : "") . ", \$con);

    \$repository->updateLoadedNodes();
});
";
        $method = $this->addMethod('moveSubtreeTo')
            ->setDescription('Move current node and its children to location $destLeft and updates rest of tree')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('destLeft', 'int', 'Destination left value')
            ->addSimpleDescParameter('levelDelta', 'int', 'Delta to add to the levels')
            ->setBody($body)
        ;
        if ($useScope) {
            $method->addSimpleParameter('targetScope', 'int', null);
        }
        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }
}
