<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once 'builder/sql/DDLBuilder.php';

/**
 * The SQL DDL-building class for Oracle.
 *
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.builder.sql.pgsql
 */
class OracleDDLBuilder extends DDLBuilder
{

	/**
	 * This function adds any _database_ start/initialization SQL.
	 * This is designed to be called for a database, not a specific table, hence it is static.
	 * @see        parent::getDatabaseStartDDL()
	 *
	 * @return     string The DDL is returned as astring.
	 */
	public static function getDatabaseStartDDL()
	{
		return "
ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD';
ALTER SESSION SET NLS_TIMESTAMP_FORMAT='YYYY-MM-DD HH24:MI:SS';
";
	}

	/**
	 *
	 * @see        parent::addColumns()
	 */
	protected function addTable(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();
		
		$script .= "

-----------------------------------------------------------------------
-- ".$table->getName()."
-----------------------------------------------------------------------
";

		$script .= $platform->getDropTableDDL($table);

		$script .= "
CREATE TABLE ".$this->quoteIdentifier($table->getName())."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			$lines[] = $platform->getColumnDDL($col);
		}
		
		foreach ($table->getUnices() as $unique ) {
			$lines[] = $platform->getUniqueDDL($unique);
		}

		$sep = ",
	";
		$script .= implode($sep, $lines);
		$script .= "
);
";
		$this->addPrimaryKey($script);
		$this->addSequences($script);

	}

	/**
	 *
	 *
	 */
	protected function addPrimaryKey(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();
		if (is_array($table->getPrimaryKey()) && count($table->getPrimaryKey())) {
			$script .= "
ALTER TABLE " . $this->quoteIdentifier($table->getName()) . "
	ADD " . $platform->getPrimaryKeyDDL($table) . ";
";
		}
	}

	/**
	 * Adds CREATE SEQUENCE statements for this table.
	 *
	 */
	protected function addSequences(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();
		if ($table->getIdMethod() == "native") {
			$script .= "
CREATE SEQUENCE ".$this->quoteIdentifier($platform->getSequenceName($table))."
	INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";
		}
	}

	/**
	 *
	 * @see        parent::addForeignKeys()
	 */
	protected function addForeignKeys(&$script)
	{
		$table = $this->getTable();
		foreach ($table->getForeignKeys() as $fk) {
			if ($fk->hasOnUpdate()) {
				$this->warn(sprintf('ON UPDATE not yet implemented for Foreign Keys on Oracle builder (ignoring for %s)', $fk->getName()));
			}
		}
		$script .= $this->getPlatform()->getAddForeignKeysDDL($table);
	}


}
