<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/DefaultPlatform.php';
require_once dirname(__FILE__) . '/../model/Domain.php';

/**
 * MS SQL PropelPlatformInterface implementation.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.generator.platform
 */
class MssqlPlatform extends DefaultPlatform
{

	/**
	 * Initializes db specific domain mapping.
	 */
	protected function initialize()
	{
		parent::initialize();
		$this->setSchemaDomainMapping(new Domain(PropelTypes::INTEGER, "INT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "INT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DOUBLE, "FLOAT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "TEXT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "TEXT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::DATE, "DATETIME"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BU_DATE, "DATETIME"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIME, "DATETIME"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, "DATETIME"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BU_TIMESTAMP, "DATETIME"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BINARY(7132)"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "IMAGE"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "IMAGE"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "IMAGE"));
	}

	public function getMaxColumnNameLength()
	{
		return 128;
	}

	public function getNullString($notNull)
	{
		return ($notNull ? "NOT NULL" : "NULL");
	}

	public function supportsNativeDeleteTrigger()
	{
		return true;
	}

	public function supportsInsertNullPk()
	{
		return false;
	}
	
	public function getPrimaryKeyDDL(Table $table)
	{
		if ($table->hasPrimaryKey()) {
			return 'CONSTRAINT ' . $this->quoteIdentifier($table->getName() . '_PK') . ' PRIMARY KEY (' . $this->getColumnListDDL($table->getPrimaryKey()) . ')';
		}
	}

	public function hasSize($sqlType)
	{
		return !("INT" == $sqlType || "TEXT" == $sqlType);
	}

	public function quoteIdentifier($text)
	{
		return '[' . $text . ']';
	}

	public function getTimestampFormatter()
	{
		return 'Y-m-d H:i:s';
	}

}
