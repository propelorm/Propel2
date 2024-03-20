<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Reverse;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\VendorInfo;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use RuntimeException;

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
     * @var \Propel\Runtime\Connection\ConnectionInterface
     */
    protected $dbh;

    /**
     * Stack of warnings.
     *
     * @var list<string>
     */
    protected $warnings = [];

    /**
     * GeneratorConfig object holding build properties.
     *
     * @var \Propel\Generator\Config\GeneratorConfigInterface
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
     * @var \Propel\Generator\Platform\PlatformInterface|null
     */
    protected $platform;

    /**
     * Constructor.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface|null $dbh Optional database connection
     */
    public function __construct(?ConnectionInterface $dbh = null)
    {
        if ($dbh !== null) {
            $this->setConnection($dbh);
        }
    }

    /**
     * Sets the database connection.
     *
     * @param \Propel\Runtime\Connection\ConnectionInterface $dbh
     *
     * @return void
     */
    public function setConnection(ConnectionInterface $dbh): void
    {
        $this->dbh = $dbh;
    }

    /**
     * Gets the database connection.
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->dbh;
    }

    /**
     * Setter for the migrationTable property
     *
     * @param string $migrationTable
     *
     * @return void
     */
    public function setMigrationTable(string $migrationTable): void
    {
        $this->migrationTable = $migrationTable;
    }

    /**
     * Getter for the migrationTable property
     *
     * @return string
     */
    public function getMigrationTable(): string
    {
        return $this->migrationTable;
    }

    /**
     * Pushes a message onto the stack of warnings.
     *
     * @param string $msg The warning message.
     *
     * @return void
     */
    protected function warn(string $msg): void
    {
        $this->warnings[] = $msg;
    }

    /**
     * Gets array of warning messages.
     *
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Sets the GeneratorConfig to use in the parsing.
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $config
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config): void
    {
        $this->generatorConfig = $config;
    }

    /**
     * Gets the GeneratorConfig option.
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Gets a type mapping from native type to Propel type.
     *
     * @return array<string> The mapped Propel type.
     */
    abstract protected function getTypeMapping(): array;

    /**
     * Gets a mapped Propel type for specified native type.
     *
     * @param string $nativeType
     *
     * @return string|null The mapped Propel type.
     */
    protected function getMappedPropelType(string $nativeType): ?string
    {
        if ($this->nativeToPropelTypeMap === null) {
            $this->nativeToPropelTypeMap = $this->getTypeMapping();
        }

        return $this->nativeToPropelTypeMap[$nativeType] ?? null;
    }

    /**
     * Give a best guess at the native type.
     *
     * @param string $propelType
     *
     * @return string|null The native SQL type that best matches the specified Propel type.
     */
    protected function getMappedNativeType(string $propelType): ?string
    {
        if ($this->reverseTypeMap === null) {
            $this->reverseTypeMap = array_flip($this->getTypeMapping());
        }

        return $this->reverseTypeMap[$propelType] ?? null;
    }

    /**
     * Gets a new VendorInfo object for this platform with specified params.
     *
     * @param array $params
     *
     * @return \Propel\Generator\Model\VendorInfo
     */
    protected function getNewVendorInfoObject(array $params): VendorInfo
    {
        $type = $this->getPlatform()->getDatabaseType();

        $vi = new VendorInfo($type);
        $vi->setParameters($params);

        return $vi;
    }

    /**
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return void
     */
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * @return bool
     */
    public function hasPlatform(): bool
    {
        return $this->platform !== null;
    }

    /**
     * Returns the database's platform.
     *
     * @throws \RuntimeException
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform(): PlatformInterface
    {
        if ($this->platform === null) {
            $this->platform = $this->getGeneratorConfig()->getConfiguredPlatform();
        }

        $platform = $this->platform;
        if ($platform === null) {
            throw new RuntimeException('No platform set, please use `hasPlatform()` to check for existence first.');
        }

        return $platform;
    }
}
