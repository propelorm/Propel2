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
	
	public function hasSize($sqlType)
	{
		return !("BYTEA" == $sqlType || "TEXT" == $sqlType);
	}

	public function hasStreamBlobImpl()
	{
		return true;
	}
}
