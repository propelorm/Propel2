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
	
	public function preSave($builder)
	{
		$script = "if (\$this->isVersioningNecessary()) {
	\$this->set{$this->getColumnPhpName()}(\$this->isNew() ? 1 : \$this->getLastVersionNumber(\$con) + 1);";
		if ($this->behavior->getParameter('log_created_at') == 'true') {
			$col = $this->behavior->getTable()->getColumn($this->getParameter('version_created_at_column'));
			$script .= "
	if (!\$this->isColumnModified({$this->builder->getColumnConstant($col)})) {
		\$this->{$this->getColumnSetter('version_created_at_column')}(time());
	}";
		}
		$script .= "
	\$createVersion = true; // for postSave hook
}";
		return $script;
	}

	public function postSave($builder)
	{
		return "if (isset(\$createVersion)) {
	\$this->addVersion(\$con);
}";
	}

	public function postDelete($builder)
	{
		$this->builder = $builder;
		if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->getBuildProperty('emulateForeignKeyConstraints')) {
			$script = "// emulate delete cascade
{$this->getVersionQueryClassName()}::create()
	->filterBy{$this->table->getPhpName()}(\$this)
	->delete(\$con);";
			return $script;
		}
	}
	
	public function objectMethods($builder)
	{
		$this->setBuilder($builder);
		$script = '';
		if ($this->getParameter('version_column') != 'version') {
			$this->addVersionSetter($script);
			$this->addVersionGetter($script);
		}
		$this->addIsVersioningNecessary($script);
		$this->addAddVersion($script);
		$this->addToVersion($script);
		$this->addPopulateFromVersion($script);
		$this->addGetLastVersionNumber($script);
		$this->addIsLastVersion($script);
		$this->addGetOneVersion($script);
		$this->addGetAllVersions($script);
		$this->addCompareVersions($script);
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

	protected function addIsVersioningNecessary(&$script)
	{
		$peerClass = $this->builder->getStubPeerBuilder()->getClassname();
		$script .= "
/**
 * Checks whether the current state must be recorded as a version
 *
 * @return  boolean
 */
public function isVersioningNecessary(\$con = null)
{
	if (\$this->alreadyInSave) {
		return false;
	}
	if ({$peerClass}::isVersioningEnabled() && (\$this->isNew() || \$this->isModified())) {
		return true;
	}";
		foreach ($this->behavior->getVersionableFks() as $fk) {
			$fkGetter = $this->builder->getFKPhpNameAffix($fk, $plural = false);
			$script .= "
	if (\$this->get{$fkGetter}(\$con)->isVersioningNecessary(\$con)) {
		return true;
	}
";
		}
		foreach ($this->behavior->getVersionableReferrers() as $fk) {
			$fkGetter = $this->builder->getRefFKPhpNameAffix($fk, $plural = true);
			$script .= "
	foreach (\$this->get{$fkGetter}(null, \$con) as \$relatedObject) {
		if (\$relatedObject->isVersioningNecessary(\$con)) {
			return true;
		}
	}
";
		}
		$script .= "
	return false;
}
";
	}

	protected function addAddVersion(&$script)
	{
		$versionTable = $this->behavior->getVersionTable();
		$versionARClassname = $this->builder->getNewStubObjectBuilder($versionTable)->getClassname();
		$script .= "
/**
 * Creates a version of the current object and saves it.
 *
 * @param   PropelPDO \$con the connection to use
 *
 * @return  {$versionARClassname} A version object
 */
public function addVersion(\$con = null)
{
	\$version = new {$versionARClassname}();";
		foreach ($this->table->getColumns() as $col) {
			$script .= "
	\$version->set" . $col->getPhpName() . "(\$this->" . strtolower($col->getName()) . ");";
		}
		$script .= "
	\$version->set{$this->table->getPhpName()}(\$this);";
		foreach ($this->behavior->getVersionableFks() as $fk) {
			$fkGetter = $this->builder->getFKPhpNameAffix($fk, $plural = false);
			$fkVersionColumnName = $fk->getLocalColumnName() . '_version';
			$fkVersionColumnPhpName = $versionTable->getColumn($fkVersionColumnName)->getPhpName();
			$script .= "
	if ((\$related = \$this->get{$fkGetter}(\$con)) && \$related->getVersion()) {
		\$version->set{$fkVersionColumnPhpName}(\$related->getVersion());
	}";
		}
		foreach ($this->behavior->getVersionableReferrers() as $fk) {
			$fkGetter = $this->builder->getRefFKPhpNameAffix($fk, $plural = true);
			$idsColumn = $this->behavior->getReferrerIdsColumn($fk);
			$versionsColumn = $this->behavior->getReferrerVersionsColumn($fk);
			$script .= "
	if (\$relateds = \$this->get{$fkGetter}(\$con)->toKeyValue('{$fk->getForeignColumn()->getPhpName()}', 'Version')) {
		\$version->set{$idsColumn->getPhpName()}(array_keys(\$relateds));
		\$version->set{$versionsColumn->getPhpName()}(array_values(\$relateds));
	}";
		}
			$script .= "
	\$version->save(\$con);
	
	return \$version;
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
 * @param   integer \$versionNumber The version number to read
 * @param   PropelPDO \$con the connection to use
 *
 * @return  {$ARclassName} The current object (for fluent API support)
 */
public function toVersion(\$versionNumber, \$con = null)
{
	\$version = \$this->getOneVersion(\$versionNumber, \$con);
	if (!\$version) {
		throw new PropelException(sprintf('No {$ARclassName} object found with version %d', \$version));
	}
	\$this->populateFromVersion(\$version, \$con);
	
	return \$this;
}
";
	}

	protected function addPopulateFromVersion(&$script)
	{
		$ARclassName = $this->getActiveRecordClassName();
		$versionTable = $this->behavior->getVersionTable();
		$versionARClassname = $this->builder->getNewStubObjectBuilder($versionTable)->getClassname();
		$script .= "
/**
 * Sets the properties of the curent object to the value they had at a specific version
 *
 * @param   {$versionARClassname} \$version The version object to use
 * @param   PropelPDO \$con the connection to use
 *
 * @return  {$ARclassName} The current object (for fluent API support)
 */
public function populateFromVersion(\$version, \$con = null)
{";
		foreach ($this->table->getColumns() as $col) {
			$script .= "
	\$this->set" . $col->getPhpName() . "(\$version->get" . $col->getPhpName() . "());";
		}
		foreach ($this->behavior->getVersionableFks() as $fk) {
			$foreignTable = $fk->getForeignTable();
			$foreignVersionTable = $fk->getForeignTable()->getBehavior('versionable')->getVersionTable();
			$relatedClassname = $this->builder->getNewStubObjectBuilder($foreignTable)->getClassname();
			$relatedVersionQueryClassname = $this->builder->getNewStubQueryBuilder($foreignVersionTable)->getClassname();
			$fkColumnName = $fk->getLocalColumnName();
			$fkColumnPhpName = $fk->getLocalColumn()->getPhpName();
			$fkVersionColumnPhpName = $versionTable->getColumn($fkColumnName . '_version')->getPhpName();
			$fkPhpname = $this->builder->getFKPhpNameAffix($fk, $plural = false);
			// FIXME: breaks lazy-loading
			$script .= "
	if (\$fkValue = \$version->get{$fkColumnPhpName}()) {
		\$related = new {$relatedClassname}();
		\$relatedVersion = {$relatedVersionQueryClassname}::create()
			->filterBy{$fk->getForeignColumn()->getPhpName()}(\$fkValue)
			->filterByVersion(\$version->get{$fkVersionColumnPhpName}())
			->findOne(\$con);
		\$related->populateFromVersion(\$relatedVersion, \$con);
		\$related->setNew(false);
		\$this->set{$fkPhpname}(\$related);
	}";
		}
		foreach ($this->behavior->getVersionableReferrers() as $fk) {
			$fkPhpNames = $this->builder->getRefFKPhpNameAffix($fk, $plural = true);
			$fkPhpName = $this->builder->getRefFKPhpNameAffix($fk, $plural = false);
			$foreignTable = $fk->getTable();
			$foreignBehavior = $foreignTable->getBehavior('versionable');
			$foreignVersionTable = $foreignBehavior->getVersionTable();
			$fkColumnIds = $this->behavior->getReferrerIdsColumn($fk);
			$fkColumnVersions = $this->behavior->getReferrerVersionsColumn($fk);
			$relatedVersionQueryClassname = $this->builder->getNewStubQueryBuilder($foreignVersionTable)->getClassname();
			$relatedClassname = $this->builder->getNewStubObjectBuilder($foreignTable)->getClassname();
			$script .= "
	if (\$fkValues = \$version->get{$fkColumnIds->getPhpName()}()) {
		\$this->clear{$fkPhpNames}();
		\$fkVersions = \$version->get{$fkColumnVersions->getPhpName()}();
		\$query = {$relatedVersionQueryClassname}::create();
		foreach (\$fkValues as \$key => \$value) {
			\$c1 = \$query->getNewCriterion({$this->builder->getColumnConstant($fkColumnIds)}, \$value);
			\$c2 = \$query->getNewCriterion({$this->builder->getColumnConstant($fkColumnVersions)}, \$fkVersions[\$key]);
			\$c1->addAnd(\$c2);
			\$query->addOr(\$c1);
		}
		foreach (\$query->find(\$con) as \$relatedVersion) {
			\$related = new {$relatedClassname}();
			\$related->populateFromVersion(\$relatedVersion, \$con);
			\$related->setNew(false);
			\$this->add{$fkPhpName}(\$related);
		}
	}";
		}
		$script .= "
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
		->filterBy{$this->table->getPhpName()}(\$this)
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

	protected function addGetOneVersion(&$script)
	{
		$versionARClassname = $this->builder->getNewStubObjectBuilder($this->behavior->getVersionTable())->getClassname();
		$script .= "
/**
 * Retrieves a version object for this entity and a version number
 *
 * @param   integer \$versionNumber The version number to read
 * @param   PropelPDO \$con the connection to use
 *
 * @return  {$versionARClassname} A version object
 */
public function getOneVersion(\$versionNumber, \$con = null)
{
	return {$this->getVersionQueryClassName()}::create()
		->filterBy{$this->table->getPhpName()}(\$this)
		->filterBy{$this->getColumnPhpName()}(\$versionNumber)
		->findOne(\$con);
}
";
	}

	protected function addGetAllVersions(&$script)
	{
		$versionTable = $this->behavior->getVersionTable();
		$versionARClassname = $this->builder->getNewStubObjectBuilder($versionTable)->getClassname();
		$versionForeignColumn = $versionTable->getColumn($this->behavior->getParameter('version_column'));
		$fks = $versionTable->getForeignKeysReferencingTable($this->table->getName());
		$relCol = $this->builder->getRefFKPhpNameAffix($fks[0], $plural = true);
		$script .= "
/**
 * Gets all the versions of this object, in incremental order
 *
 * @param   PropelPDO \$con the connection to use
 *
 * @return  PropelObjectCollection A list of {$versionARClassname} objects
 */
public function getAllVersions(\$con = null)
{
	\$criteria = new Criteria();
	\$criteria->addAscendingOrderByColumn({$this->builder->getColumnConstant($versionForeignColumn)});
	return \$this->get{$relCol}(\$criteria, \$con);
}
";
	}

	protected function addCompareVersions(&$script)
	{
		$versionTable = $this->behavior->getVersionTable();
		$versionARClassname = $this->builder->getNewStubObjectBuilder($versionTable)->getClassname();
		$versionForeignColumn = $versionTable->getColumn($this->behavior->getParameter('version_column'));
		$fks = $versionTable->getForeignKeysReferencingTable($this->table->getName());
		$relCol = $this->builder->getRefFKPhpNameAffix($fks[0], $plural = true);
		$script .= "
/**
 * Gets all the versions of this object, in incremental order.
 * <code>
 * print_r(\$book->compare(1, 2));
 * => array(
 *   '1' => array('Title' => 'Book title at version 1'),
 *   '2' => array('Title' => 'Book title at version 2')
 * );
 * </code>
 *
 * @param   integer   \$fromVersionNumber
 * @param   integer   \$toVersionNumber
 * @param   string    \$keys Main key used for the result diff (versions|columns)
 * @param   PropelPDO \$con the connection to use
 *
 * @return  array A list of differences
 */
public function compareVersions(\$fromVersionNumber, \$toVersionNumber, \$keys = 'columns', \$con = null)
{
	\$fromVersion = \$this->getOneVersion(\$fromVersionNumber, \$con)->toArray();
	\$toVersion = \$this->getOneVersion(\$toVersionNumber, \$con)->toArray();
	\$ignoredColumns = array(
		'{$this->getColumnPhpName()}',";
		if ($this->behavior->getParameter('log_created_at') == 'true') {
			$script .= "
		'VersionCreatedAt',";
		}
		if ($this->behavior->getParameter('log_created_by') == 'true') {
			$script .= "
		'VersionCreatedBy',";
		}
		if ($this->behavior->getParameter('log_comment') == 'true') {
			$script .= "
		'VersionComment',";
		}
		$script .= "
	);
	\$diff = array();
	foreach (\$fromVersion as \$key => \$value) {
		if (in_array(\$key, \$ignoredColumns)) {
			continue;
		}
		if (\$toVersion[\$key] != \$value) {
			switch (\$keys) {
				case 'versions':
					\$diff[\$fromVersionNumber][\$key] = \$value;
					\$diff[\$toVersionNumber][\$key] = \$toVersion[\$key];
					break;
				default:
					\$diff[\$key] = array(
						\$fromVersionNumber => \$value,
						\$toVersionNumber => \$toVersion[\$key],
					);
					break;
			}
		}
	}
	return \$diff;
}
";
	}
}