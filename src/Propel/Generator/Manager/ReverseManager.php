<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Manager;

use Exception;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Schema\Dumper\DumperInterface;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class ReverseManager extends AbstractManager
{
    /**
     * @var string|null
     */
    private $schemaName;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * DOM document produced.
     *
     * @var \DOMDocument
     */
    protected $xml;

    /**
     * Whether to use same name for phpName or not.
     *
     * @var bool
     */
    protected $samePhpName;

    /**
     * @var string
     */
    protected $databaseName;

    /**
     * Whether to add vendor info or not.
     *
     * @var bool
     */
    protected $addVendorInfo;

    /**
     * The schema dumper.
     *
     * @var \Propel\Generator\Schema\Dumper\DumperInterface
     */
    private $schemaDumper;

    /**
     * Constructor.
     *
     * @param \Propel\Generator\Schema\Dumper\DumperInterface $schemaDumper
     */
    public function __construct(DumperInterface $schemaDumper)
    {
        $this->schemaDumper = $schemaDumper;
    }

    /**
     * Gets the (optional) schema name to use.
     *
     * @return string|null
     */
    public function getSchemaName(): ?string
    {
        return $this->schemaName;
    }

    /**
     * Sets the name of a database schema to use (optional).
     *
     * @param string $schemaName
     *
     * @return void
     */
    public function setSchemaName(string $schemaName): void
    {
        $this->schemaName = $schemaName;
    }

    /**
     * Gets the (optional) php namespace to use.
     *
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * Sets the php namespace to use (optional).
     *
     * @param string $namespace
     *
     * @return void
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Gets the datasource name.
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Sets the datasource name.
     * This will be used as the <database name=""> value in the generated
     * schema.xml
     *
     * @param string $databaseName
     *
     * @return void
     */
    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    /**
     * Sets whether to use the column name as phpName without any translation.
     *
     * @param bool $samePhpName
     *
     * @return void
     */
    public function setSamePhpName(bool $samePhpName): void
    {
        $this->samePhpName = $samePhpName;
    }

    /**
     * Sets whether to add vendor info to the schema.
     *
     * @param bool $addVendorInfo
     *
     * @return void
     */
    public function setAddVendorInfo(bool $addVendorInfo): void
    {
        $this->addVendorInfo = $addVendorInfo;
    }

    /**
     * Returns whether to use the column name as phpName without any
     * translation.
     *
     * @return bool
     */
    public function isSamePhpName(): bool
    {
        return $this->samePhpName;
    }

    /**
     * @throws \Propel\Generator\Exception\BuildException
     *
     * @return bool
     */
    public function reverse(): bool
    {
        if (!$this->getDatabaseName()) {
            throw new BuildException('The databaseName attribute is required for schema reverse engineering');
        }

        try {
            $database = $this->buildModel();
            $schema = $this->schemaDumper->dump($database);

            $file = $this->getWorkingDirectory() . DIRECTORY_SEPARATOR . $this->getSchemaName() . '.xml';
            $this->log('Writing XML file to ' . $file);

            file_put_contents($file, $schema);
        } catch (Exception $e) {
            $this->log(sprintf('<error>There was an error building XML from metadata: %s</error>', $e->getMessage()));

            throw $e;
        }

        return true;
    }

    /**
     * Builds the model classes from the database schema.
     *
     * @return \Propel\Generator\Model\Database The built-out Database (with all tables, etc.)
     */
    protected function buildModel(): Database
    {
        /** @var \Propel\Generator\Config\GeneratorConfig $config */
        $config = $this->getGeneratorConfig();
        $connection = $this->getConnection();
        $databaseName = $config->getConfigProperty('reverse.connection');

        $database = new Database($this->getDatabaseName());
        $database->setPlatform($config->getConfiguredPlatform($connection));
        $database->setDefaultIdMethod(IdMethod::NATIVE);

        if ($this->getNamespace()) {
            $database->setNamespace($this->getNamespace());
        }

        $buildConnection = $config->getBuildConnection($databaseName);
        $this->log(sprintf('Reading database structure of database `%s` using dsn `%s`', $this->getDatabaseName(), $buildConnection['dsn']));

        $parser = $config->getConfiguredSchemaParser($connection, $databaseName);
        $this->log(sprintf('SchemaParser `%s` chosen', get_class($parser)));
        $nbTables = $parser->parse($database);

        $excludeTables = $config->getConfigProperty('exclude_tables');
        $tableNames = [];

        foreach ($database->getTables() as $table) {
            $skip = false;
            $tableName = $table->getName();

            if (in_array($tableName, $excludeTables, true)) {
                $skip = true;
            } else {
                foreach ($excludeTables as $excludeTable) {
                    if (preg_match('/^' . str_replace('*', '.*', $excludeTable) . '$/', $tableName)) {
                        $skip = true;

                        break;
                    }
                }
            }

            if (!$skip) {
                continue;
            }

            $tableNames[] = $tableName;

            $database->removeTable($table);
        }

        if ($tableNames) {
            $this->log(sprintf('Successfully reverse engineered %d/%d tables (%s)', count($tableNames), $nbTables, implode(', ', $tableNames)));
        }

        return $database;
    }

    /**
     * @throws \Propel\Generator\Exception\BuildException if there isn't a configured connection for reverse
     *
     * @return \Propel\Runtime\Connection\ConnectionInterface
     */
    protected function getConnection(): ConnectionInterface
    {
        /** @var \Propel\Generator\Config\GeneratorConfig $generatorConfig */
        $generatorConfig = $this->getGeneratorConfig();
        /** @var string|null $database */
        $database = $generatorConfig->getConfigProperty('reverse.connection');

        if ($database === null) {
            throw new BuildException('No configured connection. Please add a connection to your configuration file
            or pass a `connection` option to your command line.');
        }

        return $generatorConfig->getConnection($database);
    }
}
