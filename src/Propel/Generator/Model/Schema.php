<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Model;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Schema\Dumper\XmlDumper;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Schema
{
    /**
     * @var array<\Propel\Generator\Model\Database>
     */
    private $databases;

    /**
     * @var \Propel\Generator\Platform\PlatformInterface
     */
    private $platform;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isInitialized;

    /**
     * @var \Propel\Generator\Config\GeneratorConfigInterface
     */
    protected $generatorConfig;

    /**
     * Creates a new instance for the specified database type.
     *
     * @param \Propel\Generator\Platform\PlatformInterface|null $platform
     */
    public function __construct(?PlatformInterface $platform = null)
    {
        if ($platform !== null) {
            $this->setPlatform($platform);
        }

        $this->isInitialized = false;
        $this->databases = [];
    }

    /**
     * Sets the platform object to use for any databases added to this
     * application schema.
     *
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return void
     */
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * Returns the platform object to use for any databases added to this
     * application schema.
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform(): PlatformInterface
    {
        return $this->platform;
    }

    /**
     * Sets the generator configuration
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $generatorConfig
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig): void
    {
        $this->generatorConfig = $generatorConfig;
    }

    /**
     * Returns the generator configuration
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface|null
     */
    public function getGeneratorConfig(): ?GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Sets the schema name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the schema name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the schema short name (without the '-schema' postfix).
     *
     * @return string
     */
    public function getShortName(): string
    {
        return str_replace('-schema', '', $this->name);
    }

    /**
     * Returns an array of all databases.
     *
     * The first boolean parameter tells whether to run the
     * final initialization process.
     *
     * @param bool $doFinalInitialization
     *
     * @return array<\Propel\Generator\Model\Database>
     */
    public function getDatabases(bool $doFinalInitialization = true): array
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
        if ($doFinalInitialization) {
            $this->doFinalInitialization();
        }

        return $this->databases;
    }

    /**
     * Returns whether this schema has multiple databases.
     *
     * @return bool
     */
    public function hasMultipleDatabases(): bool
    {
        return count($this->databases) > 1;
    }

    /**
     * Returns the database according to the specified name.
     *
     * @param string|null $name
     * @param bool $doFinalInitialization
     *
     * @return \Propel\Generator\Model\Database|null
     */
    public function getDatabase(?string $name = null, bool $doFinalInitialization = true): ?Database
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
        if ($doFinalInitialization) {
            $this->doFinalInitialization();
        }

        if ($name === null) {
            return $this->databases[0];
        }

        foreach ($this->databases as $database) {
            if ($database->getName() !== $name) {
                continue;
            }

            return $database;
        }

        return null;
    }

    /**
     * Returns whether a database with the specified name exists in this
     * schema.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasDatabase(string $name): bool
    {
        foreach ($this->databases as $database) {
            if ($database->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a database to the list and sets the Schema property to this
     * Schema. The database can be specified as a Database object or a
     * DOMNode object.
     *
     * @param \Propel\Generator\Model\Database|array $database
     *
     * @return \Propel\Generator\Model\Database
     */
    public function addDatabase($database): Database
    {
        if ($database instanceof Database) {
            $platform = null;
            $database->setParentSchema($this);
            if ($database->getPlatform() === null) {
                $config = $this->getGeneratorConfig();
                if ($config) {
                    $platform = $config->getConfiguredPlatform(null, $database->getName());
                }

                $database->setPlatform($platform ?: $this->platform);
            }
            $this->databases[] = $database;

            return $database;
        }

        // XML attributes array / hash
        $db = new Database();
        $db->setParentSchema($this);
        $db->loadMapping($database);

        return $this->addDatabase($db);
    }

    /**
     * Finalizes the databases initialization.
     *
     * @return void
     */
    public function doFinalInitialization(): void
    {
        if (!$this->isInitialized) {
            foreach ($this->databases as $database) {
                $database->doFinalInitialization();
            }
            $this->isInitialized = true;
        }
    }

    /**
     * Merge other Schema objects together into this Schema object.
     *
     * @param array<\Propel\Generator\Model\Schema> $schemas
     *
     * @throws \Propel\Generator\Exception\EngineException
     *
     * @return void
     */
    public function joinSchemas(array $schemas): void
    {
        foreach ($schemas as $schema) {
            foreach ($schema->getDatabases(false) as $addDb) {
                $addDbName = $addDb->getName();
                if ($this->hasDatabase($addDbName)) {
                    $db = $this->getDatabase($addDbName, false);
                    // temporarily reset database namespace to avoid double namespace decoration (see ticket #1355)
                    $namespace = $db->getNamespace();
                    $db->setNamespace('');
                    // join tables
                    foreach ($addDb->getTables() as $addTable) {
                        if ($db->getTable($addTable->getName())) {
                            throw new EngineException(sprintf('Duplicate table found: %s.', $addTable->getName()));
                        }
                        $db->addTable($addTable);
                    }
                    // join database behaviors
                    foreach ($addDb->getBehaviors() as $addBehavior) {
                        if (!$db->hasBehavior($addBehavior->getId())) {
                            $db->addBehavior($addBehavior);
                        }
                    }
                    // restore the database namespace
                    $db->setNamespace($namespace);
                } else {
                    $this->addDatabase($addDb);
                }
            }
        }
    }

    /**
     * Returns the number of tables in all the databases of this Schema object.
     *
     * @return int
     */
    public function countTables(): int
    {
        $nb = 0;
        foreach ($this->getDatabases() as $database) {
            $nb += $database->countTables();
        }

        return $nb;
    }

    /**
     * Creates a string representation of this Schema.
     * The representation is given in xml format.
     *
     * @return string Representation in xml format
     */
    public function toString(): string
    {
        $dumper = new XmlDumper();

        return $dumper->dumpSchema($this);
    }

    /**
     * Magic string method
     *
     * @see toString()
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
