<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/DefaultPlatform.php';

/**
 * Postgresql PropelPlatformInterface implementation.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.generator.platform
 */
class PgsqlPlatform extends DefaultPlatform
{

	/**
	 * Initializes db specific domain mapping.
	 */
	protected function initialize()
	{
		parent::initialize();
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "BOOLEAN"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, "INT2"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, "INT2"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, "INT8"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, "FLOAT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "DOUBLE PRECISION"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "TEXT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "BYTEA"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "TEXT"));
	}

	public function getNativeIdMethod()
	{
		return PropelPlatformInterface::SERIAL;
	}

	public function getAutoIncrement()
	{
		return '';
	}

	public function getMaxColumnNameLength()
	{
		return 32;
	}

	/**
	 * Escape the string for RDBMS.
	 * @param      string $text
	 * @return     string
	 */
	public function disconnectedEscapeText($text)
	{
		if (function_exists('pg_escape_string')) {
			return pg_escape_string($text);
		} else {
			return parent::disconnectedEscapeText($text);
		}
	}

	public function getBooleanString($b)
	{
		// parent method does the checking for allowes tring
		// representations & returns integer
		$b = parent::getBooleanString($b);
		return ($b ? "'t'" : "'f'");
	}

	public function supportsNativeDeleteTrigger()
	{
		return true;
	}

	/**
	 * Override to provide sequence names that conform to postgres' standard when
	 * no id-method-parameter specified.
	 *
	 * @param      Table $table
	 *
	 * @return     string
	 */
	public function getSequenceName(Table $table)
	{
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

	protected function getAddSequenceDDL(Table $table)
	{
		if ($table->getIdMethod() == IDMethod::NATIVE 
		 && $table->getIdMethodParameters() != null) {
			$pattern = "
CREATE SEQUENCE %s;
";
			return sprintf($pattern,
				$this->quoteIdentifier(strtolower($this->getSequenceName($table)))
			);
		}
	}

	protected function getDropSequenceDDL(Table $table)
	{
		if ($table->getIdMethod() == IDMethod::NATIVE 
		 && $table->getIdMethodParameters() != null) {
			$pattern = "
DROP SEQUENCE %s;
";
			return sprintf($pattern,
				$this->quoteIdentifier(strtolower($this->getSequenceName($table)))
			);
		}
	}

	public function getAddSchemasDDL(Database $database)
	{
		$ret = '';
		$schemas = array();
		foreach ($database->getTables() as $table) {
			$vi = $table->getVendorInfoForType('pgsql');
			if ($vi->hasParameter('schema') && !isset($schemas[$vi->getParameter('schema')])) {
				$schemas[$vi->getParameter('schema')] = true;
				$ret .= $this->getAddSchemaDDL($table);
			}
		}
		return $ret;
	}

	public function getAddSchemaDDL(Table $table)
	{
		$vi = $table->getVendorInfoForType('pgsql');
		if ($vi->hasParameter('schema')) {
			$pattern = "
CREATE SCHEMA %s;
";
			return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
		};
	}


	public function getUseSchemaDDL(Table $table)
	{
		$vi = $table->getVendorInfoForType('pgsql');
		if ($vi->hasParameter('schema')) {
			$pattern = "
SET search_path TO %s;
";
			return sprintf($pattern, $this->quoteIdentifier($vi->getParameter('schema')));
		}
	}

	public function getResetSchemaDDL(Table $table)
	{
		$vi = $table->getVendorInfoForType('pgsql');
		if ($vi->hasParameter('schema')) {
			return "
SET search_path TO public;
";
		}
	}
	
	public function getAddTablesDDL(Database $database)
	{
		$ret = $this->getBeginDDL();
		$ret .= $this->getAddSchemasDDL($database);
		foreach ($database->getTablesForSql() as $table) {
			$ret .= $this->getCommentBlockDDL($table->getName());
			$ret .= $this->getDropTableDDL($table);
			$ret .= $this->getAddTableDDL($table);
			$ret .= $this->getAddIndicesDDL($table);
		}
		foreach ($database->getTablesForSql() as $table) {
			$ret .= $this->getAddForeignKeysDDL($table);
		}
		$ret .= $this->getEndDDL();
		return $ret;
	}
	
	public function getAddTableDDL(Table $table)
	{
		$ret = '';
		$ret .= $this->getUseSchemaDDL($table);
		$ret .= $this->getAddSequenceDDL($table);

		$lines = array();

		foreach ($table->getColumns() as $column) {
			$lines[] = $this->getColumnDDL($column);
		}

		if ($table->hasPrimaryKey()) {
			$lines[] = $this->getPrimaryKeyDDL($table);
		}

		foreach ($table->getUnices() as $unique) {
			$lines[] = $this->getUniqueDDL($unique);
		}

		$sep = ",
	";
		$pattern = "
CREATE TABLE %s
(
	%s
);
";
		$ret .= sprintf($pattern,
			$this->quoteIdentifier($table->getName()),
			implode($sep, $lines)
		);
		
		if ($table->hasDescription()) {
			$pattern = "
COMMENT ON TABLE %s IS %s;
";
			$ret .= sprintf($pattern,
				$this->quoteIdentifier($table->getName()),
				$this->quote($table->getDescription())
			);
		}
		
		$ret .= $this->getAddColumnsComments($table);
		$ret .= $this->getResetSchemaDDL($table);
		
		return $ret;
	}
	
	protected function getAddColumnsComments(Table $table)
	{
		$ret = '';
		foreach ($table->getColumns() as $column) {
			$ret .= $this->getAddColumnComment($column);
		}
		return $ret;
	}

	protected function getAddColumnComment(Column $column)
	{
		$pattern = "
COMMENT ON COLUMN %s.%s IS %s;
";
		if ($description = $column->getDescription()) {
			return sprintf($pattern,
				$this->quoteIdentifier($column->getTable()->getName()),
				$this->quoteIdentifier($column->getName()),
				$this->quote($description)
			);
		}
	}

	public function getDropTableDDL(Table $table)
	{
		$ret = '';
		$ret .= $this->getUseSchemaDDL($table);
		$pattern = "
DROP TABLE %s CASCADE;
";
		$ret .= sprintf($pattern, $this->quoteIdentifier($table->getName()));
		$ret .= $this->getDropSequenceDDL($table);
		$ret .= $this->getResetSchemaDDL($table);
		return $ret;
	}
	
	public function getColumnDDL(Column $col)
	{
		$domain = $col->getDomain();
		
		$ddl = array($this->quoteIdentifier($col->getName()));
		$sqlType = $domain->getSqlType();
		$table = $col->getTable();
		if ($col->isAutoIncrement() && $table && $table->getIdMethodParameters() == null) {
			$sqlType = $col->getType() === PropelTypes::BIGINT ? 'bigserial' : 'serial';
		}
		if ($this->hasSize($sqlType)) {
			$ddl []= $sqlType . $domain->printSize();
		} else {
			$ddl []= $sqlType;
		}
		if ($default = $this->getColumnDefaultValueDDL($col)) {
			$ddl []= $default;
		}
		if ($notNull = $this->getNullString($col->isNotNull())) {
			$ddl []= $notNull;
		}
		if ($autoIncrement = $col->getAutoIncrementString()) {
			$ddl []= $autoIncrement;
		}

		return implode(' ', $ddl);
	}

	public function getUniqueDDL(Unique $unique)
	{
		return sprintf('CONSTRAINT %s UNIQUE (%s)',
			$this->quoteIdentifier($unique->getName()),
			$this->getColumnListDDL($unique->getColumns())
		);
	}
	
	public function hasSize($sqlType)
	{
		return !("BYTEA" == $sqlType || "TEXT" == $sqlType);
	}

	public function hasStreamBlobImpl()
	{
		return true;
	}
}
