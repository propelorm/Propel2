<?php

/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */
 
 
/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author     François Zaninotto
 * @author     heltem <heltem@o2php.com>
 * @package    propel.engine.behavior.nestedset
 */
class NestedSetBehaviorObjectBuilderModifier
{
	protected $behavior, $table;
	
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
	
	/*
	public function objectFilter(&$script, $builder)
	{
		$script = str_replace('implements Persistent', 'implements Persistent, NodeObject', $script);
	}
	*/
	
	public function objectAttributes($builder)
	{
		$objectClassName = $builder->getStubObjectBuilder()->getClassname();
		return "
/**
 * Store level of node
 * @var        int
 */
protected \$level = null;

/**
 * Store if node has prev sibling
 * @var        bool
 */
protected \$hasPrevSibling = null;

/**
 * Store node if has prev sibling
 * @var        $objectClassName
 */
protected \$prevSibling = null;

/**
 * Store if node has next sibling
 * @var        bool
 */
protected \$hasNextSibling = null;

/**
 * Store node if has next sibling
 * @var        $objectClassName
 */
protected \$nextSibling = null;

/**
 * Store if node has parent node
 * @var        bool
 */
protected \$hasParentNode = null;

/**
 * The parent node for this node.
 * @var        $objectClassName
 */
protected \$parentNode = null;

/**
 * Store children of the node
 * @var        array
 */
protected \$_children = null;

/**
 * Queries to be executed in the save transaction
 * @var        array
 */
protected \$nestedSetQueries = array();
";
	}
	
	public function preSave($builder)
	{
		$peerClassname = $builder->getStubPeerBuilder()->getClassname();
		return "
foreach (\$this->nestedSetQueries as \$query) {
	\$query['arguments'][]= \$con;
	call_user_func_array(array('$peerClassname', \$query['method']), \$query['arguments']);
}
\$this->nestedSetQueries = array();
";
	}
	
	public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		
		$this->addGetLeft($script);
		$this->addGetRight($script);
		if ($this->getParameter('use_scope') == 'true')
		{
			$this->addGetScope($script);
		}

		$this->addSetLeft($script);
		$this->addSetRight($script);
		if ($this->getParameter('use_scope') == 'true')
		{
			$this->addSetScope($script);
		}
		
		$this->addMakeRoot($script);
		
		$this->addSetParent($script);
		$this->addHasParent($script);
		$this->addGetParent($script);
		
		$this->addSetPrevSibling($script);
		$this->addHasPrevSibling($script);
		$this->addGetPrevSibling($script);
		
		$this->addSetNextSibling($script);
		$this->addHasNextSibling($script);
		$this->addGetNextSibling($script);
		
		$this->addInsertAsFirstChildOf($script);
		$this->addInsertAsLastChildOf($script);
		$this->addInsertAsPrevSiblingOf($script);
		$this->addInsertAsNextSiblingOf($script);
		
		$this->addCompatibilityProxies($script);
		
		return $script;
	}

	protected function addGetLeft(&$script)
	{
		$script .= "
/**
 * Wraps the getter for the left value
 *
 * @return     int
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
 * Wraps the getter for the right value
 *
 * @return     int
 */
public function getRightValue()
{
	return \$this->{$this->getColumnAttribute('right_column')};
}
";
	}

	protected function addGetScope(&$script)
	{
		$script .= "
/**
 * Wraps the getter for the scope value
 *
 * @return     int or null if scope is disabled
 */
public function getScopeValue()
{
	return \$this->{$this->getColumnAttribute('scope_column')};
}
";
	}

	protected function addSetLeft(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();

		$script .= "
/**
 * Set the value left column
 *
 * @param      int \$v new value
 * @return     $objectClassName The current object (for fluent API support)
 */
public function setLeftValue(\$v)
{
	return \$this->set{$this->getColumnPhpName('left_column')}(\$v);
}
";
	}

	protected function addSetRight(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();

		$script .= "
/**
 * Set the value of right column
 *
 * @param      int \$v new value
 * @return     $objectClassName The current object (for fluent API support)
 */
public function setRightValue(\$v)
{
	return \$this->set{$this->getColumnPhpName('right_column')}(\$v);
}
";
	}

	protected function addSetScope(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();

		$script .= "
/**
 * Set the value of scope column
 *
 * @param      int \$v new value
 * @return     $objectClassName The current object (for fluent API support)
 */
public function setScopeValue(\$v)
{
	return \$this->set{$this->getColumnPhpName('scope_column')}(\$v);
}
";
	}

	protected function addMakeRoot(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();
		
		$script .= "
/**
 * Creates the supplied node as the root node.
 *
 * @return     $objectClassName The current object (for fluent API support)
 * @throws     PropelException
 */
public function makeRoot()
{
	if (\$this->getLeftValue() || \$this->getRightValue()) {
		throw new PropelException('Cannot turn an existing node into a root node.');
	}

	\$this->setLeftValue(1);
	\$this->setRightValue(2);
	return \$this;
}
";
	}

	protected function addSetParent(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Sets the parentNode of the node in the tree
 *
 * @param      $objectClassName \$parent Propel node object
 * @return     $objectClassName The current object (for fluent API support)
 */
public function setParent($objectClassName \$parent = null)
{
	\$this->hasParentNode = $peerClassname::isValid(\$parent);
	\$this->parentNode = \$this->hasParentNode ? \$parent : null;
	return \$this;
}
";
	}

	protected function addHasParent(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Tests if object has an ancestor
 *
 * @param      PropelPDO \$con Connection to use.
 * @return     bool
 */
public function hasParent(PropelPDO \$con = null)
{
	if (null === \$this->hasParentNode) {
		if (!$peerClassname::isValid(\$this)) {
			return false;
		}
		\$this->getParent(\$con);
	}
	return \$this->hasParentNode;
}
";
	}

	protected function addGetParent(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Gets ancestor for the given node if it exists
 *
 * @param      PropelPDO \$con Connection to use.
 * @return     mixed 		Propel object if exists else false
 */
public function getParent(PropelPDO \$con = null)
{
	if (null === \$this->hasParentNode) {
		\$c = new Criteria($peerClassname::DATABASE_NAME);
		\$c1 = \$c->getNewCriterion($peerClassname::LEFT_COL, \$this->getLeftValue(), Criteria::LESS_THAN);
		\$c2 = \$c->getNewCriterion($peerClassname::RIGHT_COL, \$this->getRightValue(), Criteria::GREATER_THAN);
		\$c1->addAnd(\$c2);
		\$c->add(\$c1);";
		if ($this->behavior->useScope()) {
			$script .= "
		\$c->add($peerClassname::SCOPE_COL, \$this->getScopeValue(), Criteria::EQUAL);";
		}
		$script .= "		
		\$c->addAscendingOrderByColumn($peerClassname::RIGHT_COL);

		\$parent = $peerClassname::doSelectOne(\$c, \$con);

		\$this->setParent(\$parent);
	}
	return \$this->parentNode;
}
";
	}
	
	protected function addSetPrevSibling(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Sets the previous sibling of the node in the tree
 *
 * @param      $objectClassName \$node Propel node object
 * @return     $objectClassName The current object (for fluent API support)
 */
public function setPrevSibling($objectClassName \$node = null)
{
	\$this->hasPrevSibling = $peerClassname::isValid(\$node);
	\$this->prevSibling = \$this->hasPrevSibling ? \$node : null;
	return \$this;
}
";
	}

	protected function addHasPrevSibling(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Determines if the node has previous sibling
 *
 * @param      PropelPDO \$con Connection to use.
 * @return     bool
 */
public function hasPrevSibling(PropelPDO \$con = null)
{
	if (null === \$this->hasPrevSibling) {
		if (!$peerClassname::isValid(\$this)) {
			return false;
		}
		\$this->getPrevSibling(\$con);
	}
	return \$this->hasPrevSibling;
}
";
	}

	protected function addGetPrevSibling(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Gets previous sibling for the given node if it exists
 *
 * @param      PropelPDO \$con Connection to use.
 * @return     mixed 		Propel object if exists else false
 */
public function getPrevSibling(PropelPDO \$con = null)
{
	if (null === \$this->hasPrevSibling) {
		\$c = new Criteria($peerClassname::DATABASE_NAME);
		\$c->add($peerClassname::RIGHT_COL, \$this->getLeftValue() - 1, Criteria::EQUAL);";
		if ($this->behavior->useScope()) {
			$script .= "
		\$c->add($peerClassname::SCOPE_COL, \$this->getScopeValue(), Criteria::EQUAL);";
		}
		$script .= "		
		\$prevSibling = $peerClassname::doSelectOne(\$c, \$con);

		\$this->setPrevSibling(\$prevSibling);
	}
	return \$this->prevSibling;
}
";
	}

	protected function addSetNextSibling(&$script)
	{
		$objectClassName = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Sets the next sibling of the node in the tree
 *
 * @param      $objectClassName \$node Propel node object
 * @return     $objectClassName The current object (for fluent API support)
 */
public function setNextSibling($objectClassName \$node = null)
{
	\$this->hasNextSibling = $peerClassname::isValid(\$node);
	\$this->nextSibling = \$this->hasNextSibling ? \$node : null;
	return \$this;
}
";
	}

	protected function addHasNextSibling(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Determines if the node has next sibling
 *
 * @param      PropelPDO \$con Connection to use.
 * @return     bool
 */
public function hasNextSibling(PropelPDO \$con = null)
{
	if (null === \$this->hasNextSibling) {
		if (!$peerClassname::isValid(\$this)) {
			return false;
		}
		\$this->getNextSibling(\$con);
	}
	return \$this->hasNextSibling;
}
";
	}

	protected function addGetNextSibling(&$script)
	{
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Gets next sibling for the given node if it exists
 *
 * @param      PropelPDO \$con Connection to use.
 * @return     mixed 		Propel object if exists else false
 */
public function getNextSibling(PropelPDO \$con = null)
{
	if (null === \$this->hasNextSibling) {
		\$c = new Criteria($peerClassname::DATABASE_NAME);
		\$c->add($peerClassname::LEFT_COL, \$this->getRightValue() + 1, Criteria::EQUAL);";
		if ($this->behavior->useScope()) {
			$script .= "
		\$c->add($peerClassname::SCOPE_COL, \$this->getScopeValue(), Criteria::EQUAL);";
		}
		$script .= "		
		\$nextSibling = $peerClassname::doSelectOne(\$c, \$con);

		\$this->setNextSibling(\$nextSibling);
	}
	return \$this->nextSibling;
}
";
	}

	protected function addInsertAsFirstChildOf(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Inserts the current node as first child of given \$parent node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassname \$parent	Propel object for parent node
 *
 * @return     $objectClassname The current Propel object
 */
public function insertAsFirstChildOf($objectClassname \$parent)
{
	if (!\$this->isNew()) {
		throw new PropelException('A $objectClassname object must be new to be inserted in the tree. Use the moveToFirstChildOf() instead.');
	}
	\$left = \$parent->getLeftValue() + 1;
	// Update node properties
	\$this->setLeftValue(\$left);
	\$this->setRightValue(\$left + 1);";
		if ($useScope)
		{
			$script .= "
	\$scope = \$parent->getScopeValue();
	\$this->setScopeValue(\$scope);";
		}
		$script .= "
	\$this->setParent(\$parent);
	// Keep the tree modification query for the save() transaction
	\$this->nestedSetQueries []= array(
		'method'    => 'makeRoomForLeaf',
		'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ")
	);
	return \$this;
}
";
	}

	protected function addInsertAsLastChildOf(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Inserts the current node as last child of given \$parent node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassname \$parent	Propel object for parent node
 *
 * @return     $objectClassname The current Propel object
 */
public function insertAsLastChildOf($objectClassname \$parent)
{
	if (!\$this->isNew()) {
		throw new PropelException('A $objectClassname object must be new to be inserted in the tree. Use the moveToLastChildOf() instead.');
	}
	\$left = \$parent->getRightValue();
	// Update node properties
	\$this->setLeftValue(\$left);
	\$this->setRightValue(\$left + 1);";
		if ($useScope)
		{
			$script .= "
	\$scope = \$parent->getScopeValue();
	\$this->setScopeValue(\$scope);";
		}
		$script .= "
	\$this->setParent(\$parent);
	// Keep the tree modification query for the save() transaction
	\$this->nestedSetQueries []= array(
		'method'    => 'makeRoomForLeaf',
		'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ")
	);
	return \$this;
}
";
	}

	protected function addInsertAsPrevSiblingOf(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Inserts the current node as prev sibling given \$sibling node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassname \$sibling	Propel object for parent node
 *
 * @return     $objectClassname The current Propel object
 */
public function insertAsPrevSiblingOf($objectClassname \$sibling)
{
	if (!\$this->isNew()) {
		throw new PropelException('A $objectClassname object must be new to be inserted in the tree. Use the moveToPrevSiblingOf() instead.');
	}
	\$left = \$sibling->getLeftValue();
	// Update node properties
	\$this->setLeftValue(\$left);
	\$this->setRightValue(\$left + 1);";
		if ($useScope)
		{
			$script .= "
	\$scope = \$sibling->getScopeValue();
	\$this->setScopeValue(\$scope);";
		}
		$script .= "
	\$this->setNextSibling(\$sibling);
	\$sibling->setPrevSibling(\$this);
	// Keep the tree modification query for the save() transaction
	\$this->nestedSetQueries []= array(
		'method'    => 'makeRoomForLeaf',
		'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ")
	);
	return \$this;
}
";
	}

	protected function addInsertAsNextSiblingOf(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$useScope = $this->behavior->useScope();
		$script .= "
/**
 * Inserts the current node as next sibling given \$sibling node
 * The modifications in the current object and the tree
 * are not persisted until the current object is saved.
 *
 * @param      $objectClassname \$sibling	Propel object for parent node
 *
 * @return     $objectClassname The current Propel object
 */
public function insertAsNextSiblingOf($objectClassname \$sibling)
{
	if (!\$this->isNew()) {
		throw new PropelException('A $objectClassname object must be new to be inserted in the tree. Use the moveToNextSiblingOf() instead.');
	}
	\$left = \$sibling->getRightValue() + 1;
	// Update node properties
	\$this->setLeftValue(\$left);
	\$this->setRightValue(\$left + 1);";
		if ($useScope)
		{
			$script .= "
	\$scope = \$sibling->getScopeValue();
	\$this->setScopeValue(\$scope);";
		}
		$script .= "
	\$this->setPrevSibling(\$sibling);
	\$sibling->setNextSibling(\$this);
	// Keep the tree modification query for the save() transaction
	\$this->nestedSetQueries []= array(
		'method'    => 'makeRoomForLeaf',
		'arguments' => array(\$left" . ($useScope ? ", \$scope" : "") . ")
	);
	return \$this;
}
";
	}
	
	protected function addCompatibilityProxies(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$script .= "
/**
 * Alias for makeRoot(), for BC with Propel 1.4 nested sets
 *
 * @deprecated since 1.5
 * @see        makeRoot
 */
public function createRoot()
{
	return \$this->makeRoot();
}

/**
 * Alias for getParent(), for BC with Propel 1.4 nested sets
 *
 * @deprecated since 1.5
 * @see        getParent
 */
public function retrieveParent(PropelPDO \$con = null)
{
	return \$this->getParent(\$con);
}

/**
 * Alias for setParent(), for BC with Propel 1.4 nested sets
 *
 * @deprecated since 1.5
 * @see        setParent
 */
public function setParentNode($objectClassname \$parent = null)
{
	return \$this->setParent(\$parent);
}

/**
 * Alias for getPrevSibling(), for BC with Propel 1.4 nested sets
 *
 * @deprecated since 1.5
 * @see        getParent
 */
public function retrievePrevSibling(PropelPDO \$con = null)
{
	return \$this->getPrevSibling(\$con);
}

/**
 * Alias for getNextSibling(), for BC with Propel 1.4 nested sets
 *
 * @deprecated since 1.5
 * @see        getParent
 */
public function retrieveNextSibling(PropelPDO \$con = null)
{
	return \$this->getNextSibling(\$con);
}
";
	}
}