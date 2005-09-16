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
 * 
 * 
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.sql.pgsql
 */
class PgsqlDDLBuilder extends DDLBuilder {
		
	/**
	 * 
	 * @see parent::addDropStatement()
	 */
	protected function addDropStatements(&$script)
	{
		$table = $this->getTable();
		$script .= "
DROP TABLE ".$table->getName()." CASCADE;
";
		if ($table->getIdMethod() == "native") {
			$script .= "
DROP SEQUENCE ".$table->getSequenceName().";
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
		$script .= "
-----------------------------------------------------------------------------
-- ".$table->getName()."
-----------------------------------------------------------------------------
";

		$this->addDropStatements($script);
		$this->addSequences($script);

		$script .= "

CREATE TABLE ".$table->getName()." 
(
	";
	
		$lines = array();
		
		foreach ($table->getColumns() as $col) {
			$lines[] = trim($col->getSqlString());
		}
		
		if ($table->hasPrimaryKey()) {
			$lines[] = "PRIMARY KEY (".$table->printPrimaryKey().")";
		}
		
		foreach ($table->getUnices() as $unique ) { 
			$lines[] = "CONSTRAINT ".$unique->getName()." UNIQUE (".$unique->getColumnList().")";
    	}

		$sep = ",
	";
		$script .= implode($sep, $lines);
		$script .= "
);

COMMENT ON TABLE ".$table->getName()." IS '" . $this->getPlatform()->escapeText($table->getDescription())."';

";

		$this->addColumnComments($script);
	}
	
	/**
	 * Adds comments for the columns.
	 * 
	 */
	protected function addColumnComments(&$script)
	{
		foreach ($this->getTable()->getColumns() as $col) {
    		if( $col->getDescription() != '' ) {
				$script .= "
COMMENT ON COLUMN ".$this->getTable()->getName().".".$col->getName()." IS '".$this->getPlatform()->escapeText($col->getDescription()) ."';
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
		if ($table->getIdMethod() == "native") {
			$script .= "
CREATE SEQUENCE ".$table->getSequenceName().";
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
		foreach ($table->getIndices() as $index) {
			$script .= "
CREATE ";
			if($index->getIsUnique()) {
				$script .= "UNIQUE";
			}
			$script .= "INDEX ".$index->getName() ." ON ".$table->getName()." (".$index->getColumnList().");
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
		foreach ($table->getForeignKeys() as $fk) {
			$script .= "
ALTER TABLE ".$table->getName()." ADD CONSTRAINT ".$fk->getName()." FOREIGN KEY (".$fk->getLocalColumnNames() .") REFERENCES ".$fk->getForeignTableName()." (".$fk->getForeignColumnNames().")";
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