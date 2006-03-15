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
 * The SQL DDL-building class for PostgreSQL.
 *
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.sql.pgsql
 */
class PgsqlDDLBuilder extends DDLBuilder {


    /**
     * Array that keeps track of already
     * added schema names
     *
     * @var Array of schema names
     */
    protected static $addedSchemas = array();

    /**
     * Get the schema for the current table
     *
     * @author Markus Lervik <markus.lervik@necora.fi>
     * @access protected
     * @return schema name if table has one, else
     *         null
     **/
    protected function getSchema()
    {

        $table = $this->getTable();
        $schema = $table->getVendorSpecificInfo();
        if (!empty($schema) && isset($schema['schema'])) {
            return $schema['schema'];
        }

        return null;

    }

    /**
     * Add a schema to the generated SQL script
     *
     * @author Markus Lervik <markus.lervik@necora.fi>
     * @access protected
     * @return string with CREATE SCHEMA statement if
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
	 * @see parent::addDropStatement()
	 */
	protected function addDropStatements(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		$script .= "
DROP TABLE ".$this->quoteIdentifier($table->getName())." CASCADE;
";
		if ($table->getIdMethod() == "native") {
			$script .= "
DROP SEQUENCE ".$this->quoteIdentifier(strtolower($table->getSequenceName())).";
";
		}
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

CREATE TABLE ".$this->quoteIdentifier($table->getName())."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			$lines[] = $this->getColumnDDL($col);
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

COMMENT ON TABLE ".$this->quoteIdentifier($table->getName())." IS '" . $platform->escapeText($table->getDescription())."';

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
    		if( $col->getDescription() != '' ) {
				$script .= "
COMMENT ON COLUMN ".$this->quoteIdentifier($table->getName()).".".$this->quoteIdentifier($col->getName())." IS '".$platform->escapeText($col->getDescription()) ."';
";
			}
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
CREATE SEQUENCE ".$this->quoteIdentifier(strtolower($table->getSequenceName())).";
";
		}
	}


	/**
	 * Adds CREATE INDEX statements for this table.
	 * @see parent::addIndices()
	 */
	protected function addIndices(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($table->getIndices() as $index) {
			$script .= "
CREATE ";
			if($index->getIsUnique()) {
				$script .= "UNIQUE";
			}
			$script .= "INDEX ".$this->quoteIdentifier($index->getName())." ON ".$this->quoteIdentifier($table->getName())." (".$this->getColumnList($index->getColumns()).");
";
		}
	}

	/**
	 *
	 * @see parent::addForeignKeys()
	 */
	protected function addForeignKeys(&$script)
	{
		$table = $this->getTable();
		$platform = $this->getPlatform();

		foreach ($table->getForeignKeys() as $fk) {
			$script .= "
ALTER TABLE ".$this->quoteIdentifier($table->getName())." ADD CONSTRAINT ".$this->quoteIdentifier($fk->getName())." FOREIGN KEY (".$this->getColumnList($fk->getLocalColumns()) .") REFERENCES ".$this->quoteIdentifier($fk->getForeignTableName())." (".$this->getColumnList($fk->getForeignColumns()).")";
			if ($fk->hasOnUpdate()) {
				$script .= " ON UPDATE ".$fk->getOnUpdate();
			}
			if ($fk->hasOnDelete()) {
				$script .= " ON DELETE ".$fk->getOnDelete();
			}
			$script .= ";
";
		}
	}

}
