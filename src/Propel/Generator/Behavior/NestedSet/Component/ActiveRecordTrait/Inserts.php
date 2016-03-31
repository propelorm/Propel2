<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\ActiveRecordTrait;

/**
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Inserts extends NestedSetBuildComponent
{
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
{$this->getNestedManagerAssignment()}
\$manager->addChild(\$this, \$child);

return \$this;
";
        $this->addMethod('addChild')
            ->setDescription('Inserts the given \$child node as first child of current.
The modifications in the current object and the tree
are not persisted until the child object is saved.')
            ->setType("\$this|{$objectClassName}", "The current Propel object for fluid api")
            ->addSimpleDescParameter('child', $objectClassName, 'Propel object for child node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsFirstChildOf()
    {
        $body= "
{$this->getNestedManagerAssignment()}
\$manager->insertAsFirstChildOf(\$this, \$parent);

return \$this;
";
        $this->addMethod('insertAsFirstChildOf')
            ->setDescription('Inserts the current node as first child of given \$parent node.
The modifications in the current object and the tree
are not persisted until the current object is saved.')
            ->setType("\$this|{$this->getObjectClassName()}", "The current Propel object for fluid api")
            ->addSimpleDescParameter('parent', $this->getObjectClassName(), 'Propel object for parent node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsLastChildOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "
{$this->getNestedManagerAssignment()}
\$manager->insertAsLastChildOf(\$this, \$parent);

return \$this;
";
        $this->addMethod('insertAsLastChildOf')
            ->setDescription('Inserts the current node as last child of given $parent node
The modifications in the current object and the tree
are not persisted until the current object is saved.
')
            ->setType("\$this|{$objectClassName}", "The current Propel object for fluid api")
            ->addSimpleDescParameter('parent', $objectClassName, 'Propel object for parent node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsPrevSiblingOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "{$this->getNestedManagerAssignment()}
\$manager->insertAsPrevSiblingOf(\$this, \$sibling);

return \$this;
";
        $this->addMethod('insertAsPrevSiblingOf')
            ->setDescription('Inserts the current node as previous sibling given $sibling node
The modifications in the current object and the tree
are not persisted until the current object is saved.')
            ->setType("\$this|{$objectClassName}", "The current Propel object for fluid api")
            ->addSimpleDescParameter('sibling', $objectClassName, 'Propel object for sibling node')
            ->setBody($body)
        ;
    }

    protected function addInsertAsNextSiblingOf()
    {
        $objectClassName = $this->getObjectClassName();

        $body = "
{$this->getNestedManagerAssignment()}
\$manager->insertAsNextSiblingOf(\$this, \$sibling);

return \$this;
";
        $this->addMethod('insertAsNextSiblingOf')
            ->setDescription('Inserts the current node as next sibling given $sibling node
The modifications in the current object and the tree
are not persisted until the current object is saved.')
            ->setType("\$this|{$objectClassName}", "The current Propel object for fluid api")
            ->addSimpleDescParameter('sibling', $objectClassName, 'Propel object for sibling node')
            ->setBody($body)
        ;
    }
}
