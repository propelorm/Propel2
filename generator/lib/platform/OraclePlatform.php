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
 * Oracle PropelPlatformInterface implementation.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.generator.platform
 */
class OraclePlatform extends DefaultPlatform
{

	/**
	 * Initializes db specific domain mapping.
	 */
	protected function initialize()
	{
		parent::initialize();
		$this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain(PropelTypes::BOOLEAN_EMU, "NUMBER", "1", "0");
		$this->schemaDomainMap[PropelTypes::CLOB] = new Domain(PropelTypes::CLOB_EMU, "CLOB");
		$this->schemaDomainMap[PropelTypes::CLOB_EMU] = $this->schemaDomainMap[PropelTypes::CLOB];
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TINYINT, "NUMBER", "3", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::SMALLINT, "NUMBER", "5", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BIGINT, "NUMBER", "20", "0"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::REAL, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "FLOAT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DECIMAL, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, "NUMBER"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARCHAR, "NVARCHAR2"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "NVARCHAR2", "2000")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, "DATE")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, "DATE")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, "TIMESTAMP")); 
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "LONG RAW"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "BLOB"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "LONG RAW"));
	}

	public function getMaxColumnNameLength()
	{
		return 30;
	}

	public function getNativeIdMethod()
	{
		return PropelPlatformInterface::SEQUENCE;
	}

	public function getAutoIncrement()
	{
		return "";
	}

	public function supportsNativeDeleteTrigger()
	{
		return true;
	}

	public function getAddTableDDL(Table $table)
	{
		$tableDescription = $table->hasDescription() ? $this->getCommentLineDDL($table->getDescription()) : '';

		$lines = array();

		foreach ($table->getColumns() as $column) {
			$lines[] = $this->getColumnDDL($column);
		}

		foreach ($table->getUnices() as $unique) {
			$lines[] = $this->getUniqueDDL($unique);
		}

		$sep = ",
	";

		$pattern = "
%sCREATE TABLE %s
(
	%s
);
";
		$ret = sprintf($pattern,
			$tableDescription,
			$this->quoteIdentifier($table->getName()),
			implode($sep, $lines)
		);

		$ret .= $this->getAddPrimaryKeyDDL($table);
		$ret .= $this->getAddSequencesDDL($table);
		
		return $ret;
	}
	
	public function getAddPrimaryKeyDDL(Table $table)
	{
		if (is_array($table->getPrimaryKey()) && count($table->getPrimaryKey())) {
			$pattern = "
ALTER TABLE %s
	ADD %s;
";
			return sprintf($pattern,
				$this->quoteIdentifier($table->getName()),
				$this->getPrimaryKeyDDL($table)
			);
		}
	}
	
	public function getAddSequencesDDL(Table $table)
	{
		if ($table->getIdMethod() == "native") {
			$pattern = "
CREATE SEQUENCE %s
	INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCYCLE NOCACHE ORDER;
";
			return sprintf($pattern, 
				$this->quoteIdentifier($this->getSequenceName($table))
			);
		}
	}
	
	public function getDropTableDDL(Table $table)
	{
		$ret = "
DROP TABLE " . $this->quoteIdentifier($table->getName()) . " CASCADE CONSTRAINTS;
";
		if ($table->getIdMethod() == IDMethod::NATIVE) {
			$ret .= "
DROP SEQUENCE " . $this->quoteIdentifier($this->getSequenceName($table)) . ";
";
		}
		return $ret;
	}
	
	public function getPrimaryKeyDDL(Table $table)
	{
		$tableName = $table->getName();
		// pk constraint name must be 30 chars at most
		$tableName = substr($tableName, 0, min(27, strlen($tableName)));
		if ($table->hasPrimaryKey()) {
			$pattern = "CONSTRAINT %s
	PRIMARY KEY (%s)";
			return sprintf($pattern,
				$this->quoteIdentifier($tableName . '_PK'),
				$this->getColumnListDDL($table->getPrimaryKey())
			);
		}
	}

	public function getUniqueDDL(Unique $unique)
	{
		return sprintf('CONSTRAINT %s UNIQUE (%s)',
			$this->quoteIdentifier($unique->getName()),
			$this->getColumnListDDL($unique->getColumns())
		);
	}

	public function getForeignKeyDDL(ForeignKey $fk)
	{
		$pattern = "CONSTRAINT %s
	FOREIGN KEY (%s) REFERENCES %s (%s)";
		$script = sprintf($pattern,
			$this->quoteIdentifier($fk->getName()),
			$this->getColumnListDDL($fk->getLocalColumns()),
			$this->quoteIdentifier($fk->getForeignTableName()),
			$this->getColumnListDDL($fk->getForeignColumns())
		);
		if ($fk->hasOnDelete()) {
			$script .= "
	ON DELETE " . $fk->getOnDelete();
		}
		
		return $script;
	}

	
	/**
	 * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
	 * @return     boolean
	 */
	public function hasStreamBlobImpl()
	{
		return true;
	}
	
	public function quoteIdentifier($text)
	{
		return $text;
	}

	public function getTimestampFormatter()
	{
		return 'Y-m-d H:i:s';
	}

}
