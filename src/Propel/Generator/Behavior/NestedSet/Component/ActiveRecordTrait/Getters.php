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
class Getters extends NestedSetBuildComponent
{
    public function process()
    {
        $this->addGetParent();
        $this->addGetPrevSibling();
        $this->addGetNextSibling();
        $this->addGetChildren();
        $this->addGetFirstChild();
        $this->addGetLastChild();
        $this->addGetSiblings();
        $this->addGetDescendants();
        $this->addGetBranch();
        $this->addGetAncestors();
    }

    protected function addGetParent()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getParent(\$this,\$con);
";
        $this->addMethod('getParent')
            ->setDescription("Gets parent node for the current object if it exists.")
            ->setType("{$this->getObjectClassName()}|null", "Propel object if exists.")
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body);
    }

    protected function addGetPrevSibling()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getPrevSibling(\$this, \$con);
";
        $this->addMethod('getPrevSibling')
            ->setDescription('Gets previous sibling for the given node if it exists.')
            ->setType('mixed', 'Propel object if exists else false')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetNextSibling()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getNextSibling(\$this, \$con);
";
        $this->addMethod('getNextSibling')
            ->setDescription('Gets next sibling for the given node if it exists.')
            ->setType('mixed','Propel object if exists else false')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }

    protected function addGetChildren()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getChildren(\$this, \$criteria, \$con);
";
        $this->addMethod('getChildren')
            ->setDescription('Gets the children of the given node.')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }

    protected function addGetFirstChild()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getFirstChild(\$this, \$criteria, \$con);
";
        $this->addMethod('getFirstChild')
            ->setDescription('Gets the first child of a given node.')
            ->setType($this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetLastChild()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getLastChild(\$this, \$criteria, \$con);
";
        $this->addMethod('getLastChild')
            ->setDescription('Gets the last child of a given node.')
            ->setType($this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetSiblings()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getSiblings(\$this, \$includeNode, \$criteria, \$con);
";
        $this->addMethod('getSiblings')
            ->setDescription('Gets the siblings of the given node')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleDescParameter('includeNode', 'bool', 'Whether to include the current node or not', false)
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetDescendants()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getDescendants(\$this, \$criteria, \$con);
";
        $this->addMethod('getDescendants')
            ->setDescription('Gets descendants for the given node')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetBranch()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getBranch(\$this, \$criteria, \$con);
";

        $this->addMethod('getBranch')
            ->setDescription('Gets descendants for the given node, plus the current node.')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetAncestors()
    {
        $body = "
{$this->getNestedManagerAssignment()}

return \$manager->getAncestors(\$this, \$criteria, \$con);
";

        $this->addMethod('getAncestors')
            ->setDescription('Gets ancestors for the given node, starting with the root node.
Use it for breadcrumb paths for instance')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }
}
