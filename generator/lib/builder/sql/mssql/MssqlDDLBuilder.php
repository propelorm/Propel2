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

	private static $dropCount = 0;

	/**
	 *
	 * @see        parent::addDropStatement()
	 */
	protected function addDropStatements(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($table->getForeignKeys() as $fk) {
			$script .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type ='RI' AND name='".$fk->getName()."')
	ALTER TABLE ".$this->quoteIdentifier($table->getName())." DROP CONSTRAINT ".$this->quoteIdentifier($fk->getName()).";
";
		}


		self::$dropCount++;

		$script .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = '".$table->getName()."')
BEGIN
	 DECLARE @reftable_".self::$dropCount." nvarchar(60), @constraintname_".self::$dropCount." nvarchar(60)
	 DECLARE refcursor CURSOR FOR
	 select reftables.name tablename, cons.name constraintname
	  from sysobjects tables,
		   sysobjects reftables,
		   sysobjects cons,
		   sysreferences ref
	   where tables.id = ref.rkeyid
		 and cons.id = ref.constid
		 and reftables.id = ref.fkeyid
		 and tables.name = '".$table->getName()."'
	 OPEN refcursor
	 FETCH NEXT from refcursor into @reftable_".self::$dropCount.", @constraintname_".self::$dropCount."
	 while @@FETCH_STATUS = 0
	 BEGIN
	   exec ('alter table '+@reftable_".self::$dropCount."+' drop constraint '+@constraintname_".self::$dropCount.")
	   FETCH NEXT from refcursor into @reftable_".self::$dropCount.", @constraintname_".self::$dropCount."
	 END
	 CLOSE refcursor
	 DEALLOCATE refcursor
	 DROP TABLE ".$this->quoteIdentifier($table->getName())."
END
";
	}

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

		$this->addDropStatements($script);

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
