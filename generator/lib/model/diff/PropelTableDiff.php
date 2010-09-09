<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license     MIT License
 */

require_once dirname(__FILE__) . '/../Table.php';
require_once dirname(__FILE__) . '/PropelColumnDiff.php';

/**
 * Value object for storing Table object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 * @package    propel.generator.model.diff
 */
class PropelTableDiff
{
	protected $addedColumns = array();
	protected $removedColumns = array();
	protected $modifiedColumns = array();
	protected $renamedColumns = array();

	protected $addedPkColumns = array();
	protected $removedPkColumns = array();
	protected $renamedPkColumns = array();

	protected $addedIndices = array();
	protected $removedIndices = array();
	protected $modifiedIndices = array();

	protected $addedFks = array();
	protected $removedFks = array();
	protected $modifiedFks = array();
	
	/**
	 * Setter for the addedColumns property
	 *
	 * @param array $addedColumns
	 */
	function setAddedColumns($addedColumns)
	{
		$this->addedColumns = $addedColumns;
	}

	/**
	 * Add an added column
	 *
	 * @param string $columnName
	 * @param Column $addedColumn
	 */
	function addAddedColumn($columnName, Column $addedColumn)
	{
		$this->addedColumns[$columnName] = $addedColumn;
	}

	/**
	 * Remove an added column
	 *
	 * @param string $columnName
	 */
	function removeAddedColumn($columnName)
	{
		unset($this->addedColumns[$columnName]);
	}

	/**
	 * Getter for the addedColumns property
	 *
	 * @return array
	 */
	function getAddedColumns()
	{
		return $this->addedColumns;
	}

	/**
	 * Get an added column
	 *
	 * @param string $columnName
	 *
	 * @param Column
	 */
	function getAddedColumn($columnName)
	{
		return $this->addedColumns[$columnName];
	}

	/**
	 * Setter for the removedColumns property
	 *
	 * @param array $removedColumns
	 */
	function setRemovedColumns($removedColumns)
	{
		$this->removedColumns = $removedColumns;
	}

	/**
	 * Add a removed column
	 *
	 * @param string $columnName
	 * @param Column $removedColumn
	 */
	function addRemovedColumn($columnName, Column $removedColumn)
	{
		$this->removedColumns[$columnName] = $removedColumn;
	}

	/**
	 * Remove a removed column
	 *
	 * @param string $columnName
	 */
	function removeRemovedColumn($columnName)
	{
		unset($this->removedColumns[$columnName]);
	}

	/**
	 * Getter for the removedColumns property
	 *
	 * @return array
	 */
	function getRemovedColumns()
	{
		return $this->removedColumns;
	}

	/**
	 * Get a removed column
	 *
	 * @param string $columnName
	 *
	 * @param Column
	 */
	function getRemovedColumn($columnName)
	{
		return $this->removedColumns[$columnName];
	}

	/**
	 * Setter for the modifiedColumns property
	 *
	 * @param array $modifiedColumns
	 */
	function setModifiedColumns($modifiedColumns)
	{
		$this->modifiedColumns = $modifiedColumns;
	}

	/**
	 * Add a column difference
	 *
	 * @param string $columnName
	 * @param PropelColumnDiff $modifiedColumn
	 */
	function addModifiedColumn($columnName, PropelColumnDiff $modifiedColumn)
	{
		$this->modifiedColumns[$columnName] = $modifiedColumn;
	}

	/**
	 * Getter for the modifiedColumns property
	 *
	 * @return array
	 */
	function getModifiedColumns()
	{
		return $this->modifiedColumns;
	}

	/**
	 * Setter for the renamedColumns property
	 *
	 * @param array $renamedColumns
	 */
	function setRenamedColumns($renamedColumns)
	{
		$this->renamedColumns = $renamedColumns;
	}

	/**
	 * Add a renamed column
	 *
	 * @param string $fromName
	 * @param string $toName
	 */
	function addRenamedColumn($fromName, $toName)
	{
		$this->renamedColumns[$fromName] = $toName;
	}

	/**
	 * Getter for the renamedColumns property
	 *
	 * @return array
	 */
	function getRenamedColumns()
	{
		return $this->renamedColumns;
	}

	/**
	 * Setter for the addedPkColumns property
	 *
	 * @param  $addedPkColumns
	 */
	function setAddedPkColumns($addedPkColumns)
	{
		$this->addedPkColumns = $addedPkColumns;
	}

	/**
	 * Add an added Pk column
	 *
	 * @param string $columnName
	 * @param Column $addedPkColumn
	 */
	function addAddedPkColumn($columnName, Column $addedPkColumn)
	{
		$this->addedPkColumns[$columnName] = $addedPkColumn;
	}

	/**
	 * Remove an added Pk column
	 *
	 * @param string $columnName
	 */
	function removeAddedPkColumn($columnName)
	{
		unset($this->addedPkColumns[$columnName]);
	}

	/**
	 * Getter for the addedPkColumns property
	 *
	 * @return array
	 */
	function getAddedPkColumns()
	{
		return $this->addedPkColumns;
	}

	/**
	 * Setter for the removedPkColumns property
	 *
	 * @param  $removedPkColumns
	 */
	function setRemovedPkColumns($removedPkColumns)
	{
		$this->removedPkColumns = $removedPkColumns;
	}

	/**
	 * Add a removed Pk column
	 *
	 * @param string $columnName
	 * @param Column $removedColumn
	 */
	function addRemovedPkColumn($columnName, Column $removedPkColumn)
	{
		$this->removedPkColumns[$columnName] = $removedPkColumn;
	}

	/**
	 * Remove a removed Pk column
	 *
	 * @param string $columnName
	 */
	function removeRemovedPkColumn($columnName)
	{
		unset($this->removedPkColumns[$columnName]);
	}

	/**
	 * Getter for the removedPkColumns property
	 *
	 * @return array
	 */
	function getRemovedPkColumns()
	{
		return $this->removedPkColumns;
	}

	/**
	 * Setter for the renamedPkColumns property
	 *
	 * @param $renamedPkColumns
	 */
	function setRenamedPkColumns($renamedPkColumns)
	{
		$this->renamedPkColumns = $renamedPkColumns;
	}

	/**
	 * Add a renamed Pk column
	 *
	 * @param string $fromName
	 * @param string $toName
	 */
	function addRenamedPkColumn($fromName, $toName)
	{
		$this->renamedPkColumns[$fromName] = $toName;
	}

	/**
	 * Getter for the renamedPkColumns property
	 *
	 * @return array
	 */
	function getRenamedPkColumns()
	{
		return $this->renamedPkColumns;
	}

	/**
	 * Setter for the addedIndices property
	 *
	 * @param  $addedIndices
	 */
	function setAddedIndices($addedIndices)
	{
		$this->addedIndices = $addedIndices;
	}

	/**
	 * Add an added Index
	 *
	 * @param string $indexName
	 * @param Index $addedIndex
	 */
	function addAddedIndex($indexName, Index $addedIndex)
	{
		$this->addedIndices[$indexName] = $addedIndex;
	}

	/**
	 * Getter for the addedIndices property
	 *
	 * @return array
	 */
	function getAddedIndices()
	{
		return $this->addedIndices;
	}

	/**
	 * Setter for the removedIndices property
	 *
	 * @param  $removedIndices
	 */
	function setRemovedIndices($removedIndices)
	{
		$this->removedIndices = $removedIndices;
	}

	/**
	 * Add a removed Index
	 *
	 * @param string $indexName
	 * @param Index $removedIndex
	 */
	function addRemovedIndex($indexName, Index $removedIndex)
	{
		$this->removedIndices[$indexName] = $removedIndex;
	}

	/**
	 * Getter for the removedIndices property
	 *
	 * @return array
	 */
	function getRemovedIndices()
	{
		return $this->removedIndices;
	}

	/**
	 * Setter for the modifiedIndices property
	 *
	 * @param  $modifiedIndices
	 */
	function setModifiedIndices( $modifiedIndices)
	{
		$this->modifiedIndices = $modifiedIndices;
	}

	/**
	 * Add a modified Index
	 *
	 * @param string $indexName
	 * @param Index $fromIndex
	 * @param Index $toIndex
	 */
	function addModifiedIndex($indexName, Index $fromIndex, Index $toIndex)
	{
		$this->modifiedIndices[$indexName] = array($fromIndex, $toIndex);
	}

	/**
	 * Getter for the modifiedIndices property
	 *
	 * @return 
	 */
	function getModifiedIndices()
	{
		return $this->modifiedIndices;
	}

	/**
	 * Setter for the addedFks property
	 *
	 * @param  $addedFks
	 */
	function setAddedFks($addedFks)
	{
		$this->addedFks = $addedFks;
	}

	/**
	 * Add an added Fk column
	 *
	 * @param string $fkName
	 * @param ForeignKey $addedFk
	 */
	function addAddedFk($fkName, ForeignKey $addedFk)
	{
		$this->addedFks[$fkName] = $addedFk;
	}

	/**
	 * Remove an added Fk column
	 *
	 * @param string $fkName
	 */
	function removeAddedFk($fkName)
	{
		unset($this->addedFks[$fkName]);
	}

	/**
	 * Getter for the addedFks property
	 *
	 * @return array
	 */
	function getAddedFks()
	{
		return $this->addedFks;
	}

	/**
	 * Setter for the removedFks property
	 *
	 * @param  $removedFks
	 */
	function setRemovedFks($removedFks)
	{
		$this->removedFks = $removedFks;
	}

	/**
	 * Add a removed Fk column
	 *
	 * @param string $fkName
	 * @param ForeignKey $removedColumn
	 */
	function addRemovedFk($fkName, ForeignKey $removedFk)
	{
		$this->removedFks[$fkName] = $removedFk;
	}

	/**
	 * Remove a removed Fk column
	 *
	 * @param string $fkName
	 */
	function removeRemovedFk($fkName)
	{
		unset($this->removedFks[$columnName]);
	}

	/**
	 * Getter for the removedFks property
	 *
	 * @return array
	 */
	function getRemovedFks()
	{
		return $this->removedFks;
	}

	/**
	 * Setter for the modifiedFks property
	 *
	 * @param array $modifiedFks
	 */
	function setModifiedFks($modifiedFks)
	{
		$this->modifiedFks = $modifiedFks;
	}

	/**
	 * Add a modified Fk
	 *
	 * @param string $fkName
	 * @param ForeignKey $fromFk
	 * @param ForeignKey $toFk
	 */
	function addModifiedFk($fkName, ForeignKey $fromFk, ForeignKey $toFk)
	{
		$this->modifiedFks[$fkName] = array($fromFk, $toFk);
	}

	/**
	 * Getter for the modifiedFks property
	 *
	 * @return array
	 */
	function getModifiedFks()
	{
		return $this->modifiedFks;
	}
	
}
