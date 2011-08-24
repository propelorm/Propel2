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
		$script = '';
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

	public function preDeleteQuery($builder)
	{
		if ($this->behavior->isArchiveOnDelete()) {
			return "
if (\$this->archiveOnDelete) {
	\$this->archive(\$con);
} else {
	\$this->archiveOnDelete = true;
}
";
		}
	}

	public function postUpdateQuery($builder)
	{
		if ($this->behavior->isArchiveOnUpdate()) {
			return "
if (\$this->archiveOnUpdate) {
	\$this->archive(\$con);
} else {
	\$this->archiveOnUpdate = true;
}
";
		}
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function queryMethods($builder)
	{
		$script = '';
		$script .= $this->addArchive($builder);
		if ($this->behavior->isArchiveOnUpdate()) {
			$script .= $this->addSetArchiveOnUpdate($builder);
			$script .= $this->addUpdateWithoutArchive($builder);
		}
		if ($this->behavior->isArchiveOnDelete()) {
			$script .= $this->addSetArchiveOnDelete($builder);
			$script .= $this->addDeleteWithoutArchive($builder);
		}
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
	if (\$con === null) {
		\$con = Propel::getConnection(" . $builder->getPeerClassname() . "::DATABASE_NAME, Propel::CONNECTION_WRITE);
	}
	\$con->beginTransaction();
	try {
		// archive all results one by one
		foreach (\$criteria->find(\$con) as \$object) {
			\$object->archive(\$con);
			\$totalArchivedObjects++;
		}
		\$con->commit();
	} catch (PropelException \$e) {
		\$con->rollBack();
		throw \$e;
	}
	
	return \$totalArchivedObjects;
}
";
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addSetArchiveOnUpdate($builder)
	{
		return "
/**
 * Enable/disable auto-archiving on update for the next query.
 *
 * @param Boolean True if the query must archive updated objects, false otherwise.
 */
public function setArchiveOnUpdate(\$archiveOnUpdate)
{
	\$this->archiveOnUpdate = \$archiveOnUpdate;
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

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addSetArchiveOnDelete($builder)
	{
		return "
/**
 * Enable/disable auto-archiving on delete for the next query.
 *
 * @param Boolean True if the query must archive deleted objects, false otherwise.
 */
public function setArchiveOnDelete(\$archiveOnDelete)
{
	\$this->archiveOnDelete = \$archiveOnDelete;
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
 * @param      PropelPDO \$con	Connection to use.
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
 * @param      PropelPDO \$con	Connection to use.
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
}
