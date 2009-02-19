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
 * @package    propel.engine.database.reverse.mysql
 */
class OracleSchemaParser extends BaseSchemaParser {

	/**
	 * Map Oracle native types to Propel types.
	 *
	 * There really aren't any Oracle native types, so we're just
	 * using the MySQL ones here.
	 *
	 * @var        array
	 */
	private static $oracleTypeMap = array(
		'tinyint' => PropelTypes::TINYINT,
		'smallint' => PropelTypes::SMALLINT,
		'mediumint' => PropelTypes::SMALLINT,
		'int' => PropelTypes::INTEGER,
		'integer' => PropelTypes::INTEGER,
		'bigint' => PropelTypes::BIGINT,
		'int24' => PropelTypes::BIGINT,
		'real' => PropelTypes::REAL,
		'float' => PropelTypes::FLOAT,
		'decimal' => PropelTypes::DECIMAL,
		'numeric' => PropelTypes::NUMERIC,
		'double' => PropelTypes::DOUBLE,
		'char' => PropelTypes::CHAR,
		'varchar' => PropelTypes::VARCHAR,
		'date' => PropelTypes::DATE,
		'time' => PropelTypes::TIME,
		'year' => PropelTypes::INTEGER,
		'datetime' => PropelTypes::TIMESTAMP,
		'timestamp' => PropelTypes::TIMESTAMP,
		'tinyblob' => PropelTypes::BINARY,
		'blob' => PropelTypes::BLOB,
		'mediumblob' => PropelTypes::BLOB,
		'longblob' => PropelTypes::BLOB,
		'longtext' => PropelTypes::CLOB,
		'tinytext' => PropelTypes::VARCHAR,
		'mediumtext' => PropelTypes::LONGVARCHAR,
		'text' => PropelTypes::LONGVARCHAR,
		'enum' => PropelTypes::CHAR,
		'set' => PropelTypes::CHAR,
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
	 *
	 */
	public function parse(Database $database)
	{
		$stmt = $this->dbh->query("SELECT OBJECT_NAME FROM USER_OBJECTS WHERE OBJECT_TYPE = 'TABLE'");

		// First load the tables (important that this happen before filling out details of tables)
		$tables = array();
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$name = $row[0];
			$table = new Table($name);
			$database->addTable($table);
			$tables[] = $table;
		}

		// Now populate only columns.
		foreach ($tables as $table) {
			$this->addColumns($table);
		}

		// Now add indexes and constraints.
		foreach ($tables as $table) {
			$this->addIndexes($table);
		}

	}


	/**
	 * Adds Columns to the specified table.
	 *
	 * @param      Table $table The Table model class to add columns to.
	 * @param      int $oid The table OID
	 * @param      string $version The database version.
	 */
	protected function addColumns(Table $table)
	{
		$stmt = $this->dbh->query("SELECT COLUMN_NAME, DATA_TYPE, NULLABLE, DATA_LENGTH, DATA_SCALE, DATA_DEFAULT FROM USER_TAB_COLS WHERE TABLE_NAME = '" . $table->getName() . "'");

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

			$name = $row['COLUMN_NAME'];

			$type = $row["DATA_TYPE"];
			$nativeType = $type;
			$isNullable = ($row['NULLABLE'] == 'Y');
			$size = $row["DATA_LENGTH"];
			//$precision = $row["DATA_PRECISION"]; // NOT USED
			$scale = $row["DATA_SCALE"];
			$autoIncrement = false; // YET TO BE PARSED
			$default = $row['DATA_DEFAULT'];


			$propelType = $this->getMappedPropelType($nativeType);
			if (!$propelType) {
				$propelType = Column::DEFAULT_TYPE;
				$this->warn("Column [" . $table->getName() . "." . $name. "] has a column type (".$nativeType.") that Propel does not support.");
			}

			$column = new Column($name);
			$column->setTable($table);
			$column->setDomainForType($propelType);
			// We may want to provide an option to include this:
			// $column->getDomain()->replaceSqlType($type);
			$column->getDomain()->replaceSize($size);
			$column->getDomain()->replaceScale($scale);
			if ($default !== null) {
				$column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, ColumnDefaultValue::TYPE_VALUE));
			}
			$column->setAutoIncrement($autoIncrement);
			$column->setNotNull(!$isNullable);

			$table->addColumn($column);
		}
		$this->addPrimaryKey($table);
		$this->addForeignKeys($table);
	} // addColumn()

	/**
	 * Load indexes for this table
	 */
	protected function addIndexes(Table $table)
	{
		$stmt = $this->dbh->query("SELECT INDEX_NAME, COLUMN_NAME FROM USER_IND_COLUMNS WHERE TABLE_NAME = '" . $table->getName() . "'");

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

			$name = $row['INDEX_NAME'];
			$index = new Index($name);
			$colname = $row['COLUMN_NAME'];
			$index->addColumn($table->getColumn($colname));

			$table->addIndex($index);
		}
	}
	
	/**
	 * Load foreign keys for this table.
	 */
	protected function addForeignKeys(Table $table)
	{
		$database = $table->getDatabase();

		$stmt = $this->dbh->query("SELECT CONSTRAINT_NAME, DELETE_RULE, R_CONSTRAINT_NAME FROM ALL_CONSTRAINTS WHERE CONSTRAINT_TYPE = 'R' AND TABLE_NAME = '" . $table->getName(). "'");
		
		$foreignKeys = array(); // local store to avoid duplicates

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {			
			$name = $row['CONSTRAINT_NAME'];
			
			$stmt2 = $this->dbh->query("SELECT CON.CONSTRAINT_NAME, CON.TABLE_NAME, COL.COLUMN_NAME FROM USER_CONS_COLUMNS COL, USER_CONSTRAINTS CON WHERE COL.CONSTRAINT_NAME = CON.CONSTRAINT_NAME AND COL.CONSTRAINT_NAME = '".$row['R_CONSTRAINT_NAME']."'");
			$foreignKeyCols = $stmt2->fetch(PDO::FETCH_ASSOC);
			if (!is_array($foreignKeyCols[0])) {
				$foreignRows[0] = $foreignKeyCols;
			} else {
				$foreignRows = $foreignKeyCols;
			}
			
			$stmt2 = $this->dbh->query("SELECT CON.CONSTRAINT_NAME, CON.TABLE_NAME, COL.COLUMN_NAME FROM USER_CONS_COLUMNS COL, USER_CONSTRAINTS CON WHERE COL.CONSTRAINT_NAME = CON.CONSTRAINT_NAME AND COL.CONSTRAINT_NAME = '".$row['CONSTRAINT_NAME']."'");
			$localKeyCols = $stmt2->fetch(PDO::FETCH_ASSOC);
			if (!is_array($localKeyCols[0])) {
				$localRows[0] = $localKeyCols;
			} else {
				$localRows = $localKeyCols;
			}
			
			print_r($foreignRows);
			
			$localColumns = array();
			$foreignColumns = array();
			
			$foreignTable = $database->getTable($foreignRows[0]["TABLE_NAME"]);
			
			foreach($foreignRows as $foreignCol) {
				$foreignColumns[] = $foreignTable->getColumn($foreignCol["COLUMN_NAME"]);
			}
			foreach($localRows as $localCol) {
				$localColumns[] = $table->getColumn($localCol['COLUMN_NAME']);
			}
			
			if (!isset($foreignKeys[$name])) {
				$fk = new ForeignKey($name);
				$fk->setForeignTableName($foreignTable->getName());
				$fk->setOnDelete($row["DELETE_RULE"]);
				$fk->setOnUpdate($row["DELETE_RULE"]);
				$table->addForeignKey($fk);
				$foreignKeys[$name] = $fk;
			}
			
			for($i=0; $i < count($localColumns); $i++) {
				$foreignKeys[$name]->addReference($localColumns[$i], $foreignColumns[$i]);
			}
			
		}

	}

	/**
	 * Loads the primary key for this table.
	 */
	protected function addPrimaryKey(Table $table)
	{
		$stmt = $this->dbh->query("SELECT COL.COLUMN_NAME FROM USER_CONS_COLUMNS COL, USER_CONSTRAINTS CON WHERE COL.TABLE_NAME = '" . $table->getName() . "' AND COL.TABLE_NAME = CON.TABLE_NAME AND COL.CONSTRAINT_NAME = CON.CONSTRAINT_NAME AND CON.CONSTRAINT_TYPE = 'P'");

		// Loop through the returned results, grouping the same key_name together
		// adding each column for that key.
		
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$table->getColumn($row['COLUMN_NAME'])->setPrimaryKey(true);
		}
	}

}
