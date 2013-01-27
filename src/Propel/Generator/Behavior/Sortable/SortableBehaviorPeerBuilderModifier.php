<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

/**
 * Behavior to add sortable peer methods
 *
 * @author François Zaninotto
 * @author heltem <heltem@o2php.com>
 */
class SortableBehaviorPeerBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

    protected $peerClassName;

    protected $queryClassName;

    protected $tableMapClassName;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getColumnAttribute($name)
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    protected function getColumnConstant($name)
    {
        return strtoupper($this->behavior->getColumnForParameter($name)->getName());
    }

    protected function getColumnPhpName($name)
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->peerClassName = $builder->getPeerClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->tableMapClassName = $builder->getTableMapClassName();
    }

    /**
     * Static methods
     *
     * @return string
     */
    public function staticMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';

        $this->addGetMaxRank($script);
        $this->addRetrieveByRank($script);
        $this->addReorder($script);
        $this->addDoSelectOrderByRank($script);
        if ($this->behavior->useScope()) {
            $this->addRetrieveList($script);
            $this->addCountList($script);
            $this->addDeleteList($script);
        }
        $this->addShiftRank($script);

        return $script;
    }

    protected function addGetMaxRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Get the highest rank
 * ";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which suite to consider";
        }
        $script .= "
 * @param     ConnectionInterface optional connection
 *
 * @return    integer highest position
 */
static public function getMaxRank(" . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    // shift the objects with a position lower than the one of object
    \$c = new Criteria();
    \$c->addSelectColumn('MAX(' . {$this->tableMapClassName}::RANK_COL . ')');";
        if ($useScope) {
            $script .= "
    \$c->add({$this->tableMapClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "
    \$stmt = {$this->peerClassName}::doSelectStmt(\$c, \$con);

    return \$stmt->fetchColumn();
}
";
    }

    protected function addRetrieveByRank(&$script)
    {
        $peerClassName = $this->peerClassName;
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Get an item from the list based on its rank
 *
 * @param     integer   \$rank rank";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which suite to consider";
        }
        $script .= "
 * @param     ConnectionInterface \$con optional connection
 *
 * @return {$this->objectClassName}
 */
static public function retrieveByRank(\$rank, " . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$c = new Criteria;
    \$c->add({$this->tableMapClassName}::RANK_COL, \$rank);";
        if ($useScope) {
            $script .= "
    \$c->add({$this->tableMapClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    return $peerClassName::doSelectOne(\$c, \$con);
}
";
    }

    protected function addReorder(&$script)
    {
        $queryClassName = $this->queryClassName;
        $columnGetter = 'get' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
        $columnSetter = 'set' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
        $script .= "
/**
 * Reorder a set of sortable objects based on a list of id/position
 * Beware that there is no check made on the positions passed
 * So incoherent positions will result in an incoherent list
 *
 * @param     array     \$order id => rank pairs
 * @param     ConnectionInterface \$con   optional connection
 *
 * @return    boolean true if the reordering took place, false if a database problem prevented it
 */
static public function reorder(array \$order, ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$con->beginTransaction();
    try {
        \$ids = array_keys(\$order);
        \$objects = $queryClassName::create()->findPKs(\$ids);
        foreach (\$objects as \$object) {
            \$pk = \$object->getPrimaryKey();
            if (\$object->$columnGetter() != \$order[\$pk]) {
                \$object->$columnSetter(\$order[\$pk]);
                \$object->save(\$con);
            }
        }
        \$con->commit();

        return true;
    } catch (PropelException \$e) {
        \$con->rollback();
        throw \$e;
    }
}
";
    }

    protected function addDoSelectOrderByRank(&$script)
    {
        $peerClassName = $this->peerClassName;
        $script .= "
/**
 * Return an array of sortable objects ordered by position
 *
 * @param     Criteria  \$criteria  optional criteria object
 * @param     string    \$order     sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param     ConnectionInterface \$con       optional connection
 *
 * @return    array list of sortable objects
 */
static public function doSelectOrderByRank(Criteria \$criteria = null, \$order = Criteria::ASC, ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    if (null === \$criteria) {
        \$criteria = new Criteria();
    } elseif (\$criteria instanceof Criteria) {
        \$criteria = clone \$criteria;
    }

    \$criteria->clearOrderByColumns();

    if (Criteria::ASC == \$order) {
        \$criteria->addAscendingOrderByColumn({$this->tableMapClassName}::RANK_COL);
    } else {
        \$criteria->addDescendingOrderByColumn({$this->tableMapClassName}::RANK_COL);
    }

    return $peerClassName::doSelect(\$criteria, \$con);
}
";
    }

    protected function addRetrieveList(&$script)
    {
        $peerClassName = $this->peerClassName;
        $script .= "
/**
 * Return an array of sortable objects in the given scope ordered by position
 *
 * @param     int       \$scope  the scope of the list
 * @param     string    \$order  sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param     ConnectionInterface \$con    optional connection
 *
 * @return    array list of sortable objects
 */
static public function retrieveList(\$scope, \$order = Criteria::ASC, ConnectionInterface \$con = null)
{
    \$c = new Criteria();
    \$c->add({$this->tableMapClassName}::SCOPE_COL, \$scope);

    return $peerClassName::doSelectOrderByRank(\$c, \$order, \$con);
}
";
    }

    protected function addCountList(&$script)
    {
        $script .= "
/**
 * Return the number of sortable objects in the given scope
 *
 * @param     int       \$scope  the scope of the list
 * @param     ConnectionInterface \$con    optional connection
 *
 * @return    array list of sortable objects
 */
static public function countList(\$scope, ConnectionInterface \$con = null)
{
    \$c = new Criteria();
    \$c->add({$this->tableMapClassName}::SCOPE_COL, \$scope);

    return {$this->peerClassName}::doCount(\$c, \$con);
}
";
    }

    protected function addDeleteList(&$script)
    {
        $peerClassName = $this->peerClassName;
        $script .= "
/**
 * Deletes the sortable objects in the given scope
 *
 * @param     int       \$scope  the scope of the list
 * @param     ConnectionInterface \$con    optional connection
 *
 * @return    int number of deleted objects
 */
static public function deleteList(\$scope, ConnectionInterface \$con = null)
{
    \$c = new Criteria();
    \$c->add({$this->tableMapClassName}::SCOPE_COL, \$scope);

    return $peerClassName::doDelete(\$c, \$con);
}
";
    }
    protected function addShiftRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $peerClassName = $this->peerClassName;
        $script .= "
/**
 * Adds \$delta to all Rank values that are >= \$first and <= \$last.
 * '\$delta' can also be negative.
 *
 * @param      int \$delta Value to be shifted by, can be negative
 * @param      int \$first First node to be shifted
 * @param      int \$last  Last node to be shifted";
        if ($useScope) {
            $script .= "
 * @param      int \$scope Scope to use for the shift";
        }
        $script .= "
 * @param      ConnectionInterface \$con Connection to use.
 */
static public function shiftRank(\$delta, \$first, \$last = null, " . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$whereCriteria = new Criteria({$this->tableMapClassName}::DATABASE_NAME);
    \$criterion = \$whereCriteria->getNewCriterion({$this->tableMapClassName}::RANK_COL, \$first, Criteria::GREATER_EQUAL);
    if (null !== \$last) {
        \$criterion->addAnd(\$whereCriteria->getNewCriterion({$this->tableMapClassName}::RANK_COL, \$last, Criteria::LESS_EQUAL));
    }
    \$whereCriteria->add(\$criterion);";
        if ($useScope) {
            $script .= "
    \$whereCriteria->add({$this->tableMapClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "

    \$valuesCriteria = new Criteria({$this->tableMapClassName}::DATABASE_NAME);
    \$valuesCriteria->add({$this->tableMapClassName}::RANK_COL, array('raw' => {$this->tableMapClassName}::RANK_COL . ' + ?', 'value' => \$delta), Criteria::CUSTOM_EQUAL);

    {$this->builder->getPeerBuilder()->getBasePeerClassName()}::doUpdate(\$whereCriteria, \$valuesCriteria, \$con);
    $peerClassName::clearInstancePool();
}
";
    }
}
