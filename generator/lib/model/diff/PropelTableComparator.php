<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license     MIT License
 */

require_once dirname(__FILE__) . '/../Table.php';
require_once dirname(__FILE__) . '/PropelTableDiff.php';
require_once dirname(__FILE__) . '/PropelColumnComparator.php';
require_once dirname(__FILE__) . '/PropelColumnDiff.php';
require_once dirname(__FILE__) . '/PropelIndexComparator.php';
require_once dirname(__FILE__) . '/PropelForeignKeyComparator.php';

/**
 * Service class for comparing Table objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 * @package     propel.generator.model.diff
 */
class PropelTableComparator
{
	protected $tableDiff;
	protected $fromTable;
	protected $toTable;
	
	public function __construct($tableDiff = null)
	{
		$this->tableDiff = (null === $tableDiff) ? new PropelTableDiff() : $tableDiff;
	}
	
	public function getTableDiff()
	{
		return $this->tableDiff;
	}
	
	/**
	 * Setter for the fromTable property
	 *
	 * @param Table $fromTable
	 */
	function setFromTable(Table $fromTable)
	{
		$this->fromTable = $fromTable;
	}

	/**
	 * Getter for the fromTable property
	 *
	 * @return Table
	 */
	function getFromTable()
	{
		return $this->fromTable;
	}

	/**
	 * Setter for the toTable property
	 *
	 * @param Table $toTable
	 */
	function setToTable(Table $toTable)
	{
		$this->toTable = $toTable;
	}

	/**
	 * Getter for the toTable property
	 *
	 * @return Table
	 */
	function getToTable()
	{
		return $this->toTable;
	}

	/**
	 * Compute and return the difference between two table objects
	 *
	 * @param Column $fromTable
	 * @param Column $toTable
	 *
	 * @return PropelTableDiff|boolean return false if the two tables are similar
	 */
	public static function computeDiff(Table $fromTable, Table $toTable)
	{
		$tc = new self();
		$tc->setFromTable($fromTable);
		$tc->setToTable($toTable);
		$differences = 0;
		$differences += $tc->compareColumns();
		$differences += $tc->comparePrimaryKeys();
		$differences += $tc->compareIndices();
		$differences += $tc->compareForeignKeys();
		
		return ($differences > 0) ? $tc->getTableDiff() : false;
	}
	
	/**
	 * Compare the columns of the fromTable and the toTable,
	 * and modifies the inner tableDiff if necessary.
	 * Returns the number of differences.
	 *
	 * @return integer The number of column differences
	 */
	public function compareColumns()
	{
		$fromTableColumns = $this->fromTable->getColumns();
		$toTableColumns = $this->toTable->getColumns();
		$columnDifferences = 0;
		
		// check for new columns in $toTable
		foreach ($toTableColumns as $column) {
			if (!$this->fromTable->hasColumn($column->getName())) {
				$this->tableDiff->addAddedColumn($column->getName(), $column);
				$columnDifferences++;
			}
		}
		
		// check for removed columns in $toTable
		foreach ($fromTableColumns as $column) {
			if (!$this->toTable->hasColumn($column->getName())) {
				$this->tableDiff->addRemovedColumn($column->getName(), $column);
				$columnDifferences++;
			}
		}
		
		// check for column differences
		foreach ($fromTableColumns as $fromColumn) {
			if ($this->toTable->hasColumn($fromColumn->getName())) {
				$toColumn = $this->toTable->getColumn($fromColumn->getName());
				$columnDiff = PropelColumnComparator::computeDiff($fromColumn, $toColumn);
				if ($columnDiff) {
					$this->tableDiff->addModifiedColumn($fromColumn->getName(), $columnDiff);
					$columnDifferences++;
				}
			}
		}
		
		// check for column renamings
		foreach ($this->tableDiff->getAddedColumns() as $addedColumnName => $addedColumn) {
			foreach ($this->tableDiff->getRemovedColumns() as $removedColumnName => $removedColumn) {
				if (!PropelColumnComparator::computeDiff($addedColumn, $removedColumn)) {
					// no difference except the name, that's probably a renaming
					$this->tableDiff->addRenamedColumn($removedColumnName, $addedColumnName);
					$this->tableDiff->removeAddedColumn($addedColumnName);
					$this->tableDiff->removeRemovedColumn($removedColumnName);
					$columnDifferences--;
				}
			}
		}
		
		return $columnDifferences;
	}
	
	/**
	 * Compare the primary keys of the fromTable and the toTable,
	 * and modifies the inner tableDiff if necessary.
	 * Returns the number of differences.
	 *
	 * @return integer The number of primary key differences
	 */
	public function comparePrimaryKeys()
	{
		$pkDifferences = 0;
		$fromTablePk = $this->fromTable->getPrimaryKey();
		$toTablePk = $this->toTable->getPrimaryKey();
		
		// check for new pk columns in $toTable
		foreach ($toTablePk as $column) {
			if (!$this->fromTable->hasColumn($column->getName()) ||
					!$this->fromTable->getColumn($column->getName())->isPrimaryKey()) {
				$this->tableDiff->addAddedPkColumn($column->getName(), $column);
				$pkDifferences++;
			}
		}
		
		// check for removed pk columns in $toTable
		foreach ($fromTablePk as $column) {
			if (!$this->toTable->hasColumn($column->getName()) ||
					!$this->toTable->getColumn($column->getName())->isPrimaryKey()) {
				$this->tableDiff->addRemovedPkColumn($column->getName(), $column);
				$pkDifferences++;
			}
		}
		
		// check for column renamings
		foreach ($this->tableDiff->getAddedPkColumns() as $addedColumnName => $addedColumn) {
			foreach ($this->tableDiff->getRemovedPkColumns() as $removedColumnName => $removedColumn) {
				if (!PropelColumnComparator::computeDiff($addedColumn, $removedColumn)) {
					// no difference except the name, that's probably a renaming
					$this->tableDiff->addRenamedPkColumn($removedColumnName, $addedColumnName);
					$this->tableDiff->removeAddedPkColumn($addedColumnName);
					$this->tableDiff->removeRemovedPkColumn($removedColumnName);
					$pkDifferences--;
				}
			}
		}
		
		return $pkDifferences;
	}

	/**
	 * Compare the indices and unique indices of the fromTable and the toTable,
	 * and modifies the inner tableDiff if necessary.
	 * Returns the number of differences.
	 *
	 * @return integer The number of index differences
	 */
	public function compareIndices()
	{
		$indexDifferences = 0;
		$fromTableIndices = array_merge($this->fromTable->getIndices(), $this->fromTable->getUnices());
		$toTableIndices = array_merge($this->toTable->getIndices(), $this->toTable->getUnices());

		foreach ($toTableIndices as $toTableIndexPos => $toTableIndex) {
			foreach ($fromTableIndices as $fromTableIndexPos => $fromTableIndex) {
				if (PropelIndexComparator::computeDiff($fromTableIndex, $toTableIndex) === false) {
					unset($fromTableIndices[$fromTableIndexPos]);
					unset($toTableIndices[$toTableIndexPos]);
				} else {
					if ($fromTableIndex->getName() == $toTableIndex->getName()) {
						// same name, but different columns
						$this->tableDiff->addModifiedIndex($fromTableIndex->getName(), $fromTableIndex, $toTableIndex);
						unset($fromTableIndices[$fromTableIndexPos]);
						unset($toTableIndices[$toTableIndexPos]);
						$indexDifferences++;
					}
				}
			}
		}

		foreach ($fromTableIndices as $fromTableIndexPos => $fromTableIndex) {
			$this->tableDiff->addRemovedIndex($fromTableIndex->getName(), $fromTableIndex);
			$indexDifferences++;
		}

		foreach ($toTableIndices as $toTableIndexPos => $toTableIndex) {
			$this->tableDiff->addAddedIndex($toTableIndex->getName(), $toTableIndex);
			$indexDifferences++;
		}
		
		return $indexDifferences;
	}
	
	/**
	 * Compare the foreign keys of the fromTable and the toTable,
	 * and modifies the inner tableDiff if necessary.
	 * Returns the number of differences.
	 *
	 * @return integer The number of foreign key differences
	 */
	public function compareForeignKeys()
	{
		$fkDifferences = 0;
		$fromTableFks = $this->fromTable->getForeignKeys();
		$toTableFks = $this->toTable->getForeignKeys();

		foreach ($fromTableFks as $fromTableFkPos => $fromTableFk) {
			foreach ($toTableFks as $toTableFkPos => $toTableFk) {
				if (PropelForeignKeyComparator::computeDiff($fromTableFk, $toTableFk) === false) {
					unset($fromTableFks[$fromTableFkPos]);
					unset($toTableFks[$toTableFkPos]);
				} else {
					if ($fromTableFk->getName() == $toTableFk->getName()) {
						// same name, but different columns
						$this->tableDiff->addModifiedFk($fromTableFk->getName(), $fromTableFk, $toTableFk);
						unset($fromTableFks[$fromTableFkPos]);
						unset($toTableFks[$toTableFkPos]);
						$fkDifferences++;
					}
				}
			}
		}

		foreach ($fromTableFks as $fromTableFkPos => $fromTableFk) {
			$this->tableDiff->addRemovedFk($fromTableFk->getName(), $fromTableFk);
			$fkDifferences++;
		}

		foreach ($toTableFks as $toTableFkPos => $toTableFk) {
			$this->tableDiff->addAddedFk($toTableFk->getName(), $toTableFk);
			$fkDifferences++;
		}
		
		return $fkDifferences;
	}

}