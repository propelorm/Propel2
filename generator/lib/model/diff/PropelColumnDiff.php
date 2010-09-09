<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license     MIT License
 */

require_once dirname(__FILE__) . '/../Column.php';

/**
 * Value object for storing Column object diffs.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 * @package    propel.generator.model.diff
 */
class PropelColumnDiff
{
	protected $changedProperties = array();
	protected $fromColumn;
	protected $toColumn;
	
	/**
	 * Setter for the changedProperties property
	 *
	 * @param array $changedProperties
	 */
	function setChangedProperties($changedProperties)
	{
		$this->changedProperties = $changedProperties;
	}

	/**
	 * Getter for the changedProperties property
	 *
	 * @return array
	 */
	function getChangedProperties()
	{
		return $this->changedProperties;
	}
	
	/**
	 * Setter for the fromColumn property
	 *
	 * @param Column $fromColumn
	 */
	function setFromColumn(Column $fromColumn)
	{
		$this->fromColumn = $fromColumn;
	}
	
	/**
	 * Getter for the fromColumn property
	 *
	 * @return Column
	 */
	function getFromColumn()
	{
		return $this->fromColumn;
	}
	
	/**
	 * Setter for the toColumn property
	 *
	 * @param Column $toColumn
	 */
	function setToColumn(Column $toColumn)
	{
		$this->toColumn = $toColumn;
	}
	
	/**
	 * Getter for the toColumn property
	 *
	 * @return Column
	 */
	function getToColumn()
	{
		return $this->toColumn;
	}
	
}
