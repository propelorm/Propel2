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
	
	protected function getVersionQueryClassName()
	{
		return $this->builder->getNewStubQueryBuilder($this->behavior->getVersionTable())->getClassname();
	}
	
	protected function getActiveRecordClassName()
	{
		return $this->builder->getStubObjectBuilder()->getClassname();
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
	\$this->set{$this->getColumnPhpName()}(\$this->getLastVersionNumber(\$con) + 1);
	\$this->wasModified = true;
}";
	}
	
	public function postSave($builder)
	{
		$versionTablePhpName = $this->builder->getNewStubObjectBuilder($this->behavior->getVersionTable())->getClassname();
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
		$this->builder = $builder;
		if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->getBuildProperty('emulateForeignKeyConstraints')) {
			$script = "// emulate delete cascade
{$this->getVersionQueryClassName()}::create()
	->filterBy{$this->getActiveRecordClassName()}(\$this)
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
		$this->addToVersion($script);
		$this->addGetLastVersionNumber($script);
		$this->addIsLastVersion($script);
		
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

	protected function addToVersion(&$script)
	{
		$ARclassName = $this->getActiveRecordClassName();
		$script .= "
/**
 * Sets the properties of the curent object to the value they had at a specific version
 *
 * @param   integer \$version The version number to read
 * @param   PropelPDO \$con the connection to use
 *
 * @return  {$ARclassName} The current object (for fluent API support)
 */
public function toVersion(\$version, \$con = null)
{
	\$v = {$this->getVersionQueryClassName()}::create()
		->filterBy{$ARclassName}(\$this)
		->filterBy{$this->getColumnPhpName()}(\$version)
		->findOne(\$con);
	if (!\$v) {
		throw new PropelException(sprintf('No {$ARclassName} object found with version %d', \$version));
	}
	\$v->copyInto(\$this, false, false);
	return \$this;
}
";
	}
	
	protected function addGetLastVersionNumber(&$script)
	{
		$script .= "
/**
 * Gets the latest persisted version number for the current object
 *
 * @param   PropelPDO \$con the connection to use
 *
 * @return  integer
 */
public function getLastVersionNumber(\$con = null)
{
	\$v = {$this->getVersionQueryClassName()}::create()
		->filterBy{$this->getActiveRecordClassName()}(\$this)
		->orderBy{$this->getColumnPhpName()}('desc')
		->findOne(\$con);
	if (!\$v) {
		return 0;
	}
	return \$v->get{$this->getColumnPhpName()}();
}
";
	}

	protected function addIsLastVersion(&$script)
	{
		$script .= "
/**
 * Checks whether the current object is the latest one
 *
 * @param   PropelPDO \$con the connection to use
 *
 * @return  Boolean
 */
public function isLastVersion(\$con = null)
{
	return \$this->getLastVersionNumber(\$con) == \$this->getVersion();
}
";
	}

}