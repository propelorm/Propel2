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
	public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$script .= $this->addArchive($builder);
		$script .= $this->addPopulateFromArchive($builder);
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
 * @return     " . $this->builder->getObjectClassname() . " The current object (for fluent API support)
 */
public function archive(PropelPDO \$con = null)
{
	\$archive = " . $archiveTableQueryName . "::create()
		->filterByPrimaryKey(\$this->getPrimaryKey())
		->findOneOrCreate(\$con);
	\$this->copyInto(\$archive, \$deepCopy = false, \$makeNew = false);";
		if ($archivedAtColumn = $this->behavior->getArchivedAtColumn()) {
			$script .= "
	\$archive->set" . $archivedAtColumn->getPhpName()."(time());";
		}
		$script .= "
	\$archive->save(\$con);

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
	public function postInsert($builder)
	{
		if ($this->getParameter('archive_on_insert') == 'true') {
			return "\$this->archive(\$con);";
		}
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function postUpdate($builder)
	{
		if ($this->getParameter('archive_on_update') == 'true') {
			return "\$this->archive(\$con);";
		}
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function postDelete($builder)
	{
		if ($this->getParameter('archive_on_delete') == 'true') {
			return "\$this->archive(\$con);";
		}
	}


}
