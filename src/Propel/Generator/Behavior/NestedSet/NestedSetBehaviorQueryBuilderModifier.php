<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Builder\Om\QueryBuilder;

/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author François Zaninotto
 */
class NestedSetBehaviorQueryBuilderModifier
{
    /**
     * @var \Propel\Generator\Behavior\NestedSet\NestedSetBehavior
     */
    protected $behavior;

    /**
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * @var \Propel\Generator\Builder\Om\QueryBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $objectClassName;

    /**
     * @var string
     */
    protected $queryClassName;

    /**
     * @var string
     */
    protected $tableMapClassName;

    /**
     * @param \Propel\Generator\Behavior\NestedSet\NestedSetBehavior $behavior
     */
    public function __construct(NestedSetBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    /**
     * @param string $name
     *
     * @return \Propel\Generator\Model\Column
     */
    protected function getColumn($name)
    {
        return $this->behavior->getColumnForParameter($name);
    }

    /**
     * @param \Propel\Generator\Builder\Om\QueryBuilder $builder
     *
     * @return void
     */
    protected function setBuilder(QueryBuilder $builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->tableMapClassName = $builder->getTableMapClassName();
    }

    /**
     * @param \Propel\Generator\Builder\Om\QueryBuilder $builder
     *
     * @return string
     */
    public function queryMethods(QueryBuilder $builder)
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

        if ($this->behavior->useScope()) {
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

        if ($this->behavior->useScope()) {
            $this->addSetNegativeScope($script);
        }

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addTreeRoots(&$script)
    {
        $script .= "
/**
 * Filter the query to restrict the result to root objects
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function treeRoots()
{
    return \$this->addUsingAlias({$this->objectClassName}::LEFT_COL, 1, Criteria::EQUAL);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addInTree(&$script)
    {
        $script .= "
/**
 * Returns the objects in a certain tree, from the tree scope
 *
 * @param     int \$scope        Scope to determine which objects node to return
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function inTree(\$scope = null)
{
    return \$this->addUsingAlias({$this->objectClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addDescendantsOf(&$script)
    {
        $objectName = '$' . $this->table->getCamelCaseName();
        $script .= "
/**
 * Filter the query to restrict the result to descendants of an object
 *
 * @param     {$this->objectClassName} $objectName The object to use for descendant search
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function descendantsOf($this->objectClassName $objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::GREATER_THAN)
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, {$objectName}->getRightValue(), Criteria::LESS_THAN);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addBranchOf(&$script)
    {
        $objectName = '$' . $this->table->getCamelCaseName();
        $script .= "
/**
 * Filter the query to restrict the result to the branch of an object.
 * Same as descendantsOf(), except that it includes the object passed as parameter in the result
 *
 * @param     {$this->objectClassName} $objectName The object to use for branch search
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function branchOf($this->objectClassName $objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::GREATER_EQUAL)
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, {$objectName}->getRightValue(), Criteria::LESS_EQUAL);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addChildrenOf(&$script)
    {
        $objectName = '$' . $this->table->getCamelCaseName();
        $script .= "
/**
 * Filter the query to restrict the result to children of an object
 *
 * @param     {$this->objectClassName} $objectName The object to use for child search
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function childrenOf($this->objectClassName $objectName)
{
    return \$this
        ->descendantsOf($objectName)
        ->addUsingAlias({$this->objectClassName}::LEVEL_COL, {$objectName}->getLevel() + 1, Criteria::EQUAL);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addSiblingsOf(&$script)
    {
        $objectName = '$' . $this->table->getCamelCaseName();
        $script .= "
/**
 * Filter the query to restrict the result to siblings of an object.
 * The result does not include the object passed as parameter.
 *
 * @param     {$this->objectClassName} $objectName The object to use for sibling search
 * @param      ConnectionInterface \$con Connection to use.
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function siblingsOf($this->objectClassName $objectName, ConnectionInterface \$con = null)
{
    if ({$objectName}->isRoot()) {
        return \$this->
            add({$this->objectClassName}::LEVEL_COL, '1<>1', Criteria::CUSTOM);
    } else {
        return \$this
            ->childrenOf({$objectName}->getParent(\$con))
            ->prune($objectName);
    }
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addAncestorsOf(&$script)
    {
        $objectName = '$' . $this->table->getCamelCaseName();
        $script .= "
/**
 * Filter the query to restrict the result to ancestors of an object
 *
 * @param     {$this->objectClassName} $objectName The object to use for ancestors search
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function ancestorsOf($this->objectClassName $objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::LESS_THAN)
        ->addUsingAlias({$this->objectClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::GREATER_THAN);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRootsOf(&$script)
    {
        $objectName = '$' . $this->table->getCamelCaseName();
        $script .= "
/**
 * Filter the query to restrict the result to roots of an object.
 * Same as ancestorsOf(), except that it includes the object passed as parameter in the result
 *
 * @param     {$this->objectClassName} $objectName The object to use for roots search
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function rootsOf($this->objectClassName $objectName)
{
    return \$this";
        if ($this->behavior->useScope()) {
            $script .= "
        ->inTree({$objectName}->getScopeValue())";
        }
        $script .= "
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, {$objectName}->getLeftValue(), Criteria::LESS_EQUAL)
        ->addUsingAlias({$this->objectClassName}::RIGHT_COL, {$objectName}->getRightValue(), Criteria::GREATER_EQUAL);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addOrderByBranch(&$script)
    {
        $script .= "
/**
 * Order the result by branch, i.e. natural tree order
 *
 * @param     bool \$reverse if true, reverses the order
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function orderByBranch(\$reverse = false)
{
    if (\$reverse) {
        return \$this
            ->addDescendingOrderByColumn({$this->objectClassName}::LEFT_COL);
    } else {
        return \$this
            ->addAscendingOrderByColumn({$this->objectClassName}::LEFT_COL);
    }
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addOrderByLevel(&$script)
    {
        $script .= "
/**
 * Order the result by level, the closer to the root first
 *
 * @param     bool \$reverse if true, reverses the order
 *
 * @return    \$this|{$this->queryClassName} The current query, for fluid interface
 */
public function orderByLevel(\$reverse = false)
{
    if (\$reverse) {
        return \$this
            ->addDescendingOrderByColumn({$this->objectClassName}::LEVEL_COL)
            ->addDescendingOrderByColumn({$this->objectClassName}::LEFT_COL);
    } else {
        return \$this
            ->addAscendingOrderByColumn({$this->objectClassName}::LEVEL_COL)
            ->addAscendingOrderByColumn({$this->objectClassName}::LEFT_COL);
    }
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFindRoot(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns " . ($useScope ? 'a' : 'the') . " root node for the tree
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
public function findRoot(" . ($useScope ? '$scope = null, ' : '') . "ConnectionInterface \$con = null)
{
    return \$this
        ->addUsingAlias({$this->objectClassName}::LEFT_COL, 1, Criteria::EQUAL)";
        if ($useScope) {
            $script .= "
        ->inTree(\$scope)";
        }
        $script .= "
        ->findOne(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFindRoots(&$script)
    {
        $script .= "
/**
 * Returns the root objects for all trees.
 *
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return    {$this->objectClassName}[]|ObjectCollection|mixed the list of results, formatted by the current formatter
 */
public function findRoots(ConnectionInterface \$con = null)
{
    return \$this
        ->treeRoots()
        ->find(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFindTree(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns " . ($useScope ? 'a' : 'the') . " tree of objects
 *";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which tree node to return";
        }

        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     {$this->objectClassName}[]|ObjectCollection|mixed the list of results, formatted by the current formatter
 */
public function findTree(" . ($useScope ? '$scope = null, ' : '') . "ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRetrieveRoots(&$script)
    {
        $queryClassName = $this->queryClassName;
        $objectClassName = $this->objectClassName;
        $tableMapClassName = $this->builder->getTableMapClass();

        $script .= "
/**
 * Returns the root nodes for the tree
 *
 * @param      Criteria \$criteria    Optional Criteria to filter the query
 * @param      ConnectionInterface \$con    Connection to use.
 * @return     {$this->objectClassName}[]|ObjectCollection|mixed the list of results, formatted by the current formatter
 */
static public function retrieveRoots(Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$criteria) {
        \$criteria = new Criteria($tableMapClassName::DATABASE_NAME);
    }
    \$criteria->add($objectClassName::LEFT_COL, 1, Criteria::EQUAL);

    return $queryClassName::create(null, \$criteria)->find(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRetrieveRoot(&$script)
    {
        $queryClassName = $this->queryClassName;
        $objectClassName = $this->objectClassName;
        $useScope = $this->behavior->useScope();
        $tableMapClassName = $this->builder->getTableMapClass();

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
static public function retrieveRoot(" . ($useScope ? '$scope = null, ' : '') . "ConnectionInterface \$con = null)
{
    \$c = new Criteria($tableMapClassName::DATABASE_NAME);
    \$c->add($objectClassName::LEFT_COL, 1, Criteria::EQUAL);";
        if ($useScope) {
            $script .= "
    \$c->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    return $queryClassName::create(null, \$c)->findOne(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRetrieveTree(&$script)
    {
        $queryClassName = $this->queryClassName;
        $objectClassName = $this->objectClassName;
        $useScope = $this->behavior->useScope();
        $tableMapClassName = $this->builder->getTableMapClass();

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
 * @return     {$this->objectClassName}[]|ObjectCollection|mixed the list of results, formatted by the current formatter
 */
static public function retrieveTree(" . ($useScope ? '$scope = null, ' : '') . "Criteria \$criteria = null, ConnectionInterface \$con = null)
{
    if (null === \$criteria) {
        \$criteria = new Criteria($tableMapClassName::DATABASE_NAME);
    }
    \$criteria->addAscendingOrderByColumn($objectClassName::LEFT_COL);";
        if ($useScope) {
            $script .= "
    \$criteria->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    return $queryClassName::create(null, \$criteria)->find(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addDeleteTree(&$script)
    {
        $objectClassName = $this->objectClassName;
        $useScope = $this->behavior->useScope();
        $tableMapClassName = $this->builder->getTableMapClass();

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
static public function deleteTree(" . ($useScope ? '$scope = null, ' : '') . "ConnectionInterface \$con = null)
{";
        if ($useScope) {
            $script .= "
    \$c = new Criteria($tableMapClassName::DATABASE_NAME);
    \$c->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);

    return $tableMapClassName::doDelete(\$c, \$con);";
        } else {
            $script .= "

    return $tableMapClassName::doDeleteAll(\$con);";
        }
        $script .= "
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addShiftRLValues(&$script)
    {
        $objectClassName = $this->objectClassName;
        $useScope = $this->behavior->useScope();
        $tableMapClassName = $this->builder->getTableMapClass();

        $this->builder->declareClass('Propel\\Runtime\Map\\TableMap');

        $script .= "
/**
 * Adds \$delta to all L and R values that are >= \$first and <= \$last.
 * '\$delta' can also be negative.
 *
 * @param int \$delta               Value to be shifted by, can be negative
 * @param int \$first               First node to be shifted
 * @param int \$last                Last node to be shifted (optional)";
        if ($useScope) {
            $script .= "
 * @param int \$scope               Scope to use for the shift";
        }
        $script .= "
 * @param ConnectionInterface \$con Connection to use.
 */
static public function shiftRLValues(\$delta, \$first, \$last = null" . ($useScope ? ', $scope = null' : '') . ", ConnectionInterface \$con = null)
{
    if (\$con === null) {
        \$con = Propel::getServiceContainer()->getWriteConnection($tableMapClassName::DATABASE_NAME);
    }

    // Shift left column values
    \$whereCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$criterion = \$whereCriteria->getNewCriterion($objectClassName::LEFT_COL, \$first, Criteria::GREATER_EQUAL);
    if (null !== \$last) {
        \$criterion->addAnd(\$whereCriteria->getNewCriterion($objectClassName::LEFT_COL, \$last, Criteria::LESS_EQUAL));
    }
    \$whereCriteria->add(\$criterion);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$valuesCriteria->add($objectClassName::LEFT_COL, array('raw' => $objectClassName::LEFT_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    \$whereCriteria->doUpdate(\$valuesCriteria, \$con);

    // Shift right column values
    \$whereCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$criterion = \$whereCriteria->getNewCriterion($objectClassName::RIGHT_COL, \$first, Criteria::GREATER_EQUAL);
    if (null !== \$last) {
        \$criterion->addAnd(\$whereCriteria->getNewCriterion($objectClassName::RIGHT_COL, \$last, Criteria::LESS_EQUAL));
    }
    \$whereCriteria->add(\$criterion);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$valuesCriteria->add($objectClassName::RIGHT_COL, array('raw' => $objectClassName::RIGHT_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    \$whereCriteria->doUpdate(\$valuesCriteria, \$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addShiftLevel(&$script)
    {
        $objectClassName = $this->objectClassName;
        $useScope = $this->behavior->useScope();
        $tableMapClassName = $this->builder->getTableMapClass();

        $this->builder->declareClass('Propel\\Runtime\Map\\TableMap');

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
static public function shiftLevel(\$delta, \$first, \$last" . ($useScope ? ', $scope = null' : '') . ", ConnectionInterface \$con = null)
{
    if (\$con === null) {
        \$con = Propel::getServiceContainer()->getWriteConnection($tableMapClassName::DATABASE_NAME);
    }

    \$whereCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$whereCriteria->add($objectClassName::LEFT_COL, \$first, Criteria::GREATER_EQUAL);
    \$whereCriteria->add($objectClassName::RIGHT_COL, \$last, Criteria::LESS_EQUAL);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$valuesCriteria->add($objectClassName::LEVEL_COL, array('raw' => $objectClassName::LEVEL_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    \$whereCriteria->doUpdate(\$valuesCriteria, \$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addUpdateLoadedNodes(&$script)
    {
        $queryClassName = $this->queryClassName;
        $objectClassName = $this->objectClassName;
        $tableMapClassName = $this->tableMapClassName;

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
        /** @var \$obj $objectClassName */
        foreach ($tableMapClassName::\$instances as \$obj) {
            if (!\$prune || !\$prune->equals(\$obj)) {
                \$keys[] = \$obj->getPrimaryKey();
            }
        }

        if (!empty(\$keys)) {
            // We don't need to alter the object instance pool; we're just modifying these ones
            // already in the pool.
            \$criteria = new Criteria($tableMapClassName::DATABASE_NAME);";
        if (count($this->table->getPrimaryKey()) === 1) {
            $pkey = $this->table->getPrimaryKey();
            $col = array_shift($pkey);
            $script .= "
            \$criteria->add(" . $this->builder->getColumnConstant($col) . ', $keys, Criteria::IN);';
        } else {
            $fields = [];
            foreach ($this->table->getPrimaryKey() as $k => $col) {
                $fields[] = $this->builder->getColumnConstant($col);
            }
            $script .= "

            // Loop on each instances in pool
            foreach (\$keys as \$values) {
              // Create initial Criterion
                \$cton = \$criteria->getNewCriterion(" . $fields[0] . ', $values[0]);';
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
            \$dataFetcher = $queryClassName::create(null, \$criteria)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find(\$con);
            while (\$row = \$dataFetcher->fetch()) {
                \$key = $tableMapClassName::getPrimaryKeyHashFromRow(\$row, 0);
                /** @var \$object $objectClassName */
                if (null !== (\$object = $tableMapClassName::getInstanceFromPool(\$key))) {";
        $n = 0;
        foreach ($this->table->getColumns() as $col) {
            if ($col->isLazyLoad()) {
                continue;
            }
            if ($col->getPhpName() === $this->getColumnPhpName('left_column')) {
                $script .= "
                    \$object->setLeftValue(\$row[$n]);";
            } elseif ($col->getPhpName() === $this->getColumnPhpName('right_column')) {
                $script .= "
                    \$object->setRightValue(\$row[$n]);";
            } elseif ($this->getParameter('use_scope') == 'true' && $col->getPhpName() === $this->getColumnPhpName('scope_column')) {
                $script .= "
                    \$object->setScopeValue(\$row[$n]);";
            } elseif ($col->getPhpName() === $this->getColumnPhpName('level_column')) {
                $script .= "
                    \$object->setLevel(\$row[$n]);
                    \$object->clearNestedSetChildren();";
            }
            $n++;
        }
        $script .= "
                }
            }
            \$dataFetcher->close();
        }
    }
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addMakeRoomForLeaf(&$script)
    {
        $queryClassName = $this->queryClassName;
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
static public function makeRoomForLeaf(\$left" . ($useScope ? ', $scope' : '') . ", \$prune = null, ConnectionInterface \$con = null)
{
    // Update database nodes
    $queryClassName::shiftRLValues(2, \$left, null" . ($useScope ? ', $scope' : '') . ", \$con);

    // Update all loaded nodes
    $queryClassName::updateLoadedNodes(\$prune, \$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFixLevels(&$script)
    {
        $objectClassName = $this->objectClassName;
        $queryClassName = $this->queryClassName;
        $tableMapClassName = $this->tableMapClassName;
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
static public function fixLevels(" . ($useScope ? '$scope, ' : '') . "ConnectionInterface \$con = null)
{
    \$c = new Criteria();";
        if ($useScope) {
            $script .= "
    \$c->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "
    \$c->addAscendingOrderByColumn($objectClassName::LEFT_COL);
    \$dataFetcher = $queryClassName::create(null, \$c)->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find(\$con);
    ";
        if (!$this->table->getChildrenColumn()) {
            $script .= "
    // set the class once to avoid overhead in the loop
    \$cls = $tableMapClassName::getOMClass(false);";
        }

        $script .= "
    \$level = null;
    // iterate over the statement
    while (\$row = \$dataFetcher->fetch()) {

        // hydrate object
        \$key = $tableMapClassName::getPrimaryKeyHashFromRow(\$row, 0);
        /** @var \$obj $objectClassName */
        if (null === (\$obj = $tableMapClassName::getInstanceFromPool(\$key))) {";
        if ($this->table->getChildrenColumn()) {
            $script .= "
            // class must be set each time from the record row
            \$cls = $tableMapClassName::getOMClass(\$row, 0);
            \$cls = substr('.'.\$cls, strrpos('.'.\$cls, '.') + 1);
            " . $this->builder->buildObjectInstanceCreationCode('$obj', '$cls') . "
            \$obj->hydrate(\$row);
            $tableMapClassName::addInstanceToPool(\$obj, \$key);";
        } else {
            $script .= "
            " . $this->builder->buildObjectInstanceCreationCode('$obj', '$cls') . "
            \$obj->hydrate(\$row);
            $tableMapClassName::addInstanceToPool(\$obj, \$key);";
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
    \$dataFetcher->close();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addSetNegativeScope(&$script)
    {
        $objectClassName = $this->objectClassName;
        $tableMapClassName = $this->tableMapClassName;
        $script .= "
/**
 * Updates all scope values for items that has negative left (<=0) values.
 *
 * @param      mixed     \$scope
 * @param      ConnectionInterface \$con  Connection to use.
 */
public static function setNegativeScope(\$scope, ConnectionInterface \$con = null)
{
    //adjust scope value to \$scope
    \$whereCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$whereCriteria->add($objectClassName::LEFT_COL, 0, Criteria::LESS_EQUAL);

    \$valuesCriteria = new Criteria($tableMapClassName::DATABASE_NAME);
    \$valuesCriteria->add($objectClassName::SCOPE_COL, \$scope, Criteria::EQUAL);

    \$whereCriteria->doUpdate(\$valuesCriteria, \$con);
}
";
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    protected function getColumnPhpName($columnName)
    {
        return $this->behavior->getColumnForParameter($columnName)->getPhpName();
    }
}
