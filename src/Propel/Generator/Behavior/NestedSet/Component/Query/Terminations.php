<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet\Component\Query;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Terminations extends BuildComponent
{
    Use NamingTrait;

    public function process()
    {
        $this->addFindRoot();
        if ($this->getBehavior()->useScope()) {
            $this->addFindRoots();
            $this->addRetrieveRoots();
        }
        $this->addFindTree();
        $this->addDeleteTree();
        $this->addRetrieveRoot();
        $this->addRetrieveTree();
    }

    protected function addFindRoot()
    {
        $useScope = $this->getBehavior()->useScope();
        $body = "
return \$this
    ->addUsingAlias({$this->getEntityMapClassName()}::LEFT_COL, 1, Criteria::EQUAL)";
        if ($useScope) {
            $body .= "
    ->inTree(\$scope)";
        }
        $body .= "
    ->findOne(\$con);
";
        $method = $this->addMethod('findRoot')
            ->setDescription("Returns " . ($useScope ? 'a' : 'the') . " root node for the tree")
            ->setType($this->getObjectClassName(), "The tree root object")
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which root node to return', null);
        }

        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }

    protected function addFindRoots()
    {
        $this->addMethod('findRoots')
            ->setDescription("Returns the root objects for all trees.")
            ->setType("mixed", "the list of results, formatted by the current formatter")
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody("return \$this->treeRoots()->find(\$con);")
        ;
    }

    protected function addFindTree()
    {
        $useScope = $this->getBehavior()->useScope();
        $body = "
return \$this";
        if ($useScope) {
            $body .= "
    ->inTree(\$scope)";
        }
        $body .= "
->orderByBranch()
    ->find(\$con);
";
        $method = $this->addMethod('findTree')
            ->setDescription("Returns " . ($useScope ? 'a' : 'the') . " tree of objects")
            ->setType('mixed', "the list of results, formatted by the current formatter")
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which tree node to return', null);
        }
        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }

    protected function addDeleteTree()
    {
        $useScope = $this->getBehavior()->useScope();

        if ($useScope) {
            $body = "return \$this->inTree(\$scope)->delete(\$con);";
        } else {
            $body = "return \$this->deleteAll(\$con);";
        }

        $method = $this->addMethod('deleteTree')
            ->setType('int', 'The number of deleted nodes')
            ->setDescription('Delete an entire tree')
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which root node to return', null);
        }
        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }

    protected function addRetrieveRoots()
    {
        $body = "
if (null !== \$criteria) {
    \$this->mergeWith(\$criteria);
}

return \$this->filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(1)->find(\$con);
";
        $this->addMethod('retrieveRoots')
            ->setType('ObjectCollection|' . $this->getObjectClassName(), 'Propel objects for root node')
            ->setDescription("Return the root nodes for the tree")
            ->addSimpleDescParameter('criteria', 'Criteria', 'Optional criteria to filter the query', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addRetrieveRoot()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "return \$this->";

        if ($useScope) {
            $body .= "filterBy{$this->getBehavior()->getFieldForParameter('scope_field')->getMethodName()}(\$scope)->";
        }

        $body .= "filterBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}(1)->findOne(\$con);";
        $method = $this->addMethod('retrieveRoot')
            ->setType($this->getObjectClassName(), 'Propel objects for root node')
            ->setDescription("Return the root node for the given scope")
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which root node to return', null);
        }
        $method->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null);
    }

    protected function addRetrieveTree()
    {
        $useScope = $this->getBehavior()->useScope();

        $body = "
        if (null !== \$criteria) {
            \$this->mergeWith(\$criteria);
        }

        return \$this->orderBy{$this->getBehavior()->getFieldForParameter('left_field')->getMethodName()}()";
        if ($useScope) {
            $body .= "->filterBy{$this->getBehavior()->getFieldForParameter('scope_field')->getMethodName()}(\$scope)";
        }
        $body .= "->find(\$con);";

        $method = $this->addMethod('retrieveTree')
            ->setType('ObjectCollection|' . $this->getObjectClassName())
            ->setDescription('Returns the whole tree nodes for a given scope')
            ->setBody($body)
        ;

        if ($useScope) {
            $method->addSimpleDescParameter('scope', 'int', 'Scope to determine which root node to return', null);
        }
        $method
            ->addSimpleDescParameter('criteria', 'Criteria', 'Optional Criteria to filter the query', null)
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
        ;
    }
}
