<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
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
        $this->addReorder($script);

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
    return \$this->addUsingAlias({$this->peerClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);
}
";
    }

    protected function addFilterByRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $peerClassName = $this->peerClassName;
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
        ->addUsingAlias($peerClassName::RANK_COL, \$rank, Criteria::EQUAL);
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
            return \$this->addAscendingOrderByColumn(\$this->getAliasedColName(" . $this->peerClassName . "::RANK_COL));
            break;
        case Criteria::DESC:
            return \$this->addDescendingOrderByColumn(\$this->getAliasedColName(" . $this->peerClassName . "::RANK_COL));
            break;
        default:
            throw new \Propel\Runtime\Exception\PropelException('" . $this->queryClassName . "::orderBy() only accepts \"asc\" or \"desc\" as argument');
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
        \$con = Propel::getServiceContainer()->getReadConnection({$this->peerClassName}::DATABASE_NAME);
    }
    // shift the objects with a position lower than the one of object
    \$this->addSelectColumn('MAX(' . {$this->peerClassName}::RANK_COL . ')');";
        if ($useScope) {
            $script .= "
    \$this->add({$this->peerClassName}::SCOPE_COL, \$scope, Criteria::EQUAL);";
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
        $peerClassName = $this->peerClassName;
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
        \$con = Propel::getServiceContainer()->getReadConnection($peerClassName::DATABASE_NAME);
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
}
