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
 * @author    Francois Zaninotto
 * @version		$Revision$
 * @package		propel.generator.archivable
 */
class ArchivableBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'archive_table'       => '',
		'archive_class'       => '',
		'log_archived_at'     => 'true',
		'archived_at_column'  => 'archived_at',
		'archive_on_insert'   => 'false',
		'archive_on_update'   => 'false',
		'archive_on_delete'   => 'true',
	);

	protected $archiveTable;

	public function modifyTable()
	{
		if ($this->getParameter('archive_class') && $this->getParameter('archive_table')) {
			throw new InvalidArgumentException('Please set only one of the two parameters "archive_class" and "archive_table".');
		}
		if (!$this->getParameter('archive_class')) {
			$this->addArchiveTable();
		}
	}

	protected function addArchiveTable()
	{
		$table = $this->getTable();
		$database = $table->getDatabase();
		$archiveTableName = $this->getParameter('archive_table') ? $this->getParameter('archive_table') : ($this->getTable()->getName() . '_archive');;
		if (!$database->hasTable($archiveTableName)) {
			// create the version table
			$archiveTable = $database->addTable(array(
				'name'      => $archiveTableName,
				'package'   => $table->getPackage(),
				'schema'    => $table->getSchema(),
				'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
			));
			// copy all the columns
			foreach ($table->getColumns() as $column) {
				$columnInArchiveTable = clone $column;
				if ($columnInArchiveTable->hasReferrers()) {
					$columnInArchiveTable->clearReferrers();
				}
				if ($columnInArchiveTable->isAutoincrement()) {
					$columnInArchiveTable->setAutoIncrement(false);
				}
				$archiveTable->addColumn($columnInArchiveTable);
			}
			// add archived_at column
			if ($this->getParameter('log_archived_at') == 'true') {
				$archiveTable->addColumn(array(
					'name' => $this->getParameter('archived_at_column'),
					'type' => 'TIMESTAMP'
				));
			}
			// do not copy foreign keys
			// copy the indices
			foreach ($table->getIndices() as $index) {
				$copiedIndex = clone $index;
				$copiedIndex->setName('');
				$archiveTable->addIndex($copiedIndex);
			}
			// copy unique indices
			foreach ($table->getUnices() as $unique) {
				$copiedUnique = clone $unique;
				$copiedUnique->setName('');
				$archiveTable->addUnique($copiedUnique);
			}
			// every behavior adding a table should re-execute database behaviors
			foreach ($database->getBehaviors() as $behavior) {
				$behavior->modifyDatabase();
			}
			$this->archiveTable = $archiveTable;
		} else {
			$this->archiveTable = $database->getTable($archiveTableName);
		}
	}

	/**
	 * @return Table
	 */
	public function getArchiveTable()
	{
		return $this->archiveTable;
	}

	public function getArchiveTablePhpName($builder)
	{
		if ($this->getParameter('archive_class') == '') {
			return $builder->getNewStubObjectBuilder($this->getArchiveTable())->getClassname();
		} else {
			return $this->getParameter('archive_class');
		}
	}

	public function getArchiveTableQueryName($builder)
	{
		if ($this->getParameter('archive_class') == '') {
			return $builder->getNewStubQueryBuilder($this->getArchiveTable())->getClassname();
		} else {
			return $this->getParameter('archive_class') . 'Query';
		}
	}

	/**
	 * @return Column
	 */
	public function getArchivedAtColumn()
	{
		if ($this->getParameter('log_archived_at') == 'true') {
			return $this->getTable()->getColumn($this->getParameter('archived_at_column'));
		}
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$script .= $this->addObjectArchive($builder);
		$script .= $this->addObjectPopulateFromArchive($builder);
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addObjectArchive($builder)
	{
		$archiveTablePhpName = $this->getArchiveTablePhpName($builder);
		$archiveTableQueryName = $this->getArchiveTableQueryName($builder);
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
		if ($archivedAtColumn = $this->getArchivedAtColumn()) {
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
	public function addObjectPopulateFromArchive($builder)
	{
		$archiveTablePhpName = $this->getArchiveTablePhpName($builder);
		$usesAutoIncrement = $this->getTable()->hasAutoIncrementPrimaryKey();
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
			foreach ($this->getTable()->getColumns() as $col) {
				$snippet = "";
				if ($col->isAutoIncrement()) {
					$script .= "
		\$this->set" . $col->getPhpName() . "(\$archive->get" . $col->getPhpName() . "());";
				}
			}
			$script .= "
	}";
		}
		foreach ($this->getTable()->getColumns() as $col) {
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
