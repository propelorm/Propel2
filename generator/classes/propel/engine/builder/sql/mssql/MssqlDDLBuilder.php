<?php

/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'propel/engine/builder/sql/DDLBuilder.php';

/**
 * The SQL DDL-building class for MS SQL Server.
 *
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.sql.pgsql
 */
class MssqlDDLBuilder extends DDLBuilder {

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
	ALTER TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." DROP CONSTRAINT ".$this->quoteIdentifier($fk->getName()).";
";
		}


		self::$dropCount++;

		$script .= "
IF EXISTS (SELECT 1 FROM sysobjects WHERE type = 'U' AND name = '".$this->prefixTablename($table->getName())."')
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
		 and tables.name = '".$this->prefixTablename($table->getName())."'
	 OPEN refcursor
	 FETCH NEXT from refcursor into @reftable_".self::$dropCount.", @constraintname_".self::$dropCount."
	 while @@FETCH_STATUS = 0
	 BEGIN
	   exec ('alter table '+@reftable_".self::$dropCount."+' drop constraint '+@constraintname_".self::$dropCount.")
	   FETCH NEXT from refcursor into @reftable_".self::$dropCount.", @constraintname_".self::$dropCount."
	 END
	 CLOSE refcursor
	 DEALLOCATE refcursor
	 DROP TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))."
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

CREATE TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			$lines[] = $this->getColumnDDL($col);
		}

		if ($table->hasPrimaryKey()) {
			$lines[] = "CONSTRAINT ".$this->quoteIdentifier($table->getName()."_PK") . " PRIMARY KEY (".$this->getColumnList($table->getPrimaryKey()).")";
		}

		foreach ($table->getUnices() as $unique ) {
			$lines[] = "UNIQUE (".$this->getColumnList($unique->getColumns()).")";
		}

		$sep = ",
	";
		$script .= implode($sep, $lines);
		$script .= "
);
";
	}

	/**
	 * Adds CREATE INDEX statements for this table.
	 * @see        parent::addIndices()
	 */
	protected function addIndices(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($table->getIndices() as $index) {
			$script .= "
CREATE ";
			if ($index->getIsUnique()) {
				$script .= "UNIQUE";
			}
			$script .= "INDEX ".$this->quoteIdentifier($index->getName())." ON ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." (".$this->getColumnList($index->getColumns()).");
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
		$platform = $this->getPlatform();

		foreach ($table->getForeignKeys() as $fk) {
			$script .= "
BEGIN
ALTER TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." ADD CONSTRAINT ".$this->quoteIdentifier($fk->getName())." FOREIGN KEY (".$this->getColumnList($fk->getLocalColumns()) .") REFERENCES ".$this->quoteIdentifier($this->prefixTablename($fk->getForeignTableName()))." (".$this->getColumnList($fk->getForeignColumns()).")";
			if ($fk->hasOnUpdate()) {
				if ($fk->getOnUpdate() == ForeignKey::SETNULL) { // there may be others that also won't work
					// we have to skip this because it's unsupported.
					$this->warn("MSSQL doesn't support the 'SET NULL' option for ON UPDATE (ignoring for ".$this->getColumnList($fk->getLocalColumns())." fk).");
				} else {
					$script .= " ON UPDATE ".$fk->getOnUpdate();
				}

			}
			if ($fk->hasOnDelete()) {
				if ($fk->getOnDelete() == ForeignKey::SETNULL) { // there may be others that also won't work
					// we have to skip this because it's unsupported.
					$this->warn("MSSQL doesn't support the 'SET NULL' option for ON DELETE (ignoring for ".$this->getColumnList($fk->getLocalColumns())." fk).");
				} else {
					$script .= " ON DELETE ".$fk->getOnDelete();
				}
			}
			$script .= "
END
;
";
		}
	}

}
