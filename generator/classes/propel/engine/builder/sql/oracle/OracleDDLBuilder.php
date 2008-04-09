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
 * The SQL DDL-building class for Oracle.
 *
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.sql.pgsql
 */
class OracleDDLBuilder extends DDLBuilder {

	/**
	 *
	 * @see        parent::addDropStatement()
	 */
	protected function addDropStatements(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();
		$script .= "
DROP TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." CASCADE CONSTRAINTS;
";
		if ($table->getIdMethod() == "native") {
			$script .= "
DROP SEQUENCE ".$this->quoteIdentifier($this->prefixTablename($this->getSequenceName())).";
";
		}
	}

	/**
	 *
	 * @see        parent::addColumns()
	 */
	protected function addTable(&$script)
	{
		$table = $this->getTable();
		$script .= "

/* -----------------------------------------------------------------------
   ".$table->getName()."
   ----------------------------------------------------------------------- */
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
		$tableName = $table->getName();
		$length = strlen($tableName);
		if ($length > 27) {
			$length = 27;
		}
		if ( is_array($table->getPrimaryKey()) && count($table->getPrimaryKey()) ) {
			$script .= "
	ALTER TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))."
		ADD CONSTRAINT ".$this->quoteIdentifier(substr($tableName,0,$length)."_PK")."
	PRIMARY KEY (";
			$delim = "";
			foreach ($table->getPrimaryKey() as $col) {
				$script .= $delim . $this->quoteIdentifier($col->getName());
				$delim = ",";
			}
	$script .= ");
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
			$script .= "CREATE SEQUENCE ".$this->quoteIdentifier($this->prefixTablename($this->getSequenceName()))." INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";
		}
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
			$script .= "CREATE ";
			if ($index->getIsUnique()) {
				$script .= "UNIQUE";
			}
			$script .= "INDEX ".$this->quoteIdentifier($index->getName()) ." ON ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." (".$this->getColumnList($index->getColumns()).");
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
ALTER TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." ADD CONSTRAINT ".$this->quoteIdentifier($fk->getName())." FOREIGN KEY (".$this->getColumnList($fk->getLocalColumns()) .") REFERENCES ".$this->quoteIdentifier($this->prefixTablename($fk->getForeignTableName()))." (".$this->getColumnList($fk->getForeignColumns()).")";
			if ($fk->hasOnUpdate()) {
				$this->warn("ON UPDATE not yet implemented for Oracle builder.(ignoring for ".$this->getColumnList($fk->getLocalColumns())." fk).");
				//$script .= " ON UPDATE ".$fk->getOnUpdate();
			}
			if ($fk->hasOnDelete()) {
				$script .= " ON DELETE ".$fk->getOnDelete();
			}
			$script .= ";
";
		}
	}


}
