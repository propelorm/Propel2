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
		
		$this->addInsertAsFirstChildOf($script);
		
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
	
	protected function addInsertAsFirstChildOf(&$script)
	{
		$objectClassname = $this->builder->getStubObjectBuilder()->getClassname();
		$peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Inserts the current node as first child of given \$parent node
 *
 * @param      $objectClassname \$parent	Propel object for parent node
 * @param      PropelPDO \$con	Connection to use.
 * @return     void
 */
public function insertAsFirstChildOf($objectClassname \$parent, PropelPDO \$con = null)
{
	if (!\$this->isNew())
	{
		throw new PropelException('A $objectClassname object must be new to be inserted in the tree. Use moveToFirstChildOf() instead.');
	}
	
	// Update node properties
	\$this->setLeftValue(\$parent->getLeftValue() + 1);
	\$this->setRightValue(\$parent->getLeftValue() + 2);
	\$this->setParent(\$parent);";
		if ($this->behavior->useScope())
		{
			$script .= "
	\$sidv = \$parent->getScopeValue();
	\$this->setScopeValue(\$sidv);
	
	// Update database nodes
	$peerClassname::shiftRLValues(\$this->getLeftValue(), 2, \$con, \$sidv);";
		} else {
			$script .= "
	
	// Update database nodes
	$peerClassname::shiftRLValues(\$this->getLeftValue(), 2, \$con);";
		}
		
		$script .="

	// Update all loaded nodes
	$peerClassname::updateLoadedNodes(\$con);
	
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
";
	}
}