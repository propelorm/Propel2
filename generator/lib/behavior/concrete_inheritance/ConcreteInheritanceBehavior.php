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

require_once 'ConcreteInheritanceParentBehavior.php';

/**
 * Gives a model class the ability to remain in database even when the user deletes object
 * Uses an additional column storing the deletion date
 * And an additional condition for every read query to only consider rows with no deletion date
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.behavior.concrete_inheritance
 */
class ConcreteInheritanceBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'extends'             => '',
		'descendant_column'   => 'descendant_class',
		'copy_data_to_parent' => 'true'
	);
	
	public function modifyTable()
	{
		$table = $this->getTable();
		$parentTable = $this->getParentTable();

		if ($this->isCopyData()) {
			// tell the parent table that it has a descendant
			if (!$parentTable->hasBehavior('concrete_inheritance_parent')) {
				$parentBehavior = new ConcreteInheritanceParentBehavior();
				$parentBehavior->setName('concrete_inheritance_parent');
				$parentBehavior->addParameter(array('name' => 'descendant_column', 'value' => $this->getParameter('descendant_column')));
				$parentTable->addBehavior($parentBehavior);
				// The parent table's behavior modifyTable() must be executed before this one
				$parentBehavior->getTableModifier()->modifyTable();
				$parentBehavior->setTableModified(true);
			}
		}

		// Add the columns of the parent table
		foreach ($parentTable->getColumns() as $column) {
			if ($column->getName() == $this->getParameter('descendant_column')) {
				continue;
			}
			if ($table->containsColumn($column->getName())) {
				continue;
			}
			$copiedColumn = clone $column;
			if ($column->isAutoIncrement() && $this->isCopyData()) {
				$copiedColumn->setAutoIncrement(false);
			}
			$table->addColumn($copiedColumn);
			if ($column->isPrimaryKey() && $this->isCopyData()) {
				$fk = new ForeignKey();
				$fk->setForeignTableName($column->getTable()->getName());
				$fk->setOnDelete('CASCADE');
				$fk->setOnUpdate(null);
				$fk->addReference($copiedColumn, $column);
				$table->addForeignKey($fk);
			}
		}
		
		// add the foreign keys of the parent table
		foreach ($parentTable->getForeignKeys() as $fk) {
			$copiedFk = clone $fk;
			$copiedFk->setName('');
			$this->getTable()->addForeignKey($copiedFk);
		}
		
		// add the validators of the parent table
		foreach ($parentTable->getValidators() as $validator) {
			$copiedValidator = clone $validator;
			$this->getTable()->addValidator($copiedValidator);
		}

		// add the indices of the parent table
		foreach ($parentTable->getIndices() as $index) {
			$copiedIndex = clone $index;
			$copiedIndex->setName('');
			$this->getTable()->addIndex($copiedIndex);
		}

		// add the unique indices of the parent table
		foreach ($parentTable->getUnices() as $unique) {
			$copiedUnique = clone $unique;
			$copiedUnique->setName('');
			$this->getTable()->addUnique($copiedUnique);
		}
		
		// give name to newly added foreign keys and indices 
		// (this is already done for other elements of the current table)
		$table->doNaming(); 

		// add the Behaviors of the parent table
		foreach ($parentTable->getBehaviors() as $behavior) {
			if ($behavior->getName() == 'concrete_inheritance_parent' || $behavior->getName() == 'concrete_inheritance') {
				continue;
			}
			$copiedBehavior = clone $behavior;
			$this->getTable()->addBehavior($copiedBehavior);
		}

	}
	
	protected function getParentTable()
	{
		return $this->getTable()->getDatabase()->getTable($this->getParameter('extends'));
	}
	
	protected function isCopyData()
	{
		return $this->getParameter('copy_data_to_parent') == 'true';
	}
	
	public function parentClass($builder)
	{
		switch (get_class($builder)) {
			case 'PHP5ObjectBuilder':
				return $builder->getNewStubObjectBuilder($this->getParentTable())->getClassname();
				break;
			case 'QueryBuilder':
				return $builder->getNewStubQueryBuilder($this->getParentTable())->getClassname();
				break;
			default:
				return null;
				break;
		}
	}
	
	public function preSave($script)
	{
		if ($this->isCopyData()) {
			return "\$parent = \$this->getSyncParent(\$con);
\$parent->save(\$con);
\$this->setPrimaryKey(\$parent->getPrimaryKey());
";
		}
	}

	public function postDelete($script)
	{
		if ($this->isCopyData()) {
			return "\$this->getParentOrCreate(\$con)->delete(\$con);
";
		}
	}
	
	public function objectMethods($builder)
	{
		if (!$this->isCopyData()) {
			return;
		}
		$this->builder = $builder;
		$script .= '';
		$this->addObjectGetParentOrCreate($script);
		$this->addObjectGetSyncParent($script);
		
		return $script;
	}

	protected function addObjectGetParentOrCreate(&$script)
	{
		$parentTable = $this->getParentTable();
		$parentClass = $this->builder->getNewStubObjectBuilder($parentTable)->getClassname();
		$script .= "
/**
 * Get or Create the parent " . $parentClass . " object of the current object
 * 
 * @return    " . $parentClass . " The parent object
 */
public function getParentOrCreate(\$con = null)
{
	if (\$this->isNew()) {
		\$parent = new " . $parentClass . "();
		\$parent->set" . $this->getParentTable()->getColumn($this->getParameter('descendant_column'))->getPhpName() . "('" . $this->builder->getStubObjectBuilder()->getClassname() . "');
		return \$parent;
	} else {
		return " . $this->builder->getNewStubQueryBuilder($parentTable)->getClassname() . "::create()->findPk(\$this->getPrimaryKey(), \$con);
	}
}
";
	}
	
	protected function addObjectGetSyncParent(&$script)
	{
		$parentTable = $this->getParentTable();
		$pkeys = $parentTable->getPrimaryKey();
		$cptype = $pkeys[0]->getPhpType();
		$script .= "
/**
 * Create or Update the parent " . $parentTable->getPhpName() . " object
 * And return its primary key
 *
 * @return    " . $cptype . " The primary key of the parent object
 */
public function getSyncParent(\$con = null)
{
	\$parent = \$this->getParentOrCreate(\$con);";
		foreach ($parentTable->getColumns() as $column) {
			if ($column->isPrimaryKey() || $column->getName() == $this->getParameter('descendant_column')) {
				continue;
			}
			$phpName = $column->getPhpName();
			$script .= "
	\$parent->set{$phpName}(\$this->get{$phpName}());";
		}
		foreach ($parentTable->getForeignKeys() as $fk) {
			$refPhpName = $this->builder->getFKPhpNameAffix($fk, $plural = false);
			$script .= "
	if (\$this->get" . $refPhpName . "() && \$this->get" . $refPhpName . "()->isNew()) {
		\$parent->set" . $refPhpName . "(\$this->get" . $refPhpName . "());
	}";
		}
		$script .= "
		
	return \$parent;
}
";
	}

}