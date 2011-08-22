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
class ArchivableBehaviorQueryBuilderModifier
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

	public function queryAttributes($builder)
	{
		return "
protected \$archiveOnDelete = " . ($this->getParameter('archive_on_delete') == 'true' ? 'true' : 'false'). ";
protected \$archiveOnUpdate = " . ($this->getParameter('archive_on_update') == 'true' ? 'true' : 'false'). ";
";
	}

	public function preDeleteQuery($builder)
	{
		return "
if (\$this->archiveOnDelete) {
	\$this->archive(\$con);
}
";
	}

	public function postUpdateQuery($builder)
	{
		return "
if (\$this->archiveOnUpdate) {
	\$this->archive(\$con);
}
";
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function queryMethods($builder)
	{
		$script = '';
		$script .= $this->addArchive($builder);
		$script .= $this->addDeleteAndArchive($builder);
		$script .= $this->addDeleteWithoutArchive($builder);
		$script .= $this->addUpdateAndArchive($builder);
		$script .= $this->addUpdateWithoutArchive($builder);
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	protected function addArchive($builder)
	{
		$archiveTablePhpName = $this->behavior->getArchiveTablePhpName($builder);
		return "
/**
 * Copy the data of the objects satisfying the query into $archiveTablePhpName archive objects.
 * The archived objects are then saved.
 * If any of the objects has already been archived, the archived object
 * is updated and not duplicated.
 * Warning: This termination methods issues 2n+1 queries.
 *
 * @param      PropelPDO \$con	Connection to use.
 * @param      Boolean \$useLittleMemory	Whether or not to use PropelOnDemandFormatter to retrieve objects.
 *               Set to false if the identity map matters.
 *               Set to true (default) to use less memory.
 *
 * @return     int the number of archived objects
 */
public function archive(\$con = null, \$useLittleMemory = true)
{
	\$totalArchivedObjects = 0;
	\$criteria = clone \$this;
	// prepare the query
	\$criteria->setWith(array());
	if (\$useLittleMemory) {
		\$criteria->setFormatter(ModelCriteria::FORMAT_ON_DEMAND);
	}
	// archive all results one by one
	foreach (\$criteria->find(\$con) as \$object) {
		\$object->archive(\$con);
		\$totalArchivedObjects++;
	}
	
	return \$totalArchivedObjects;
}
";
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addDeleteAndArchive($builder)
	{
		return "
/**
 * Archive and delete records mathing the current query.
 *
 * @return integer the number of deleted rows
 */
public function deleteAndArchive(\$con = null)
{
	\$this->archiveOnDelete = true;

	return \$this->delete(\$con);
}

/**
 * Archive and delete all records.
 *
 * @return integer the number of deleted rows
 */
public function deleteAllAndArchive(\$con = null)
{
	\$this->archiveOnDelete = true;

	return \$this->deleteAll(\$con);
}
";
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addDeleteWithoutArchive($builder)
	{
		return "
/**
 * Delete records matching the current query without archiving them.
 *
 * @return integer the number of deleted rows
 */
public function deleteWithoutArchive(\$con = null)
{
	\$this->archiveOnDelete = false;

	return \$this->delete(\$con);
}

/**
 * Delete all records without archiving them.
 *
 * @return integer the number of deleted rows
 */
public function deleteAllWithoutArchive(\$con = null)
{
	\$this->archiveOnDelete = false;

	return \$this->deleteAll(\$con);
}
";
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addUpdateAndArchive($builder)
	{
		return "
/**
 * Update and archive records mathing the current query.
 *
 * @param      array \$values Associative array of keys and values to replace
 * @param      PropelPDO \$con an optional connection object
 * @param      boolean \$forceIndividualSaves If false (default), the resulting call is a BasePeer::doUpdate(), ortherwise it is a series of save() calls on all the found objects
 *
 * @return integer the number of deleted rows
 */
public function updateAndArchive(\$values, \$con = null, \$forceIndividualSaves = false)
{
	\$this->archiveOnUpdate = true;

	return \$this->update(\$values, \$con, \$forceIndividualSaves);
}
";
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addUpdateWithoutArchive($builder)
	{
		return "
/**
 * Delete records matching the current query without archiving them.
 *
 * @param      array \$values Associative array of keys and values to replace
 * @param      PropelPDO \$con an optional connection object
 * @param      boolean \$forceIndividualSaves If false (default), the resulting call is a BasePeer::doUpdate(), ortherwise it is a series of save() calls on all the found objects
 *
 * @return integer the number of deleted rows
 */
public function updateWithoutArchive(\$values, \$con = null, \$forceIndividualSaves = false)
{
	\$this->archiveOnUpdate = false;

	return \$this->update(\$values, \$con, \$forceIndividualSaves);
}
";
	}

}
