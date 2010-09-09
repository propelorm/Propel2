<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license     MIT License
 */

require_once dirname(__FILE__) . '/../Database.php';
require_once dirname(__FILE__) . '/PropelTableDiff.php';

/**
 * Value object for storing Database object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 * @package    propel.generator.model.diff
 */
class PropelDatabaseDiff
{
	protected $addedTables = array();
	protected $removedTables = array();
	protected $modifiedTables = array();
	protected $renamedTables = array();
	
	/**
	 * Setter for the addedTables property
	 *
	 * @param array $addedTables
	 */
	function setAddedTables($addedTables)
	{
		$this->addedTables = $addedTables;
	}

	/**
	 * Add an added table
	 *
	 * @param string $tableName
	 * @param Table $addedTable
	 */
	function addAddedTable($tableName, Table $addedTable)
	{
		$this->addedTables[$tableName] = $addedTable;
	}

	/**
	 * Remove an added table
	 *
	 * @param string $tableName
	 */
	function removeAddedTable($tableName)
	{
		unset($this->addedTables[$tableName]);
	}

	/**
	 * Getter for the addedTables property
	 *
	 * @return array
	 */
	function getAddedTables()
	{
		return $this->addedTables;
	}

	/**
	 * Get an added table
	 *
	 * @param string $tableName
	 *
	 * @param Table
	 */
	function getAddedTable($tableName)
	{
		return $this->addedTables[$tableName];
	}

	/**
	 * Setter for the removedTables property
	 *
	 * @param array $removedTables
	 */
	function setRemovedTables($removedTables)
	{
		$this->removedTables = $removedTables;
	}

	/**
	 * Add a removed table
	 *
	 * @param string $tableName
	 * @param Table $removedTable
	 */
	function addRemovedTable($tableName, Table $removedTable)
	{
		$this->removedTables[$tableName] = $removedTable;
	}

	/**
	 * Remove a removed table
	 *
	 * @param string $tableName
	 */
	function removeRemovedTable($tableName)
	{
		unset($this->removedTables[$tableName]);
	}

	/**
	 * Getter for the removedTables property
	 *
	 * @return array
	 */
	function getRemovedTables()
	{
		return $this->removedTables;
	}

	/**
	 * Get a removed table
	 *
	 * @param string $tableName
	 *
	 * @param Table
	 */
	function getRemovedTable($tableName)
	{
		return $this->removedTables[$tableName];
	}

	/**
	 * Setter for the modifiedTables property
	 *
	 * @param array $modifiedTables
	 */
	function setModifiedTables($modifiedTables)
	{
		$this->modifiedTables = $modifiedTables;
	}

	/**
	 * Add a table difference
	 *
	 * @param string $tableName
	 * @param PropelTableDiff $modifiedTable
	 */
	function addModifiedTable($tableName, PropelTableDiff $modifiedTable)
	{
		$this->modifiedTables[$tableName] = $modifiedTable;
	}

	/**
	 * Getter for the modifiedTables property
	 *
	 * @return array
	 */
	function getModifiedTables()
	{
		return $this->modifiedTables;
	}

	/**
	 * Setter for the renamedTables property
	 *
	 * @param array $renamedTables
	 */
	function setRenamedTables($renamedTables)
	{
		$this->renamedTables = $renamedTables;
	}

	/**
	 * Add a renamed table
	 *
	 * @param string $fromName
	 * @param string $toName
	 */
	function addRenamedTable($fromName, $toName)
	{
		$this->renamedTables[$fromName] = $toName;
	}

	/**
	 * Getter for the renamedTables property
	 *
	 * @return array
	 */
	function getRenamedTables()
	{
		return $this->renamedTables;
	}

}
