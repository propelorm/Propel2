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

require_once 'propel/engine/database/reverse/SchemaParser.php';

/**
 * Base class for reverse engineering a database schema.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @version    $Revision$
 * @package    propel.engine.database.reverse
 */
abstract class BaseSchemaParser implements SchemaParser {

	/**
	 * The database connection.
	 * @var        PDO
	 */
	protected $dbh;

	/**
	 * Stack of warnings.
	 *
	 * @var        array string[]
	 */
	protected $warnings = array();

	/**
	 * GeneratorConfig object holding build properties.
	 *
	 * @var        GeneratorConfig
	 */
	private $generatorConfig;

	/**
	 * Map native DB types to Propel types.
	 * (Override in subclasses.)
	 * @var        array
	 */
	protected $nativeToPropelTypeMap;

	/**
	 * Map to hold reverse type mapping (initialized on-demand).
	 *
	 * @var        array
	 */
	protected $reverseTypeMap;

	/**
	 * @param      PDO $dbh Optional database connection
	 */
	public function __construct(PDO $dbh = null)
	{
		if ($dbh) $this->setConnection($dbh);
	}

	/**
	 * Sets the database connection.
	 *
	 * @param      PDO $dbh
	 */
	public function setConnection(PDO $dbh)
	{
		$this->dbh = $dbh;
	}

	/**
	 * Gets the database connection.
	 * @return     PDO
	 */
	public function getConnection()
	{
		return $this->dbh;
	}

	/**
	 * Pushes a message onto the stack of warnings.
	 *
	 * @param      string $msg The warning message.
	 */
	protected function warn($msg)
	{
		$this->warnings[] = $msg;
	}

	/**
	 * Gets array of warning messages.
	 *
	 * @return     array string[]
	 */
	public function getWarnings()
	{
		return $this->warnings;
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
	public function getBuildProperty($name)
	{
		if ($this->generatorConfig !== null) {
			return $this->generatorConfig->getBuildProperty($name);
		}
		return null;
	}

	/**
	 * Gets a type mapping from native type to Propel type.
	 *
	 * @return     array The mapped Propel type.
	 */
	abstract protected function getTypeMapping();

	/**
	 * Gets a mapped Propel type for specified native type.
	 *
	 * @param      string $nativeType
	 * @return     string The mapped Propel type.
	 */
	protected function getMappedPropelType($nativeType)
	{
		if ($this->nativeToPropelTypeMap === null) {
			$this->nativeToPropelTypeMap = $this->getTypeMapping();
		}
		if (isset($this->nativeToPropelTypeMap[$nativeType])) {
			return $this->nativeToPropelTypeMap[$nativeType];
		}
		return null;
	}

	/**
	 * Give a best guess at the native type.
	 *
	 * @param      string $propelType
	 * @return     string The native SQL type that best matches the specified Propel type.
	 */
	protected function getMappedNativeType($propelType)
	{
		if ($this->reverseTypeMap === null) {
			$this->reverseTypeMap = array_flip($this->getTypeMapping());
		}
		return isset($this->reverseTypeMap[$propelType]) ? $this->reverseTypeMap[$propelType] : null;
	}

	/**
	 * Gets a new VendorInfo object for this platform with specified params.
	 *
	 * @param      array $params
	 */
	protected function getNewVendorInfoObject(array $params)
	{
		$type = $this->getGeneratorConfig()->getConfiguredPlatform()->getDatabaseType();
		$vi = new VendorInfo($type);
		$vi->setParameters($params);
		return $vi;
	}
}
