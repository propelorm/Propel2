<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/VersionableBehaviorObjectBuilderModifier.php';
require_once dirname(__FILE__) . '/VersionableBehaviorQueryBuilderModifier.php';
require_once dirname(__FILE__) . '/VersionableBehaviorPeerBuilderModifier.php';


/**
 * Keeps tracks of all the modifications in an ActiveRecord object
 *
 * @author    Francois Zaninotto
 * @version		$Revision$
 * @package		propel.generator.behavior.versionable
 */
class VersionableBehavior extends Behavior
{
	// default parameters value
	protected $parameters = array(
		'version_column' => 'version',
		'version_table'  => '',
		'log_created_at' => 'false',
		'log_created_by' => 'false',
		'log_comment'    => 'false',
	);

	protected 
		$versionTable,
		$objectBuilderModifier,
		$queryBuilderModifier,
		$peerBuilderModifier;
	
	public function modifyTable()
	{
		$this->addVersionColumn();
		$this->addLogColumns();
		$this->addVersionTable();
		$this->addForeignKeyVersionColumns();
	}
	
	protected function addVersionColumn()
	{
		$table = $this->getTable();
		// add the version column
		if(!$table->containsColumn($this->getParameter('version_column'))) {
			$table->addColumn(array(
				'name'    => $this->getParameter('version_column'),
				'type'    => 'INTEGER',
				'default' => 0
			));
		}
	}
	
	protected function addLogColumns()
	{
		$table = $this->getTable();
		if ($this->getParameter('log_created_at') == 'true' && !$table->containsColumn('version_created_at')) {
			$table->addColumn(array(
				'name' => 'version_created_at',
				'type' => 'TIMESTAMP'
			));
		}
		if ($this->getParameter('log_created_by') == 'true' && !$table->containsColumn('version_created_by')) {
			$table->addColumn(array(
				'name' => 'version_created_by',
				'type' => 'VARCHAR',
				'size' => 100
			));
		}
		if ($this->getParameter('log_comment') == 'true'  && !$table->containsColumn('version_comment')) {
			$table->addColumn(array(
				'name' => 'version_comment',
				'type' => 'VARCHAR',
				'size' => 255
			));
		}
	}
	
	protected function addVersionTable()
	{
		$table = $this->getTable();
		$database = $table->getDatabase();
		$versionTableName = $this->getParameter('version_table') ? $this->getParameter('version_table') : ($table->getName() . '_version');
		if (!$database->hasTable($versionTableName)) {
			// create the version table
			$versionTable = $database->addTable(array(
				'name'      => $versionTableName,
				'phpName'   => $this->getVersionTablePhpName(),
				'package'   => $table->getPackage(),
				'schema'    => $table->getSchema(),
				'namespace' => $table->getNamespace(),
			));
			// copy all the columns
			foreach ($table->getColumns() as $column) {
				$columnInVersionTable = clone $column;
				if ($columnInVersionTable->isAutoincrement()) {
					$columnInVersionTable->setAutoIncrement(false);
				}
				$versionTable->addColumn($columnInVersionTable);
			}
			// create the foreign key
			$fk = new ForeignKey();
			$fk->setForeignTableCommonName($table->getCommonName());
			$fk->setForeignSchemaName($table->getSchema());
			$fk->setOnDelete('CASCADE');
			$fk->setOnUpdate(null);
			$tablePKs = $table->getPrimaryKey();
			foreach ($versionTable->getPrimaryKey() as $key => $column) {
				$fk->addReference($column, $tablePKs[$key]);
			}
			$versionTable->addForeignKey($fk);
			$table->addReferrer($fk);
			// add the version column to the primary key
			$versionTable->getColumn($this->getParameter('version_column'))->setPrimaryKey(true);
			$this->versionTable = $versionTable;
		} else {
			$this->versionTable = $database->getTable($versionTableName);
		}
	}

	public function addForeignKeyVersionColumns()
	{
		$table = $this->getTable();
		foreach ($this->getVersionableFks() as $fk) {
			$fkVersionColumnName = $fk->getLocalColumnName() . '_version';
			if (!$this->versionTable->containsColumn($fkVersionColumnName)) {
				$this->versionTable->addColumn(array(
					'name'    => $fkVersionColumnName,
					'type'    => 'INTEGER',
					'default' => 0
				));
			}
		}
	}
	
	public function getVersionTable()
	{
		return $this->versionTable;
	}
	
	public function getVersionTablePhpName()
	{
		return $this->getTable()->getPhpName() . 'Version';
	}
	
	public function getVersionableFks()
	{
		$versionableFKs = array();
		if ($fks = $this->getTable()->getForeignKeys()) {
			foreach ($fks as $fk) {
				if ($fk->getForeignTable()->hasBehavior('versionable') && ! $fk->isComposite()) {
					$versionableFKs []= $fk;
				}
			}
		}
		return $versionableFKs;
	}


	public function getObjectBuilderModifier()
	{
		if (is_null($this->objectBuilderModifier))
		{
			$this->objectBuilderModifier = new VersionableBehaviorObjectBuilderModifier($this);
		}
		return $this->objectBuilderModifier;
	}

	public function getQueryBuilderModifier()
	{
		if (is_null($this->queryBuilderModifier))
		{
			$this->queryBuilderModifier = new VersionableBehaviorQueryBuilderModifier($this);
		}
		return $this->queryBuilderModifier;
	}

	public function getPeerBuilderModifier()
	{
		if (is_null($this->peerBuilderModifier))
		{
			$this->peerBuilderModifier = new VersionableBehaviorPeerBuilderModifier($this);
		}
		return $this->peerBuilderModifier;
	}
	
}
