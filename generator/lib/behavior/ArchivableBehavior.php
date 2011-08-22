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
		'log_archived_at'     => 'true',
		'archived_at_column'  => 'archived_at',
		'archive_on_insert'   => 'false',
		'archive_on_update'   => 'false',
		'archive_on_delete'   => 'true',
	);

	protected $archiveTable;

	public function modifyTable()
	{
		$this->addArchiveTable();
	}

	protected function addArchiveTable()
	{
		$table = $this->getTable();
		$database = $table->getDatabase();
		$archiveTableName = $this->getArchiveTableName();
		if (!$database->hasTable($archiveTableName)) {
			// create the version table
			$archiveTable = $database->addTable(array(
				'name'      => $this->getArchiveTableName(),
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
	 * @return string
	 */
	public function getArchiveTableName()
	{
		return $this->getParameter('archive_table') ? $this->getParameter('archive_table') : ($this->getTable()->getName() . '_archive');
	}

	/**
	 * @return Table
	 */
	public function getArchiveTable()
	{
		return $this->archiveTable;
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
		return $script;
	}

	/**
	 * @return string the PHP code to be added to the builder
	 */
	public function addObjectArchive($builder)
	{
		$archiveTablePhpName = $builder->getNewStubObjectBuilder($this->getArchiveTable())->getClassname();
		$archiveTableQueryName = $builder->getNewStubQueryBuilder($this->getArchiveTable())->getClassname();
		$script = "
/**
 * Save a copy of the current object in the '" . $this->getArchiveTable()->getName() . "' archive table.
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
