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
 * The SQL DDL-building class for PostgreSQL.
 *
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.generator.builder.sql.pgsql
 */
class PgsqlDDLBuilder extends DDLBuilder
{

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

		$script .= $platform->getDropTableDDL($table);
		
		$this->addSequences($script);

		$script .= "

CREATE TABLE ".$this->quoteIdentifier($table->getName())."
(
	";

		$lines = array();

		foreach ($table->getColumns() as $col) {
			$lines []= $platform->getColumnDDL($col);
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

COMMENT ON TABLE ".$this->quoteIdentifier($table->getName())." IS " . $platform->quote($table->getDescription()).";

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
COMMENT ON COLUMN ".$this->quoteIdentifier($table->getName()).".".$this->quoteIdentifier($col->getName())." IS ".$platform->quote($col->getDescription()) .";
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

		if ($table->getIdMethod() == IDMethod::NATIVE && $table->getIdMethodParameters() != null) {
			$script .= "
CREATE SEQUENCE ".$this->quoteIdentifier(strtolower($platform->getSequenceName($table))).";
";
		}
	}

	/**
	 *
	 * @see        parent::addForeignKeys()
	 */
	protected function addForeignKeys(&$script)
	{
		self::$queuedConstraints[] = $this->getPlatform()->getAddForeignKeysDDL($this->getTable());
	}

}
