<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;

/**
 * Behavior to add sortable columns and abilities
 *
 * @author François Zaninotto
 * @author heltem <heltem@o2php.com>
 */
class SortableBehaviorObjectBuilderModifier
{
    /**
     * @var SortableBehavior
     */
    protected $behavior;

    /**
     * @var Table
     */
    protected $table;

    /**
     * @var AbstractOMBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $objectClassName;

    /**
     * @var string
     */
    protected $tableMapClassName;

    /**
     * @var string
     */
    protected $queryClassName;

    /**
     * @var string
     */
    protected $queryFullClassName;

    /**
     * @param SortableBehavior $behavior
     */
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

    protected function getColumnPhpName($name)
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    protected function setBuilder(AbstractOMBuilder $builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->queryFullClassName = $builder->getStubQueryBuilder()->getFullyQualifiedClassName();
        $this->tableMapClassName = $builder->getTableMapClassName();
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @param string $columnName
     *
     * @return string The related getter, e.g. 'getRank'
     */
    protected function getColumnGetter($columnName = 'rank_column')
    {
        return 'get' . $this->behavior->getColumnForParameter($columnName)->getPhpName();
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @param string $columnName
     *
     * @return string The related setter, e.g. 'setRank'
     */
    protected function getColumnSetter($columnName = 'rank_column')
    {
        return 'set' . $this->behavior->getColumnForParameter($columnName)->getPhpName();
    }

    public function preSave($builder)
    {
        return "\$this->processSortableQueries(\$con);";
    }

    public function preInsert($builder)
    {
        $useScope = $this->behavior->useScope();
        $this->setBuilder($builder);

        return "if (!\$this->isColumnModified({$this->tableMapClassName}::RANK_COL)) {
    \$this->{$this->getColumnSetter()}({$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con) + 1);
}
";
    }

    public function preUpdate($builder)
    {
        if ($this->behavior->useScope()) {
            $this->setBuilder($builder);

            $condition = array();

            foreach ($this->behavior->getScopes() as $scope) {
                $condition[] = "\$this->isColumnModified({$this->tableMapClassName}::".Column::CONSTANT_PREFIX.strtoupper($scope).")";
            }

            $condition = implode(' OR ', $condition);

            $script = "// if scope has changed and rank was not modified (if yes, assuming superior action)
// insert object to the end of new scope and cleanup old one
if (($condition) && !\$this->isColumnModified({$this->tableMapClassName}::RANK_COL)) { {$this->queryClassName}::sortableShiftRank(-1, \$this->{$this->getColumnGetter()}() + 1, null, \$this->oldScope, \$con);
    \$this->insertAtBottom(\$con);
}
";

            return $script;
        }
    }

    public function preDelete($builder)
    {
        $useScope = $this->behavior->useScope();
        $this->setBuilder($builder);

        return "
{$this->queryClassName}::sortableShiftRank(-1, \$this->{$this->getColumnGetter()}() + 1, null, ". ($useScope ? "\$this->getScopeValue(), " : '') . "\$con);
{$this->tableMapClassName}::clearInstancePool();
";
    }

    public function objectAttributes($builder)
    {
        $script = "
/**
 * Queries to be executed in the save transaction
 * @var        array
 */
protected \$sortableQueries = array();
";
        if ($this->behavior->useScope()) {
            $script .= "
/**
 * The old scope value.
 * @var        int
 */
protected \$oldScope;
";
        }

        return $script;
    }

    public function objectMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';
        if ('rank' !== $this->getParameter('rank_column')) {
            $this->addRankAccessors($script);
        }
        if ($this->behavior->useScope()
            && 'scope_value' !== $this->getParameter('scope_column')) {
            $this->addScopeAccessors($script);
        }
        $this->addIsFirst($script);
        $this->addIsLast($script);
        $this->addGetNext($script);
        $this->addGetPrevious($script);
        $this->addInsertAtRank($script);
        $this->addInsertAtBottom($script);
        $this->addInsertAtTop($script);
        $this->addMoveToRank($script);
        $this->addSwapWith($script);
        $this->addMoveUp($script);
        $this->addMoveDown($script);
        $this->addMoveToTop($script);
        $this->addMoveToBottom($script);
        $this->addRemoveFromList($script);
        $this->addProcessSortableQueries($script);

        return $script;
    }

    public function objectFilter(&$script, $builder)
    {
        if ($this->behavior->useScope()) {
            if ($this->behavior->hasMultipleScopes()) {

                foreach ($this->behavior->getScopes() as $idx => $scope) {
                    $name = strtolower($this->behavior->getTable()->getColumn($scope)->getName());

                    $search = "if (\$this->$name !== \$v) {";
                    $replace = $search . "
            // sortable behavior
            \$this->oldScope[$idx] = \$this->$name;
";
                    $script = str_replace($search, $replace, $script);
                }

            } else {
                $scope = current($this->behavior->getScopes());
                $name = strtolower($this->behavior->getTable()->getColumn($scope)->getName());

                $search = "if (\$this->$name !== \$v) {";
                $replace = $search . "
            // sortable behavior
            \$this->oldScope = \$this->$name;
";
                $script = str_replace($search, $replace, $script);
            }
        }
    }

    /**
     * Get the wraps for getter/setter, if the rank column has not the default name
     *
     * @return string
     */
    protected function addRankAccessors(&$script)
    {
        $script .= "
/**
 * Wrap the getter for rank value
 *
 * @return    int
 */
public function getRank()
{
    return \$this->{$this->getColumnAttribute('rank_column')};
}

/**
 * Wrap the setter for rank value
 *
 * @param     int
 * @return    \$this|{$this->objectClassName}
 */
public function setRank(\$v)
{
    return \$this->{$this->getColumnSetter()}(\$v);
}
";
    }

    /**
     * Get the wraps for getter/setter, if the scope column has not the default name
     *
     * @return string
     */
    protected function addScopeAccessors(&$script)
    {

        $script .= "
/**
 * Wrap the getter for scope value
 *
 * @param boolean \$returnNulls If true and all scope values are null, this will return null instead of a array full with nulls
 *
 * @return    mixed A array or a native type
 */
public function getScopeValue(\$returnNulls = true)
{
";
        if ($this->behavior->hasMultipleScopes()) {
            $script .= "
    \$result = array();
    \$onlyNulls = true;
";
            foreach ($this->behavior->getScopes() as $scopeField) {
                $script .= "
    \$onlyNulls &= null === (\$result[] = \$this->{$this->behavior->getColumnGetter($scopeField)}());
";

            }

            $script .= "

    return \$onlyNulls && \$returnNulls ? null : \$result;
";
        } else {

            $script .= "

    return \$this->{$this->getColumnGetter('scope_column')}();
";
        }

        $script .= "
}

/**
 * Wrap the setter for scope value
 *
 * @param     mixed A array or a native type
 * @return    \$this|{$this->objectClassName}
 */
public function setScopeValue(\$v)
{
";

        if ($this->behavior->hasMultipleScopes()) {

            foreach ($this->behavior->getScopes() as $idx => $scopeField) {
                $script .= "
    \$this->{$this->behavior->getColumnSetter($scopeField)}(\$v === null ? null : \$v[$idx]);
";
            }

        } else {
            $script .= "

    return \$this->{$this->getColumnSetter('scope_column')}(\$v);
";

        }
        $script .= "
}
";
    }

    protected function addIsFirst(&$script)
    {
        $script .= "
/**
 * Check if the object is first in the list, i.e. if it has 1 for rank
 *
 * @return    boolean
 */
public function isFirst()
{
    return \$this->{$this->getColumnGetter()}() == 1;
}
";
    }

    protected function addIsLast(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Check if the object is last in the list, i.e. if its rank is the highest rank
 *
 * @param     ConnectionInterface  \$con      optional connection
 *
 * @return    boolean
 */
public function isLast(ConnectionInterface \$con = null)
{
    return \$this->{$this->getColumnGetter()}() == {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con);
}
";
    }

    protected function addGetNext(&$script)
    {
        $useScope = $this->behavior->useScope();
        // The generateScopePhp() method below contains the following list of variables:
        // list($methodSignature, $paramsDoc, $buildScope, $buildScopeVars)
        list($methodSignature, , , $buildScopeVars) = $this->behavior->generateScopePhp();

        $script .= "
/**
 * Get the next item in the list, i.e. the one for which rank is immediately higher
 *
 * @param     ConnectionInterface  \$con      optional connection
 *
 * @return    {$this->objectClassName}
 */
public function getNext(ConnectionInterface \$con = null)
{";
        $script .= "

    \$query = {$this->queryClassName}::create();
";

        if ($useScope) {
            $methodSignature = str_replace(' = null', '', $methodSignature);

            $script .= "
    \$scope = \$this->getScopeValue();
    $buildScopeVars
    \$query->filterByRank(\$this->{$this->getColumnGetter()}() + 1, $methodSignature);
";
        } else {

            $script .= "
    \$query->filterByRank(\$this->{$this->getColumnGetter()}() + 1);
";
        }

        $script .= "

    return \$query->findOne(\$con);
}
";
    }

    protected function addGetPrevious(&$script)
    {
        $useScope = $this->behavior->useScope();

        // The generateScopePhp() method below contains the following list of variables:
        // list($methodSignature, $paramsDoc, $buildScope, $buildScopeVars)
        list($methodSignature, , , $buildScopeVars) = $this->behavior->generateScopePhp();

        $script .= "
/**
 * Get the previous item in the list, i.e. the one for which rank is immediately lower
 *
 * @param     ConnectionInterface  \$con      optional connection
 *
 * @return    {$this->objectClassName}
 */
public function getPrevious(ConnectionInterface \$con = null)
{";
        $script .= "

    \$query = {$this->queryClassName}::create();
";

        if ($useScope) {
            $methodSignature = str_replace(' = null', '', $methodSignature);

            $script .= "
    \$scope = \$this->getScopeValue();
    $buildScopeVars
    \$query->filterByRank(\$this->{$this->getColumnGetter()}() - 1, $methodSignature);
";
        } else {

            $script .= "
    \$query->filterByRank(\$this->{$this->getColumnGetter()}() - 1);
";
        }

        $script .= "

    return \$query->findOne(\$con);
}
";
    }

    protected function addInsertAtRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $queryClassName = $this->queryFullClassName;
        $script .= "
/**
 * Insert at specified rank
 * The modifications are not persisted until the object is saved.
 *
 * @param     integer    \$rank rank value
 * @param     ConnectionInterface  \$con      optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 *
 * @throws    PropelException
 */
public function insertAtRank(\$rank, ConnectionInterface \$con = null)
{";
        $script .= "
    \$maxRank = {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con);
    if (\$rank < 1 || \$rank > \$maxRank + 1) {
        throw new PropelException('Invalid rank ' . \$rank);
    }
    // move the object in the list, at the given rank
    \$this->{$this->getColumnSetter()}(\$rank);
    if (\$rank != \$maxRank + 1) {
        // Keep the list modification query for the save() transaction
        \$this->sortableQueries []= array(
            'callable'  => array('{$queryClassName}', 'sortableShiftRank'),
            'arguments' => array(1, \$rank, null, " . ($useScope ? "\$this->getScopeValue()" : '') . ")
        );
    }

    return \$this;
}
";
    }

    protected function addInsertAtBottom(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Insert in the last rank
 * The modifications are not persisted until the object is saved.
 *
 * @param ConnectionInterface \$con optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 *
 * @throws    PropelException
 */
public function insertAtBottom(ConnectionInterface \$con = null)
{";
        $script .= "
    \$this->{$this->getColumnSetter()}({$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con) + 1);

    return \$this;
}
";
    }

    protected function addInsertAtTop(&$script)
    {
        $script .= "
/**
 * Insert in the first rank
 * The modifications are not persisted until the object is saved.
 *
 * @return    \$this|{$this->objectClassName} the current object
 */
public function insertAtTop()
{
    return \$this->insertAtRank(1);
}
";
    }

    protected function addMoveToRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Move the object to a new rank, and shifts the rank
 * Of the objects inbetween the old and new rank accordingly
 *
 * @param     integer   \$newRank rank value
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 *
 * @throws    PropelException
 */
public function moveToRank(\$newRank, ConnectionInterface \$con = null)
{
    if (\$this->isNew()) {
        throw new PropelException('New objects cannot be moved. Please use insertAtRank() instead');
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    if (\$newRank < 1 || \$newRank > {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con)) {
        throw new PropelException('Invalid rank ' . \$newRank);
    }

    \$oldRank = \$this->{$this->getColumnGetter()}();
    if (\$oldRank == \$newRank) {
        return \$this;
    }

    \$con->transaction(function () use (\$con, \$oldRank, \$newRank) {
        // shift the objects between the old and the new rank
        \$delta = (\$oldRank < \$newRank) ? -1 : 1;
        {$this->queryClassName}::sortableShiftRank(\$delta, min(\$oldRank, \$newRank), max(\$oldRank, \$newRank), " . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con);

        // move the object to its new rank
        \$this->{$this->getColumnSetter()}(\$newRank);
        \$this->save(\$con);
    });

    return \$this;
}
";
    }

    protected function addSwapWith(&$script)
    {
        $script .= "
/**
 * Exchange the rank of the object with the one passed as argument, and saves both objects
 *
 * @param     {$this->objectClassName} \$object
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 *
 * @throws Exception if the database cannot execute the two updates
 */
public function swapWith(\$object, ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    \$con->transaction(function () use (\$con, \$object) {";
        if ($this->behavior->useScope()) {
            $script .= "
        \$oldScope = \$this->getScopeValue();
        \$newScope = \$object->getScopeValue();
        if (\$oldScope != \$newScope) {
            \$this->setScopeValue(\$newScope);
            \$object->setScopeValue(\$oldScope);
        }";
        }

        $script .= "
        \$oldRank = \$this->{$this->getColumnGetter()}();
        \$newRank = \$object->{$this->getColumnGetter()}();

        \$this->{$this->getColumnSetter()}(\$newRank);
        \$object->{$this->getColumnSetter()}(\$oldRank);

        \$this->save(\$con);
        \$object->save(\$con);
    });

    return \$this;
}
";
    }

    protected function addMoveUp(&$script)
    {
        $script .= "
/**
 * Move the object higher in the list, i.e. exchanges its rank with the one of the previous object
 *
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 */
public function moveUp(ConnectionInterface \$con = null)
{
    if (\$this->isFirst()) {
        return \$this;
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    \$con->transaction(function () use (\$con) {
        \$prev = \$this->getPrevious(\$con);
        \$this->swapWith(\$prev, \$con);
    });

    return \$this;
}
";
    }

    protected function addMoveDown(&$script)
    {
        $script .= "
/**
 * Move the object higher in the list, i.e. exchanges its rank with the one of the next object
 *
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 */
public function moveDown(ConnectionInterface \$con = null)
{
    if (\$this->isLast(\$con)) {
        return \$this;
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    \$con->transaction(function () use (\$con) {
        \$next = \$this->getNext(\$con);
        \$this->swapWith(\$next, \$con);
    });

    return \$this;
}
";
    }

    protected function addMoveToTop(&$script)
    {
        $script .= "
/**
 * Move the object to the top of the list
 *
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    \$this|{$this->objectClassName} the current object
 */
public function moveToTop(ConnectionInterface \$con = null)
{
    if (\$this->isFirst()) {
        return \$this;
    }

    return \$this->moveToRank(1, \$con);
}
";
    }

    protected function addMoveToBottom(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Move the object to the bottom of the list
 *
 * @param     ConnectionInterface \$con optional connection
 *
 * @return integer the old object's rank
 */
public function moveToBottom(ConnectionInterface \$con = null)
{
    if (\$this->isLast(\$con)) {
        return false;
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    return \$con->transaction(function () use (\$con) {
        \$bottom = {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? "\$this->getScopeValue(), " : '') . "\$con);

        return \$this->moveToRank(\$bottom, \$con);
    });
}
";
    }

    protected function addRemoveFromList(&$script)
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Removes the current object from the list".($useScope ? ' (moves it to the null scope)' : '').".
 * The modifications are not persisted until the object is saved.
 *
 * @return    \$this|{$this->objectClassName} the current object
 */
public function removeFromList()
{";

        if ($useScope) {
            $script .= "
    // check if object is already removed
    if (\$this->getScopeValue() === null) {
        throw new PropelException('Object is already removed (has null scope)');
    }

    // move the object to the end of null scope
    \$this->setScopeValue(null);";
        } else {
            $script .= "
    // Keep the list modification query for the save() transaction
    \$this->sortableQueries[] = array(
        'callable'  => array('{$this->queryFullClassName}', 'sortableShiftRank'),
        'arguments' => array(-1, \$this->{$this->getColumnGetter()}() + 1, null" . ($useScope ? ", \$this->getScopeValue()" : '') . ")
    );
    // remove the object from the list
    \$this->{$this->getColumnSetter('rank_column')}(null);
    ";
        }
        $script .= "

    return \$this;
}
";
    }

    protected function addProcessSortableQueries(&$script)
    {
        $script .= "
/**
 * Execute queries that were saved to be run inside the save transaction
 */
protected function processSortableQueries(\$con)
{
    foreach (\$this->sortableQueries as \$query) {
        \$query['arguments'][]= \$con;
        call_user_func_array(\$query['callable'], \$query['arguments']);
    }
    \$this->sortableQueries = array();
}
";
    }
}
