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
 */
class NestedSetBehaviorQueryBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

    protected $peerClassName;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getColumn($name)
    {
        return $this->behavior->getColumnForParameter($name);
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->peerClassName = $builder->getPeerClassName();
    }

    public function queryMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';

        // select filters
        if ($this->behavior->useScope()) {
            $this->addTreeRoots($script);
            $this->addInTree($script);
        }
        $this->addDescendantsOf($script);
        $this->addBranchOf($script);
        $this->addChildrenOf($script);
        $this->addSiblingsOf($script);
        $this->addAncestorsOf($script);
        $this->addRootsOf($script);
        // select orders
        $this->addOrderByBranch($script);
        $this->addOrderByLevel($script);
        // select termination methods
        $this->addFindRoot($script);
        if ($this->behavior->useScope()) {
            $this->addFindRoots($script);
        }
        $this->addFindTree($script);

        return $script;
    }

    protected function addTreeRoots(&$script)
    {
        $script .= "
/**
 * Filter the query to restrict the result to root objects
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function treeRoots()
{
    return \$this->addUsingAlias({$this->peerClassName}::LEFT_COL, 1, Criteria::EQUAL);
}
";
    }

    protected function addInTree(&$script)
    {
        $script .= "
/**
 * Returns the objects in a certain tree, from the tree scope
 *
 * @param     int \$scope        Scope to determine which objects node to return
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function inTree(\$scope = null)
{
    return \$this->addUsingAlias({$this->peerClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);
}
";
    }

    protected function addDescendantsOf(&$script)
    {
        $objectName = '$' . $this->table->getStudlyPhpName();
        $script .= "
/**
 * Filter the query to restrict the result to descendants of an object
 *
 * @param     {$this->objectClassName} $objectName The object to use for descendant search
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function descendantsOf($objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::GREATER_THAN)
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, {$objectName}->getRightValue(), Criteria::LESS_THAN);
}
";
    }

    protected function addBranchOf(&$script)
    {
        $objectName = '$' . $this->table->getStudlyPhpName();
        $script .= "
/**
 * Filter the query to restrict the result to the branch of an object.
 * Same as descendantsOf(), except that it includes the object passed as parameter in the result
 *
 * @param     {$this->objectClassName} $objectName The object to use for branch search
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function branchOf($objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::GREATER_EQUAL)
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, {$objectName}->getRightValue(), Criteria::LESS_EQUAL);
}
";
    }

    protected function addChildrenOf(&$script)
    {
        $objectName = '$' . $this->table->getStudlyPhpName();
        $script .= "
/**
 * Filter the query to restrict the result to children of an object
 *
 * @param     {$this->objectClassName} $objectName The object to use for child search
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function childrenOf($objectName)
{
    return \$this
        ->descendantsOf($objectName)
        ->addUsingAlias({$this->peerClassName}::LEVEL_COL, {$objectName}->getLevel() + 1, Criteria::EQUAL);
}
";
    }

    protected function addSiblingsOf(&$script)
    {
        $objectName = '$' . $this->table->getStudlyPhpName();
        $script .= "
/**
 * Filter the query to restrict the result to siblings of an object.
 * The result does not include the object passed as parameter.
 *
 * @param     {$this->objectClassName} $objectName The object to use for sibling search
 * @param      ConnectionInterface \$con Connection to use.
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function siblingsOf($objectName, ConnectionInterface \$con = null)
{
    if ({$objectName}->isRoot()) {
        return \$this->
            add({$this->peerClassName}::LEVEL_COL, '1<>1', Criteria::CUSTOM);
    } else {
        return \$this
            ->childrenOf({$objectName}->getParent(\$con))
            ->prune($objectName);
    }
}
";
    }

    protected function addAncestorsOf(&$script)
    {
        $objectName = '$' . $this->table->getStudlyPhpName();
        $script .= "
/**
 * Filter the query to restrict the result to ancestors of an object
 *
 * @param     {$this->objectClassName} $objectName The object to use for ancestors search
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function ancestorsOf($objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::LESS_THAN)
        ->addUsingAlias({$this->peerClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::GREATER_THAN);
}
";
    }

    protected function addRootsOf(&$script)
    {
        $objectName = '$' . $this->table->getStudlyPhpName();
        $script .= "
/**
 * Filter the query to restrict the result to roots of an object.
 * Same as ancestorsOf(), except that it includes the object passed as parameter in the result
 *
 * @param     {$this->objectClassName} $objectName The object to use for roots search
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function rootsOf($objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::LESS_EQUAL)
        ->addUsingAlias({$this->peerClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::GREATER_EQUAL);
}
";
    }

    protected function addOrderByBranch(&$script)
    {
        $script .= "
/**
 * Order the result by branch, i.e. natural tree order
 *
 * @param     bool \$reverse if true, reverses the order
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function orderByBranch(\$reverse = false)
{
    if (\$reverse) {
        return \$this
            ->addDescendingOrderByColumn({$this->peerClassName}::LEFT_COL);
    } else {
        return \$this
            ->addAscendingOrderByColumn({$this->peerClassName}::LEFT_COL);
    }
}
";
    }

    protected function addOrderByLevel(&$script)
    {
        $script .= "
/**
 * Order the result by level, the closer to the root first
 *
 * @param     bool \$reverse if true, reverses the order
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function orderByLevel(\$reverse = false)
{
    if (\$reverse) {
        return \$this
            ->addAscendingOrderByColumn({$this->peerClassName}::RIGHT_COL);
    } else {
        return \$this
            ->addDescendingOrderByColumn({$this->peerClassName}::RIGHT_COL);
    }
}
";
    }

    protected function addFindRoot(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns " . ($useScope ? 'a' : 'the') ." root node for the tree
 *";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which root node to return";
        }

        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     {$this->objectClassName} The tree root object
 */
public function findRoot(" . ($useScope ? "\$scope = null, " : "") . "\$con = null)
{
    return \$this
        ->addUsingAlias({$this->peerClassName}::LEFT_COL, 1, Criteria::EQUAL)";
        if ($useScope) {
            $script .= "
        ->inTree(\$scope)";
        }
        $script .= "
        ->findOne(\$con);
}
";
    }

    protected function addFindRoots(&$script)
    {
        $script .= "
/**
 * Returns the root objects for all trees.
 *
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return    mixed the list of results, formatted by the current formatter
 */
public function findRoots(\$con = null)
{
    return \$this
        ->treeRoots()
        ->find(\$con);
}
";
    }

    protected function addFindTree(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns " . ($useScope ? 'a' : 'the') ." tree of objects
 *";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which tree node to return";
        }

        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     mixed the list of results, formatted by the current formatter
 */
public function findTree(" . ($useScope ? "\$scope = null, " : "") . "\$con = null)
{
    return \$this";
        if ($useScope) {
            $script .= "
        ->inTree(\$scope)";
        }
        $script .= "
        ->orderByBranch()
        ->find(\$con);
}
";
    }

    public function staticMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';

        if ('true' === $this->getParameter('use_scope')) {
            $this->addRetrieveRoots($script);
        }

        $this->addRetrieveRoot($script);
        $this->addRetrieveTree($script);
        $this->addIsValid($script);
        $this->addDeleteTree($script);
        $this->addShiftRLValues($script);
        $this->addShiftLevel($script);
        $this->addUpdateLoadedNodes($script);
        $this->addMakeRoomForLeaf($script);
        $this->addFixLevels($script);

        return $script;
    }

    protected function addRetrieveRoots(&$script)
    {
        $peerClassName = $this->peerClassName;
        $script .= "
/**
 * Returns the root nodes for the tree
 *
 * @param      ConnectionInterface \$con    Connection to use.
 * @return     {$this->objectClassName}            Propel object for root node
 */
static public function retrieveRoots(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$criteria) {
        \$criteria = new Criteria($peerClassName::DATABASE_NAME);
    }
    \$criteria->add($peerClassName::LEFT_COL, 1, Criteria::EQUAL);

    return $peerClassName::doSelect(\$criteria, \$con);
}
";
    }

    protected function addRetrieveRoot(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns the root node for a given scope
 *";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which root node to return";
        }
        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 * @return     {$this->objectClassName}            Propel object for root node
 */
static public function retrieveRoot(" . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{
    \$c = new Criteria($peerClassName::DATABASE_NAME);
    \$c->add($peerClassName::LEFT_COL, 1, Criteria::EQUAL);";
        if ($useScope) {
            $script .= "
    \$c->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    return $peerClassName::doSelectOne(\$c, \$con);
}
";
    }

    protected function addRetrieveTree(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns the whole tree node for a given scope
 *";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which root node to return";
        }
        $script .= "
 * @param      Criteria \$criteria    Optional Criteria to filter the query
 * @param      ConnectionInterface \$con    Connection to use.
 * @return     {$this->objectClassName}            Propel object for root node
 */
static public function retrieveTree(" . ($useScope ? "\$scope = null, " : "") . "Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$criteria) {
        \$criteria = new Criteria($peerClassName::DATABASE_NAME);
    }
    \$criteria->addAscendingOrderByColumn($peerClassName::LEFT_COL);";
        if ($useScope) {
            $script .= "
    \$criteria->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    return $peerClassName::doSelect(\$criteria, \$con);
}
";
    }

    protected function addIsValid(&$script)
    {
        $objectClassName = $this->objectClassName;
        $script .= "
/**
 * Tests if node is valid
 *
 * @param      $objectClassName \$node    Propel object for src node
 * @return     bool
 */
static public function isValid($objectClassName \$node = null)
{
    if (is_object(\$node) && \$node->getRightValue() > \$node->getLeftValue()) {
        return true;
    } else {
        return false;
    }
}
";
    }

    protected function addDeleteTree(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Delete an entire tree
 * ";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which tree to delete";
        }
        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     int  The number of deleted nodes
 */
static public function deleteTree(" . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{";
        if ($useScope) {
            $script .= "
    \$c = new Criteria($peerClassName::DATABASE_NAME);
    \$c->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);

    return $peerClassName::doDelete(\$c, \$con);";
        } else {
            $script .= "

    return $peerClassName::doDeleteAll(\$con);";
        }
        $script .= "
}
";
    }

    protected function addShiftRLValues(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Adds \$delta to all L and R values that are >= \$first and <= \$last.
 * '\$delta' can also be negative.
 *
 * @param      int \$delta        Value to be shifted by, can be negative
 * @param      int \$first        First node to be shifted
 * @param      int \$last            Last node to be shifted (optional)";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to use for the shift";
        }
        $script .= "
 * @param      ConnectionInterface \$con        Connection to use.
 */
static public function shiftRLValues(\$delta, \$first, \$last = null" . ($useScope ? ", \$scope = null" : ""). ", ConnectionInterface \$con = null)
{
    if (\$con === null) {
        \$con = Propel::getServiceContainer()->getWriteConnection($peerClassName::DATABASE_NAME);
    }

    // Shift left column values
    \$whereCriteria = new Criteria($peerClassName::DATABASE_NAME);
    \$criterion = \$whereCriteria->getNewCriterion($peerClassName::LEFT_COL, \$first, Criteria::GREATER_EQUAL);
    if (null !== \$last) {
        \$criterion->addAnd(\$whereCriteria->getNewCriterion($peerClassName::LEFT_COL, \$last, Criteria::LESS_EQUAL));
    }
    \$whereCriteria->add(\$criterion);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria($peerClassName::DATABASE_NAME);
    \$valuesCriteria->add($peerClassName::LEFT_COL, array('raw' => $peerClassName::LEFT_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    {$this->builder->getBasePeerClassName()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);

    // Shift right column values
    \$whereCriteria = new Criteria($peerClassName::DATABASE_NAME);
    \$criterion = \$whereCriteria->getNewCriterion($peerClassName::RIGHT_COL, \$first, Criteria::GREATER_EQUAL);
    if (null !== \$last) {
        \$criterion->addAnd(\$whereCriteria->getNewCriterion($peerClassName::RIGHT_COL, \$last, Criteria::LESS_EQUAL));
    }
    \$whereCriteria->add(\$criterion);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria($peerClassName::DATABASE_NAME);
    \$valuesCriteria->add($peerClassName::RIGHT_COL, array('raw' => $peerClassName::RIGHT_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    {$this->builder->getBasePeerClassName()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);
}
";
    }

    protected function addShiftLevel(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Adds \$delta to level for nodes having left value >= \$first and right value <= \$last.
 * '\$delta' can also be negative.
 *
 * @param      int \$delta        Value to be shifted by, can be negative
 * @param      int \$first        First node to be shifted
 * @param      int \$last            Last node to be shifted";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to use for the shift";
        }
        $script .= "
 * @param      ConnectionInterface \$con        Connection to use.
 */
static public function shiftLevel(\$delta, \$first, \$last" . ($useScope ? ", \$scope = null" : ""). ", ConnectionInterface \$con = null)
{
    if (\$con === null) {
        \$con = Propel::getServiceContainer()->getWriteConnection($peerClassName::DATABASE_NAME);
    }

    \$whereCriteria = new Criteria($peerClassName::DATABASE_NAME);
    \$whereCriteria->add($peerClassName::LEFT_COL, \$first, Criteria::GREATER_EQUAL);
    \$whereCriteria->add($peerClassName::RIGHT_COL, \$last, Criteria::LESS_EQUAL);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria($peerClassName::DATABASE_NAME);
    \$valuesCriteria->add($peerClassName::LEVEL_COL, array('raw' => $peerClassName::LEVEL_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    {$this->builder->getBasePeerClassName()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);
}
";
    }

    protected function addUpdateLoadedNodes(&$script)
    {
        $peerClassName = $this->peerClassName;
        $objectClassName = $this->objectClassName;
        $script .= "
/**
 * Reload all already loaded nodes to sync them with updated db
 *
 * @param      $objectClassName \$prune        Object to prune from the update
 * @param      ConnectionInterface \$con        Connection to use.
 */
static public function updateLoadedNodes(\$prune = null, ConnectionInterface \$con = null)
{
    if (Propel::isInstancePoolingEnabled()) {
        \$keys = array();
        foreach ($peerClassName::\$instances as \$obj) {
            if (!\$prune || !\$prune->equals(\$obj)) {
                \$keys[] = \$obj->getPrimaryKey();
            }
        }

        if (!empty(\$keys)) {
            // We don't need to alter the object instance pool; we're just modifying these ones
            // already in the pool.
            \$criteria = new Criteria($peerClassName::DATABASE_NAME);";
        if (1 === count($this->table->getPrimaryKey())) {
            $pkey = $this->table->getPrimaryKey();
            $col = array_shift($pkey);
            $script .= "
            \$criteria->add(".$this->builder->getColumnConstant($col).", \$keys, Criteria::IN);";
        } else {
            $fields = array();
            foreach ($this->table->getPrimaryKey() as $k => $col) {
                $fields[] = $this->builder->getColumnConstant($col);
            };
            $script .= "

            // Loop on each instances in pool
            foreach (\$keys as \$values) {
              // Create initial Criterion
                \$cton = \$criteria->getNewCriterion(" . $fields[0] . ", \$values[0]);";
            unset($fields[0]);
            foreach ($fields as $k => $col) {
                $script .= "

                // Create next criterion
                \$nextcton = \$criteria->getNewCriterion(" . $col . ", \$values[$k]);
                // And merge it with the first
                \$cton->addAnd(\$nextcton);";
            }
            $script .= "

                // Add final Criterion to Criteria
                \$criteria->addOr(\$cton);
            }";
        }

        $script .= "
            \$stmt = $peerClassName::doSelectStmt(\$criteria, \$con);
            while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
                \$key = $peerClassName::getPrimaryKeyHashFromRow(\$row, 0);
                if (null !== (\$object = $peerClassName::getInstanceFromPool(\$key))) {";
        $n = 0;
        foreach ($this->table->getColumns() as $col) {
            if ($col->isLazyLoad()) {
                continue;
            }
            if ($col->getPhpName() == $this->getColumnPhpName('left_column')) {
                $script .= "
                    \$object->setLeftValue(\$row[$n]);";
            } elseif ($col->getPhpName() == $this->getColumnPhpName('right_column')) {
                $script .= "
                    \$object->setRightValue(\$row[$n]);";
            } elseif ($col->getPhpName() == $this->getColumnPhpName('level_column')) {
                $script .= "
                    \$object->setLevel(\$row[$n]);
                    \$object->clearNestedSetChildren();";
            }
            $n++;
        }
        $script .= "
                }
            }
            \$stmt->closeCursor();
        }
    }
}
";
    }

    protected function addMakeRoomForLeaf(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Update the tree to allow insertion of a leaf at the specified position
 *
 * @param      int \$left    left column value";
        if ($useScope) {
            $script .= "
 * @param      integer \$scope    scope column value";
        }
        $script .= "
 * @param      mixed \$prune    Object to prune from the shift
 * @param      ConnectionInterface \$con    Connection to use.
 */
static public function makeRoomForLeaf(\$left" . ($useScope ? ", \$scope" : ""). ", \$prune = null, ConnectionInterface \$con = null)
{
    // Update database nodes
    $peerClassName::shiftRLValues(2, \$left, null" . ($useScope ? ", \$scope" : "") . ", \$con);

    // Update all loaded nodes
    $peerClassName::updateLoadedNodes(\$prune, \$con);
}
";
    }

    protected function addFixLevels(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Update the tree to allow insertion of a leaf at the specified position
 *";
        if ($useScope) {
            $script .= "
 * @param      integer \$scope    scope column value";
        }
        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 */
static public function fixLevels(" . ($useScope ? "\$scope, " : ""). "ConnectionInterface \$con = null)
{
    \$c = new Criteria();";
        if ($useScope) {
            $script .= "
    \$c->add($peerClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "
    \$c->addAscendingOrderByColumn($peerClassName::LEFT_COL);
    \$stmt = $peerClassName::doSelectStmt(\$c, \$con);
    ";
        if (!$this->table->getChildrenColumn()) {
            $script .= "
    // set the class once to avoid overhead in the loop
    \$cls = $peerClassName::getOMClass(false);";
        }

        $script .= "
    \$level = null;
    // iterate over the statement
    while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {

        // hydrate object
        \$key = $peerClassName::getPrimaryKeyHashFromRow(\$row, 0);
        if (null === (\$obj = $peerClassName::getInstanceFromPool(\$key))) {";
        if ($this->table->getChildrenColumn()) {
            $script .= "
            // class must be set each time from the record row
            \$cls = $peerClassName::getOMClass(\$row, 0);
            \$cls = substr('.'.\$cls, strrpos('.'.\$cls, '.') + 1);
            " . $this->builder->buildObjectInstanceCreationCode('$obj', '$cls') . "
            \$obj->hydrate(\$row);
            $peerClassName::addInstanceToPool(\$obj, \$key);";
        } else {
            $script .= "
            " . $this->builder->buildObjectInstanceCreationCode('$obj', '$cls') . "
            \$obj->hydrate(\$row);
            $peerClassName::addInstanceToPool(\$obj, \$key);";
        }
        $script .= "
        }

        // compute level
        // Algorithm shamelessly stolen from sfPropelActAsNestedSetBehaviorPlugin
        // Probably authored by Tristan Rivoallan
        if (\$level === null) {
            \$level = 0;
            \$i = 0;
            \$prev = array(\$obj->getRightValue());
        } else {
            while (\$obj->getRightValue() > \$prev[\$i]) {
                \$i--;
            }
            \$level = ++\$i;
            \$prev[\$i] = \$obj->getRightValue();
        }

        // update level in node if necessary
        if (\$obj->getLevel() !== \$level) {
            \$obj->setLevel(\$level);
            \$obj->save(\$con);
        }
    }
    \$stmt->closeCursor();
}
";
    }

}
