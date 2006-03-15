<?php

/*
 *  $Id: OMBuilder.php 186 2005-09-08 13:33:09Z hans $
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
 * DDL Builder class for MySQL.
 *
 * @author David Zülke
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.sql.mysql
 */
class MysqlDDLBuilder extends DDLBuilder {

	/**
	 * Returns some header SQL that disables foreign key checking.
	 * @return string DDL
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
	 * @return string DDL
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
	 *
	 * @see parent::addDropStatement()
	 */
	protected function addDropStatements(&$script)
	{
		$script .= "
DROP TABLE IF EXISTS ".$this->quoteIdentifier($this->getTable()->getName()).";
";
	}

	/**
	 * Builds the SQL for current table and returns it as a string.
	 *
	 * This is the main entry point and defines a basic structure that classes should follow.
	 * In most cases this method will not need to be overridden by subclasses.
	 *
	 * @return string The resulting SQL DDL.
	 */
	public function build()
	{
		$script = "";
		$this->addTable($script);
		return $script;
	}

	/**
	 *
	 * @see parent::addColumns()
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

		$this->addDropStatements($script);

		$script .= "

CREATE TABLE ".$this->quoteIdentifier($table->getName())."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			$entry = $this->getColumnDDL($col);
			if ($col->getDescription()) {
				$entry .= " COMMENT '".$platform->escapeText($col->getDescription())."'";
			}
			$lines[] = $entry;
		}

		if ($table->hasPrimaryKey()) {
			$lines[] = "PRIMARY KEY (".$this->getColumnList($table->getPrimaryKey()).")";
		}

		$this->addIndicesLines($lines);
		$this->addForeignKeysLines($lines);

		$sep = ",
	";
		$script .= implode($sep, $lines);

		$script .= "
)";

		$mysqlTableType = $this->getBuildProperty("mysqlTableType");
		if (!$mysqlTableType) {
			$vendorSpecific = $table->getVendorSpecificInfo();
			if(isset($vendorSpecific['Type'])) {
				$mysqlTableType = $vendorSpecific['Type'];
			} else {
				$mysqlTableType = 'MyISAM';
			}
		}

		$script .= "Type=$mysqlTableType";
		if($table->getDescription()) {
			$script .= " COMMENT='".$platform->escapeText($table->getDescription())."'";
		}
		$script .= ";
";
	}

	/**
	 * Creates a comma-separated list of column names for the index.
	 * For MySQL unique indexes there is the option of specifying size, so we cannot simply use
	 * the getColumnsList() method.
	 * @param Index $index
	 * @return string
	 */
	private function getIndexColumnList(Index $index)
	{
		$platform = $this->getPlatform();

		$cols = $index->getColumns();
		$list = array();
		foreach($cols as $col) {
			$list[] = $this->quoteIdentifier($col) . ($index->hasColumnSize($col) ? '(' . $index->getColumnSize($col) . ')' : '');
		}
		return implode(', ', $list);
	}

	/**
	 * Adds indexes
	 */
	protected function addIndicesLines(&$lines)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($table->getUnices() as $unique) {
			$lines[] = "UNIQUE KEY ".$this->quoteIdentifier($unique->getName())." (".$this->getIndexColumnList($unique).")";
		}

		foreach ($table->getIndices() as $index ) {
			$vendor = $index->getVendorSpecificInfo();
			$lines[] .= (($vendor && $vendor['Index_type'] == 'FULLTEXT') ? 'FULLTEXT ' : '') . "KEY " . $this->quoteIdentifier($index->getName()) . "(" . $this->getIndexColumnList($index) . ")";
		}

	}

	/**
	 * Adds foreign key declarations & necessary indexes for mysql (if they don't exist already).
	 * @see parent::addForeignKeys()
	 */
	protected function addForeignKeysLines(&$lines)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();


		$_indices = array();
		$_previousColumns = array();

		// we're building an array of indices here which is smart about multi-column indices.
		// for example, if we have to indices foo(ColA) and bar(ColB, ColC), we have actually three indices already defined:
		// ColA, ColB+ColC, and ColB (but not ColC!). This is because of the way SQL multi-column indices work.
		// we will later match found, defined foreign key and referenced column definitions against this array to know
		// whether we should create a new index for mysql or not
		foreach($table->getPrimaryKey() as $_primaryKeyColumn) {
			// do the above for primary keys
			$_previousColumns[] = $this->quoteIdentifier($_primaryKeyColumn->getName());
			$_indices[] = implode(',', $_previousColumns);
		}

		$_tableIndices = array_merge($table->getIndices(), $table->getUnices());
		foreach($_tableIndices as $_index) {
			// same procedure, this time for unices and indices
			$_previousColumns = array();
			$_indexColumns = $_index->getColumns();
			foreach($_indexColumns as $_indexColumn) {
				$_previousColumns[] = $this->quoteIdentifier($_indexColumn);
				$_indices[] = implode(',', $_previousColumns);
			}
		}

		// we're determining which tables have foreign keys that point to this table, since MySQL needs an index on
		// any column that is referenced by another table (yep, MySQL _is_ a PITA)
		$counter = 0;
		$allTables = $table->getDatabase()->getTables();
		foreach($allTables as $_table) {
			foreach($_table->getForeignKeys() as $_foreignKey) {
				if($_foreignKey->getForeignTableName() == $table->getName()) {
					if(!in_array($this->getColumnList($_foreignKey->getForeignColumns()), $_indices)) {
						// no matching index defined in the schema, so we have to create one
						$lines[] = "INDEX ".$this->quoteIdentifier("I_referenced_".$_foreignKey->getName()."_".(++$counter))." (" .$this->getColumnList($_foreignKey->getForeignColumns()).")";
					}
				}
			}
		}

		foreach ($table->getForeignKeys() as $fk) {

			$indexName = $this->quoteIdentifier(substr_replace($fk->getName(), 'FI_',  strrpos($fk->getName(), 'FK_'), 3));

			if(!in_array($this->getColumnList($fk->getLocalColumns()), $_indices)) {
				// no matching index defined in the schema, so we have to create one. MySQL needs indices on any columns that serve as foreign keys. these are not auto-created prior to 4.1.2
				$lines[] = "INDEX $indexName (".$this->getColumnList($fk->getLocalColumns()).")";
			}
			$str = "CONSTRAINT ".$this->quoteIdentifier($fk->getName())."
		FOREIGN KEY (".$this->getColumnList($fk->getLocalColumns()).")
		REFERENCES ".$this->quoteIdentifier($fk->getForeignTableName()) . " (".$this->getColumnList($fk->getForeignColumns()).")";
			if ($fk->hasOnUpdate()) {
				$str .= "
		ON UPDATE ".$fk->getOnUpdate();
			}
			if ($fk->hasOnDelete()) {
				$str .= "
		ON DELETE ".$fk->getOnDelete();
			}
			$lines[] = $str;
		}

	}

	/**
	 * Checks whether passed-in array of Column objects contains a column with specified name.
	 * @param array Column[] or string[]
	 * @param string $searchcol Column name to search for
	 */
	private function containsColname($columns, $searchcol)
	{
		foreach($columns as $col) {
			if ($col instanceof Column) {
				$col = $col->getName();
			}
			if ($col == $searchcol) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Not used for MySQL since foreign keys are declared inside table declaration.
	 * @see addForeignKeysLines()
	 */
	protected function addForeignKeys(&$script)
	{
	}

	/**
	 * Not used for MySQL since indexes are declared inside table declaration.
	 * @see addIndicesLines()
	 */
	protected function addIndices(&$script)
	{
	}

}