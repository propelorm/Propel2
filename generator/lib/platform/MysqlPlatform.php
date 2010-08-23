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
 * MySql PropelPlatformInterface implementation.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.generator.platform
 */
class MysqlPlatform extends DefaultPlatform
{

	/**
	 * Initializes db specific domain mapping.
	 */
	protected function initialize()
	{
		parent::initialize();
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BOOLEAN, "TINYINT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::NUMERIC, "DECIMAL"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARCHAR, "TEXT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BINARY, "BLOB"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::VARBINARY, "MEDIUMBLOB"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::LONGVARBINARY, "LONGBLOB"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::BLOB, "LONGBLOB"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::CLOB, "LONGTEXT"));
		$this->setSchemaDomainMapping(new Domain(PropelTypes::TIMESTAMP, "DATETIME"));
	}

	public function getAutoIncrement()
	{
		return "AUTO_INCREMENT";
	}

	public function getMaxColumnNameLength()
	{
		return 64;
	}

	public function supportsNativeDeleteTrigger()
	{
		$usingInnoDB = false;
		if (class_exists('DataModelBuilder', false)) {
			$usingInnoDB = strtolower($this->getBuildProperty('mysqlTableType')) == 'innodb';
		}
		return $usingInnoDB || false;
	}

	/**
	 * Builds the DDL SQL for a Column object.
	 * @return     string
	 */
	public function getColumnDDL(Column $col)
	{
		$domain = $col->getDomain();
		$sqlType = $domain->getSqlType();
		$notNullString = $this->getNullString($col->isNotNull());
		$defaultSetting = $this->getColumnDefaultValueDDL($col);

		// Special handling of TIMESTAMP/DATETIME types ...
		// See: http://propel.phpdb.org/trac/ticket/538
		if ($sqlType == 'DATETIME') {
			$def = $domain->getDefaultValue();
			if ($def && $def->isExpression()) { // DATETIME values can only have constant expressions
				$sqlType = 'TIMESTAMP';
			}
		} elseif ($sqlType == 'DATE') {
			$def = $domain->getDefaultValue();
			if ($def && $def->isExpression()) {
				throw new EngineException("DATE columns cannot have default *expressions* in MySQL.");
			}
		} elseif ($sqlType == 'TEXT' || $sqlType == 'BLOB') {
			if ($domain->getDefaultValue()) {
				throw new EngineException("BLOB and TEXT columns cannot have DEFAULT values. in MySQL.");
			}
		}

		$ddl = array($this->quoteIdentifier($col->getName()));
		if ($this->hasSize($sqlType)) {
			$ddl []= $sqlType . $domain->printSize();
		} else {
			$ddl []= $sqlType;
		}
		if ($sqlType == 'TIMESTAMP') {
			if ($notNullString == '') {
				$notNullString = 'NULL';
			}
			if ($defaultSetting == '' && $notNullString == 'NOT NULL') {
				$defaultSetting = 'DEFAULT CURRENT_TIMESTAMP';
			}
			if ($notNullString) {
				$ddl []= $notNullString;
			}
			if ($defaultSetting) {
				$ddl []= $defaultSetting;
			}
		} else {
			if ($defaultSetting) {
				$ddl []= $defaultSetting;
			}
			if ($notNullString) {
				$ddl []= $notNullString;
			}
		}
		if ($autoIncrement = $col->getAutoIncrementString()) {
			$ddl []= $autoIncrement;
		}
		$colinfo = $col->getVendorInfoForType($this->getDatabaseType());
		if ($colinfo->hasParameter('Charset')) {
			$ddl []= 'CHARACTER SET '. $this->quote($colinfo->getParameter('Charset'));
		}
		if ($colinfo->hasParameter('Collation')) {
			$ddl []= 'COLLATE '. $this->quote($colinfo->getParameter('Collation'));
		} elseif ($colinfo->hasParameter('Collate')) {
			$ddl []= 'COLLATE '. $this->quote($colinfo->getParameter('Collate'));
		}
		if ($col->getDescription()) {
			$ddl []= 'COMMENT ' . $this->quote($col->getDescription());
		}

		return implode(' ', $ddl);
	}

	public function hasSize($sqlType)
	{
		return !("MEDIUMTEXT" == $sqlType || "LONGTEXT" == $sqlType
				|| "BLOB" == $sqlType || "MEDIUMBLOB" == $sqlType
				|| "LONGBLOB" == $sqlType);
	}

	/**
	 * Escape the string for RDBMS.
	 * @param      string $text
	 * @return     string
	 */
	public function disconnectedEscapeText($text)
	{
		if (function_exists('mysql_escape_string')) {
			return mysql_escape_string($text);
		} else {
			return addslashes($text);
		}
	}

	public function quoteIdentifier($text)
	{
		return '`' . $text . '`';
	}

	public function getTimestampFormatter()
	{
		return 'Y-m-d H:i:s';
	}
}
