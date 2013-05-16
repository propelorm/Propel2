<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author François Zaninotto
 * @author heltem <heltem@o2php.com>
 */
class NestedSetBehaviorObjectBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
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

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
    }

    public function preSave($builder)
    {
        $queryClassName  = $builder->getQueryClassName();
        $objectClassName = $builder->getObjectClassName();

        $script = "if (\$this->isNew() && \$this->isRoot()) {
    // check if no other root exist in, the tree
    \$nbRoots = $queryClassName::create()
        ->addUsingAlias($objectClassName::LEFT_COL, 1, Criteria::EQUAL)";

        if ($this->behavior->useScope()) {
            $script .= "
        ->addUsingAlias($objectClassName::SCOPE_COL, \$this->getScopeValue(), Criteria::EQUAL)";
        }

        $script .= "
        ->count(\$con);
    if (\$nbRoots > 0) {
            throw new PropelException(";

        if ($this->behavior->useScope()) {
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

    public function preDelete($builder)
    {
        $queryClassName = $builder->getQueryClassName();

        return "if (\$this->isRoot()) {
    throw new PropelException('Deletion of a root node is disabled for nested sets. Use $queryClassName::deleteTree(" . ($this->behavior->useScope() ? '$scope' : '') . ") instead to delete an entire tree');
}

if (\$this->isInTree()) {
    \$this->deleteDescendants(\$con);
}
";
    }

    public function postDelete($builder)
    {
        $queryClassName = $builder->getQueryClassName();

        return "if (\$this->isInTree()) {
    // fill up the room that was used by the node
    $queryClassName::shiftRLValues(-2, \$this->getRightValue() + 1, null" . ($this->behavior->useScope() ? ", \$this->getScopeValue()" : "") . ", \$con);
}
";
    }

    public function objectClearReferences($builder)
    {
        return "\$this->collNestedSetChildren = null;
\$this->aNestedSetParent = null;";
    }

    public function objectMethods($builder)
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
 */
protected function processNestedSetQueries(\$con)
{
    foreach (\$this->nestedSetQueries as \$query) {
        \$query['arguments'][]= \$con;
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
        return $this->behavior->renderTemplate('objectSetLeft', array(
            'objectClassName'   => $this->builder->getObjectClassName(),
            'leftColumn'        => $this->getColumnPhpName('left_column'),
        ));
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
 * @return     {$objectClassName} The current object (for fluent API support)
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
 * @return     {$objectClassName} The current object (for fluent API support)
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
 * @return     {$objectClassName} The current object (for fluent API support)
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
 * @return     {$objectClassName} The current object (for fluent API support)
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
 * @param      $objectClassName \$node Propel node object
 * @return     bool
 */
public function isDescendantOf(\$parent)
{";
        if ($this->behavior->useScope()) {
            $script .= "
    if (\$this->getScopeValue() !== \$parent->getScopeValue()) {
        return false; //since the `this` and \$parent are in different scopes, there's no way that `this` is be a descendant of \$parent.
    }";
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
 * @param      $objectClassName \$node Propel node object
 * @return     bool
 */
public function isAncestorOf(\$child)
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
 * @return     $objectClassName The current object, for fluid interface
 */
public function setParent(\$parent = null)
{
    \$this->aNestedSetParent = \$parent;

    return \$this;
}
";
    }

    protected function addGetParent(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets parent node for the current object if it exists
 * The result is cached so further calls to the same method don't issue any queries
 *
 * @param  ConnectionInterface \$con Connection to use.
 * @return mixed Propel object if exists else false
 */
public function getParent(ConnectionInterface \$con = null)
{
    if (null === \$this->aNestedSetParent && \$this->hasParent()) {
        \$this->aNestedSetParent = {$queryClassName}::create()
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
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Determines if the node has previous sibling
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     bool
 */
public function hasPrevSibling(ConnectionInterface \$con = null)
{
    if (!{$queryClassName}::isValid(\$this)) {
        return false;
    }

    return $queryClassName::create()
        ->filterBy" . $this->getColumnPhpName('right_column') . "(\$this->getLeftValue() - 1)";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree(\$this->getScopeValue())";
        }
        $script .= "
        ->count(\$con) > 0;
}
";
    }

    protected function addGetPrevSibling(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets previous sibling for the given node if it exists
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     mixed         Propel object if exists else false
 */
public function getPrevSibling(ConnectionInterface \$con = null)
{
    return $queryClassName::create()
        ->filterBy" . $this->getColumnPhpName('right_column') . "(\$this->getLeftValue() - 1)";
        if ($this->behavior->useScope()) {
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
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Determines if the node has next sibling
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     bool
 */
public function hasNextSibling(ConnectionInterface \$con = null)
{
    if (!{$queryClassName}::isValid(\$this)) {
        return false;
    }

    return $queryClassName::create()
        ->filterBy" . $this->getColumnPhpName('left_column') . "(\$this->getRightValue() + 1)";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree(\$this->getScopeValue())";
        }
        $script .= "
        ->count(\$con) > 0;
}
";
    }

    protected function addGetNextSibling(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets next sibling for the given node if it exists
 *
 * @param      ConnectionInterface \$con Connection to use.
 * @return     mixed         Propel object if exists else false
 */
public function getNextSibling(ConnectionInterface \$con = null)
{
    return $queryClassName::create()
        ->filterBy" . $this->getColumnPhpName('left_column') . "(\$this->getRightValue() + 1)";
        if ($this->behavior->useScope()) {
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
    \$this->collNestedSetChildren = new ObjectCollection();
    \$this->collNestedSetChildren->setModel('" . $this->builder->getNewStubObjectBuilder($this->table)->getFullyQualifiedClassName() . "');
}
";
    }

    protected function addNestedSetChildAdd(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $objectName      = '$' . $this->table->getStudlyPhpName();

        $script .= "
/**
 * Adds an element to the internal \$collNestedSetChildren collection.
 * Beware that this doesn't insert a node in the tree.
 * This method is only used to facilitate children hydration.
 *
 * @param      $objectClassName $objectName
 *
 * @return     void
 */
public function addNestedSetChild($objectName)
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
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets the children of the given node
 *
 * @param      Criteria  \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     array     List of $objectClassName objects
 */
public function getChildren(\$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$this->collNestedSetChildren || null !== \$criteria) {
        if (\$this->isLeaf() || (\$this->isNew() && null === \$this->collNestedSetChildren)) {
            // return empty collection
            \$this->initNestedSetChildren();
        } else {
            \$collNestedSetChildren = $queryClassName::create(null, \$criteria)
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
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets number of children for the given node
 *
 * @param      Criteria  \$criteria Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     int       Number of children
 */
public function countChildren(\$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$this->collNestedSetChildren || null !== \$criteria) {
        if (\$this->isLeaf() || (\$this->isNew() && null === \$this->collNestedSetChildren)) {
            return 0;
        } else {
            return $queryClassName::create(null, \$criteria)
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
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();
        $script .= "
/**
 * Gets the first child of the given node
 *
 * @param      Criteria \$query Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     array         List of $objectClassName objects
 */
public function getFirstChild(\$query = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        return array();
    } else {
        return $queryClassName::create(null, \$query)
            ->childrenOf(\$this)
            ->orderByBranch()
            ->findOne(\$con);
    }
}
";
    }

    protected function addGetLastChild(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets the last child of the given node
 *
 * @param      Criteria \$query Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     array         List of $objectClassName objects
 */
public function getLastChild(\$query = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        return array();
    } else {
        return $queryClassName::create(null, \$query)
            ->childrenOf(\$this)
            ->orderByBranch(true)
            ->findOne(\$con);
    }
}
";
    }

    protected function addGetSiblings(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets the siblings of the given node
 *
 * @param boolean             \$includeNode Whether to include the current node or not
 * @param Criteria            \$query Criteria to filter results.
 * @param ConnectionInterface \$con Connection to use.
 *
 * @return array List of $objectClassName objects
 */
public function getSiblings(\$includeNode = false, \$query = null, ConnectionInterface \$con = null)
{
    if (\$this->isRoot()) {
        return array();
    } else {
         \$query = $queryClassName::create(null, \$query)
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
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets descendants for the given node
 *
 * @param      Criteria \$query Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     array         List of $objectClassName objects
 */
public function getDescendants(\$query = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        return array();
    } else {
        return $queryClassName::create(null, \$query)
            ->descendantsOf(\$this)
            ->orderByBranch()
            ->find(\$con);
    }
}
";
    }

    protected function addCountDescendants(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets number of descendants for the given node
 *
 * @param      Criteria \$query Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     int         Number of descendants
 */
public function countDescendants(\$query = null, ConnectionInterface \$con = null)
{
    if (\$this->isLeaf()) {
        // save one query
        return 0;
    } else {
        return $queryClassName::create(null, \$query)
            ->descendantsOf(\$this)
            ->count(\$con);
    }
}
";
    }

    protected function addGetBranch(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets descendants for the given node, plus the current node
 *
 * @param      Criteria \$query Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     array         List of $objectClassName objects
 */
public function getBranch(\$query = null, ConnectionInterface \$con = null)
{
    return $queryClassName::create(null, \$query)
        ->branchOf(\$this)
        ->orderByBranch()
        ->find(\$con);
}
";
    }

    protected function addGetAncestors(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();

        $script .= "
/**
 * Gets ancestors for the given node, starting with the root node
 * Use it for breadcrumb paths for instance
 *
 * @param      Criteria \$query Criteria to filter results.
 * @param      ConnectionInterface \$con Connection to use.
 * @return     array         List of $objectClassName objects
 */
public function getAncestors(\$query = null, ConnectionInterface \$con = null)
{
    if (\$this->isRoot()) {
        // save one query
        return array();
    } else {
        return $queryClassName::create(null, \$query)
            ->ancestorsOf(\$this)
            ->orderByBranch()
            ->find(\$con);
    }
}
";
    }

    protected function addAddChild(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Inserts the given \$child node as first child of current
 * The modifications in the current object and the tree
 * are not persisted until the child object is saved.
 *
 * @param      $objectClassName \$child    Propel object for child node
 *
 * @return     $objectClassName The current Propel object
 */
public function addChild($objectClassName \$child)
{
    if (\$this->isNew()) {
        throw new PropelException('A $objectClassName object must not be new to accept children.');
    }
    \$child->insertAsFirstChildOf(\$this);

    return \$this;
}
";
    }

    protected function addInsertAsFirstChildOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName(true);
        $useScope        = $this->behavior->useScope();

        $script .= "
/**
 * Inserts the current node as first child of given \$parent node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassName \$parent    Propel object for parent node
 *
 * @return     $objectClassName The current Propel object
 */
public function insertAsFirstChildOf(\$parent)
{
    if (\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must not already be in the tree to be inserted. Use the moveToFirstChildOf() instead.');
    }
    \$left = \$parent->getLeftValue() + 1;
    // Update node properties
    \$this->setLeftValue(\$left);
    \$this->setRightValue(\$left + 1);
    \$this->setLevel(\$parent->getLevel() + 1);";

        if ($useScope) {
            $script .= "
    \$scope = \$parent->getScopeValue();
    \$this->setScopeValue(\$scope);";
        }

        $script .= "
    // update the children collection of the parent
    \$parent->addNestedSetChild(\$this);

    // Keep the tree modification query for the save() transaction
    \$this->nestedSetQueries []= array(
        'callable'  => array('$queryClassName', 'makeRoomForLeaf'),
        'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ", \$this->isNew() ? null : \$this)
    );

    return \$this;
}
";
    }

    protected function addInsertAsLastChildOf()
    {
        return $this->behavior->renderTemplate('objectInsertAsLastChildOf', array(
            'objectClassName' => $this->builder->getObjectClassName(),
            'queryClassName'  => $this->builder->getQueryClassName(true),
            'useScope'        => $this->behavior->useScope(),
        ));
    }

    protected function addInsertAsPrevSiblingOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName(true);
        $useScope        = $this->behavior->useScope();

        $script .= "
/**
 * Inserts the current node as prev sibling given \$sibling node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassName \$sibling    Propel object for parent node
 *
 * @return     $objectClassName The current Propel object
 */
public function insertAsPrevSiblingOf(\$sibling)
{
    if (\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must not already be in the tree to be inserted. Use the moveToPrevSiblingOf() instead.');
    }
    \$left = \$sibling->getLeftValue();
    // Update node properties
    \$this->setLeftValue(\$left);
    \$this->setRightValue(\$left + 1);
    \$this->setLevel(\$sibling->getLevel());";
        if ($useScope) {
            $script .= "
    \$scope = \$sibling->getScopeValue();
    \$this->setScopeValue(\$scope);";
        }
        $script .= "
    // Keep the tree modification query for the save() transaction
    \$this->nestedSetQueries []= array(
        'callable'  => array('$queryClassName', 'makeRoomForLeaf'),
        'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ", \$this->isNew() ? null : \$this)
    );

    return \$this;
}
";
    }

    protected function addInsertAsNextSiblingOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName(true);
        $useScope        = $this->behavior->useScope();

        $script .= "
/**
 * Inserts the current node as next sibling given \$sibling node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassName \$sibling    Propel object for parent node
 *
 * @return     $objectClassName The current Propel object
 */
public function insertAsNextSiblingOf(\$sibling)
{
    if (\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must not already be in the tree to be inserted. Use the moveToNextSiblingOf() instead.');
    }
    \$left = \$sibling->getRightValue() + 1;
    // Update node properties
    \$this->setLeftValue(\$left);
    \$this->setRightValue(\$left + 1);
    \$this->setLevel(\$sibling->getLevel());";
        if ($useScope) {
            $script .= "
    \$scope = \$sibling->getScopeValue();
    \$this->setScopeValue(\$scope);";
        }
        $script .= "
    // Keep the tree modification query for the save() transaction
    \$this->nestedSetQueries []= array(
        'callable'  => array('$queryClassName', 'makeRoomForLeaf'),
        'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ", \$this->isNew() ? null : \$this)
    );

    return \$this;
}
";
    }

    protected function addMoveToFirstChildOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $script .= "
/**
 * Moves current node and its subtree to be the first child of \$parent
 * The modifications in the current object and the tree are immediate
 *
 * @param      $objectClassName \$parent    Propel object for parent node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     $objectClassName The current Propel object
 */
public function moveToFirstChildOf(\$parent, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must be already in the tree to be moved. Use the insertAsFirstChildOf() instead.');
    }";

        $script .= "
    if (\$parent->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$parent->getLeftValue() + 1, \$parent->getLevel() - \$this->getLevel() + 1" . ($this->behavior->useScope() ? ", \$parent->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveToLastChildOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Moves current node and its subtree to be the last child of \$parent
 * The modifications in the current object and the tree are immediate
 *
 * @param      $objectClassName \$parent    Propel object for parent node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     $objectClassName The current Propel object
 */
public function moveToLastChildOf(\$parent, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must be already in the tree to be moved. Use the insertAsLastChildOf() instead.');
    }";

        $script .= "
    if (\$parent->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as child of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$parent->getRightValue(), \$parent->getLevel() - \$this->getLevel() + 1" . ($this->behavior->useScope() ? ", \$parent->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveToPrevSiblingOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Moves current node and its subtree to be the previous sibling of \$sibling
 * The modifications in the current object and the tree are immediate
 *
 * @param      $objectClassName \$sibling    Propel object for sibling node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     $objectClassName The current Propel object
 */
public function moveToPrevSiblingOf(\$sibling, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must be already in the tree to be moved. Use the insertAsPrevSiblingOf() instead.');
    }
    if (\$sibling->isRoot()) {
        throw new PropelException('Cannot move to previous sibling of a root node.');
    }";

        $script .= "
    if (\$sibling->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$sibling->getLeftValue(), \$sibling->getLevel() - \$this->getLevel()" . ($this->behavior->useScope() ? ", \$sibling->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveToNextSiblingOf(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();

        $script .= "
/**
 * Moves current node and its subtree to be the next sibling of \$sibling
 * The modifications in the current object and the tree are immediate
 *
 * @param      $objectClassName \$sibling    Propel object for sibling node
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     $objectClassName The current Propel object
 */
public function moveToNextSiblingOf(\$sibling, ConnectionInterface \$con = null)
{
    if (!\$this->isInTree()) {
        throw new PropelException('A $objectClassName object must be already in the tree to be moved. Use the insertAsNextSiblingOf() instead.');
    }
    if (\$sibling->isRoot()) {
        throw new PropelException('Cannot move to next sibling of a root node.');
    }";

        $script .= "
    if (\$sibling->isDescendantOf(\$this)) {
        throw new PropelException('Cannot move a node as sibling of one of its subtree nodes.');
    }

    \$this->moveSubtreeTo(\$sibling->getRightValue() + 1, \$sibling->getLevel() - \$this->getLevel()" . ($this->behavior->useScope() ? ", \$sibling->getScopeValue()" : "") . ", \$con);

    return \$this;
}
";
    }

    protected function addMoveSubtreeTo(&$script)
    {
        $queryClassName = $this->builder->getQueryClassName();
        $tableMapClass  = $this->builder->getTableMapClass();
        $useScope       = $this->behavior->useScope();

        $script .= "
/**
 * Move current node and its children to location \$destLeft and updates rest of tree
 *
 * @param      int    \$destLeft Destination left value
 * @param      int    \$levelDelta Delta to add to the levels
 * @param      ConnectionInterface \$con        Connection to use.
 */
protected function moveSubtreeTo(\$destLeft, \$levelDelta" . ($this->behavior->useScope() ? ", \$targetScope = null" : "") . ", PropelPDO \$con = null)
{
    \$preventDefault = false;
    \$left  = \$this->getLeftValue();
    \$right = \$this->getRightValue();";

        if ($useScope) {
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

    \$con->beginTransaction();
    try {
        // make room next to the target for the subtree
        $queryClassName::shiftRLValues(\$treeSize, \$destLeft, null" . ($useScope ? ", \$targetScope" : "") . ", \$con);

";

        if ($useScope) {
            $script .= "

        if (\$targetScope != \$scope) {

            //move subtree to < 0, so the items are out of scope.
            $queryClassName::shiftRLValues(-\$right, \$left, \$right" . ($useScope ? ", \$scope" : "") . ", \$con);

            //update scopes
            $queryClassName::setNegativeScope(\$targetScope, \$con);

            //update levels
            $queryClassName::shiftLevel(\$levelDelta, \$left - \$right, 0" . ($useScope ? ", \$targetScope" : "") . ", \$con);

            //move the subtree to the target
            $queryClassName::shiftRLValues((\$right - \$left) + \$destLeft, \$left - \$right, 0" . ($useScope ? ", \$targetScope" : "") . ", \$con);


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
                $queryClassName::shiftLevel(\$levelDelta, \$left, \$right" . ($useScope ? ", \$scope" : "") . ", \$con);
            }

            // move the subtree to the target
            $queryClassName::shiftRLValues(\$destLeft - \$left, \$left, \$right" . ($useScope ? ", \$scope" : "") . ", \$con);
        }
";

        $script .= "
        // remove the empty room at the previous location of the subtree
        $queryClassName::shiftRLValues(-\$treeSize, \$right + 1, null" . ($useScope ? ", \$scope" : "") . ", \$con);

        // update all loaded nodes
        $queryClassName::updateLoadedNodes(null, \$con);

        \$con->commit();
    } catch (PropelException \$e) {
        \$con->rollback();
        throw \$e;
    }
}
";
    }

    protected function addDeleteDescendants(&$script)
    {
        $objectClassName = $this->builder->getObjectClassName();
        $queryClassName  = $this->builder->getQueryClassName();
        $tableMapClass   = $this->builder->getTableMapClass();
        $useScope        = $this->behavior->useScope();

        $script .= "
/**
 * Deletes all descendants for the given node
 * Instance pooling is wiped out by this command,
 * so existing $objectClassName instances are probably invalid (except for the current one)
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
        if ($useScope) {
            $script .= "
    \$scope = \$this->getScopeValue();";
        }
        $script .= "
    \$con->beginTransaction();
    try {
        // delete descendant nodes (will empty the instance pool)
        \$ret = $queryClassName::create()
            ->descendantsOf(\$this)
            ->delete(\$con);

        // fill up the room that was used by descendants
        $queryClassName::shiftRLValues(\$left - \$right + 1, \$right, null" . ($useScope ? ", \$scope" : "") . ", \$con);

        // fix the right value for the current node, which is now a leaf
        \$this->setRightValue(\$left + 1);

        \$con->commit();
    } catch (Exception \$e) {
        \$con->rollback();
        throw \$e;
    }

    return \$ret;
}
";
    }

    public function objectAttributes($builder)
    {
        $tableName = $this->table->getName();
        $objectClassName = $builder->getObjectClassName();

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
 * @var        null|$objectClassName
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

        if ($this->behavior->useScope()) {
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
