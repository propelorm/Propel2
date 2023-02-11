<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Sortable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Model\Column;

/**
 * Behavior to add sortable query methods
 *
 * @author François Zaninotto
 */
class SortableBehaviorQueryBuilderModifier
{
    /**
     * @var \Propel\Generator\Behavior\Sortable\SortableBehavior
     */
    protected $behavior;

    /**
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder
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
     * @param \Propel\Generator\Behavior\Sortable\SortableBehavior $behavior
     */
    public function __construct(SortableBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getParameter(string $key)
    {
        return $this->behavior->getParameter($key);
    }

    /**
     * @param string $name
     *
     * @return \Propel\Generator\Model\Column
     */
    protected function getColumn(string $name): Column
    {
        return $this->behavior->getColumnForParameter($name);
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return void
     */
    protected function setBuilder(AbstractOMBuilder $builder): void
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->tableMapClassName = $builder->getTableMapClassName();
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryMethods(AbstractOMBuilder $builder): string
    {
        $this->setBuilder($builder);
        $this->builder->declareClasses(
            '\Propel\Runtime\Propel',
            '\Propel\Runtime\Connection\ConnectionInterface',
        );
        $script = '';

        // select filters
        if ($this->behavior->useScope()) {
            $this->addInList($script);
        }
        if ($this->getParameter('rank_column') !== 'rank') {
            $this->addFilterByRank($script);
            $this->addOrderByRank($script);
        }

        // select termination methods
        if (
            $this->getParameter('rank_column') !== 'rank'
            || $this->behavior->useScope()
        ) {
            $this->addFindOneByRank($script);
        }
        $this->addFindList($script);

        // utilities
        $this->addGetMaxRank($script);
        $this->addGetMaxRankArray($script);
        $this->addRetrieveByRank($script);
        $this->addReorder($script);
        $this->addDoSelectOrderByRank($script);
        if ($this->behavior->useScope()) {
            $this->addRetrieveList($script);
            $this->addCountList($script);
            $this->addDeleteList($script);
            $this->addSortableApplyScopeCriteria($script);
        }
        $this->addShiftRank($script);

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function addSortableApplyScopeCriteria(string &$script): void
    {
        $script .= "
/**
 * Applies all scope fields to the given criteria.
 *
 * @param Criteria \$criteria Applies the values directly to this criteria.
 * @param mixed \$scope The scope value as scalar type or array(\$value1, ...).
 * @param string \$method The method we use to apply the values.
 *
 * @return void
 */
static public function sortableApplyScopeCriteria(Criteria \$criteria, \$scope, string \$method = 'add'): void
{
";
        if ($this->behavior->hasMultipleScopes()) {
            foreach ($this->behavior->getScopes() as $idx => $scope) {
                $script .= "
    \$criteria->\$method({$this->tableMapClassName}::" . Column::CONSTANT_PREFIX . strtoupper($scope) . ", \$scope[$idx], Criteria::EQUAL);
";
            }
        } else {
            /** @var string $scope */
            $scope = current($this->behavior->getScopes());
            $script .= "
    \$criteria->\$method({$this->tableMapClassName}::" . Column::CONSTANT_PREFIX . strtoupper($scope) . ", \$scope, Criteria::EQUAL);
";
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
    protected function addInList(string &$script): void
    {
        [$methodSignature, $paramsDoc, $buildScope] = $this->behavior->generateScopePhp();
        $script .= "
/**
 * Returns the objects in a certain list, from the list scope
 *
$paramsDoc
 *
 * @return \$this The current query, for fluid interface
 */
public function inList($methodSignature)
{
    $buildScope
    static::sortableApplyScopeCriteria(\$this, \$scope, 'addUsingAlias');

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFilterByRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        if ($useScope) {
            [$methodSignature, $paramsDoc] = $this->behavior->generateScopePhp();
        }

        $script .= "
/**
 * Filter the query based on a rank in the list
 *
 * @param int \$rank rank";
        if ($useScope) {
            $script .= "
$paramsDoc
";
        }
        $script .= "
 *
 * @return \$this The current object, for fluid interface
 */
public function filterByRank(\$rank" . ($useScope ? ", $methodSignature" : '') . ")
{";

        if ($useScope) {
            $methodSignature = str_replace(' = null', '', $methodSignature);
        }

        $script .= "

    \$this";
        if ($useScope) {
            $script .= "
        ->inList($methodSignature)";
        }
        $script .= "
        ->addUsingAlias({$this->tableMapClassName}::RANK_COL, \$rank, Criteria::EQUAL);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addOrderByRank(string &$script): void
    {
        $script .= "
/**
 * Order the query based on the rank in the list.
 * Using the default \$order, returns the item with the lowest rank first
 *
 * @param string \$order either Criteria::ASC (default) or Criteria::DESC
 *
 * @return \$this The current query, for fluid interface
 */
public function orderByRank(string \$order = Criteria::ASC)
{
    \$order = strtoupper(\$order);
    switch (\$order) {
        case Criteria::ASC:
            \$this->addAscendingOrderByColumn(\$this->getAliasedColName({$this->tableMapClassName}::RANK_COL));

            return \$this;
        case Criteria::DESC:
            \$this->addDescendingOrderByColumn(\$this->getAliasedColName({$this->tableMapClassName}::RANK_COL));

            return \$this;
        default:
            throw new \Propel\Runtime\Exception\PropelException('{$this->queryClassName}::orderBy() only accepts \"asc\" or \"desc\" as argument');
    }
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFindOneByRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        if ($useScope) {
            [$methodSignature, $paramsDoc] = $this->behavior->generateScopePhp();
        }

        $script .= "
/**
 * Get an item from the list based on its rank
 *
 * @param int \$rank rank";
        if ($useScope) {
            $script .= "
$paramsDoc";
        }
        $script .= "
 * @param ConnectionInterface \$con optional connection
 *
 * @return {$this->objectClassName}
 */
public function findOneByRank(\$rank, " . ($useScope ? "$methodSignature, " : '') . "?ConnectionInterface \$con = null)
{";
        if ($useScope) {
            $methodSignature = str_replace(' = null', '', $methodSignature);
        }

        $script .= "

    return \$this
        ->filterByRank(\$rank" . ($useScope ? ", $methodSignature" : '') . ")
        ->findOne(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFindList(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        if ($useScope) {
            [$methodSignature, $paramsDoc] = $this->behavior->generateScopePhp();
        }

        $script .= "
/**
 * Returns " . ($useScope ? 'a' : 'the') . " list of objects
 *";
        if ($useScope) {
            $script .= "
$paramsDoc
";
        }

        $script .= "
 * @param ConnectionInterface \$con Connection to use.
 *
 * @return mixed the list of results, formatted by the current formatter
 */
public function findList(" . ($useScope ? "$methodSignature, " : '') . "\$con = null)
{";

        if ($useScope) {
            $methodSignature = str_replace(' = null', '', $methodSignature);
        }

        $script .= "

    return \$this";

        if ($useScope) {
            $script .= "
        ->inList($methodSignature)";
        }
        $script .= "
        ->orderByRank()
        ->find(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetMaxRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        if ($useScope) {
            [$methodSignature, $paramsDoc, $buildScope] = $this->behavior->generateScopePhp();
        }

        $script .= "
/**
 * Get the highest rank
 * ";
        if ($useScope) {
            $script .= "
$paramsDoc";
        }
        $script .= "
 * @param ConnectionInterface \$con Optional connection
 *
 * @return int|null Highest position
 */
public function getMaxRank(" . ($useScope ? "$methodSignature, " : '') . "?ConnectionInterface \$con = null): ?int
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    // shift the objects with a position lower than the one of object
    \$this->addSelectColumn('MAX(' . {$this->tableMapClassName}::RANK_COL . ')');";
        if ($useScope) {
            $script .= "
            $buildScope
            static::sortableApplyScopeCriteria(\$this, \$scope);";
        }
        $script .= "
    \$stmt = \$this->doSelect(\$con);

    return \$stmt->fetchColumn();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetMaxRankArray(string &$script): void
    {
        $useScope = $this->behavior->useScope();

        $script .= "
/**
 * Get the highest rank by a scope with a array format.
 * ";
        if ($useScope) {
            $script .= "
 * @param mixed \$scope      The scope value as scalar type or array(\$value1, ...).
";
        }
        $script .= "
 * @param ConnectionInterface \$con Optional connection
 *
 * @return int|null Highest position
 */
public function getMaxRankArray(" . ($useScope ? '$scope, ' : '') . "ConnectionInterface \$con = null): ?int
{
    if (\$con === null) {
        \$con = Propel::getConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    // shift the objects with a position lower than the one of object
    \$this->addSelectColumn('MAX(' . {$this->tableMapClassName}::RANK_COL . ')');";
        if ($useScope) {
            $script .= "
    static::sortableApplyScopeCriteria(\$this, \$scope);";
        }
        $script .= "
    \$stmt = \$this->doSelect(\$con);

    return \$stmt->fetchColumn();
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addReorder(string &$script): void
    {
        $columnGetter = 'get' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
        $columnSetter = 'set' . $this->behavior->getColumnForParameter('rank_column')->getPhpName();
        $script .= "
/**
 * Reorder a set of sortable objects based on a list of id/position
 * Beware that there is no check made on the positions passed
 * So incoherent positions will result in an incoherent list
 *
 * @param mixed \$order id => rank pairs
 * @param ConnectionInterface \$con   optional connection
 *
 * @return bool true if the reordering took place, false if a database problem prevented it
 */
public function reorder(\$order, ?ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$con->transaction(function () use (\$con, \$order) {
        \$ids = array_keys(\$order);
        \$objects = \$this->findPks(\$ids, \$con);
        foreach (\$objects as \$object) {
            \$pk = \$object->getPrimaryKey();
            if (\$object->$columnGetter() != \$order[\$pk]) {
                \$object->$columnSetter(\$order[\$pk]);
                \$object->save(\$con);
            }
        }
    });

    return true;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRetrieveByRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Get an item from the list based on its rank
 *
 * @param int \$rank rank";
        if ($useScope) {
            $script .= "
 * @param int \$scope        Scope to determine which suite to consider";
        }
        $script .= "
 * @param ConnectionInterface \$con optional connection
 *
 * @return {$this->objectClassName}
 */
static public function retrieveByRank(\$rank, " . ($useScope ? '$scope = null, ' : '') . "ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getReadConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$c = new Criteria;
    \$c->add({$this->tableMapClassName}::RANK_COL, \$rank);";
        if ($useScope) {
            $script .= "
            static::sortableApplyScopeCriteria(\$c, \$scope);";
        }
        $script .= "

    return static::create(null, \$c)->findOne(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addDoSelectOrderByRank(string &$script): void
    {
        $queryClassName = $this->queryClassName;
        $script .= "
/**
 * Return an array of sortable objects ordered by position
 *
 * @param Criteria \$criteria  optional criteria object
 * @param string \$order     sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param ConnectionInterface \$con       optional connection
 *
 * @return array list of sortable objects
 */
static public function doSelectOrderByRank(?Criteria \$criteria = null, \$order = Criteria::ASC, ?ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRetrieveList(string &$script): void
    {
        $script .= "
/**
 * Return an array of sortable objects in the given scope ordered by position
 *
 * @param int \$scope  the scope of the list
 * @param string \$order  sorting order, to be chosen between Criteria::ASC (default) and Criteria::DESC
 * @param ConnectionInterface \$con    optional connection
 *
 * @return \Propel\Runtime\Collection\ObjectCollection List of sortable objects
 */
static public function retrieveList(\$scope, \$order = Criteria::ASC, ?ConnectionInterface \$con = null)
{
    \$c = new Criteria();
    static::sortableApplyScopeCriteria(\$c, \$scope);

    return {$this->queryClassName}::doSelectOrderByRank(\$c, \$order, \$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCountList(string &$script): void
    {
        $script .= "
/**
 * Return the number of sortable objects in the given scope
 *
 * @param int \$scope  the scope of the list
 * @param ConnectionInterface \$con    optional connection
 *
 * @return int Count.
 */
static public function countList(\$scope, ?ConnectionInterface \$con = null): int
{
    \$c = new Criteria();
    \$c->add({$this->tableMapClassName}::SCOPE_COL, \$scope);

    return {$this->queryClassName}::create(null, \$c)->count(\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addDeleteList(string &$script): void
    {
        $script .= "
/**
 * Deletes the sortable objects in the given scope
 *
 * @param int \$scope  the scope of the list
 * @param ConnectionInterface \$con    optional connection
 *
 * @return int number of deleted objects
 */
static public function deleteList(\$scope, ?ConnectionInterface \$con = null): int
{
    \$c = new Criteria();
    static::sortableApplyScopeCriteria(\$c, \$scope);

    return {$this->tableMapClassName}::doDelete(\$c, \$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addShiftRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Adds \$delta to all Rank values that are >= \$first and <= \$last.
 * '\$delta' can also be negative.
 *
 * @param int \$delta Value to be shifted by, can be negative
 * @param int \$first First node to be shifted
 * @param int \$last  Last node to be shifted";
        if ($useScope) {
            $script .= "
 * @param int \$scope Scope to use for the shift";
        }
        $script .= "
 * @param ConnectionInterface \$con Connection to use.
 */
static public function sortableShiftRank(\$delta, \$first, \$last = null, " . ($useScope ? '$scope = null, ' : '') . "ConnectionInterface \$con = null)
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
            static::sortableApplyScopeCriteria(\$whereCriteria, \$scope);";
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
