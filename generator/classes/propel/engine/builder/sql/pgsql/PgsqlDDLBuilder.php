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
 * The SQL DDL-building class for PostgreSQL.
 *
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.sql.pgsql
 */
class PgsqlDDLBuilder extends DDLBuilder {


	/**
	 * Array that keeps track of already
	 * added schema names
	 *
	 * @var        Array of schema names
	 */
	protected static $addedSchemas = array();

	/**
	 * Queue of constraint SQL that will be added to script at the end.
	 *
	 * PostgreSQL seems (now?) to not like constraints for tables that don't exist,
	 * so the solution is to queue up the statements and execute it at the end.
	 *
	 * @var        array
	 */
	protected static $queuedConstraints = array();

	/**
	 * Reset static vars between db iterations.
	 */
	public static function reset()
	{
		self::$addedSchemas = array();
		self::$queuedConstraints = array();
	}

	/**
	 * Returns all the ALTER TABLE ADD CONSTRAINT lines for inclusion at end of file.
	 * @return     string DDL
	 */
	public static function getDatabaseEndDDL()
	{
		$ddl = implode("", self::$queuedConstraints);
		return $ddl;
	}

	/**
	 * Get the schema for the current table
	 *
	 * @author     Markus Lervik <markus.lervik@necora.fi>
	 * @access     protected
	 * @return     schema name if table has one, else
	 *         null
	 **/
	protected function getSchema()
	{
		$table = $this->getTable();
		$vi = $table->getVendorInfoForType($this->getPlatform()->getDatabaseType());
		if ($vi->hasParameter('schema')) {
			return $vi->getParameter('schema');
		}
		return null;
	}

	/**
	 * Add a schema to the generated SQL script
	 *
	 * @author     Markus Lervik <markus.lervik@necora.fi>
	 * @access     protected
	 * @return     string with CREATE SCHEMA statement if
	 *         applicable, else empty string
	 **/
	protected function addSchema()
	{

		$schemaName = $this->getSchema();

		if ($schemaName !== null) {

			if (!in_array($schemaName, self::$addedSchemas)) {
		$platform = $this->getPlatform();
				self::$addedSchemas[] = $schemaName;
		return "\nCREATE SCHEMA " . $this->quoteIdentifier($schemaName) . ";\n";
			}
		}

		return '';

	}

	/**
	 *
	 * @see        parent::addDropStatement()
	 */
	protected function addDropStatements(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		$script .= "
DROP TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." CASCADE;
";

		if ($table->getIdMethod() == IDMethod::NATIVE && $table->getIdMethodParameters()) {
			$script .= "
DROP SEQUENCE ".$this->quoteIdentifier($this->prefixTablename(strtolower($this->getSequenceName()))).";
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
		$platform = $this->getPlatform();

		$script .= "
-----------------------------------------------------------------------------
-- ".$table->getName()."
-----------------------------------------------------------------------------
";

		$script .= $this->addSchema();

		$schemaName = $this->getSchema();
		if ($schemaName !== null) {
			$script .= "\nSET search_path TO " . $this->quoteIdentifier($schemaName) . ";\n";
		}

		$this->addDropStatements($script);
		$this->addSequences($script);

		$script .= "

CREATE TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			/* @var $col Column */
			$colDDL = $this->getColumnDDL($col);
			if ($col->isAutoIncrement() && $table->getIdMethodParameters() == null) {
				if ($col->getType() === PropelTypes::BIGINT) {
					$colDDL = str_replace($col->getDomain()->getSqlType(), 'bigserial', $colDDL);
				} else {
					$colDDL = str_replace($col->getDomain()->getSqlType(), 'serial', $colDDL);
				}
			}
			$lines[] = $colDDL;
		}

		if ($table->hasPrimaryKey()) {
			$lines[] = "PRIMARY KEY (".$this->getColumnList($table->getPrimaryKey()).")";
		}

		foreach ($table->getUnices() as $unique ) {
			$lines[] = "CONSTRAINT ".$this->quoteIdentifier($unique->getName())." UNIQUE (".$this->getColumnList($unique->getColumns()).")";
		}

		$sep = ",
	";
		$script .= implode($sep, $lines);
		$script .= "
);

COMMENT ON TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." IS " . $platform->quote($table->getDescription()).";

";

		$this->addColumnComments($script);

		$script .= "\nSET search_path TO public;";

	}

	/**
	 * Adds comments for the columns.
	 *
	 */
	protected function addColumnComments(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($this->getTable()->getColumns() as $col) {
			if ( $col->getDescription() != '' ) {
				$script .= "
COMMENT ON COLUMN ".$this->quoteIdentifier($this->prefixTablename($table->getName())).".".$this->quoteIdentifier($col->getName())." IS ".$platform->quote($col->getDescription()) .";
";
			}
		}
	}

	/**
	 * Override to provide sequence names that conform to postgres' standard when
	 * no id-method-parameter specified.
	 *
	 * @see        DataModelBuilder::getSequenceName()
	 * @return     string
	 */
	public function getSequenceName()
	{
		$table = $this->getTable();
		static $longNamesMap = array();
		$result = null;
		if ($table->getIdMethod() == IDMethod::NATIVE) {
			$idMethodParams = $table->getIdMethodParameters();
			if (empty($idMethodParams)) {
				$result = null;
				// We're going to ignore a check for max length (mainly
				// because I'm not sure how Postgres would handle this w/ SERIAL anyway)
				foreach ($table->getColumns() as $col) {
					if ($col->isAutoIncrement()) {
						$result = $table->getName() . '_' . $col->getName() . '_seq';
						break; // there's only one auto-increment column allowed
					}
				}
			} else {
				$result = $idMethodParams[0]->getValue();
			}
		}
		return $result;
	}

	/**
	 * Adds CREATE SEQUENCE statements for this table.
	 *
	 */
	protected function addSequences(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		if ($table->getIdMethod() == IDMethod::NATIVE && $table->getIdMethodParameters() != null) {
			$script .= "
CREATE SEQUENCE ".$this->quoteIdentifier($this->prefixTablename(strtolower($this->getSequenceName()))).";
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
			$privscript = "
ALTER TABLE ".$this->quoteIdentifier($this->prefixTablename($table->getName()))." ADD CONSTRAINT ".$this->quoteIdentifier($fk->getName())." FOREIGN KEY (".$this->getColumnList($fk->getLocalColumns()) .") REFERENCES ".$this->quoteIdentifier($this->prefixTablename($fk->getForeignTableName()))." (".$this->getColumnList($fk->getForeignColumns()).")";
			if ($fk->hasOnUpdate()) {
				$privscript .= " ON UPDATE ".$fk->getOnUpdate();
			}
			if ($fk->hasOnDelete()) {
				$privscript .= " ON DELETE ".$fk->getOnDelete();
			}
			$privscript .= ";
";
			self::$queuedConstraints[] = $privscript;
		}
	}

}
