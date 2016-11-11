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
class Getters extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $this->addGetPk();
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

    protected function addGetPk()
    {
        $body = "
\$entityMap = Configuration::getCurrentConfiguration()->getEntityMap('{$this->getBehavior()->getEntity()->getFullClassName()}');
\$pkFields = \$entityMap->getPrimaryKeys();
\$propReader = \$entityMap->getPropReader();

\$primaryKeys = [];
foreach (\$pkFields as \$pkField) {
    \$primaryKeys[] = \$propReader(\$object, \$pkField->getName());
}

if (count(\$primaryKeys) === 1) {
    return \$primaryKeys[0];
}

return \$primaryKeys;
";
        $this->addMethod('getPk')
            ->setDescription('Return the primary key of a given object.')
            ->setType('mixed')
            ->addSimpleParameter('object', "{$this->getObjectClassName()}")
            ->setBody($body)
        ;
    }

    protected function addGetParent()
    {
        $body = "
{$this->getRepositoryAssignment()}

if (\$this->hasParent(\$node)) {
    \$parent = \$repository->createQuery()
        ->ancestorsOf(\$node)
        ->orderByLevel(true)
        ->findOne(\$con);

    return \$parent;
}
";
        $this->addMethod('getParent')
            ->setDescription("Gets parent node for the current object if it exists.")
            ->setType("{$this->getObjectClassName()}|null", "Propel object if exists.")
            ->addSimpleDescParameter('node', $this->getObjectClassName(), 'The entity to get the parent of')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body);
    }

    protected function addGetPrevSibling()
    {
        $body = "
{$this->getRepositoryAssignment()}

return \$repository->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('right_field')->getMethodName()}(\$node->getLeftValue() - 1)";

        if ($this->behavior->useScope()) {
            $body .= "
    ->inTree(\$node->getScopeValue())";
        }

        $body .= "
    ->findOne(\$con);
";
        $this->addMethod('getPrevSibling')
            ->setDescription('Gets previous sibling for the given node if it exists.')
            ->setType('mixed', 'Propel object if exists else false')
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetNextSibling()
    {
        $body = "
{$this->getRepositoryAssignment()}

return \$repository->createQuery()
    ->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(\$node->getRightValue() + 1)";

        if ($this->getBehavior()->useScope()) {
            $body .= "
    ->inTree(\$node->getScopeValue())";
        }

        $body .= "
    ->findOne(\$con);
";
        $this->addMethod('getNextSibling')
            ->setDescription('Gets next sibling for the given node if it exists.')
            ->setType('mixed','Propel object if exists else false')
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }

    protected function addGetChildren()
    {
        $body = "
if (\$this->isLeaf(\$node) || (Configuration::getCurrentConfiguration()->getSession()->isNew(\$node))) {
    // return empty collection
    return new ObjectCollection();
}

{$this->getRepositoryAssignment()}

return \$repository->createQuery(null, \$criteria)
  ->childrenOf(\$node)
  ->orderByBranch()
    ->find(\$con);
";
        $this->addMethod('getChildren')
            ->setDescription('Gets the children of the given node.')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use.', null)
            ->setBody($body)
        ;
    }

    protected function addGetFirstChild()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isLeaf(\$node)) {
    return null;
}

return \$repository->createQuery(null, \$criteria)
    ->childrenOf(\$node)
    ->orderByBranch()
    ->findOne(\$con);
";
        $this->addMethod('getFirstChild')
            ->setDescription('Gets the first child of a given node.')
            ->setType($this->getObjectClassName())
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetLastChild()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isLeaf(\$node)) {
    return null;
}

return \$repository->createQuery(null, \$criteria)
    ->childrenOf(\$node)
    ->orderByBranch(true)
    ->findOne(\$con);
";
        $this->addMethod('getLastChild')
            ->setDescription('Gets the last child of a given node.')
            ->setType($this->getObjectClassName())
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetSiblings()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isRoot(\$node)) {
    return array();
}

\$query = \$repository->createQuery(null, \$criteria)
        ->childrenOf(\$this->getParent(\$node, \$con))
        ->orderByBranch();
if (!\$includeNode) {
    \$query->prune(\$node);
}

return \$query->find(\$con);
";
        $this->addMethod('getSiblings')
            ->setDescription('Gets the siblings of the given node')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('includeNode', 'bool', 'Whether to include the current node or not', false)
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetDescendants()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isLeaf(\$node)) {
    return array();
}

return \$repository->createQuery(null, \$criteria)
    ->descendantsOf(\$node)
    ->orderByBranch()
    ->find(\$con);
";
        $this->addMethod('getDescendants')
            ->setDescription('Gets descendants for the given node')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetBranch()
    {
        $body = "
{$this->getRepositoryAssignment()}

return \$repository->createQuery(null, \$criteria)
    ->branchOf(\$node)
    ->orderByBranch()
    ->find(\$con);
";

        $this->addMethod('getBranch')
            ->setDescription('Gets descendants for the given node, plus the current node.')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addGetAncestors()
    {
        $body = "
{$this->getRepositoryAssignment()}
if (\$this->isRoot(\$node)) {
    // save one query
    return array();
}

return \$repository->createQuery(null, \$criteria)
    ->ancestorsOf(\$node)
    ->orderByBranch()
    ->find(\$con);
";

        $this->addMethod('getAncestors')
            ->setDescription('Gets ancestors for the given node, starting with the root node.
Use it for breadcrumb paths for instance')
            ->setType('array', "List of {$this->getObjectClassName()} objects")
            ->addSimpleParameter('node', $this->getObjectClassName())
            ->addSimpleDescParameter('criteria', 'Criteria', 'Criteria to filter results.', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }
}
