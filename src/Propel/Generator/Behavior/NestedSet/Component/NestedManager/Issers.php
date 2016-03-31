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
class Issers extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addIsInTree();
        $this->addIsLeaf();
        $this->addIsRoot();
        $this->addIsDescendantOf();
        $this->addIsAncestorOf();
        $this->addIsValid();
    }

    protected function addIsInTree()
    {
        $this->addMethod('isInTree')
            ->setDescription('Tests if object is a node, i.e. if it is inserted in the tree.')
            ->setType("bool")
            ->addSimpleDescParameter('object', "{$this->getObjectClassName()}", "The object to test.")
            ->setBody("return \$object->getLeftValue() > 0 && \$object->getRightValue() > \$object->getLeftValue();")
        ;
    }

    protected function addIsLeaf()
    {
        $this->addMethod('isLeaf')
            ->setDescription('Tests if node is a leaf')
            ->setType("bool")
            ->addSimpleDescParameter('node', "{$this->getObjectClassName()}", "The node to test.")
            ->setBody("return \$this->isInTree(\$node) &&  (\$node->getRightValue() - \$node->getLeftValue()) == 1;")
        ;
    }

    protected function addIsRoot()
    {
        $this->addMethod('isRoot')
            ->setDescription('Tests if node is a root')
            ->setType("bool")
            ->addSimpleDescParameter('node', "{$this->getObjectClassName()}", "The node to test.")
            ->setBody("return \$this->isInTree(\$node) && \$node->getLeftValue() == 1;")
        ;
    }

    protected function addIsDescendantOf()
    {
        $body = "";

        if ($this->getBehavior()->useScope()) {
            $body .= "
if (\$descNode->getScopeValue() !== \$parentNode->getScopeValue()) {
    return false; //since the nodes are in different scopes, there's no way that \$descNode is be a descendant of \$parentNode.
}";
        }

        $body .= "

return \$this->isInTree(\$descNode) && \$descNode->getLeftValue() > \$parentNode->getLeftValue() && \$descNode->getRightValue() < \$parentNode->getRightValue();
";

        $this->addMethod('isDescendantOf')
            ->setDescription('Tests if node is a descendant of another node')
            ->setType('bool')
            ->addSimpleDescParameter('descNode', "{$this->getObjectClassName()}", "The node to test if descendant.")
            ->addSimpleDescParameter('parentNode', "{$this->getObjectClassName()}", "The node supposed to be the parent.")
            ->setBody($body);
    }

    protected function addIsAncestorOf()
    {
       $this->addMethod('isAncestorOf')
           ->setDescription('Tests if node is a ancestor of another node')
           ->setType('bool')
           ->addSimpleDescParameter('node', "{$this->getObjectClassName()}", "The node to test if ancestor.")
           ->addSimpleDescParameter('child', "{$this->getObjectClassName()}", 'The child entity')
           ->setBody("return \$this->isDescendantOf(\$child, \$node);");
    }

    protected function addIsValid()
    {
        $body = "
if (is_object(\$node) && \$node->getRightValue() > \$node->getLeftValue()) {
    return true;
}

return false;
";
        $this->addMethod('isValid')
            ->setType('bool')
            ->setDescription('Test if node is valid')
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'Propel object for src node', null)
            ->setBody($body)
        ;
    }
}
