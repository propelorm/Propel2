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
 * Behavior to add sortable columns and abilities
 *
 * @author François Zaninotto
 * @author heltem <heltem@o2php.com>
 */
class SortableBehaviorObjectBuilderModifier
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
     * @return string
     */
    protected function getColumnAttribute(string $name): string
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    protected function getColumnPhpName(string $name): ?string
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
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
    protected function getColumnGetter(string $columnName = 'rank_column'): string
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
    protected function getColumnSetter(string $columnName = 'rank_column'): string
    {
        return 'set' . $this->behavior->getColumnForParameter($columnName)->getPhpName();
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function preSave(AbstractOMBuilder $builder): string
    {
        return '$this->processSortableQueries($con);';
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function preInsert(AbstractOMBuilder $builder): string
    {
        $useScope = $this->behavior->useScope();
        $this->setBuilder($builder);

        return "if (!\$this->isColumnModified({$this->tableMapClassName}::RANK_COL)) {
    \$this->{$this->getColumnSetter()}({$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con) + 1);
}
";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function preUpdate(AbstractOMBuilder $builder): string
    {
        if ($this->behavior->useScope()) {
            $this->setBuilder($builder);

            $condition = [];

            foreach ($this->behavior->getScopes() as $scope) {
                $condition[] = "\$this->isColumnModified({$this->tableMapClassName}::" . Column::CONSTANT_PREFIX . strtoupper($scope) . ')';
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

        return '';
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function preDelete(AbstractOMBuilder $builder): string
    {
        $useScope = $this->behavior->useScope();
        $this->setBuilder($builder);

        return "
{$this->queryClassName}::sortableShiftRank(-1, \$this->{$this->getColumnGetter()}() + 1, null, " . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con);
{$this->tableMapClassName}::clearInstancePool();
";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectAttributes(AbstractOMBuilder $builder): string
    {
        $script = "
/**
 * Queries to be executed in the save transaction
 * @var        array
 */
protected \$sortableQueries = [];
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

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectMethods(AbstractOMBuilder $builder): string
    {
        $this->setBuilder($builder);
        $script = '';
        if ($this->getParameter('rank_column') !== 'rank') {
            $this->addRankAccessors($script);
        }
        if (
            $this->behavior->useScope()
            && $this->getParameter('scope_column') !== 'scope_value'
        ) {
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

    /**
     * @param string $script
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return void
     */
    public function objectFilter(string &$script, AbstractOMBuilder $builder): void
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
                /** @var string $scope */
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
     * @param string $script
     *
     * @return void
     */
    protected function addRankAccessors(string &$script): void
    {
        $script .= "
/**
 * Wrap the getter for rank value
 *
 * @return int
 */
public function getRank()
{
    return \$this->{$this->getColumnAttribute('rank_column')};
}

/**
 * Wrap the setter for rank value
 *
 * @param int
 * @return \$this
 */
public function setRank(\$v)
{
    \$this->{$this->getColumnSetter()}(\$v);

    return \$this;
}
";
    }

    /**
     * Get the wraps for getter/setter, if the scope column has not the default name
     *
     * @param string $script
     *
     * @return void
     */
    protected function addScopeAccessors(string &$script): void
    {
        $script .= "
/**
 * Wrap the getter for scope value
 *
 * @param bool \$returnNulls If true and all scope values are null, this will return null instead of a array full with nulls
 *
 * @return mixed A array or a native type
 */
public function getScopeValue(\$returnNulls = true)
{
";
        if ($this->behavior->hasMultipleScopes()) {
            $script .= "
    \$result = [];
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
        } elseif ($this->behavior->getColumnForParameter('scope_column')->isEnumType()) {
            $columnConstant = strtoupper(preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $this->getColumnAttribute('scope_column')));
            $script .= "
    return array_search(\$this->{$this->getColumnGetter('scope_column')}(), {$this->tableMapClassName}::getValueSet({$this->tableMapClassName}::COL_{$columnConstant}));
            ";
        } elseif ($this->behavior->getColumnForParameter('scope_column')->isSetType()) {
            $columnConstant = strtoupper(preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $this->getColumnAttribute('scope_column')));
            $script .= "
    try {
        return SetColumnConverter::convertToInt(\$this->{$this->getColumnGetter('scope_column')}(), {$this->tableMapClassName}::getValueSet({$this->tableMapClassName}::COL_{$columnConstant}));
    } catch (SetColumnConverterException \$e) {
        throw new PropelException(sprintf('Value `%s` is not accepted in this set column', \$e->getValue()), \$e->getCode(), \$e);
    }
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
 * @param mixed A array or a native type
 * @return \$this
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

    \$this->{$this->getColumnSetter('scope_column')}(\$v);

    return \$this;
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
    protected function addIsFirst(string &$script): void
    {
        $script .= "
/**
 * Check if the object is first in the list, i.e. if it has 1 for rank
 *
 * @return bool
 */
public function isFirst()
{
    return \$this->{$this->getColumnGetter()}() == 1;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addIsLast(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Check if the object is last in the list, i.e. if its rank is the highest rank
 *
 * @param ConnectionInterface \$con Optional connection
 *
 * @return bool
 */
public function isLast(?ConnectionInterface \$con = null)
{
    return \$this->{$this->getColumnGetter()}() == {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetNext(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        // The generateScopePhp() method below contains the following list of variables:
        // list($methodSignature, $paramsDoc, $buildScope, $buildScopeVars)
        [$methodSignature, , , $buildScopeVars] = $this->behavior->generateScopePhp();

        $script .= "
/**
 * Get the next item in the list, i.e. the one for which rank is immediately higher
 *
 * @param ConnectionInterface \$con Optional connection
 *
 * @return {$this->objectClassName}
 */
public function getNext(?ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetPrevious(string &$script): void
    {
        $useScope = $this->behavior->useScope();

        // The generateScopePhp() method below contains the following list of variables:
        // list($methodSignature, $paramsDoc, $buildScope, $buildScopeVars)
        [$methodSignature, , , $buildScopeVars] = $this->behavior->generateScopePhp();

        $script .= "
/**
 * Get the previous item in the list, i.e. the one for which rank is immediately lower
 *
 * @param ConnectionInterface \$con      optional connection
 *
 * @return {$this->objectClassName}
 */
public function getPrevious(?ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addInsertAtRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $queryClassName = $this->queryFullClassName;
        $script .= "
/**
 * Insert at specified rank
 * The modifications are not persisted until the object is saved.
 *
 * @param int \$rank rank value
 * @param ConnectionInterface \$con Optional connection
 *
 * @return \$this The current object
 *
 * @throws    PropelException
 */
public function insertAtRank(\$rank, ?ConnectionInterface \$con = null)
{";
        $script .= "
    \$maxRank = {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con);
    if (\$rank < 1 || \$rank > \$maxRank + 1) {
        throw new PropelException('Invalid rank ' . \$rank);
    }
    // move the object in the list, at the given rank
    \$this->{$this->getColumnSetter()}(\$rank);
    if (\$rank != \$maxRank + 1) {
        // Keep the list modification query for the save() transaction
        \$this->sortableQueries []= [
            'callable'  => array('{$queryClassName}', 'sortableShiftRank'),
            'arguments' => array(1, \$rank, null, " . ($useScope ? '$this->getScopeValue()' : '') . "),
        ];
    }

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addInsertAtBottom(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Insert in the last rank
 * The modifications are not persisted until the object is saved.
 *
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 *
 * @throws    PropelException
 */
public function insertAtBottom(?ConnectionInterface \$con = null)
{";
        $script .= "
    \$this->{$this->getColumnSetter()}({$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con) + 1);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addInsertAtTop(string &$script): void
    {
        $script .= "
/**
 * Insert in the first rank
 * The modifications are not persisted until the object is saved.
 *
 * @return \$this The current object
 */
public function insertAtTop()
{
    \$this->insertAtRank(1);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addMoveToRank(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Move the object to a new rank, and shifts the rank
 * Of the objects inbetween the old and new rank accordingly
 *
 * @param int \$newRank rank value
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 *
 * @throws    PropelException
 */
public function moveToRank(\$newRank, ?ConnectionInterface \$con = null)
{
    if (\$this->isNew()) {
        throw new PropelException('New objects cannot be moved. Please use insertAtRank() instead');
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }
    if (\$newRank < 1 || \$newRank > {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con)) {
        throw new PropelException('Invalid rank ' . \$newRank);
    }

    \$oldRank = \$this->{$this->getColumnGetter()}();
    if (\$oldRank == \$newRank) {
        return \$this;
    }

    \$con->transaction(function () use (\$con, \$oldRank, \$newRank) {
        // shift the objects between the old and the new rank
        \$delta = (\$oldRank < \$newRank) ? -1 : 1;
        {$this->queryClassName}::sortableShiftRank(\$delta, min(\$oldRank, \$newRank), max(\$oldRank, \$newRank), " . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con);

        // move the object to its new rank
        \$this->{$this->getColumnSetter()}(\$newRank);
        \$this->save(\$con);
    });

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addSwapWith(string &$script): void
    {
        $script .= "
/**
 * Exchange the rank of the object with the one passed as argument, and saves both objects
 *
 * @param {$this->objectClassName} \$object
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 *
 * @throws Exception if the database cannot execute the two updates
 */
public function swapWith(\$object, ?ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addMoveUp(string &$script): void
    {
        $script .= "
/**
 * Move the object higher in the list, i.e. exchanges its rank with the one of the previous object
 *
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 */
public function moveUp(?ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addMoveDown(string &$script): void
    {
        $script .= "
/**
 * Move the object higher in the list, i.e. exchanges its rank with the one of the next object
 *
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 */
public function moveDown(?ConnectionInterface \$con = null)
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

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addMoveToTop(string &$script): void
    {
        $script .= "
/**
 * Move the object to the top of the list
 *
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 */
public function moveToTop(?ConnectionInterface \$con = null)
{
    if (\$this->isFirst()) {
        return \$this;
    }

    \$this->moveToRank(1, \$con);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addMoveToBottom(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Move the object to the bottom of the list
 *
 * @param ConnectionInterface \$con optional connection
 *
 * @return \$this The current object
 */
public function moveToBottom(?ConnectionInterface \$con = null)
{
    if (\$this->isLast(\$con)) {
        return \$this;
    }

    if (\$con === null) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->tableMapClassName}::DATABASE_NAME);
    }

    \$con->transaction(function () use (\$con) {
        \$bottom = {$this->queryClassName}::create()->getMaxRankArray(" . ($useScope ? '$this->getScopeValue(), ' : '') . "\$con);

        \$this->moveToRank(\$bottom, \$con);
    });

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addRemoveFromList(string &$script): void
    {
        $useScope = $this->behavior->useScope();
        $script .= "
/**
 * Removes the current object from the list" . ($useScope ? ' (moves it to the null scope)' : '') . ".
 * The modifications are not persisted until the object is saved.
 *
 * @return \$this The current object
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
    \$this->sortableQueries[] = [
        'callable'  => ['{$this->queryFullClassName}', 'sortableShiftRank'],
        'arguments' => [-1, \$this->{$this->getColumnGetter()}() + 1, null]
    ];
    // remove the object from the list
    \$this->{$this->getColumnSetter('rank_column')}(null);
    ";
        }
        $script .= "

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addProcessSortableQueries(string &$script): void
    {
        $script .= "
/**
 * Execute queries that were saved to be run inside the save transaction
 */
protected function processSortableQueries(\$con)
{
    foreach (\$this->sortableQueries as ['callable' => \$callable, 'arguments' => \$arguments]) {
        \$arguments[] = \$con;
        \$callable(...\$arguments);
    }
    \$this->sortableQueries = [];
}
";
    }
}
