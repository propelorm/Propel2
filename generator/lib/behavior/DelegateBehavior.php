<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Gives a model class the ability to delegate methods to a relationship.
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior
 */
class DelegateBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'to' => ''
	);
	
	protected $delegates = array();

	/**
	 * Lists the delegates and checks that the behavior can use them,
	 * And adds a fk from the delegate to the main table if not already set
	 */
	public function modifyTable()
	{
		$table = $this->getTable();
		$database = $table->getDatabase();
		$delegates = explode(',', $this->parameters['to']);
		foreach ($delegates as $delegate) {
			$delegate = trim($delegate);
			if (!$database->hasTable($delegate)) {
				throw new InvalidArgumentException(sprintf(
					'No delegate table "%s" found for table "%s"',
					$delegate,
					$table->getName()
				));
			}
			$this->relateDelegateToMainTable($this->getDelegateTable($delegate), $table);
			$this->delegates []= $delegate;
		}
	}

	protected function relateDelegateToMainTable($delegateTable, $mainTable)
	{
		if (in_array($mainTable->getName(), $delegateTable->getForeignTableNames())) {
			// FIXME: check that it's a one-to-one relationship
			return;
		}
		$pks = $mainTable->getPrimaryKey();
		foreach ($pks as $column) {
			$mainColumnName = $column->getName();
			if (!$delegateTable->hasColumn($mainColumnName)) {
				$column = clone $column;
				$column->setAutoIncrement(false);
				$delegateTable->addColumn($column);
			}
		}
		// Add a one-to-one fk
		$fk = new ForeignKey();
		$fk->setForeignTableCommonName($mainTable->getCommonName());
		$fk->setForeignSchemaName($mainTable->getSchema());
		$fk->setDefaultJoin('LEFT JOIN');
		$fk->setOnDelete(ForeignKey::CASCADE);
		$fk->setOnUpdate(ForeignKey::NONE);
		foreach ($pks as $column) {
			$fk->addReference($column->getName(), $column->getName());
		}
		$delegateTable->addForeignKey($fk);
	}
	
	protected function getDelegateTable($delegateTableName)
	{
		return $this->getTable()->getDatabase()->getTable($delegateTableName);
	}
	
	public function objectCall($builder)
	{
		$script = '';
		foreach ($this->delegates as $delegate) {
			$delegateTable = $this->getDelegateTable($delegate);
			foreach ($delegateTable->getForeignKeys() as $fk) {
				if ($fk->getForeignTableName() == $this->getTable()->getName()) {
					$ARClassName = $builder->getNewStubObjectBuilder($fk->getTable())->getClassname();
					$relationName = $builder->getRefFKPhpNameAffix($fk, $plural = false);
					$script .= "
if (method_exists('$ARClassName', \$name)) {
	if (!\$delegate = \$this->get$relationName()) {
		\$delegate = new $ARClassName();
		\$this->set$relationName(\$delegate);
	}
	return call_user_func_array(array(\$delegate, \$name), \$params);
}";
				}
			}
		}
		return $script;
	}

}