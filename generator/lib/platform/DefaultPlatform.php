<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PropelPlatformInterface.php';
require_once dirname(__FILE__) . '/../model/Column.php';
require_once dirname(__FILE__) . '/../model/Domain.php';
require_once dirname(__FILE__) . '/../model/PropelTypes.php';

/**
 * Default implementation for the Platform interface.
 *
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.generator.platform
 */
class DefaultPlatform implements PropelPlatformInterface
{

	/**
	 * Mapping from Propel types to Domain objects.
	 *
	 * @var        array
	 */
	protected $schemaDomainMap;

	/**
	 * GeneratorConfig object holding build properties.
	 *
	 * @var        GeneratorConfig
	 */
	protected $generatorConfig;

	/**
	 * @var        PDO Database connection.
	 */
	protected $con;

	/**
	 * Default constructor.
	 * @param      PDO $con Optional database connection to use in this platform.
	 */
	public function __construct(PDO $con = null)
	{
		if ($con) $this->setConnection($con);
		$this->initialize();
	}

	/**
	 * Set the database connection to use for this Platform class.
	 * @param      PDO $con Database connection to use in this platform.
	 */
	public function setConnection(PDO $con = null)
	{
		$this->con = $con;
	}

	/**
	 * Returns the database connection to use for this Platform class.
	 * @return     PDO The database connection or NULL if none has been set.
	 */
	public function getConnection()
	{
		return $this->con;
	}

	/**
	 * Sets the GeneratorConfig to use in the parsing.
	 *
	 * @param      GeneratorConfig $config
	 */
	public function setGeneratorConfig(GeneratorConfig $config)
	{
		$this->generatorConfig = $config;
	}

	/**
	 * Gets the GeneratorConfig option.
	 *
	 * @return     GeneratorConfig
	 */
	public function getGeneratorConfig()
	{
		return $this->generatorConfig;
	}

	/**
	 * Gets a specific propel (renamed) property from the build.
	 *
	 * @param      string $name
	 * @return     mixed
	 */
	protected function getBuildProperty($name)
	{
		if ($this->generatorConfig !== null) {
			return $this->generatorConfig->getBuildProperty($name);
		}
		return null;
	}

	/**
	 * Initialize the type -> Domain mapping.
	 */
	protected function initialize()
	{
		$this->schemaDomainMap = array();
		foreach (PropelTypes::getPropelTypes() as $type) {
			$this->schemaDomainMap[$type] = new Domain($type);
		}
		// BU_* no longer needed, so map these to the DATE/TIMESTAMP domains
		$this->schemaDomainMap[PropelTypes::BU_DATE] = new Domain(PropelTypes::DATE);
		$this->schemaDomainMap[PropelTypes::BU_TIMESTAMP] = new Domain(PropelTypes::TIMESTAMP);

		// Boolean is a bit special, since typically it must be mapped to INT type.
		$this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain(PropelTypes::BOOLEAN, "INTEGER");
	}

	/**
	 * Adds a mapping entry for specified Domain.
	 * @param      Domain $domain
	 */
	protected function setSchemaDomainMapping(Domain $domain)
	{
		$this->schemaDomainMap[$domain->getType()] = $domain;
	}

	/**
	 * Returns the short name of the database type that this platform represents.
	 * For example MysqlPlatform->getDatabaseType() returns 'mysql'.
	 * @return     string
	 */
	public function getDatabaseType()
	{
		$clazz = get_class($this);
		$pos = strpos($clazz, 'Platform');
		return strtolower(substr($clazz,0,$pos));
	}

	/**
	 * Returns the max column length supported by the db.
	 *
	 * @return     int The max column length
	 */
	public function getMaxColumnNameLength()
	{
		return 64;
	}

	/**
	 * Returns the native IdMethod (sequence|identity)
	 *
	 * @return     string The native IdMethod (PropelPlatformInterface:IDENTITY, PropelPlatformInterface::SEQUENCE).
	 */
	public function getNativeIdMethod()
	{
		return PropelPlatformInterface::IDENTITY;
	}

	/**
	 * Returns the db specific domain for a propelType.
	 *
	 * @param      string $propelType the Propel type name.
	 * @return     Domain The db specific domain.
	 */
	public function getDomainForType($propelType)
	{
		if (!isset($this->schemaDomainMap[$propelType])) {
			throw new EngineException("Cannot map unknown Propel type " . var_export($propelType, true) . " to native database type.");
		}
		return $this->schemaDomainMap[$propelType];
	}

	/**
	 * @return     string The RDBMS-specific SQL fragment for <code>NULL</code>
	 * or <code>NOT NULL</code>.
	 */
	public function getNullString($notNull)
	{
		return ($notNull ? "NOT NULL" : "");
	}

	/**
	 * @return     The RDBMS-specific SQL fragment for autoincrement.
	 */
	public function getAutoIncrement()
	{
		return "IDENTITY";
	}

	/**
	 * Builds the DDL SQL for a Column object.
	 * @return     string
	 */
	public function getColumnDDL(Column $col)
	{
		$domain = $col->getDomain();
		
		$ddl = array($this->quoteIdentifier($col->getName()));
		$sqlType = $domain->getSqlType();
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
	
	/**
	 * Returns the SQL for the default value of a Column object
	 * @return     string
	 */
	public function getColumnDefaultValueDDL(Column $col)
	{
		$default = '';
		$defaultValue = $col->getDefaultValue();
		if ($defaultValue !== null) {
			$default .= 'DEFAULT ';
			if ($defaultValue->isExpression()) {
				$default .= $defaultValue->getValue();
			} else {
				if ($col->isTextType()) {
					$default .= $this->quote($defaultValue->getValue());
				} elseif ($col->getType() == PropelTypes::BOOLEAN) {
					$default .= $this->getBooleanString($defaultValue->getValue());
				} else {
					$default .= $defaultValue->getValue();
				}
			}
		}
		
		return $default;
	}

	/**
	 * Creates a delimiter-delimited string list of column names, quoted using quoteIdentifier().
	 * @example
	 * <code>
	 * echo $platform->getColumnListDDL(array('foo', 'bar');
	 * // '"foo","bar"'
	 * </code>
	 * @param      array Column[] or string[]
	 * @param      string $delim The delimiter to use in separating the column names.
	 *
	 * @return     string
	 */
	public function getColumnListDDL($columns, $delimiter = ',')
	{
		$list = array();
		foreach ($columns as $column) {
			if ($col instanceof Column) {
				$column = $column->getName();
			}
			$list[] = $this->quoteIdentifier($column);
		}
		return implode($delimiter, $list);
	}

	/**
	 * Returns if the RDBMS-specific SQL type has a size attribute.
	 *
	 * @param      string $sqlType the SQL type
	 * @return     boolean True if the type has a size attribute
	 */
	public function hasSize($sqlType)
	{
		return true;
	}

	/**
	 * Returns if the RDBMS-specific SQL type has a scale attribute.
	 *
	 * @param      string $sqlType the SQL type
	 * @return     boolean True if the type has a scale attribute
	 */
	public function hasScale($sqlType)
	{
		return true;
	}

	/**
	 * Quote and escape needed characters in the string for unerlying RDBMS.
	 * @param      string $text
	 * @return     string
	 */
	public function quote($text)
	{
		if ($con = $this->getConnection()) {
			return $con->quote($text);
		} else {
			return "'" . $this->disconnectedEscapeText($text) . "'";
		}
	}

	/**
	 * Method to escape text when no connection has been set.
	 *
	 * The subclasses can implement this using string replacement functions
	 * or native DB methods.
	 *
	 * @param      string $text Text that needs to be escaped.
	 * @return     string
	 */
	protected function disconnectedEscapeText($text)
	{
		return str_replace("'", "''", $text);
	}

	/**
	 * Quotes identifiers used in database SQL.
	 * @param      string $text
	 * @return     string Quoted identifier.
	 */
	public function quoteIdentifier($text)
	{
		return '"' . $text . '"';
	}

	/**
	 * Whether RDBMS supports native ON DELETE triggers (e.g. ON DELETE CASCADE).
	 * @return     boolean
	 */
	public function supportsNativeDeleteTrigger()
	{
		return false;
	}

	/**
	 * Whether RDBMS supports INSERT null values in autoincremented primary keys
	 * @return     boolean
	 */
	public function supportsInsertNullPk()
	{
		return true;
	}
	
	/**
	 * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
	 * @return     boolean
	 */
	public function hasStreamBlobImpl()
	{
		return false;
	}

	/**
	 * Returns the boolean value for the RDBMS.
	 *
	 * This value should match the boolean value that is set
	 * when using Propel's PreparedStatement::setBoolean().
	 *
	 * This function is used to set default column values when building
	 * SQL.
	 *
	 * @param      mixed $tf A boolean or string representation of boolean ('y', 'true').
	 * @return     mixed
	 */
	public function getBooleanString($b)
	{
		$b = ($b === true || strtolower($b) === 'true' || $b === 1 || $b === '1' || strtolower($b) === 'y' || strtolower($b) === 'yes');
		return ($b ? '1' : '0');
	}

	/**
	 * Gets the preferred timestamp formatter for setting date/time values.
	 * @return     string
	 */
	public function getTimestampFormatter()
	{
		return DateTime::ISO8601;
	}

	/**
	 * Gets the preferred time formatter for setting date/time values.
	 * @return     string
	 */
	public function getTimeFormatter()
	{
		return 'H:i:s';
	}

	/**
	 * Gets the preferred date formatter for setting date/time values.
	 * @return     string
	 */
	public function getDateFormatter()
	{
		return 'Y-m-d';
	}

}
