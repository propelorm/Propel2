<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Table;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Interface for RDBMS platform specific behaviour.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
interface PlatformInterface
{

    /**
     * Constant for auto-increment id method.
     */
    const IDENTITY = "identity";

    /**
     * Constant for sequence id method.
     */
    const SEQUENCE = "sequence";

    /**
     * Constant for serial id method (postgresql).
     */
    const SERIAL = "serial";

    /**
     * Sets a database connection to use (for quoting, etc.).
     * @param ConnectionInterface $con The database connection to use in this Platform class.
     */
    public function setConnection(ConnectionInterface $con = null);

    /**
     * Returns the database connection to use for this Platform class.
     * @return ConnectionInterface The database connection or NULL if none has been set.
     */
    public function getConnection();

    /**
     * Sets the GeneratorConfigInterface which contains any generator build properties.
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig);

    /**
     * Returns the short name of the database type that this platform represents.
     * For example MysqlPlatform->getDatabaseType() returns 'mysql'.
     * @return string
     */
    public function getDatabaseType();

    /**
     * Returns the native IdMethod (sequence|identity)
     *
     * @return string The native IdMethod (PlatformInterface:IDENTITY, PlatformInterface::SEQUENCE).
     */
    public function getNativeIdMethod();

    /**
     * Returns the max column length supported by the db.
     *
     * @return int The max column length
     */
    public function getMaxColumnNameLength();

    /**
     * Returns the db specific domain for a propelType.
     *
     * @param  string $propelType the Propel type name.
     * @return Domain The db specific domain.
     */
    public function getDomainForType($propelType);

    /**
     * @return string The RDBMS-specific SQL fragment for <code>NULL</code>
     *                or <code>NOT NULL</code>.
     */
    public function getNullString($notNull);

    /**
     * @return string The RDBMS-specific SQL fragment for autoincrement.
     */
    public function getAutoIncrement();

    /**
     * Returns the DDL SQL for a Column object.
     * @return string
     */
    public function getColumnDDL(Column $col);

    /**
     * Returns the SQL for the default value of a Column object.
     * @return string
     */
    public function getColumnDefaultValueDDL(Column $col);

    /**
     * Creates a delimiter-delimited string list of column names, quoted using quoteIdentifier().
     * @example
     * <code>
     * echo $platform->getColumnListDDL(array('foo', 'bar');
     * // '"foo","bar"'
     * </code>
     * @param Column[]|string[] $columns
     * @param string            $delimiter The delimiter to use in separating the column names.
     *
     * @return string
     */
    public function getColumnListDDL($columns, $delimiter = ',');

    /**
     * Returns the SQL for the primary key of a Table object
     * @return string
     */
    public function getPrimaryKeyDDL(Table $table);

    /**
     * Returns if the RDBMS-specific SQL type has a size attribute.
     *
     * @param  string  $sqlType the SQL type
     * @return boolean True if the type has a size attribute
     */
    public function hasSize($sqlType);

    /**
     * Returns if the RDBMS-specific SQL type has a scale attribute.
     *
     * @param  string  $sqlType the SQL type
     * @return boolean True if the type has a scale attribute
     */
    public function hasScale($sqlType);

    /**
     * Quote and escape needed characters in the string for underlying RDBMS.
     * @param  string $text
     * @return string
     */
    public function quote($text);

    /**
     * Quotes a identifier.
     *
     * @param string $text
     * @return mixed
     */
    public function doQuoting($text);

    /**
     * Whether RDBMS supports native index sizes.
     * @return boolean
     */
    public function supportsIndexSize();

    /**
     * Whether RDBMS supports native ON DELETE triggers (e.g. ON DELETE CASCADE).
     * @return boolean
     */
    public function supportsNativeDeleteTrigger();

    /**
     * Whether RDBMS supports INSERT null values in autoincremented primary keys
     * @return boolean
     */
    public function supportsInsertNullPk();

    /**
     * Whether RDBMS supports native schemas for table layout.
     * @return boolean
     */
    public function supportsSchemas();

    /**
     * Whether RDBMS supports migrations.
     * @return boolean
     */
    public function supportsMigrations();

    /**
     * Whether RDBMS supports VARCHAR without explicit size
     * @return boolean
     */
    public function supportsVarcharWithoutSize();

    /**
     * Returns the boolean value for the RDBMS.
     *
     * This value should match the boolean value that is set
     * when using Propel's PreparedStatement::setBoolean().
     *
     * This function is used to set default column values when building
     * SQL.
     *
     * @param  mixed $tf A boolean or string representation of boolean ('y', 'true').
     * @return mixed
     */
    public function getBooleanString($tf);

    /**
     * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
     * @return boolean
     */
    public function hasStreamBlobImpl();

    /**
     * Gets the preferred timestamp formatter for setting date/time values.
     * @return string
     */
    public function getTimestampFormatter();

    /**
     * Gets the preferred date formatter for setting time values.
     * @return string
     */
    public function getDateFormatter();

    /**
     * Gets the preferred time formatter for setting time values.
     * @return string
     */
    public function getTimeFormatter();

    /**
     * @return string
     */
    public function getSchemaDelimiter();

    /**
     * Normalizes a table for the current platform. Very important for the TableComparator to not
     * generate useless diffs.
     * Useful for checking needed definitions/structures. E.g. Unique Indexes for ForeignKey columns,
     * which the most Platforms requires but which is not always explicitly defined in the table model.
     *
     * @param Table $table The table object which gets modified.
     */
    public function normalizeTable(Table $table);


    /**
     * Get the PHP snippet for binding a value to a column.
     * Warning: duplicates logic from AdapterInterface::bindValue().
     * Any code modification here must be ported there.
     */
    public function getColumnBindingPHP(Column $column, $identifier, $columnValueAccessor, $tab = "            ");

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled();

    /**
     * @param boolean $enabled
     */
    public function setIdentifierQuoting($enabled);
}
