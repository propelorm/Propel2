<?php

/*
 *	$Id: ConcreteInheritanceBehavior.php 1471 2010-01-20 14:31:12Z francois $
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
 * Gives a model class the ability to remain in database even when the user deletes object
 * Uses an additional column storing the deletion date
 * And an additional condition for every read query to only consider rows with no deletion date
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.behavior.concrete_inheritance
 */
class ConcreteInheritanceParentBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'descendant_column' => 'descendant_class'
	);
	
	public function modifyTable()
	{
		$table = $this->getTable();
		if (!$table->containsColumn($this->getParameter('descendant_column'))) {
			$table->addColumn(array(
				'name' => $this->getParameter('descendant_column'),
				'type' => 'VARCHAR',
				'size' => 100
			));
		}
	}
	
	protected function getColumnGetter()
	{
		return 'get' . $this->getColumnForParameter('descendant_column')->getPhpName();
	}
	
	public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script .= '';
		$this->addHasChildObject($script);
		$this->addGetChildObject($script);
		
		return $script;
	}
	
	protected function addHasChildObject(&$script)
	{
		$script .= "
/**
 * Whether or not this object is the parent of a child object
 *
 * @return    bool
 */
public function hasChildObject()
{
	return \$this->" . $this->getColumnGetter() . "() !== null;
}
";
	}

	protected function addGetChildObject(&$script)
	{
		$script .= "
/**
 * Get the child object of this object
 *
 * @return    mixed
 */
public function getChildObject()
{
	if (!\$this->hasChildObject()) {
		return null;
	}
	\$childObjectClass = \$this->" . $this->getColumnGetter() . "();
	\$childObject = PropelQuery::from(\$childObjectClass)->findPk(\$this->getPrimaryKey());
	return \$childObject->hasChildObject() ? \$childObject->getChildObject() : \$childObject;
}
";
	}

}