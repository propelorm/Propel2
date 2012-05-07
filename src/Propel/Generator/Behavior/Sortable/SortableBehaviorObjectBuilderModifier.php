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
 * Behavior to add sortable columns and abilities
 *
 * @author     François Zaninotto
 * @author     heltem <heltem@o2php.com>
 */
class SortableBehaviorObjectBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

    protected $peerClassName;

    protected $peerFullClassName;

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

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
        $this->peerClassName = $builder->getPeerClassName();
        $this->peerFullClassName = $builder->getStubPeerBuilder()->getFullyQualifiedClassName();
    }

    /**
     * Get the getter of the column of the behavior
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

        return "if (!\$this->isColumnModified({$this->peerClassName}::RANK_COL)) {
    \$this->{$this->getColumnSetter()}({$this->queryClassName}::create()->getMaxRank(" . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con) + 1);
}
";
    }

    public function preDelete($builder)
    {
        $useScope = $this->behavior->useScope();
        $this->setBuilder($builder);

        return "
{$this->peerClassName}::shiftRank(-1, \$this->{$this->getColumnGetter()}() + 1, null, " . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);
{$this->peerClassName}::clearInstancePool();
";
    }

    public function objectAttributes($builder)
    {
        return "
/**
 * Queries to be executed in the save transaction
 * @var        array
 */
protected \$sortableQueries = array();
";
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
 * @return    {$this->objectClassName}
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
 * @return    int
 */
public function getScopeValue()
{
    return \$this->{$this->getColumnAttribute('scope_column')};
}

/**
 * Wrap the setter for scope value
 *
 * @param     int
 * @return    {$this->objectClassName}
 */
public function setScopeValue(\$v)
{
    return \$this->{$this->getColumnSetter('scope_column')}(\$v);
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
    return \$this->{$this->getColumnGetter()}() == {$this->queryClassName}::create()->getMaxRank(" . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);
}
";
    }

    protected function addGetNext(&$script)
    {
        $useScope = $this->behavior->useScope();
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
        if ('rank' === $this->behavior->getParameter('rank_column') && $useScope) {
            $script .= "

    return {$this->queryClassName}::create()
        ->filterByRank(\$this->{$this->getColumnGetter()}() + 1)
        ->inList(\$this->{$this->getColumnGetter('scope_column')}())
        ->findOne(\$con);";
        } else {
            $script .= "

    return {$this->queryClassName}::create()->findOneByRank(\$this->{$this->getColumnGetter()}() + 1, " . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);";
        }

        $script .= "
}
";
    }

    protected function addGetPrevious(&$script)
    {
        $useScope = $this->behavior->useScope();
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
        if ('rank' === $this->behavior->getParameter('rank_column') && $useScope) {
            $script .= "

    return {$this->queryClassName}::create()
        ->filterByRank(\$this->{$this->getColumnGetter()}() - 1)
        ->inList(\$this->{$this->getColumnGetter('scope_column')}())
        ->findOne(\$con);";
        } else {
            $script .= "

    return {$this->queryClassName}::create()->findOneByRank(\$this->{$this->getColumnGetter()}() - 1, " . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);";
        }
        $script .= "
}
";
    }

    protected function addInsertAtRank(&$script)
    {
        $useScope = $this->behavior->useScope();
        $peerClassName = $this->peerFullClassName;
        $script .= "
/**
 * Insert at specified rank
 * The modifications are not persisted until the object is saved.
 *
 * @param     integer    \$rank rank value
 * @param     ConnectionInterface  \$con      optional connection
 *
 * @return    {$this->objectClassName} the current object
 *
 * @throws    PropelException
 */
public function insertAtRank(\$rank, ConnectionInterface \$con = null)
{";
        if ($useScope) {
            $script .= "
    if (null === \$this->{$this->getColumnGetter('scope_column')}()) {
        throw new PropelException('The scope must be defined before inserting an object in a suite');
    }";
        }
        $script .= "
    \$maxRank = {$this->queryClassName}::create()->getMaxRank(" . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);
    if (\$rank < 1 || \$rank > \$maxRank + 1) {
        throw new PropelException('Invalid rank ' . \$rank);
    }
    // move the object in the list, at the given rank
    \$this->{$this->getColumnSetter()}(\$rank);
    if (\$rank != \$maxRank + 1) {
        // Keep the list modification query for the save() transaction
        \$this->sortableQueries []= array(
            'callable'  => array('$peerClassName', 'shiftRank'),
            'arguments' => array(1, \$rank, null, " . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}()" : '') . ")
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
 * @return    {$this->objectClassName} the current object
 *
 * @throws    PropelException
 */
public function insertAtBottom(ConnectionInterface \$con = null)
{";
        if ($useScope) {
            $script .= "
    if (null === \$this->{$this->getColumnGetter('scope_column')}()) {
        throw new PropelException('The scope must be defined before inserting an object in a suite');
    }";
        }
        $script .= "
    \$this->{$this->getColumnSetter()}({$this->queryClassName}::create()->getMaxRank(" . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con) + 1);

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
 * @return    {$this->objectClassName} the current object
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
        $peerClassName = $this->peerClassName;
        $script .= "
/**
 * Move the object to a new rank, and shifts the rank
 * Of the objects inbetween the old and new rank accordingly
 *
 * @param     integer   \$newRank rank value
 * @param     ConnectionInterface \$con optional connection
 *
 * @return    {$this->objectClassName} the current object
 *
 * @throws    PropelException
 */
public function moveToRank(\$newRank, ConnectionInterface \$con = null)
{
    if (\$this->isNew()) {
        throw new PropelException('New objects cannot be moved. Please use insertAtRank() instead');
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection($peerClassName::DATABASE_NAME);
    }
    if (\$newRank < 1 || \$newRank > {$this->queryClassName}::create()->getMaxRank(" . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con)) {
        throw new PropelException('Invalid rank ' . \$newRank);
    }

    \$oldRank = \$this->{$this->getColumnGetter()}();
    if (\$oldRank == \$newRank) {
        return \$this;
    }

    \$con->beginTransaction();
    try {
        // shift the objects between the old and the new rank
        \$delta = (\$oldRank < \$newRank) ? -1 : 1;
        $peerClassName::shiftRank(\$delta, min(\$oldRank, \$newRank), max(\$oldRank, \$newRank), " . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);

        // move the object to its new rank
        \$this->{$this->getColumnSetter()}(\$newRank);
        \$this->save(\$con);

        \$con->commit();

        return \$this;
    } catch (Exception \$e) {
        \$con->rollback();
        throw \$e;
    }
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
 * @return    {$this->objectClassName} the current object
 *
 * @throws Exception if the database cannot execute the two updates
 */
public function swapWith(\$object, ConnectionInterface \$con = null)
{
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->peerClassName}::DATABASE_NAME);
    }
    \$con->beginTransaction();
    try {
        \$oldRank = \$this->{$this->getColumnGetter()}();
        \$newRank = \$object->{$this->getColumnGetter()}();
        \$this->{$this->getColumnSetter()}(\$newRank);
        \$this->save(\$con);
        \$object->{$this->getColumnSetter()}(\$oldRank);
        \$object->save(\$con);
        \$con->commit();

        return \$this;
    } catch (Exception \$e) {
        \$con->rollback();
        throw \$e;
    }
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
 * @return    {$this->objectClassName} the current object
 */
public function moveUp(ConnectionInterface \$con = null)
{
    if (\$this->isFirst()) {
        return \$this;
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->peerClassName}::DATABASE_NAME);
    }
    \$con->beginTransaction();
    try {
        \$prev = \$this->getPrevious(\$con);
        \$this->swapWith(\$prev, \$con);
        \$con->commit();

        return \$this;
    } catch (Exception \$e) {
        \$con->rollback();
        throw \$e;
    }
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
 * @return    {$this->objectClassName} the current object
 */
public function moveDown(ConnectionInterface \$con = null)
{
    if (\$this->isLast(\$con)) {
        return \$this;
    }
    if (null === \$con) {
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->peerClassName}::DATABASE_NAME);
    }
    \$con->beginTransaction();
    try {
        \$next = \$this->getNext(\$con);
        \$this->swapWith(\$next, \$con);
        \$con->commit();

        return \$this;
    } catch (Exception \$e) {
        \$con->rollback();
        throw \$e;
    }
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
 * @return    {$this->objectClassName} the current object
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
        \$con = Propel::getServiceContainer()->getWriteConnection({$this->peerClassName}::DATABASE_NAME);
    }
    \$con->beginTransaction();
    try {
        \$bottom = {$this->queryClassName}::create()->getMaxRank(" . ($useScope ? "\$this->{$this->getColumnGetter('scope_column')}(), " : '') . "\$con);
        \$res = \$this->moveToRank(\$bottom, \$con);
        \$con->commit();

        return \$res;
    } catch (Exception \$e) {
        \$con->rollback();
        throw \$e;
    }
}
";
    }

    protected function addRemoveFromList(&$script)
    {
        $useScope = $this->behavior->useScope();
        $peerClassName = $this->peerFullClassName;
        $script .= "
/**
 * Removes the current object from the list.
 * The modifications are not persisted until the object is saved.
 *
 * @return    {$this->objectClassName} the current object
 */
public function removeFromList()
{
    // Keep the list modification query for the save() transaction
    \$this->sortableQueries []= array(
        'callable'  => array('$peerClassName', 'shiftRank'),
        'arguments' => array(-1, \$this->{$this->getColumnGetter()}() + 1, null" . ($useScope ? ", \$this->{$this->getColumnGetter('scope_column')}()" : '') . ")
    );
    // remove the object from the list
    \$this->{$this->getColumnSetter('rank_column')}(null);";
        if ($useScope) {
            $script .= "
    \$this->{$this->getColumnSetter('scope_column')}(null);";
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