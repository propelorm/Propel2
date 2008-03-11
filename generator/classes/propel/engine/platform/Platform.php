<?php
/*
 *  $Id$
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

/**
 * Interface for RDBMS platform specific behaviour.
 *
 * @author     Hans Lellelid <hans@xmpl.org> (Propel)
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision$
 * @package    propel.engine.platform
 */
interface Platform {

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
	 * @param      PDO $con The database connection to use in this Platform class.
	 */
	public function setConnection(PDO $con = null);

	/**
	 * Returns the database connection to use for this Platform class.
	 * @return     PDO The database connection or NULL if none has been set.
	 */
	public function getConnection();

	/**
	 * Sets the GeneratorConfig which contains any generator build properties.
	 *
	 * @param      GeneratorConfig $config
	 */
	public function setGeneratorConfig(GeneratorConfig $config);

	/**
	 * Gets the GeneratorConfig object.
	 *
	 * @return     GeneratorConfig
	 */
	public function getGeneratorConfig();

	/**
	 * Returns the short name of the database type that this platform represents.
	 * For example MysqlPlatform->getDatabaseType() returns 'mysql'.
	 * @return     string
	 */
	public function getDatabaseType();

	/**
	 * Returns the native IdMethod (sequence|identity)
	 *
	 * @return     string The native IdMethod (Platform:IDENTITY, Platform::SEQUENCE).
	 */
	public function getNativeIdMethod();

	/**
	 * Returns the max column length supported by the db.
	 *
	 * @return     int The max column length
	 */
	public function getMaxColumnNameLength();

	/**
	 * Returns the db specific domain for a jdbcType.
	 *
	 * @param      string $creoleType the creole type name.
	 * @return     Domain The db specific domain.
	 */
	public function getDomainForType($creoleType);

	/**
	 * @return     string The RDBMS-specific SQL fragment for <code>NULL</code>
	 * or <code>NOT NULL</code>.
	 */
	public function getNullString($notNull);

	/**
	 * @return     The RDBMS-specific SQL fragment for autoincrement.
	 */
	public function getAutoIncrement();

	/**
	 * Returns if the RDBMS-specific SQL type has a size attribute.
	 *
	 * @param      string $sqlType the SQL type
	 * @return     boolean True if the type has a size attribute
	 */
	public function hasSize($sqlType);

	/**
	 * Returns if the RDBMS-specific SQL type has a scale attribute.
	 *
	 * @param      string $sqlType the SQL type
	 * @return     boolean True if the type has a scale attribute
	 */
	public function hasScale($sqlType);

	/**
	 * Quote and escape needed characters in the string for unerlying RDBMS.
	 * @param      string $text
	 * @return     string
	 */
	public function quote($text);

	/**
	 * Quotes identifiers used in database SQL.
	 * @param      string $text
	 * @return     string Quoted identifier.
	 */
	public function quoteIdentifier($text);

	/**
	 * Whether RDBMS supports native ON DELETE triggers (e.g. ON DELETE CASCADE).
	 * @return     boolean
	 */
	public function supportsNativeDeleteTrigger();

	/**
	 * Returns the boolean value for the RDBMS.
	 *
	 * This value should match the boolean value that is set
	 * when using Creole's PreparedStatement::setBoolean().
	 *
	 * This function is used to set default column values when building
	 * SQL.
	 *
	 * @param      mixed $tf A boolean or string representation of boolean ('y', 'true').
	 * @return     mixed
	 */
	public function getBooleanString($tf);

	/**
	 * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
	 * @return     boolean
	 */
	public function hasStreamBlobImpl();

	/**
	 * Gets the preferred timestamp formatter for setting date/time values.
	 * @return     string
	 */
	public function getTimestampFormatter();

	/**
	 * Gets the preferred date formatter for setting time values.
	 * @return     string
	 */
	public function getDateFormatter();

	/**
	 * Gets the preferred time formatter for setting time values.
	 * @return     string
	 */
	public function getTimeFormatter();
}
