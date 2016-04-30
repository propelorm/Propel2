<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Table;

/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author François Zaninotto
 * @author heltem <heltem@o2php.com>
 */
class NestedSetBehaviorObjectBuilderModifier
{
    /** @var NestedSetBehavior */
    protected $behavior;

    /** @var Table */
    protected $table;

    /** @var ObjectBuilder */
    protected $builder;

    protected $queryClassName;
    protected $objectClassName;
    protected $useScope;

    public function __construct(NestedSetBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->useScope = $behavior->useScope();
        $this->table    = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getColumnAttribute($name)
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    protected function getColumnPhpName($name)
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    protected function processBuilder(ObjectBuilder $builder)
    {
        $this->queryClassName  = $builder->getQueryClassName();
        $this->objectClassName = $builder->getObjectClassName();
    }

    protected function setBuilder(ObjectBuilder $builder)
    {
        $this->builder = $builder;
        $this->processBuilder($builder);
    }

    public function preSave(ObjectBuilder $builder)
    {
        $this->processBuilder($builder);

        $script = "if (\$this->isNew() && \$this->isRoot()) {
    // check if no other root exist in, the tree
    \$rootExists = {$this->queryClassName}::create()
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, 1, Criteria::EQUAL)";

        if ($this->useScope) {
            $script .= "
        ->addUsingAlias({$this->objectClassName}::SCOPE_COL, \$this->getScopeValue(), Criteria::EQUAL)";
        }

        $script .= "
        ->exists(\$con);
    if (\$rootExists) {
            throw new PropelException(";

        if ($this->useScope) {
            $script .= "sprintf('A root node already exists in this tree with scope \"%s\".', \$this->getScopeValue())";
        } else {
            $script .= "'A root node already exists in this tree. To allow multiple root nodes, add the `use_scope` parameter in the nested_set behavior tag.'";
        }

        $script .= ");
    }
}
\$this->processNestedSetQueries(\$con);";

        return $script;
    }

    public function preDelete(ObjectBuilder $builder)
    {
        return "if (\$this->isRoot()) {
    throw new PropelException('Deletion of a root node is disabled for nested sets. Use {$this->queryClassName}::deleteTree(" . ($this->useScope ? '$scope' : '') . ") instead to delete an entire tree');
}

if (\$this->isInTree()) {
    \$this->deleteDescendants(\$con);
}
";
    }

    public function postDelete(ObjectBuilder $builder)
    {
        $this->processBuilder($builder);

        return "if (\$this->isInTree()) {
    // fill up the room that was used by the node
    {$this->queryClassName}::shiftRLValues(-2, \$this->getRightValue() + 1, null" . ($this->useScope ? ", \$this->getScopeValue()" : "") . ", \$con);
}
";
    }

    public function objectClearReferences(ObjectBuilder $builder)
    {
        return "\$this->collNestedSetChildren = null;
\$this->aNestedSetParent = null;";
    }

    public function objectMethods(ObjectBuilder $builder)
    {
        $this->setBuilder($builder);
        $script = '';

        $this->addProcessNestedSetQueries($script);

        if ('LeftValue' !== $this->getColumnPhpName('left_column')) {
            $this->addGetLeft($script);
        }
        if ('RightValue' !== $this->getColumnPhpName('right_column')) {
            $this->addGetRight($script);
        }
        if ('Level' !== $this->getColumnPhpName('level_column')) {
            $this->addGetLevel($script);
        }
        if ('true' === $this->getParameter('use_scope')
            && 'ScopeValue' !== $this->getColumnPhpName('scope_column')) {
            $this->addGetScope($script);
        }

        if ('LeftValue' !== $this->getColumnPhpName('left_column')) {
            $script .= $this->addSetLeft();
        }
        if ('RightValue' !== $this->getColumnPhpName('right_column')) {
            $this->addSetRight($script);
        }
        if ('Level' !== $this->getColumnPhpName('level_column')) {
            $this->addSetLevel($script);
        }
        if ('true' === $this->getParameter('use_scope')
            && 'ScopeValue' !== $this->getColumnPhpName('scope_column')) {
            $this->addSetScope($script);
        }

        $this->addMakeRoot($script);

        $this->addIsInTree($script);
        $this->addIsRoot($script);
        $this->addIsLeaf($script);
        $this->addIsDescendantOf($script);
        $this->addIsAncestorOf($script);

        $this->addHasParent($script);
        $this->addSetParent($script);
        $this->addGetParent($script);

        $this->addHasPrevSibling($script);
        $this->addGetPrevSibling($script);

        $this->addHasNextSibling($script);
        $this->addGetNextSibling($script);

        $this->addNestedSetChildrenClear($script);
        $this->addNestedSetChildrenInit($script);
        $this->addNestedSetChildAdd($script);
        $this->addHasChildren($script);
        $this->addGetChildren($script);
        $this->addCountChildren($script);

        $this->addGetFirstChild($script);
        $this->addGetLastChild($script);
        $this->addGetSiblings($script);
        $this->addGetDescendants($script);
        $this->addCountDescendants($script);
        $this->addGetBranch($script);
        $this->addGetAncestors($script);

        $this->builder->declareClassFromBuilder($builder->getStubObjectBuilder(), 'Child');
        $this->addAddChild($script);
        $this->addInsertAsFirstChildOf($script);

        $script .= $this->addInsertAsLastChildOf();

        $this->addInsertAsPrevSiblingOf($script);
        $this->addInsertAsNextSiblingOf($script);

        $this->addMoveToFirstChildOf($script);
        $this->addMoveToLastChildOf($script);
        $this->addMoveToPrevSiblingOf($script);
        $this->addMoveToNextSiblingOf($script);
        $this->addMoveSubtreeTo($script);

        $this->addDeleteDescendants($script);

        $this->builder->declareClass(
            '\Propel\Runtime\ActiveRecord\NestedSetRecursiveIterator'
        );

        $script .= $this->addGetIterator();

        return $script;
    }

    protected function addProcessNestedSetQueries(&$script)
    {
        $script .= "
/**
 * Execute queries that were saved to be run inside the save transaction
 *
 * @param  ConnectionInterface \$con Connection to use.
 */
protected function processNestedSetQueries(ConnectionInterface \$con)
{
    foreach (\$this->nestedSetQueries as \$query) {
        \$query['arguments'][] = \$con;
        call_user_func_array(\$query['callable'], \$query['arguments']);
    }
    \$this->nestedSetQueries = array();
}
";
    }
    protected function addGetLeft(&$script)
    {
        $script .= "
/**
 * Proxy getter method for the left value of the nested set model.
 * It provides a generic way to get the value, whatever the actual column name is.
 *
 * @return     int The nested set left value
 */
public function getLeftValue()
{
    return \$this->{$this->getColumnAttribute('left_column')};
}
";
    }

    protected function addGetRight(&$script)
    {
        $script .= "
/**
 * Proxy getter method for the right value of the nested set model.
 * It provides a generic way to get the value, whatever the actual column name is.
 *
 * @return     int The nested set right value
 */
public function getRightValue()
{
    return \$this->{$this->getColumnAttribute('right_column')};
}
";
    }

    protected function addGetLevel(&$script)
    {
        $script .= "
/**
 * Proxy getter method for the level value of the nested set model.
 * It provides a generic way to get the value, whatever the actual column name is.
 *
 * @return     int The nested set level value
 */
public function getLevel()
{
    return \$this->{$this->getColumnAttribute('level_column')};
}
";
    }

    protected function addGetScope(&$script)
    {
        $script .= "
/**
 * Proxy getter method for the scope value of the nested set model.
 * It provides a generic way to get the value, whatever the actual column name is.
 *
 * @return     int The nested set scope value
 */
public function getScopeValue()
{
    return \$this->{$this->getColumnAttribute('scope_column')};
}
";
    }

    protected function addSetLeft()
    {
        return $this->behavior->renderTemplate('objectSetLeft', [
            'objectClassName'   => $this->builder->getObjectClassName(),
            'leftColumn'        => $this->getColumnPhpName('left_column'),
        ]);
    }

    protected function addSetRight(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Proxy setter method for the right value of the nested set model.
 * It provides a generic way to set the value, whatever the actual column name is.
 *
 * @param      int \$v The nested set right value
 * @return     \$this|{$objectClassName} The current object (for fluent API support)
 */
public function setRightValue(\$v)
{
    return \$this->set{$this->getColumnPhpName('right_column')}(\$v);
}
";
    }

    protected function addSetLevel(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Proxy setter method for the level value of the nested set model.
 * It provides a generic way to set the value, whatever the actual column name is.
 *
 * @param      int \$v The nested set level value
 * @return     \$this|{$objectClassName} The current object (for fluent API support)
 */
public function setLevel(\$v)
{
    return \$this->set{$this->getColumnPhpName('level_column')}(\$v);
}
";
    }

    protected function addSetScope(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Proxy setter method for the scope value of the nested set model.
 * It provides a generic way to set the value, whatever the actual column name is.
 *
 * @param      int \$v The nested set scope value
 * @return     \$this|{$objectClassName} The current object (for fluent API support)
 */
public function setScopeValue(\$v)
{
    return \$this->set{$this->getColumnPhpName('scope_column')}(\$v);
}
";
    }

    protected function addMakeRoot(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Creates the supplied node as the root node.
 *
 * @return     \$this|{$objectClassName} The current object (for fluent API support)
 * @throws     PropelException
 */
public function makeRoot()
{
    if (\$this->getLeftValue() || \$this->getRightValue()) {
        throw new PropelException('Cannot turn an existing node into a root node.');
    }

    \$this->setLeftValue(1);
    \$this->setRightValue(2);
    \$this->setLevel(0);

    return \$this;
}
";
    }

    protected function addIsInTree(&$script)
    {
        $script .= "
/**
 * Tests if object is a node, i.e. if it is inserted in the tree
 *
 * @return     bool
 */
public function isInTree()
{
    return \$this->getLeftValue() > 0 && \$this->getRightValue() > \$this->getLeftValue();
}
";
    }

    protected function addIsRoot(&$script)
    {
        $script .= "
/**
 * Tests if node is a root
 *
 * @return     bool
 */
public function isRoot()
{
    return \$this->isInTree() && \$this->getLeftValue() == 1;
}
";
    }

    protected function addIsLeaf(&$script)
    {
        $script .= "
/**
 * Tests if node is a leaf
 *
 * @return     bool
 */
public function isLeaf()
{
    return \$this->isInTree() &&  (\$this->getRightValue() - \$this->getLeftValue()) == 1;
}
";
    }

    protected function addIsDescendantOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Tests if node is a descendant of another node
 *
 * @param      $objectClassName \$parent Propel node object
 * @return     bool
 */
public function isDescendantOf($objectClassName \$parent)
{";
        if ($this->useScope) {
            $script .= "
    if (\$this->getScopeValue() !== \$parent->getScopeValue()) {
        return false; //since the `this` and \$parent are in different scopes, there's no way that `this` is be a descendant of \$parent.
    }
";
        }
        $script .= "
    return \$this->isInTree() && \$this->getLeftValue() > \$parent->getLeftValue() && \$this->getRightValue() < \$parent->getRightValue();
}
";
    }

    protected function addIsAncestorOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Tests if node is a ancestor of another node
 *
 * @param      $objectClassName \$child Propel node object
 * @return     bool
 */
public function isAncestorOf($objectClassName \$child)
{
    return \$child->isDescendantOf(\$this);
}
";
    }

    protected function addHasParent(&$script)
    {
        $script .= "
/**
 * Tests if object has an ancestor
 *
 * @return boolean
 */
public function hasParent()
{
    return \$this->getLevel() > 0;
}
";
    }

    protected function addSetParent(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Sets the cache for parent node of the current object.
 * Warning: this does not move the current object in the tree.
 * Use moveTofirstChildOf() or moveToLastChildOf() for that purpose
 *
 * @param      $objectClassName \$parent
 * @return     \$this|{$objectClassName} The current object, for fluid interface
 */
public function setParent($objectClassName \$parent = null)
{
    \$this->aNestedSetParent = \$parent;

    return \$this;
}
";
    }

    protected function addGetParent(&$script)
    {
        $script .= "
/**
 * Gets parent node for the current object if it exists
 * The result is cached so further calls to the same method don't issue any queries
 *
 * @param  ConnectionInterface \$con Connection to use.
 * @return {$this->objectClassName}|null Propel object if exists else null
 */
public function getParent(ConnectionInterface \$con = null)
{
    if (null === \$this->aNestedSetParent && \$this->hasParent()) {
        \$this->aNestedSetParent = {$this->queryClassName}::create()
            ->ancestorsOf(\$this)
            ->orderByLevel(true)
            ->findOne(\$con);
    }

    return \$this->aNestedSetParent;
}
";
    }

    protected function addHasPrevSibling(&$script)
    {
        $script .= "
/**
 * Determines if the node has previous sibling
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     bool
 */
public function hasPrevSibling(ConnectionInterface \$con = null)
{
    if (!{$this->queryClassName}::isValid(\$this)) {
        return false;
    }

    return {$this->queryClassName}::create()
        ->filterBy" . $this->getColumnPhpName('right_column') . "(\$this->getLeftValue() - 1)";
        if ($this->useScope) {
            $script .= "
        ->inTree(\$this->getScopeValue())";
        }
        $script .= "
        ->exists(\$con);
}
";
    }

    protected function addGetPrevSibling(&$script)
    {
        $script .= "
/**
 * Gets previous sibling for the given node if it exists
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     {$this->objectClassName}|null         Propel object if exists else null
 */
public function getPrevSibling(ConnectionInterface \$con = null)
{
    return {$this->queryClassName}::create()
        ->filterBy" . $this->getColumnPhpName('right_column') . "(\$this->getLeftValue() - 1)";
        if ($this->useScope) {
            $script .= "
        ->inTree(\$this->getScopeValue())";
        }
        $script .= "
        ->findOne(\$con);
}
";
    }

    protected function addHasNextSibling(&$script)
    {
        $script .= "
/**
 * Determines if the node has next sibling
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     bool
 */
public function hasNextSibling(ConnectionInterface \$con = null)
{
    if (!{$this->queryClassName}::isValid(\$this)) {
        return false;
    }

    return {$this->queryClassName}::create()
        ->filterBy" . $this->getColumnPhpName('left_column') . "(\$this->getRightValue() + 1)";
        if ($this->useScope) {
            $script .= "
        ->inTree(\$this->getScopeValue())";
        }
        $script .= "
        ->exists(\$con);
}
";
    }

    protected function addGetNextSibling(&$script)
    {
        $script .= "
/**
 * Gets next sibling for the given node if it exists
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     {$this->objectClassName}|null         Propel object if exists else null
 */
public function getNextSibling(ConnectionInterface \$con = null)
{
    return {$this->queryClassName}::create()
        ->filterBy" . $this->getColumnPhpName('left_column') . "(\$this->getRightValue() + 1)";
        if ($this->useScope) {
            $script .= "
        ->inTree(\$this->getScopeValue())";
        }
        $script .= "
        ->findOne(\$con);
}
";
    }

    protected function addNestedSetChildrenClear(&$script)
    {
        $script .= "
/**
 * Clears out the \$collNestedSetChildren collection
 *
 * This does not modify the database; however, it will remove any associated objects, causing
 * them to be refetched by subsequent calls to accessor method.
 *
 * @return     void
 */
public function clearNestedSetChildren()
{
    \$this->collNestedSetChildren = null;
}
";
    }

    protected function addNestedSetChildrenInit(&$script)
    {
        $script .= "
/**
 * Initializes the \$collNestedSetChildren collection.
 *
 * @return     void
 */
public function initNestedSetChildren()
{
    \$collectionClassName = ".$this->builder->getNewTableMapBuilder($this->table)->getFullyQualifiedClassName()."::getTableMap()->getCollectionClassName();

    \$this->collNestedSetChildren = new \$collectionClassName;
    \$this->collNestedSetChildren->setModel('" . $this->builder->getNewStubObjectBuilder($this->table)->getFullyQualifiedClassName() . "');
}
";
    }

    protected function addNestedSetChildAdd(&$script)
    {
        $objectName      = '$' . $this->table->getCamelCaseName();

        $script .= "
/**
 * Adds an element to the internal \$collNestedSetChildren collection.
 * Beware that this doesn't insert a node in the tree.
 * This method is only used to facilitate children hydration.
 *
 * @param      {$this->objectClassName} $objectName
 *
 * @return     void
 */
public function addNestedSetChild({$this->objectClassName} $objectName)
{
    if (null === \$this->collNestedSetChildren) {
        \$this->initNestedSetChildren();
    }
    if (!in_array($objectName, \$this->collNestedSetChildren->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
        \$this->collNestedSetChildren[]= $objectName;
        {$objectName}->setParent(\$this);
    }
}
";
    }

    protected function addHasChildren(&$script)
    {
        $script .= "
/**
 * Tests if node has children
 *
 * @return     bool
 */
public function hasChildren()
{
    return (\$this->getRightValue() - \$this->getLeftValue()) > 1;
}
";
    }

    protected function addGetChildren(&$script)
    {
        $script .= "
/**
 * Gets the children of the given node
 *
 * @param      Criteria  \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     ObjectCollection|{$this->objectClassName}[] List of {$this->objectClassName} objects
 */
public function getChildren(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$this->collNestedSetChildren || null !== \$criteria) {
        if (\$this->isLeaf() || (\$this->isNew() && null === \$this->collNestedSetChildren)) {
            // return empty collection
            \$this->initNestedSetChildren();
        } else {
            \$collNestedSetChildren = {$this->queryClassName}::create(null, \$criteria)
                ->childrenOf(\$this)
                ->orderByBranch()
                ->find(\$con);
            if (null !== \$criteria) {
                return \$collNestedSetChildren;
            }
            \$this->collNestedSetChildren = \$collNestedSetChildren;
        }
    }

    return \$this->collNestedSetChildren;
}
";
    }

    protected function addCountChildren(&$script)
    {
        $script .= "
/**
 * Gets number of children for the given node
 *
 * @param      Criteria  \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     int       Number of children
 */
public function countChildren(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$this->collNestedSetChildren || null !== \$criteria) {
        if (\$this->isLeaf() || (\$this->isNew() && null === \$this->collNestedSetChildren)) {
            return 0;
        } else {
            return {$this->queryClassName}::create(null, \$criteria)
                ->childrenOf(\$this)
                ->count(\$con);
        }
    } else {
        return count(\$this->collNestedSetChildren);
    }
}
";
    }

    protected function addGetFirstChild(&$script)
    {
        $script .= "
/**
 * Gets the first child of the given node
 *
 * @param      Criteria \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     {$this->objectClassName}|null First child or null if this is a leaf
 */
public function getFirstChild(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        return null;
    } else {
        return {$this->queryClassName}::create(null, \$criteria)
            ->childrenOf(\$this)
            ->orderByBranch()
            ->findOne(\$con);
    }
}
";
    }

    protected function addGetLastChild(&$script)
    {
        $script .= "
/**
 * Gets the last child of the given node
 *
 * @param      Criteria \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     {$this->objectClassName}|null Last child or null if this is a leaf
 */
public function getLastChild(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        return null;
    } else {
        return {$this->queryClassName}::create(null, \$criteria)
            ->childrenOf(\$this)
            ->orderByBranch(true)
            ->findOne(\$con);
    }
}
";
    }

    protected function addGetSiblings(&$script)
    {
        $script .= "
/**
 * Gets the siblings of the given node
 *
 * @param boolean             \$includeNode Whether to include the current node or not
 * @param Criteria            \$criteria Criteria to filter results.
 * @param ConnectionInterface \$con Connection to use.
 *
 * @return ObjectCollection|{$this->objectClassName}[] List of {$this->objectClassName} objects
 */
public function getSiblings(\$includeNode = false, Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (\$this->isRoot()) {
        return array();
    } else {
        \$query = {$this->queryClassName}::create(null, \$criteria)
            ->childrenOf(\$this->getParent(\$con))
            ->orderByBranch();
        if (!\$includeNode) {
            \$query->prune(\$this);
        }

        return \$query->find(\$con);
    }
}
";
    }

    protected function addGetDescendants(&$script)
    {
        $script .= "
/**
 * Gets descendants for the given node
 *
 * @param      Criteria \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     ObjectCollection|{{$this->objectClassName}}[] List of {$this->objectClassName} objects
 */
public function getDescendants(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        return array();
    } else {
        return {$this->queryClassName}::create(null, \$criteria)
            ->descendantsOf(\$this)
            ->orderByBranch()
            ->find(\$con);
    }
}
";
    }

    protected function addCountDescendants(&$script)
    {
        $script .= "
/**
 * Gets number of descendants for the given node
 *
 * @param      Criteria \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     int         Number of descendants
 */
public function countDescendants(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        // save one query
        return 0;
    } else {
        return {$this->queryClassName}::create(null, \$criteria)
            ->descendantsOf(\$this)
            ->count(\$con);
    }
}
";
    }

    protected function addGetBranch(&$script)
    {
        $script .= "
/**
 * Gets descendants for the given node, plus the current node
 *
 * @param      Criteria \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     ObjectCollection|{$this->objectClassName}[] List of {$this->objectClassName} objects
 */
public function getBranch(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    return {$this->queryClassName}::create(null, \$criteria)
        ->branchOf(\$this)
        ->orderByBranch()
        ->find(\$con);
}
";
    }

    protected function addGetAncestors(&$script)
    {
        $script .= "
/**
 * Gets ancestors for the given node, starting with the root node
 * Use it for breadcrumb paths for instance
 *
 * @param      Criteria \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     ObjectCollection|{$this->objectClassName}[] List of {$this->objectClassName} objects
 */
public function getAncestors(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (\$this->isRoot()) {
        // save one query
        return array();
    } else {
        return {$this->queryClassName}::create(null, \$criteria)
            ->ancestorsOf(\$this)
            ->orderByBranch()
            ->find(\$con);
    }
}
";
    }

    protected function addAddChild(&$script)
    {
        $script .= "
/**
 * Inserts the given \$child node as first child of current
 * The modifications in the current object and the tree
 * are not persisted until the child object is saved.
 *
 * @param      {$this->objectClassName} \$child    Propel object for child node
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function addChild({$this->objectClassName} \$child)
{
    if (\$this->isNew()) {
        throw new PropelException('A {$this->objectClassName} object must not be new to accept children.');
    }
    \$child->insertAsFirstChildOf(\$this);

    return \$this;
}
";
    }

    protected function addInsertAsFirstChildOf(&$script)
    {
        $queryClassName  = $this->builder->getQueryClassName(true);

        $script .= "
/**
 * Inserts the current node as first child of given \$parent node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      {$this->objectClassName} \$parent    Propel object for parent node
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function insertAsFirstChildOf({$this->objectClassName} \$parent)
{
    if (\$this->isInTree()) {
        throw new PropelException('A {$this->objectClassName} object must not already be in the tree to be inserted. Use the moveToFirstChildOf() instead.');
    }
    \$left = \$parent->getLeftValue() + 1;
    // Update node properties
    \$this->setLeftValue(\$left);
    \$this->setRightValue(\$left + 1);
    \$this->setLevel(\$parent->getLevel() + 1);";

        if ($this->useScope) {
            $script .= "
    \$scope = \$parent->getScopeValue();
    \$this->setScopeValue(\$scope);";
        }

        $script .= "
    // update the children collection of the parent
    \$parent->addNestedSetChild(\$this);

    // Keep the tree modification query for the save() transaction
    \$this->nestedSetQueries[] = array(
        'callable'  => array('$queryClassName', 'makeRoomForLeaf'),
        'arguments' => array(\$left" . ($this->useScope ? ", \$scope" : "") . ", \$this->isNew() ? null : \$this)
    );

    return \$this;
}
";
    }

    protected function addInsertAsLastChildOf()
    {
        return $this->behavior->renderTemplate('objectInsertAsLastChildOf', [
            'objectClassName' => $this->objectClassName,
            'queryClassName'  => $this->builder->getQueryClassName(true),
            'useScope'        => $this->useScope,
        ]);
    }

    protected function addInsertAsPrevSiblingOf(&$script)
    {
        $queryClassName  = $this->builder->getQueryClassName(true);

        $script .= "
/**
 * Inserts the current node as prev sibling given \$sibling node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      {$this->objectClassName} \$sibling    Propel object for parent node
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function insertAsPrevSiblingOf({$this->objectClassName} \$sibling)
{
    if (\$this->isInTree()) {
        throw new PropelException('A ({$this->objectClassName} object must not already be in the tree to be inserted. Use the moveToPrevSiblingOf() instead.');
    }
    \$left = \$sibling->getLeftValue();
    // Update node properties
    \$this->setLeftValue(\$left);
    \$this->setRightValue(\$left + 1);
    \$this->setLevel(\$sibling->getLevel());";
        if ($this->useScope) {
            $script .= "
    \$scope = \$sibling->getScopeValue();
    \$this->setScopeValue(\$scope);";
        }
        $script .= "
    // Keep the tree modification query for the save() transaction
    \$this->nestedSetQueries []= array(
        'callable'  => array('$queryClassName', 'makeRoomForLeaf'),
        'arguments' => array(\$left" . ($this->useScope ? ", \$scope" : "") . ", \$this->isNew() ? null : \$this)
    );

    return \$this;
}
";
    }

    protected function addInsertAsNextSiblingOf(&$script)
    {
        $queryClassName  = $this->builder->getQueryClassName(true);

        $script .= "
/**
 * Inserts the current node as next sibling given \$sibling node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      {$this->objectClassName} \$sibling    Propel object for parent node
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function insertAsNextSiblingOf({$this->objectClassName} \$sibling)
{
    if (\$this->isInTree()) {
        throw new PropelException('A {$this->objectClassName} object must not already be in the tree to be inserted. Use the moveToNextSiblingOf() instead.');
    }
    \$left = \$sibling->getRightValue() + 1;
    // Update node properties
    \$this->setLeftValue(\$left);
    \$this->setRightValue(\$left + 1);
    \$this->setLevel(\$sibling->getLevel());";
        if ($this->useScope) {
            $script .= "
    \$scope = \$sibling->getScopeValue();
    \$this->setScopeValue(\$scope);";
        }
        $script .= "
    // Keep the tree modification query for the save() transaction
    \$this->nestedSetQueries []= array(
        'callable'  => array('$queryClassName', 'makeRoomForLeaf'),
        'arguments' => array(\$left" . ($this->useScope ? ", \$scope" : "") . ", \$this->isNew() ? null : \$this)
    );

    return \$this;
}
";
    }

    protected function addMoveToFirstChildOf(&$script)
    {
        $script .= "
/**
 * Moves current node and its subtree to be the first child of \$parent
 * The modifications in the current object and the tree are immediate
 *
 * @param      {$this->objectClassName} \$parent    Propel object for parent node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function moveToFirstChildOf({$this->objectClassName} \$parent, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A {$this->objectClassName} object must be already in the tree to be moved. Use the insertAsFirstChildOf() instead.');
    }";

        $script .= "
    if (\$parent->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$parent->getLeftValue() + 1, \$parent->getLevel() - \$this->getLevel() + 1" . ($this->useScope ? ", \$parent->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveToLastChildOf(&$script)
    {
        $script .= "
/**
 * Moves current node and its subtree to be the last child of \$parent
 * The modifications in the current object and the tree are immediate
 *
 * @param      {$this->objectClassName} \$parent    Propel object for parent node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function moveToLastChildOf({$this->objectClassName} \$parent, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A {$this->objectClassName} object must be already in the tree to be moved. Use the insertAsLastChildOf() instead.');
    }";

        $script .= "
    if (\$parent->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$parent->getRightValue(), \$parent->getLevel() - \$this->getLevel() + 1" . ($this->useScope ? ", \$parent->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveToPrevSiblingOf(&$script)
    {
        $script .= "
/**
 * Moves current node and its subtree to be the previous sibling of \$sibling
 * The modifications in the current object and the tree are immediate
 *
 * @param      {$this->objectClassName} \$sibling    Propel object for sibling node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function moveToPrevSiblingOf({$this->objectClassName} \$sibling, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A {$this->objectClassName} object must be already in the tree to be moved. Use the insertAsPrevSiblingOf() instead.');
    }
    if (\$sibling->isRoot()) {
        throw new PropelException('Cannot move to previous sibling of a root node.');
    }";

        $script .= "
    if (\$sibling->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$sibling->getLeftValue(), \$sibling->getLevel() - \$this->getLevel()" . ($this->useScope ? ", \$sibling->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveToNextSiblingOf(&$script)
    {
        $script .= "
/**
 * Moves current node and its subtree to be the next sibling of \$sibling
 * The modifications in the current object and the tree are immediate
 *
 * @param      {$this->objectClassName} \$sibling    Propel object for sibling node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     \$this|{$this->objectClassName} The current Propel object
 */
public function moveToNextSiblingOf({$this->objectClassName} \$sibling, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A {$this->objectClassName} object must be already in the tree to be moved. Use the insertAsNextSiblingOf() instead.');
    }
    if (\$sibling->isRoot()) {
        throw new PropelException('Cannot move to next sibling of a root node.');
    }";

        $script .= "
    if (\$sibling->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$sibling->getRightValue() + 1, \$sibling->getLevel() - \$this->getLevel()" . ($this->useScope ? ", \$sibling->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveSubtreeTo(&$script)
    {
        $tableMapClass  = $this->builder->getTableMapClass();

        $script .= "
/**
 * Move current node and its children to location \$destLeft and updates rest of tree
 *
 * @param      int    \$destLeft Destination left value
 * @param      int    \$levelDelta Delta to add to the levels
 * @param      ConnectionInterface \$con        Connection to use.
 */
protected function moveSubtreeTo(\$destLeft, \$levelDelta" . ($this->useScope ? ", \$targetScope = null" : "") . ", ConnectionInterface \$con = null)
{
    \$left  = \$this->getLeftValue();
    \$right = \$this->getRightValue();";

        if ($this->useScope) {
            $script .= "
    \$scope = \$this->getScopeValue();

    if (\$targetScope === null) {
        \$targetScope = \$scope;
    }";
        }

        $script .= "

    \$treeSize = \$right - \$left +1;

    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection($tableMapClass::DATABASE_NAME);
    }

    \$con->transaction(function () use (\$con, \$treeSize, \$destLeft, \$left, \$right, \$levelDelta" . ($this->useScope ? ", \$scope, \$targetScope" : "") . ") {
        \$preventDefault = false;

        // make room next to the target for the subtree
        {$this->queryClassName}::shiftRLValues(\$treeSize, \$destLeft, null" . ($this->useScope ? ", \$targetScope" : "") . ", \$con);
";

        if ($this->useScope) {
            $script .= "
        if (\$targetScope != \$scope) {

            //move subtree to < 0, so the items are out of scope.
            {$this->queryClassName}::shiftRLValues(-\$right, \$left, \$right" . ($this->useScope ? ", \$scope" : "") . ", \$con);

            //update scopes
            {$this->queryClassName}::setNegativeScope(\$targetScope, \$con);

            //update levels
            {$this->queryClassName}::shiftLevel(\$levelDelta, \$left - \$right, 0" . ($this->useScope ? ", \$targetScope" : "") . ", \$con);

            //move the subtree to the target
            {$this->queryClassName}::shiftRLValues((\$right - \$left) + \$destLeft, \$left - \$right, 0" . ($this->useScope ? ", \$targetScope" : "") . ", \$con);


            \$preventDefault = true;
        }
";
        }

        $script .= "
        if (!\$preventDefault) {

            if (\$left >= \$destLeft) { // src was shifted too?
                \$left += \$treeSize;
                \$right += \$treeSize;
            }

            if (\$levelDelta) {
                // update the levels of the subtree
                {$this->queryClassName}::shiftLevel(\$levelDelta, \$left, \$right" . ($this->useScope ? ", \$scope" : "") . ", \$con);
            }

            // move the subtree to the target
            {$this->queryClassName}::shiftRLValues(\$destLeft - \$left, \$left, \$right" . ($this->useScope ? ", \$scope" : "") . ", \$con);
        }
";

        $script .= "
        // remove the empty room at the previous location of the subtree
        {$this->queryClassName}::shiftRLValues(-\$treeSize, \$right + 1, null" . ($this->useScope ? ", \$scope" : "") . ", \$con);

        // update all loaded nodes
        {$this->queryClassName}::updateLoadedNodes(null, \$con);
    });
}
";
    }

    protected function addDeleteDescendants(&$script)
    {
        $tableMapClass   = $this->builder->getTableMapClass();

        $script .= "
/**
 * Deletes all descendants for the given node
 * Instance pooling is wiped out by this command,
 * so existing {$this->objectClassName} instances are probably invalid (except for the current one)
 *
 * @param      ConnectionInterface \$con Connection to use.
 *
 * @return     int         number of deleted nodes
 */
public function deleteDescendants(ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        // save one query
        return;
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection($tableMapClass::DATABASE_NAME);
    }
    \$left = \$this->getLeftValue();
    \$right = \$this->getRightValue();";
        if ($this->useScope) {
            $script .= "
    \$scope = \$this->getScopeValue();";
        }
        $script .= "

    return \$con->transaction(function () use (\$con, \$left, \$right" . ($this->useScope ? ", \$scope" : "") . ") {
        // delete descendant nodes (will empty the instance pool)
        \$ret = {$this->queryClassName}::create()
            ->descendantsOf(\$this)
            ->delete(\$con);

        // fill up the room that was used by descendants
        {$this->queryClassName}::shiftRLValues(\$left - \$right + 1, \$right, null" . ($this->useScope ? ", \$scope" : "") . ", \$con);

        // fix the right value for the current node, which is now a leaf
        \$this->setRightValue(\$left + 1);

        return \$ret;
    });
}
";
    }

    public function objectAttributes(ObjectBuilder $builder)
    {
        $this->processBuilder($builder);
        $tableName = $this->table->getName();

        $script = "
/**
 * Queries to be executed in the save transaction
 * @var        array
 */
protected \$nestedSetQueries = array();

/**
 * Internal cache for children nodes
 * @var        null|ObjectCollection
 */
protected \$collNestedSetChildren = null;

/**
 * Internal cache for parent node
 * @var        null|{$this->objectClassName}
 */
protected \$aNestedSetParent = null;

/**
 * Left column for the set
 */
const LEFT_COL = '" . $tableName . '.' . $this->behavior->getColumnConstant('left_column') . "';

/**
 * Right column for the set
 */
const RIGHT_COL = '" . $tableName . '.' . $this->behavior->getColumnConstant('right_column') . "';

/**
 * Level column for the set
 */
const LEVEL_COL = '" . $tableName . '.' . $this->behavior->getColumnConstant('level_column') . "';
";

        if ($this->useScope) {
            $script .=     "
/**
 * Scope column for the set
 */
const SCOPE_COL = '" . $tableName . '.' . $this->behavior->getColumnConstant('scope_column') . "';
";
        }

        return $script;
    }

    protected function addGetIterator()
    {
        return $this->behavior->renderTemplate('objectGetIterator');
    }
}
