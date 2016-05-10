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
class Filters extends BuildComponent
{
    Use NamingTrait;

    public function process()
    {
        if ($this->getBehavior()->useScope()) {
            $this->addTreeRoots();
            $this->addInTree();
        }
        $this->addDescendantsOf();
        $this->addBranchOf();
        $this->addChildrenOf();
        $this->addSiblingsOf();
        $this->addAncestorsOf();
        $this->addRootsOf();

    }

    protected function addTreeRoots()
    {
        $this->addMethod('treeRoots')
            ->setDescription('Filter the query to restrict the result to root objects')
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->setBody("return \$this->addUsingAlias({$this->getEntityMapClassName()}::LEFT_COL, 1, Criteria::EQUAL);")
        ;
    }

    protected function addInTree()
    {
        $this->addMethod('inTree')
            ->setDescription('Returns the objects in a certain tree, from the tree scope')
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter('scope', 'int', 'Scope to determine which objects node to return')
            ->setBody("return \$this->addUsingAlias({$this->getEntityMapClassName()}::SCOPE_COL, \$scope, Criteria::EQUAL);")
        ;
    }

    protected function addDescendantsOf()
    {
        $objectClassName = $this->getObjectClassName();
        $entityMapClassName = $this->getEntityMapClassName();
        $objectName = '$' . $this->getEntity()->getCamelCaseName();
        $body = "
return \$this";
        if ($this->getBehavior()->useScope()) {
            $body .= "
    ->inTree({$objectName}->getScopeValue())";
        }
        $body .= "
    ->addUsingAlias({$entityMapClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::GREATER_THAN)
    ->addUsingAlias({$entityMapClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::LESS_THAN);
";
        $this->addMethod('descendantsOf')
            ->setDescription("Filter the query to restrict the result to descendants of an object")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($this->getEntity()->getCamelCaseName(), $objectClassName, "The object to use for descendant search")
            ->setBody($body)
        ;
    }

    protected function addBranchOf()
    {
        $entityMapClassName = $this->getEntityMapClassName();
        $objectClassName = $this->getObjectClassName();
        $objectName = '$' . $this->getEntity()->getCamelCaseName();
        $body = "
return \$this";
        if ($this->getBehavior()->useScope()) {
            $body .= "
    ->inTree({$objectName}->getScopeValue())";
        }
        $body .= "
    ->addUsingAlias({$entityMapClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::GREATER_EQUAL)
    ->addUsingAlias({$entityMapClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::LESS_EQUAL);
";
        $this->addMethod('branchOf')
            ->setDescription("Filter the query to restrict the result to the branch of an object.
Same as descendantsOf(), except that it includes the object passed as parameter in the result")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($this->getEntity()->getCamelCaseName(), $objectClassName, "The object to use for branch search")
            ->setBody($body)
        ;
    }

    protected function addChildrenOf()
    {
        $objectName = '$' . $this->getEntity()->getCamelCaseName();
        $body = "
return \$this
    ->descendantsOf($objectName)
    ->addUsingAlias({$this->getEntityMapClassName()}::LEVEL_COL, {$objectName}->getLevel() + 1, Criteria::EQUAL);
";
        $this->addMethod('childrenOf')
            ->setDescription("Filter the query to restrict the result to children of an object")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($this->getEntity()->getCamelCaseName(), $this->getObjectClassName(), "The parent object")
            ->setBody($body)
        ;
    }

    protected function addSiblingsOf()
    {
        $objectName = '$' . $this->getEntity()->getCamelCaseName();
        $body = "
\$manager = \$this->getConfiguration()->getRepository('{$this->getObjectClassName(true)}')->getNestedManager();
if (\$manager->isRoot({$objectName})) {
    return \$this->
        add({$this->getEntityMapClassName()}::LEVEL_COL, '1<>1', Criteria::CUSTOM);
} else {
    return \$this
        ->childrenOf(\$manager->getParent({$objectName}))
        ->prune($objectName);
}
";
        $this->addMethod('siblingsOf')
            ->setDescription("Filter the query to restrict the result to children of an object")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($this->getEntity()->getCamelCaseName(), $this->getObjectClassName(), "The object to use for sibling search")
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Connection to use', null)
            ->setBody($body)
        ;
    }

    protected function addAncestorsOf()
    {
        $entityMapClassName = $this->getEntityMapClassName();
        $objectClassName = $this->getObjectClassName();
        $objectName = '$' . $this->getEntity()->getCamelCaseName();

        $body = "
return \$this";
        if ($this->getBehavior()->useScope()) {
            $body .= "
    ->inTree({$objectName}->getScopeValue())";
        }
        $body .= "
    ->addUsingAlias({$entityMapClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::LESS_THAN)
    ->addUsingAlias({$entityMapClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::GREATER_THAN);
";
        $this->addMethod('ancestorsOf')
            ->setDescription("Filter the query to restrict the result to ancestors of an object")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($this->getEntity()->getCamelCaseName(), $objectClassName, "The object to use for ancestor search")
            ->setBody($body)
        ;
    }

    protected function addRootsOf()
    {
        $entityMapClassName = $this->getEntityMapClassName();
        $objectClassName = $this->getObjectClassName();
        $objectName = '$' . $this->getEntity()->getCamelCaseName();
        $body = "
return \$this";
        if ($this->getBehavior()->useScope()) {
            $body .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $body .= "
    ->addUsingAlias({$entityMapClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::LESS_EQUAL)
    ->addUsingAlias({$entityMapClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::GREATER_EQUAL);
";
        $this->addMethod('rootsOf')
            ->setDescription("Filter the query to restrict the result to roots of an object.
Same as ancestorsOf(), except that it includes the object passed as parameter in the result")
            ->setType("\$this|{$this->getQueryClassName()}", "The current query, for fluid interface")
            ->addSimpleDescParameter($this->getEntity()->getCamelCaseName(), $objectClassName, "The object to use for ancestor search")
            ->setBody($body)
        ;
    }
}
