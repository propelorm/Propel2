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
 * The SQL DDL-building class for MS SQL Server.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.builder.sql.pgsql
 */
class MssqlDDLBuilder extends DDLBuilder
{

	/**
	 * @see        parent::addColumns()
	 */
	protected function addTable(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		$script .= "
/* ---------------------------------------------------------------------- */
/* ".$table->getName()."											*/
/* ---------------------------------------------------------------------- */

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

		if ($table->hasPrimaryKey()) {
			$lines[] = $platform->getPrimaryKeyDDL($table);
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
	}

	/**
	 *
	 * @see        parent::addForeignKeys()
	 */
	protected function addForeignKeys(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();
		$pattern = "
BEGIN
ALTER TABLE %s ADD %s
END
;
";
		foreach ($table->getForeignKeys() as $fk) {
			$script .= sprintf($pattern, 
				$this->quoteIdentifier($table->getName()),
				$platform->getForeignKeyDDL($fk)
			);
			if ($fk->hasOnUpdate() && $fk->getOnUpdate() == ForeignKey::SETNULL) { // there may be others that also won't work
				// we have to skip this because it's unsupported.
					$this->warn("MSSQL doesn't support the 'SET NULL' option for ON UPDATE (ignoring for ".$this->getColumnList($fk->getLocalColumns())." fk).");
			}
			if ($fk->hasOnDelete() && $fk->getOnDelete() == ForeignKey::SETNULL) { // there may be others that also won't work
				// we have to skip this because it's unsupported.
				$this->warn("MSSQL doesn't support the 'SET NULL' option for ON DELETE (ignoring for ".$this->getColumnList($fk->getLocalColumns())." fk).");
			}
		}
	}

}
