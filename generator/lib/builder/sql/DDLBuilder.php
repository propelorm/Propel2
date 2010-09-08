<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'builder/DataModelBuilder.php';

/**
 * Baseclass for SQL DDL-building classes.
 *
 * DDL-building classes are those that build all the SQL DDL for a single table.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.builder.sql
 */
abstract class DDLBuilder extends DataModelBuilder
{

	/**
	 * Builds the SQL for current table and returns it as a string.
	 *
	 * This is the main entry point and defines a basic structure that classes should follow.
	 * In most cases this method will not need to be overridden by subclasses.
	 *
	 * @return     string The resulting SQL DDL.
	 */
	public function build()
	{
		$script = "";
		$this->addTable($script);
		$this->addIndices($script);
		$this->addForeignKeys($script);
		return $script;
	}

	/**
	 * Builds the DDL SQL for a Column object.
	 * @return     string
	 * @deprecated since 1.6, use DefaultPlatform::getColumnDDL() instead
	 */
	public function getColumnDDL(Column $col)
	{
		return $this->getPlatform()->getColumnDDL($col);
	}

	/**
	 * Creates a delimiter-delimited string list of column names, quoted using quoteIdentifier().
	 * @deprecated use Platform::getColumnList() instead
	 * @param      array Column[] or string[]
	 * @param      string $delim The delimiter to use in separating the column names.
	 * @return     string
	 */
	public function getColumnList($columns, $delim=',')
	{
		$list = array();
		foreach ($columns as $col) {
			if ($col instanceof Column) {
				$col = $col->getName();
			}
			$list[] = $this->quoteIdentifier($col);
		}
		return implode($delim, $list);
	}

	/**
	 * This function adds any _database_ start/initialization SQL.
	 * This is designed to be called for a database, not a specific table, hence it is static.
	 * @return     string The DDL is returned as astring.
	 */
	public static function getDatabaseStartDDL()
	{
		return '';
	}

	/**
	 * This function adds any _database_ end/cleanup SQL.
	 * This is designed to be called for a database, not a specific table, hence it is static.
	 * @return     string The DDL is returned as astring.
	 */
	public static function getDatabaseEndDDL()
	{
		return '';
	}

	/**
	 * Resets any static variables between building a SQL file for a database.
	 *
	 * Theoretically, Propel could build multiple .sql files for multiple databases; in
	 * many cases we don't want static values to persist between these.  This method provides
	 * a way to clear out static values between iterations, if the subclasses choose to implement
	 * it.
	 */
	public static function reset()
	{
		// nothing by default
	}

	/**
	 * Adds table definition.
	 * @param      string &$script The script will be modified in this method.
	 */
	abstract protected function addTable(&$script);

	/**
	 * Adds CREATE INDEX statements for this table.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addIndices(&$script)
	{
		foreach ($this->getTable()->getIndices() as $index) {
			$script .= $this->getPlatform()->getAddIndexDDL($index);
		}
	}

	/**
	 * Adds foreign key constraint definitions.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addForeignKeys(&$script)
	{
		$script .= $this->getPlatform()->getAddForeignKeysDDL($this->getTable());
	}

}
