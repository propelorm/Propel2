<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */
 
/**
 * Behavior to add versionable columns and abilities
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior.versionable
 */
class VersionableBehaviorObjectBuilderModifier
{
	protected $behavior, $table, $builder, $objectClassname, $peerClassname;
	
	public function __construct($behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}
	
	protected function getParameter($key)
	{
		return $this->behavior->getParameter($key);
	}
	
	protected function getColumnAttribute($name = 'version_column')
	{
		return strtolower($this->behavior->getColumnForParameter($name)->getName());
	}

	protected function getColumnPhpName($name = 'version_column')
	{
		return $this->behavior->getColumnForParameter($name)->getPhpName();
	}
	
	protected function setBuilder($builder)
	{
		$this->builder = $builder;
		$this->objectClassname = $builder->getStubObjectBuilder()->getClassname();
		$this->queryClassname = $builder->getStubQueryBuilder()->getClassname();
		$this->peerClassname = $builder->getStubPeerBuilder()->getClassname();
	}
	
	/**
	 * Get the getter of the column of the behavior
	 *
	 * @return string The related getter, e.g. 'getVersion'
	 */
	protected function getColumnGetter($name = 'version_column')
	{
		return 'get' . $this->getColumnPhpName($name);
	}

	/**
	 * Get the setter of the column of the behavior
	 *
	 * @return string The related setter, e.g. 'setVersion'
	 */
	protected function getColumnSetter($name = 'version_column')
	{
		return 'set' . $this->getColumnPhpName($name);
	}
	

	public function preInsert($builder)
	{
		return "\$this->{$this->getColumnAttribute()} = 1;
\$this->wasModified = true;";
	}
	
	public function preUpdate($builder)
	{
		return "if (\$this->isModified()) {
	\$this->{$this->getColumnAttribute()} += 1;
	\$this->wasModified = true;
}";
	}
	
	public function postSave($builder)
	{
		$versionTablePhpName = $this->behavior->getVersionTablePhpName();
		$script = "if (\$this->wasModified) {
			\$version = new {$versionTablePhpName}();
			\$this->copyInto(\$version);";
		foreach ($this->table->getPrimaryKey() as $col) {
			if ($col->isAutoIncrement()) {
				$phpName = $col->getPhpName();
				$script .= "
			\$version->set{$phpName}(\$this->get{$phpName}());";
			}
		}
		$script .= "
			\$version->save(\$con);
}
\$this->wasModified = false;";
		return $script;
	}

	public function postDelete($builder)
	{
		if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->getBuildProperty('emulateForeignKeyConstraints')) {
			$script = "// emulate delete cascade
{$this->behavior->getVersionTablePhpName()}Query::create()
	->filterBy{$this->table->getPhpName()}(\$this)
	->delete(\$con);";
			return $script;
		}
	}
	
	public function objectAttributes($builder)
	{
		return "

/**
 * Whether the object was modified. Useful for the postSave() hooks.
 * @var        boolean
 */
protected \$wasModified = false;

";
	}
	
	public function objectMethods($builder)
	{
		$this->setBuilder($builder);
		$script = '';
		if ($this->getParameter('version_column') != 'version') {
			$this->addVersionSetter($script);
			$this->addVersionGetter($script);
		}
		
		return $script;
	}

	protected function addVersionSetter(&$script)
	{
		$script .= "
/**
 * Wrap the setter for version value
 *
 * @param   string
 * @return  " . $this->table->getPhpName() . "
 */
public function setVersion(\$v)
{
	return \$this->" . $this->getColumnSetter() . "(\$v);
}
";
	}

	protected function addVersionGetter(&$script)
	{
		$script .= "
/**
 * Wrap the getter for version value
 *
 * @return  string 
 */
public function getVersion()
{
	return \$this->" . $this->getColumnGetter() . "();
}
";
	}
}