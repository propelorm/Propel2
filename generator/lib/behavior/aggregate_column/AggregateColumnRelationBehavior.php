<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'AggregateColumnRelationBehavior.php';

/**
 * Keeps an aggregate column updated with related table
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.behavior.aggregate_column
 */
class AggregateColumnRelationBehavior extends Behavior
{
	
	// default parameters value
	protected $parameters = array(
		'foreign_table' => '',
		'update_method' => '',
	);
	
	public function postSave($builder)
	{
		$relationName = $this->getRelationName($builder);
		return "\$this->updateRelated{$relationName}(\$con);";
	}
	
	// no need for a postDelete() hook, since delete() uses Query::delete(),
	// which already has a hook
	
	public function objectAttributes($builder)
	{
		$relationName = $this->getRelationName($builder);
		return "protected \$old{$relationName};
";
	}
	
	public function objectMethods($builder)
	{
		$relationName = $this->getRelationName($builder);
		$variableName = self::lcfirst($relationName);
		$updateMethodName = $this->getParameter('update_method');
		return "
protected function updateRelated{$relationName}(PropelPDO \$con)
{
	if (\${$variableName} = \$this->get{$relationName}()) {
		\${$variableName}->{$updateMethodName}(\$con);
	}
	if (\$this->old{$relationName}) {
		\$this->old{$relationName}->{$updateMethodName}(\$con);
		\$this->old{$relationName} = null;
	}
}
";
	}
	
	public function objectFilter(&$script, $builder)
	{
		$relationName = $this->getRelationName($builder);
		$relatedClass = $this->getForeignTable()->getPhpName();
		$search = "	public function set{$relationName}({$relatedClass} \$v = null)
	{";
		$replace = $search . "
		// aggregate_column_relation behavior
		if (null !== \$this->a{$relationName} && \$v !== \$this->a{$relationName}) {
			\$this->old{$relationName} = \$this->a{$relationName};
		}";
		$script = str_replace($search, $replace, $script);
	}
	
	public function preUpdateQuery($builder)
	{
		return $this->getFindRelated($builder);
	}
	
	public function preDeleteQuery($builder)
	{
		return $this->getFindRelated($builder);
	}

	protected function getFindRelated($builder)
	{
		$relationName = $this->getRelationName($builder);
		return "\$this->findRelated{$relationName}s(\$con);";
	}

	public function postUpdateQuery($builder)
	{
		return $this->getUpdateRelated($builder);
	}
	
	public function postDeleteQuery($builder)
	{
		return $this->getUpdateRelated($builder);
	}

	protected function getUpdateRelated($builder)
	{
		$relationName = $this->getRelationName($builder);
		return "\$this->updateRelated{$relationName}s(\$con);";
	}
	
	public function queryMethods($builder)
	{
		$foreignTable = $this->getForeignTable();
		$foreignKey = $this->getForeignKey();
		$relationName = $this->getRelationName($builder);
		$refRelationName = $builder->getRefFKPhpNameAffix($this->getForeignKey());
		$variableName = self::lcfirst($relationName);
		$foreignQueryName = $foreignKey->getForeignTable()->getPhpName() . 'Query';
		$updateMethodName = $this->getParameter('update_method');
		return "
/**
 * Finds the related {$foreignTable->getPhpName()} objects and keep them for later
 *
 * @param PropelPDO \$con A connection object
 */
protected function findRelated{$relationName}s(\$con)
{
	\$criteria = clone \$this;
	if (\$this->useAliasInSQL) {
		\$alias = \$this->getModelAlias();
		\$criteria->removeAlias(\$alias);
	} else {
		\$alias = '';
	}
	\$this->{$variableName}s = {$foreignQueryName}::create()
		->join{$refRelationName}(\$alias)
		->mergeWith(\$criteria)
		->find(\$con);
}

protected function updateRelated{$relationName}s(\$con)
{
	foreach (\$this->{$variableName}s as \${$variableName}) {
		\${$variableName}->{$updateMethodName}(\$con);
	}
	\$this->{$variableName}s = array();
}
";
	}

	protected function getForeignTable()
	{
		return $this->getTable()->getDatabase()->getTable($this->getParameter('foreign_table'));
	}

	protected function getForeignKey()
	{
		$foreignTable = $this->getForeignTable();
		// let's infer the relation from the foreign table
		$fks = $this->getTable()->getForeignKeysReferencingTable($foreignTable->getName());
		// FIXME doesn't work when more than one fk to the same table
		return array_shift($fks);
	}
	
	protected function getRelationName($builder)
	{
		return $builder->getFKPhpNameAffix($this->getForeignKey());
	}
	
	protected static function lcfirst($input)
	{
		// no lcfirst in php<5.3...
		$input[0] = strtolower($input[0]);
		return $input;
	}
}