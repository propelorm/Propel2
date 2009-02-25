<?php
/*
 *  $Id: OracleSchemaParser.php 989 2008-03-11 14:29:30Z heltem $
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

require_once 'propel/engine/database/reverse/BaseSchemaParser.php';

/**
 * Oracle database schema parser.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @author     Guillermo Gutierrez <ggutierrez@dailycosas.net> (Adaptation)
 * @version    $Revision: 1010 $
 * @package    propel.engine.database.reverse.oracle
 */
class OracleSchemaParser extends BaseSchemaParser {

	/**
	 * Map Oracle native types to Propel types.
	 *
	 * There really aren't any Oracle native types, so we're just
	 * using the MySQL ones here.
	 * 
	 * Left as unsupported: 
	 *   BFILE, 
	 *   RAW, 
	 *   ROWID
	 * 
	 * Supported but non existant as a specific type in Oracle: 
	 *   DECIMAL (NUMBER with scale), 
	 *   DOUBLE (FLOAT with precision = 126) 
	 *
	 * @var        array
	 */
	private static $oracleTypeMap = array(
		'BLOB'		=> PropelTypes::BLOB,
		'CHAR'		=> PropelTypes::CHAR,
		'CLOB'		=> PropelTypes::CLOB,
		'DATE'		=> PropelTypes::DATE,
		'DECIMAL'	=> PropelTypes::DECIMAL,
		'DOUBLE'	=> PropelTypes::DOUBLE,
		'FLOAT'		=> PropelTypes::FLOAT,
		'LONG'		=> PropelTypes::LONGVARCHAR,
		'NCHAR'		=> PropelTypes::CHAR,
		'NCLOB'		=> PropelTypes::CLOB,
		'NUMBER'	=> PropelTypes::BIGINT,
		'NVARCHAR2'	=> PropelTypes::VARCHAR,
		'TIMESTAMP'	=> PropelTypes::TIMESTAMP,
		'VARCHAR2'	=> PropelTypes::VARCHAR,
	);

	/**
	 * Gets a type mapping from native types to Propel types
	 *
	 * @return     array
	 */
	protected function getTypeMapping()
	{
		return self::$oracleTypeMap;
	}

	/**
	 * Searches for tables in the database. Maybe we want to search also the views.
	 * @param	Database $database The Database model class to add tables to.
	 */
	public function parse(Database $database)
	{
		$tables = array();
		$stmt = $this->dbh->query("SELECT OBJECT_NAME FROM USER_OBJECTS WHERE OBJECT_TYPE = 'TABLE'");
		/* @var stmt PDOStatement */
		// First load the tables (important that this happen before filling out details of tables)
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$table = new Table($row['OBJECT_NAME']);
			$database->addTable($table);
			// Add columns, primary keys and indexes.
			$this->addColumns($table);
			$this->addPrimaryKey($table);
			$this->addIndexes($table);
			$tables[] = $table;
		}
		foreach ($tables as $table) {
			$this->addForeignKeys($table);
		}
	}

	/**
	 * Adds Columns to the specified table.
	 *
	 * @param      Table $table The Table model class to add columns to.
	 */
	protected function addColumns(Table $table)
	{
		$stmt = $this->dbh->query("SELECT COLUMN_NAME, DATA_TYPE, NULLABLE, DATA_LENGTH, DATA_SCALE, DATA_DEFAULT FROM USER_TAB_COLS WHERE TABLE_NAME = '" . $table->getName() . "'");
		/* @var stmt PDOStatement */
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$size = $row["DATA_LENGTH"];
			$scale = $row["DATA_SCALE"];
			$default = $row['DATA_DEFAULT'];
			$type = $row["DATA_TYPE"];
			$isNullable = ($row['NULLABLE'] == 'Y');
			if ($type == "NUMBER" && $row["DATA_SCALE"] > 0) {
				$type = "DECIMAL";
			}
			if ($type == "FLOAT"&& $row["DATA_PRECISION"] == 126) {
				$type = "DOUBLE";
			}
			if (strpos($type, 'TIMESTAMP(') !== false) {
				$type = substr($type, 0, strpos($type, '('));
				$default = "0000-00-00 00:00:00";
				$size = null;
				$scale = null;
			}
			if ($type == "DATE") {
				$default = "0000-00-00";
				$size = null;
				$scale = null;
			}
				
			$propelType = $this->getMappedPropelType($type);
			if (!$propelType) {
				$propelType = Column::DEFAULT_TYPE;
				$this->warn("Column [" . $table->getName() . "." . $row['COLUMN_NAME']. "] has a column type (".$row["DATA_TYPE"].") that Propel does not support.");
			}

			$column = new Column($row['COLUMN_NAME']);
			$column->setPhpName(); // Prevent problems with strange col names
			$column->setTable($table);
			$column->setDomainForType($propelType);
			$column->getDomain()->replaceSize($size);
			$column->getDomain()->replaceScale($scale);
			if ($default !== null) {
				$column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, ColumnDefaultValue::TYPE_VALUE));
			}
			$column->setAutoIncrement(false); // Not yet supported
			$column->setNotNull(!$isNullable);
			$table->addColumn($column);
		}
		
	} // addColumn()

	/**
	 * Adds Indexes to the specified table.
	 *
	 * @param      Table $table The Table model class to add columns to.
	 */
	protected function addIndexes(Table $table)
	{
		$stmt = $this->dbh->query("SELECT COLUMN_NAME, INDEX_NAME FROM USER_IND_COLUMNS WHERE TABLE_NAME = '" . $table->getName() . "' ORDER BY COLUMN_NAME");
		/* @var stmt PDOStatement */
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($rows) > 0) {
			$index = new Index($rows[0]['INDEX_NAME']);
			foreach($rows AS $row) {
				$index->addColumn($row['COLUMN_NAME']);
			}
			$table->addIndex($index);
		}
	}
	
	/**
	 * Load foreign keys for this table.
	 * 
	 * @param      Table $table The Table model class to add FKs to
	 */
	protected function addForeignKeys(Table $table)
	{	
		// local store to avoid duplicates
		$foreignKeys = array(); 
		
		$stmt = $this->dbh->query("SELECT CONSTRAINT_NAME, DELETE_RULE, R_CONSTRAINT_NAME FROM USER_CONSTRAINTS WHERE CONSTRAINT_TYPE = 'R' AND TABLE_NAME = '" . $table->getName(). "'");
		/* @var stmt PDOStatement */
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			// Local reference
			$stmt2 = $this->dbh->query("SELECT COLUMN_NAME FROM USER_CONS_COLUMNS WHERE CONSTRAINT_NAME = '".$row['CONSTRAINT_NAME']."' AND TABLE_NAME = '" . $table->getName(). "'");
			/* @var stmt2 PDOStatement */
			$localReferenceInfo = $stmt2->fetch(PDO::FETCH_ASSOC);
			
			// Foreign reference
			$stmt2 = $this->dbh->query("SELECT TABLE_NAME, COLUMN_NAME FROM USER_CONS_COLUMNS WHERE CONSTRAINT_NAME = '".$row['R_CONSTRAINT_NAME']."'");
			$foreignReferenceInfo = $stmt2->fetch(PDO::FETCH_ASSOC);
						
			if (!isset($foreignKeys[$row["CONSTRAINT_NAME"]])) {
				$fk = new ForeignKey($row["CONSTRAINT_NAME"]);
				$fk->setForeignTableName($foreignReferenceInfo['TABLE_NAME']);
				$fk->setOnDelete($row["DELETE_RULE"]);
				$fk->setOnUpdate($row["DELETE_RULE"]);
				$fk->addReference(array("local" => $localReferenceInfo['COLUMN_NAME'], "foreign" => $foreignReferenceInfo['COLUMN_NAME']));
				$table->addForeignKey($fk);
				$foreignKeys[$row["CONSTRAINT_NAME"]] = $fk;
			}
		}
	}

	/**
	 * Loads the primary key for this table.
	 * 
	 * @param      Table $table The Table model class to add PK to. 
	 */
	protected function addPrimaryKey(Table $table)
	{
		$stmt = $this->dbh->query("SELECT COLS.COLUMN_NAME FROM USER_CONSTRAINTS CONS, USER_CONS_COLUMNS COLS WHERE CONS.CONSTRAINT_NAME = COLS.CONSTRAINT_NAME AND CONS.TABLE_NAME = '".$table->getName()."' AND CONS.CONSTRAINT_TYPE = 'P'");
		/* @var stmt PDOStatement */
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			// This fixes a strange behavior by PDO. Sometimes the
			// row values are inside an index 0 of an array
			if (array_key_exists(0, $row)) {
				$row = $row[0];
			}
			$table->getColumn($row['COLUMN_NAME'])->setPrimaryKey(true);
		}	
	}

}

