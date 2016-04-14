<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
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
    }

    protected function addIsInTree()
    {
        $this->addMethod('isInTree')
            ->setDescription('Tests if object is a node, i.e. if it is inserted in the tree.')
            ->setType("bool")
            ->setBody("return \$this->getLeftValue() > 0 && \$this->getRightValue() > \$this->getLeftValue();")
        ;
    }

    protected function addIsLeaf()
    {
        $this->addMethod('isLeaf')
            ->setDescription('Tests if node is a leaf')
            ->setType("bool")
            ->setBody("return \$this->isInTree() &&  (\$this->getRightValue() - \$this->getLeftValue()) == 1;")
        ;
    }

    protected function addIsRoot()
    {
        $this->addMethod('isRoot')
            ->setDescription('Tests if node is a root')
            ->setType("bool")
            ->setBody("return \$this->isInTree() && \$this->getLeftValue() == 1;")
        ;
    }

    protected function addIsDescendantOf()
    {
        $body = "
return \$this->getRepository()->getNestedManager()->isDescendantOf(\$this, \$parent);
";

        $this->addMethod('isDescendantOf')
            ->setDescription('Tests if node is a descendant of another node')
            ->setType('bool')
            ->addSimpleDescParameter('parent', "{$this->getObjectClassName()}", "The node supposed to be the parent.")
            ->setBody($body);
    }

    protected function addIsAncestorOf()
    {
        $body = "
return \$this->getRepository()->getNestedManager()->isAncestorOf(\$this, \$child);
";

        $this->addMethod('isAncestorOf')
           ->setDescription('Tests if node is a ancestor of another node')
           ->setType('bool')
           ->addSimpleDescParameter('child', "{$this->getObjectClassName()}", 'The child entity')
           ->setBody($body);
    }
}
