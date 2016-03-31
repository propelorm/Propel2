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
class Inserts extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addAddChild();
        $this->addInsertAsFirstChildOf();
        $this->addInsertAsLastChildOf();
        $this->addInsertAsPrevSiblingOf();
        $this->addInsertAsNextSiblingOf();
    }

    protected function addAddChild()
    {
        $objectClassName = $this->getObjectClassName();
        $body = "
if (Configuration::getCurrentConfiguration()->getSession()->isNew(\$node)) {
    throw new PropelException('A $objectClassName object must not be new to accept children.');
}

\$this->insertAsFirstChildOf(\$child, \$node);
";
        $this->addMethod('addChild')
            ->setDescription('Inserts the given \$child node as first child of current.
The modifications in the current object and the tree
are not persisted until the child object is saved.')
            ->addSimpleDescParameter('node', $objectClassName, 'The current node')
            ->addSimpleDescParameter('child', $objectClassName, 'Propel object for child node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsFirstChildOf()
    {
        $objectClassName = $this->getObjectClassName();
        $useScope = $this->getBehavior()->useScope();

        $body= "
if (\$this->isInTree(\$child)) {
    throw new PropelException('A $objectClassName object must not already be in the tree to be inserted. Use the moveToFirstChildOf() instead.');
}
{$this->getRepositoryAssignment()}

\$left = \$parent->getLeftValue() + 1;
// Update node properties
\$child->setLeftValue(\$left);
\$child->setRightValue(\$left + 1);
\$child->setLevel(\$parent->getLevel() + 1);";

    if ($useScope) {
        $body .= "
\$child->setScopeValue(\$parent->getScopeValue());";
    }

    $body .= "
// Keep the tree modification query for the save() transaction
\$query = [
    'callable'  => 'makeRoomForLeaf',
    'arguments' => array(\$left" . ($useScope ? ', $parent->getScopeValue()' : '') . ", \$child)
];
\$repository->addNestedSetQuery(\$query);
";
        $this->addMethod('insertAsFirstChildOf')
            ->setDescription('Inserts the current node as first child of given \$parent node.
The modifications in the current object and the tree
are not persisted until the current object is saved.')
            ->addSimpleDescParameter('child', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('parent', $this->getObjectClassName(), 'Propel object for parent node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsLastChildOf()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
if (\$this->isInTree(\$child)) {
   throw new PropelException(
        'A {$this->getObjectClassName()} object must not already be in the tree to be inserted. Use the moveToLastChildOf() instead.'
    );
}

{$this->getRepositoryAssignment()}

\$left = \$parent->getRightValue();
// Update node properties
\$child->setLeftValue(\$left);
\$child->setRightValue(\$left + 1);
\$child->setLevel(\$parent->getLevel() + 1);
";

if ($useScope) {
    $body .= "
\$child->setScopeValue(\$parent->getScopeValue());
";
}

$body .= "
// Keep the tree modification query for the save() transaction
\$query = [
    'callable'  => 'makeRoomForLeaf',
    'arguments' => array(\$left" . ($useScope ? ', $parent->getScopeValue()' : '') . ", \$child)
];
\$repository->addNestedSetQuery(\$query);
";
        $this->addMethod('insertAsLastChildOf')
            ->setDescription('Inserts the current node as last child of given $parent node
The modifications in the current object and the tree
are not persisted until the current object is saved.
')
            ->addSimpleDescParameter('child', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('parent', $this->getObjectClassName(), 'Propel object for parent node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsPrevSiblingOf()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isInTree(\$node)) {
    throw new PropelException('A {$this->getObjectClassName()} object must not already be in the tree to be inserted. Use the moveToPrevSiblingOf() instead.');
}
\$left = \$sibling->getLeftValue();
// Update node properties
\$node->setLeftValue(\$left);
\$node->setRightValue(\$left + 1);
\$node->setLevel(\$sibling->getLevel());";
    if ($useScope) {
        $body .= "
\$node->setScopeValue(\$sibling->getScopeValue());";
    }
    $body .= "

// Keep the tree modification query for the save() transaction
\$query = [
    'callable'  => 'makeRoomForLeaf',
    'arguments' => [\$left" . ($useScope ? ", \$sibling->getScopeValue()" : "") . ", \$node]
];
\$repository->addNestedSetQuery(\$query);
";
        $this->addMethod('insertAsPrevSiblingOf')
            ->setDescription('Inserts the current node as previous sibling given $sibling node
The modifications in the current object and the tree
are not persisted until the current object is saved.')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('sibling', $this->getObjectClassName(), 'Propel object for sibling node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsNextSiblingOf()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
if (\$this->isInTree(\$node)) {
    throw new PropelException('A {$this->getObjectClassName()} object must not already be in the tree to be inserted. Use the moveToNextSiblingOf() instead.');
}

{$this->getRepositoryAssignment()}

\$left = \$sibling->getRightValue() + 1;
// Update node properties
\$node->setLeftValue(\$left);
\$node->setRightValue(\$left + 1);
\$node->setLevel(\$sibling->getLevel());";
    if ($useScope) {
        $body .= "
\$node->setScopeValue(\$sibling->getScopeValue());";
    }
    $body .= "

// Keep the tree modification query for the save() transaction
\$query = [
    'callable'  => 'makeRoomForLeaf',
    'arguments' => [\$left" . ($useScope ? ", \$sibling->getScopeValue()" : "") . ", \$node]
];
\$repository->addNestedSetQuery(\$query);
";
        $this->addMethod('insertAsNextSiblingOf')
            ->setDescription('Inserts the current node as next sibling given $sibling node
The modifications in the current object and the tree
are not persisted until the current object is saved.')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The current node')
            ->addSimpleDescParameter('sibling', $this->getObjectClassName(), 'Propel object for sibling node')
            ->setBody($body)
        ;
    }
}
