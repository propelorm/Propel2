<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Keeps tracks of an ActiveRecord object, even after deletion
 *
 * @author     François Zaninotto
 * @package    propel.generator.behavior.archivable
 */
class ArchivableBehaviorObjectBuilderModifier
{
	protected $behavior, $table, $builder;

	public function __construct($behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}

	protected function getParameter($key)
	{
		return $this->behavior->getParameter($key);
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function objectAttributes($builder)
	{
		$script = '';
		if ($this->behavior->isArchiveOnInsert()) {
			$script .= "protected \$archiveOnInsert = true;
";
		}
		if ($this->behavior->isArchiveOnUpdate()) {
			$script .= "protected \$archiveOnUpdate = true;
";
		}
		if ($this->behavior->isArchiveOnDelete()) {
			$script .= "protected \$archiveOnDelete = true;
";
		}
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function postInsert($builder)
	{
		if ($this->behavior->isArchiveOnInsert()) {
			return "if (\$this->archiveOnInsert) {
	\$this->archive(\$con);
} else {
	\$this->archiveOnInsert = true;
}";
		}
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function postUpdate($builder)
	{
		if ($this->behavior->isArchiveOnUpdate()) {
			return "if (\$this->archiveOnUpdate) {
	\$this->archive(\$con);
} else {
	\$this->archiveOnUpdate = true;
}";
		}
	}

	/**
	 * Using preDelete rather than postDelete to allow user to retrieve 
	 * related records and archive them before cascade deletion.
	 *
	 * The actual deletion is made by the query object, so the AR class must tell 
	 * the query class to enable or disable archiveOnDelete.
	 *
	 * @return string the PHP code to be added to the builder
	 */
	public function preDelete($builder)
	{
		$queryClassname = $builder->getStubQueryBuilder()->getClassname();
		if ($this->behavior->isArchiveOnDelete()) {
			if ($builder->getGeneratorConfig()->getBuildProperty('addHooks')) {
				return "if (\$ret) {
	if (\$this->archiveOnDelete) {
		// do nothing yet. The object will be archived later when calling " . $queryClassname . "::delete().
	} else {
		\$deleteQuery->setArchiveOnDelete(false);
		\$this->archiveOnDelete = true;
	}
}";
			} else {
				return "if (\$this->archiveOnDelete) {
	// do nothing yet. The object will be archived later when calling " . $queryClassname . "::delete().
} else {
	\$deleteQuery->setArchiveOnDelete(false);
	\$this->archiveOnDelete = true;
}";
			}
		}
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$script .= $this->addGetArchive($builder);
		$script .= $this->addArchive($builder);
		$script .= $this->addRestoreFromArchive($builder);
		$script .= $this->addPopulateFromArchive($builder);
		if ($this->behavior->isArchiveOnInsert() || $this->behavior->isArchiveOnUpdate()) {
			$script .= $this->addSaveWithoutArchive($builder);
		}
		if ($this->behavior->isArchiveOnDelete()) {
			$script .= $this->addDeleteWithoutArchive($builder);
		}
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addGetArchive($builder)
	{
		$archiveTablePhpName = $this->behavior->getArchiveTablePhpName($builder);
		$archiveTableQueryName = $this->behavior->getArchiveTableQueryName($builder);
		$script = "
/**
 * Get an archived version of the current object.
 *
 * @param PropelPDO \$con Optional connection object
 *
 * @return     " . $archiveTablePhpName . " An archive object, or null if the current object was never archived
 */
public function getArchive(PropelPDO \$con = null)
{
	if (\$this->isNew()) {
		return null;
	}
	\$archive = " . $archiveTableQueryName . "::create()
		->filterByPrimaryKey(\$this->getPrimaryKey())
		->findOne(\$con);

	return \$archive;
}
";
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addArchive($builder)
	{
		$archiveTablePhpName = $this->behavior->getArchiveTablePhpName($builder);
		$archiveTableQueryName = $this->behavior->getArchiveTableQueryName($builder);
		$script = "
/**
 * Copy the data of the current object into a $archiveTablePhpName archive object.
 * The archived object is then saved.
 * If the current object has already been archived, the archived object
 * is updated and not duplicated.
 *
 * @param PropelPDO \$con Optional connection object
 *
 * @throws PropelException If the object is new
 *
 * @return     " . $archiveTablePhpName . " The archive object based on this object
 */
public function archive(PropelPDO \$con = null)
{
	if (\$this->isNew()) {
		throw new PropelException('New objects cannot be archived. You must save the current object before calling archive().');
	}
	if (!\$archive = \$this->getArchive(\$con)) {
		\$archive = new $archiveTablePhpName();
		\$archive->setPrimaryKey(\$this->getPrimaryKey());
	}
	\$this->copyInto(\$archive, \$deepCopy = false, \$makeNew = false);";
		if ($archivedAtColumn = $this->behavior->getArchivedAtColumn()) {
			$script .= "
	\$archive->set" . $archivedAtColumn->getPhpName()."(time());";
		}
		$script .= "
	\$archive->save(\$con);

	return \$archive;
}
";
		return $script;
	}

	/**
	 *
	 * @return string the PHP code to be added to the builder
	 */
	public function addRestoreFromArchive($builder)
	{
		$archiveTablePhpName = $this->behavior->getArchiveTablePhpName($builder);
		$usesAutoIncrement = $this->table->hasAutoIncrementPrimaryKey();
		$script = "
/**
 * Revert the the current object to the state it had when it was last archived.
 * The object must be saved afterwards if the changes must persist.
 *
 * @param PropelPDO \$con Optional connection object
 *
 * @throws PropelException If the object has no corresponding archive.
 *
 * @return     " . $this->builder->getObjectClassname() . " The current object (for fluent API support)
 */
public function restoreFromArchive(PropelPDO \$con = null)
{
	if (!\$archive = \$this->getArchive(\$con)) {
		throw new PropelException('The current object has never been archived and cannot be restored');
	}
	\$this->populateFromArchive(\$archive);

	return \$this;
}
";
		return $script;
	}

	/**
	 * Generates a method to populate the current AR object based on an archive object.
	 * This method is necessary because the archive's copyInto() may include the archived_at column
	 * and therefore cannot be used. Besides, the way autoincremented PKs are handled should be explicit.
	 *
	 * @return string the PHP code to be added to the builder
	 */
	public function addPopulateFromArchive($builder)
	{
		$archiveTablePhpName = $this->behavior->getArchiveTablePhpName($builder);
		$usesAutoIncrement = $this->table->hasAutoIncrementPrimaryKey();
		$script = "
/**
 * Populates the the current object based on a $archiveTablePhpName archive object.
 *
 * @param      " . $archiveTablePhpName . " \$archive An archived object based on the same class";
 		if ($usesAutoIncrement) {
 			$script .= "
 * @param      Boolean \$populateAutoIncrementPrimaryKeys 
 *               If true, autoincrement columns are copied from the archive object.
 *               If false, autoincrement columns are left intact.";
 		}
 		$script .= "
 *
 * @return     " . $this->builder->getObjectClassname() . " The current object (for fluent API support)
 */
public function populateFromArchive(\$archive" . ($usesAutoIncrement ? ", \$populateAutoIncrementPrimaryKeys = false" : '') . ")
{";
		if ($usesAutoIncrement) {
			$script .= "
	if (\$populateAutoIncrementPrimaryKeys) {";
			foreach ($this->table->getColumns() as $col) {
				$snippet = "";
				if ($col->isAutoIncrement()) {
					$script .= "
		\$this->set" . $col->getPhpName() . "(\$archive->get" . $col->getPhpName() . "());";
				}
			}
			$script .= "
	}";
		}
		foreach ($this->table->getColumns() as $col) {
			if ($col->isAutoIncrement()) {
				continue;
			}
			$script .= "
	\$this->set" . $col->getPhpName() . "(\$archive->get" . $col->getPhpName() . "());";
		}
		$script .= "

	return \$this;
}
";
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addSaveWithoutArchive($builder)
	{
		$script = "
/**
 * Persists the object to the database without archiving it.
 *
 * @param PropelPDO \$con Optional connection object
 *
 * @return     " . $this->builder->getObjectClassname() . " The current object (for fluent API support)
 */
public function saveWithoutArchive(PropelPDO \$con = null)
{";
	if (!$this->behavior->isArchiveOnInsert()) {
		$script .= "
	if (!\$this->isNew()) {
		\$this->archiveOnUpdate = false;
	}";
	}	elseif (!$this->behavior->isArchiveOnUpdate()) {
		$script .= "
	if (\$this->isNew()) {
		\$this->archiveOnInsert = false;
	}";
	} else {
		$script .= "
	if (\$this->isNew()) {
		\$this->archiveOnInsert = false;
	} else {
		\$this->archiveOnUpdate = false;
	}";		
	}
	$script .= "
	return \$this->save(\$con);
}
";
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addDeleteWithoutArchive($builder)
	{
		$script = "
/**
 * Removes the object from the database without archiving it.
 *
 * @param PropelPDO \$con Optional connection object
 *
 * @return     " . $this->builder->getObjectClassname() . " The current object (for fluent API support)
 */
public function deleteWithoutArchive(PropelPDO \$con = null)
{
	\$this->archiveOnDelete = false;
	return \$this->delete(\$con);
}
";
		return $script;
	}

}
