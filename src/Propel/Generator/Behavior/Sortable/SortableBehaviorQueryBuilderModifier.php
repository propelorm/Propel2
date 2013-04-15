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
 * Behavior to add sortable query methods
 *
 * @author François Zaninotto
 */
class SortableBehaviorQueryBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

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

    protected function getColumn($name)
    {
        return $this->behavior->getColumnForParameter($name);
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->tableMapClassName = $builder->getTableMapClassName();
    }

    public function queryMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';

        // select filters
        if ($this->behavior->useScope()) {
            $this->addInList($script);
        }
        if ('rank' !== $this->getParameter('rank_column')) {
            $this->addFilterByRank($script);
            $this->addOrderByRank($script);
        }

        // select termination methods
        if ('rank' !== $this->getParameter('rank_column')
            || $this->behavior->useScope()) {
            $this->addFindOneByRank($script);
        }
        $this->addFindList($script);

        // utilities
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

    protected function addInList(&$script)
    {
        $script .= "
/**
 * Returns the objects in a certain list, from the list scope
 *
 * @param     int \$scope        Scope to determine which objects node to return
 *
 * @return    {$this->queryClassName} The current query, for fluid interface
 */
public function inList(\$scope = null)
{
    return \$this->addUsingAlias({$this->tableMapClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);
}
";
    }

    protected function addFilterByRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Filter the query based on a rank in the list
 *
 * @param     integer   \$rank rank";
        if ($useScope) {
            $script .= "
 * @param     int \$scope        Scope to determine which suite to consider";
        }
        $script .= "
 *
 * @return    " . $this->queryClassName . " The current query, for fluid interface
 */
public function filterByRank(\$rank" . ($useScope ? ", \$scope = null" : "") . ")
{
    return \$this";
        if ($useScope) {
            $script .= "
        ->inList(\$scope)";
        }
        $script .= "
        ->addUsingAlias({$this->tableMapClassName}::RANK_COL, \$rank, Criteria::EQUAL);
}
";
    }

    protected function addOrderByRank(&$script)
    {
        $script .= "
/**
 * Order the query based on the rank in the list.
 * Using the default \$order, returns the item with the lowest rank first
 *
 * @param     string \$order either Criteria::ASC (default) or Criteria::DESC
 *
 * @return    " . $this->queryClassName . " The current query, for fluid interface
 */
public function orderByRank(\$order = Criteria::ASC)
{
    \$order = strtoupper(\$order);
    switch (\$order) {
        case Criteria::ASC:
            return \$this->addAscendingOrderByColumn(\$this->getAliasedColName({$this->tableMapClassName}::RANK_COL));
            break;
        case Criteria::DESC:
            return \$this->addDescendingOrderByColumn(\$this->getAliasedColName({$this->tableMapClassName}::RANK_COL));
            break;
        default:
            throw new \Propel\Runtime\Exception\PropelException('{$this->queryClassName}::orderBy() only accepts \"asc\" or \"desc\" as argument');
    }
}
";
    }

    protected function addFindOneByRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Get an item from the list based on its rank
 *
 * @param     integer   \$rank rank";
        if ($useScope) {
            $script .= "
 * @param     int \$scope        Scope to determine which suite to consider";
        }
        $script .= "
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    {$this->objectClassName}
 */
public function findOneByRank(\$rank, " . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{
    return \$this
        ->filterByRank(\$rank" . ($useScope ? ", \$scope" : "") . ")
        ->findOne(\$con);
}
";
    }

    protected function addFindList(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Returns " . ($useScope ? 'a' : 'the') ." list of objects
 *";
        if ($useScope) {
            $script .= "
 * @param      int \$scope        Scope to determine which list to return";
        }

        $script .= "
 * @param      ConnectionInterface \$con    Connection to use.
 *
 * @return     mixed the list of results, formatted by the current formatter
 */
public function findList(" . ($useScope ? "\$scope = null, " : "") . "\$con = null)
{
    return \$this";

        if ($useScope) {
            $script .= "
        ->inList(\$scope)";
        }
        $script .= "
        ->orderByRank()
        ->find(\$con);
}
";
    }

    protected function addGetMaxRank(&$script)
    {
        $this->builder->declareClasses(
            '\Propel\Runtime\Propel'
        );
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
public function getMaxRank(" . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    // shift the objects with a position lower than the one of object
    \$this->addSelectColumn('MAX(' . {$this->tableMapClassName}::RANK_COL . ')');";
        if ($useScope) {
            $script .= "
    \$this->add({$this->tableMapClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);";
        }
        $script .= "
    \$stmt = \$this->doSelect(\$con);

    return \$stmt->fetchColumn();
}
";
    }

    protected function addReorder(&$script)
    {
        $this->builder->declareClasses('\Propel\Runtime\Propel');
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
public function reorder(array \$order, ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$con->beginTransaction();
    try {
        \$ids = array_keys(\$order);
        \$objects = \$this->findPks(\$ids, \$con);
        foreach (\$objects as \$object) {
            \$pk = \$object->getPrimaryKey();
            if (\$object->$columnGetter() != \$order[\$pk]) {
                \$object->$columnSetter(\$order[\$pk]);
                \$object->save(\$con);
            }
        }
        \$con->commit();

        return true;
    } catch (\Propel\Runtime\Exception\PropelException \$e) {
        \$con->rollback();
        throw \$e;
    }
}
";
    }

    protected function addRetrieveByRank(&$script)
    {
        $queryClassName = $this->queryClassName;
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

    return $queryClassName::create(null, \$c)->findOne(\$con);
}
";
    }

    protected function addDoSelectOrderByRank(&$script)
    {
        $queryClassName = $this->queryClassName;
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

    return $queryClassName::create(null, \$criteria)->find(\$con);
}
";
    }

    protected function addRetrieveList(&$script)
    {
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

    return {$this->queryClassName}::doSelectOrderByRank(\$c, \$order, \$con);
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

    return {$this->queryClassName}::create(null, \$c)->count(\$con);
}
";
    }

    protected function addDeleteList(&$script)
    {
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

    return {$this->tableMapClassName}::doDelete(\$c, \$con);
}
";
    }
    protected function addShiftRank(&$script)
    {
        $useScope = $this->behavior->useScope();
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
static public function sortableShiftRank(\$delta, \$first, \$last = null, " . ($useScope ? "\$scope = null, " : "") . "ConnectionInterface \$con = null)
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

    \$whereCriteria->doUpdate(\$valuesCriteria, \$con);
    {$this->tableMapClassName}::clearInstancePool();
}
";
    }
}
