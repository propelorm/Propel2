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
 * DDL Builder class for MySQL.
 *
 * @author     David Z�lke
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.builder.sql.mysql
 */
class MysqlDDLBuilder extends DDLBuilder
{

	/**
	 * Returns some header SQL that disables foreign key checking.
	 * @return     string DDL
	 */
	public static function getDatabaseStartDDL()
	{
		$ddl = "
# This is a fix for InnoDB in MySQL >= 4.1.x
# It \"suspends judgement\" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;
";
		return $ddl;
	}

	/**
	 * Returns some footer SQL that re-enables foreign key checking.
	 * @return     string DDL
	 */
	public static function getDatabaseEndDDL()
	{
		$ddl = "
# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
";
		return $ddl;
	}

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
		return $script;
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
#-----------------------------------------------------------------------------
#-- ".$table->getName()."
#-----------------------------------------------------------------------------
";

		$script .= $platform->getDropTableDDL($table);

		$script .= "

CREATE TABLE ".$this->quoteIdentifier($table->getName())."
(
	";

		$lines = array();

		$databaseType = $platform->getDatabaseType();

		foreach ($table->getColumns() as $col) {
			$lines []= $platform->getColumnDDL($col);
		}

		if ($table->hasPrimaryKey()) {
			$lines[] = $platform->getPrimaryKeyDDL($table);
		}

		$this->addIndicesLines($lines);
		$this->addForeignKeysLines($lines);

		$sep = ",
	";
		$script .= implode($sep, $lines);

		$script .= "
)";

		$vendorSpecific = $table->getVendorInfoForType($databaseType);
		if ($vendorSpecific->hasParameter('Type')) {
			$mysqlTableType = $vendorSpecific->getParameter('Type');
		} elseif ($vendorSpecific->hasParameter('Engine')) {
			$mysqlTableType = $vendorSpecific->getParameter('Engine');
		} else {
			$mysqlTableType = $this->getBuildProperty("mysqlTableType");
		}

		$script .= sprintf(' %s=%s', $this->getBuildProperty("mysqlTableEngineKeyword"), $mysqlTableType);

		$dbVendorSpecific = $table->getDatabase()->getVendorInfoForType($databaseType);
		$tableVendorSpecific = $table->getVendorInfoForType($databaseType);
		$vendorSpecific = $dbVendorSpecific->getMergedVendorInfo($tableVendorSpecific);

		if ( $vendorSpecific->hasParameter('Charset') ) {
			$script .= ' CHARACTER SET '.$platform->quote($vendorSpecific->getParameter('Charset'));
		}
		if ( $vendorSpecific->hasParameter('Collate') ) {
			$script .= ' COLLATE '.$platform->quote($vendorSpecific->getParameter('Collate'));
		}
		if ( $vendorSpecific->hasParameter('Checksum') ) {
			$script .= ' CHECKSUM='.$platform->quote($vendorSpecific->getParameter('Checksum'));
		}
		if ( $vendorSpecific->hasParameter('Pack_Keys') ) {
			$script .= ' PACK_KEYS='.$platform->quote($vendorSpecific->getParameter('Pack_Keys'));
		}
		if ( $vendorSpecific->hasParameter('Delay_key_write') ) {
			$script .= ' DELAY_KEY_WRITE='.$platform->quote($vendorSpecific->getParameter('Delay_key_write'));
		}

		if ($table->getDescription()) {
			$script .= " COMMENT=".$platform->quote($table->getDescription());
		}
		$script .= ";
";
	}

	/**
	 * Adds indexes
	 */
	protected function addIndicesLines(&$lines)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($table->getUnices() as $unique) {
			$lines[] = $platform->getUniqueDDL($unique);
		}

		foreach ($table->getIndices() as $index ) {
			$lines[] = $platform->getIndexDDL($index);
		}

	}

	/**
	 * Adds foreign key declarations & necessary indexes for mysql (if they don't exist already).
	 * @see        parent::addForeignKeys()
	 */
	protected function addForeignKeysLines(&$lines)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();
		foreach ($table->getForeignKeys() as $foreignKey) {
			$lines[] = str_replace("
	", "
		", $platform->getForeignKeyDDL($foreignKey));
		}
	}
	
	/**
	 * Not used for MySQL since foreign keys are declared inside table declaration.
	 * @see        addForeignKeysLines()
	 */
	protected function addForeignKeys(&$script)
	{
	}

	/**
	 * Not used for MySQL since indexes are declared inside table declaration.
	 * @see        addIndicesLines()
	 */
	protected function addIndices(&$script)
	{
	}

	/**
	 * Builds the DDL SQL for a Column object.
	 * @return     string
	 * @deprecated since 1.6, use MysqlPlatform::getColumnDDL() instead
	 */
	public function getColumnDDL(Column $col)
	{
		return $this->getPlatform()->getColumnDDL($col);
	}
}
