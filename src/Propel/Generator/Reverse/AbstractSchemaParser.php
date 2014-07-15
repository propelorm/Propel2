<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\VendorInfo;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Base class for reverse engineering a database schema.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class AbstractSchemaParser implements SchemaParserInterface
{
    /**
     * The database connection.
     *
     * @var ConnectionInterface
     */
    protected $dbh;

    /**
     * Stack of warnings.
     *
     * @var array string[]
     */
    protected $warnings = array();

    /**
     * GeneratorConfig object holding build properties.
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * Map native DB types to Propel types.
     * (Override in subclasses.)
     *
     * @var array
     */
    protected $nativeToPropelTypeMap;

    /**
     * Map to hold reverse type mapping (initialized on-demand).
     *
     * @var array
     */
    protected $reverseTypeMap;

    /**
     * Name of the propel migration table - to be ignored in reverse
     *
     * @var string
     */
    protected $migrationTable = 'propel_migration';

    /**
     * The database's platform.
     *
     * @var \Propel\Generator\Platform\PlatformInterface
     */
    protected $platform;

    /**
     * Constructor.
     *
     * @param ConnectionInterface $dbh Optional database connection
     */
    public function __construct(ConnectionInterface $dbh = null)
    {
        if (null !== $dbh) {
            $this->setConnection($dbh);
        }
    }

    /**
     * Sets the database connection.
     *
     * @param ConnectionInterface $dbh
     */
    public function setConnection(ConnectionInterface $dbh)
    {
        $this->dbh = $dbh;
    }

    /**
     * Gets the database connection.
     * @return ConnectionInterface
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
     * @param string $msg The warning message.
     */
    protected function warn($msg)
    {
        $this->warnings[] = $msg;
    }

    /**
     * Gets array of warning messages.
     *
     * @return string[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Sets the GeneratorConfig to use in the parsing.
     *
     * @param GeneratorConfigInterface $config
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config)
    {
        $this->generatorConfig = $config;
    }

    /**
     * Gets the GeneratorConfig option.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig()
    {
        return $this->generatorConfig;
    }

    /**
     * Gets a type mapping from native type to Propel type.
     *
     * @return array The mapped Propel type.
     */
    abstract protected function getTypeMapping();

    /**
     * Gets a mapped Propel type for specified native type.
     *
     * @param  string $nativeType
     * @return string The mapped Propel type.
     */
    protected function getMappedPropelType($nativeType)
    {
        if (null === $this->nativeToPropelTypeMap) {
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
     * @param  string $propelType
     * @return string The native SQL type that best matches the specified Propel type.
     */
    protected function getMappedNativeType($propelType)
    {
        if (null === $this->reverseTypeMap) {
            $this->reverseTypeMap = array_flip($this->getTypeMapping());
        }

        return isset($this->reverseTypeMap[$propelType]) ? $this->reverseTypeMap[$propelType] : null;
    }

    /**
     * Gets a new VendorInfo object for this platform with specified params.
     *
     * @param array $params
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

    /**
     * Returns the database's platform.
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform()
    {
        if (null === $this->platform) {
            $this->platform = $this->getGeneratorConfig()->getConfiguredPlatform();
        }

        return $this->platform;
    }
}
