<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Base class for reverse engineering a database schema.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
abstract class AbstractSchemaParser implements SchemaParserInterface
{

    /**
     * The database connection.
     * @var        ConnectionInterface
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
     * Name of the propel migration table - to be ignored in reverse
     *
     * @var string
     */
    protected $migrationTable = 'propel_migration';

    protected $platform;

    /**
     * @param      ConnectionInterface $dbh Optional database connection
     */
    public function __construct(ConnectionInterface $dbh = null)
    {
        if ($dbh) {
            $this->setConnection($dbh);
        }
    }

    /**
     * Sets the database connection.
     *
     * @param      ConnectionInterface $dbh
     */
    public function setConnection(ConnectionInterface $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Gets the database connection.
     * @return     ConnectionInterface
     */
    public function getConnection()
    {
        return $this->dbh;
    }

    /**
     * Setter for the migrationTable property
     *
     * @param string $migrationTable
     */
    public function setMigrationTable($migrationTable)
    {
        $this->migrationTable = $migrationTable;
    }

    /**
     * Getter for the migrationTable property
     *
     * @return string
     */
    public function getMigrationTable()
    {
        return $this->migrationTable;
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
     * @param      GeneratorConfigInterface $config
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config)
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
        $type = $this->getPlatform()->getDatabaseType();
        $vi = new VendorInfo($type);
        $vi->setParameters($params);

        return $vi;
    }

    public function setPlatform($platform)
    {
      $this->platform = $platform;
    }

    public function getPlatform()
    {
      if (null === $this->platform) {
        $this->platform = $this->getGeneratorConfig()->getConfiguredPlatform();
      }

      return $this->platform;
    }
}
