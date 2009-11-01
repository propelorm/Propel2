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
 * @package    propel.engine.behavior.nestedSet
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
	\$this->{$this->getColumnAttribute('left_column')} = \$v;
	return \$this;
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
	\$this->{$this->getColumnAttribute('right_column')} = \$v;
	return \$this;
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
	\$this->{$this->getColumnAttribute('scope_column')} = \$v;
	return \$this;
}
";
	}

}