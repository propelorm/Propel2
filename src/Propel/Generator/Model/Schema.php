<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
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
     * @var Database[]
     */
    private $databases;
    /**
     * @var PlatformInterface
     */
    private $platform;
    private $name;
    private $isInitialized;
    protected $generatorConfig;

    /**
     * Creates a new instance for the specified database type.
     *
     * @param PlatformInterface $platform
     */
    public function __construct(PlatformInterface $platform = null)
    {
        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        $this->isInitialized = false;
        $this->databases     = [];
    }

    /**
     * Sets the platform object to use for any databases added to this
     * application schema.
     *
     * @param PlatformInterface $platform
     */
    public function setPlatform(PlatformInterface $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Returns the platform object to use for any databases added to this
     * application schema.
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Sets the generator configuration
     *
     * @param GeneratorConfigInterface $generatorConfig
     */
    public function setGeneratorConfig(GeneratorConfigInterface $generatorConfig)
    {
        $this->generatorConfig = $generatorConfig;
    }

    /**
     * Returns the generator configuration
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig()
    {
        return $this->generatorConfig;
    }

    /**
     * Sets the schema name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the schema name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the schema short name (without the '-schema' postfix).
     *
     * @return string
     */
    public function getShortName()
    {
        return str_replace('-schema', '', $this->name);
    }

    /**
     * Returns an array of all databases.
     *
     * The first boolean parameter tells whether or not to run the
     * final initialization process.
     *
     * @param  boolean $doFinalInitialization
     * @return array
     */
    public function getDatabases($doFinalInitialization = true)
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
        if ($doFinalInitialization) {
            $this->doFinalInitialization();
        }

        return $this->databases;
    }

    /**
     * Returns whether or not this schema has multiple databases.
     *
     * @return boolean
     */
    public function hasMultipleDatabases()
    {
        return count($this->databases) > 1;
    }

    /**
     * Returns the database according to the specified name.
     *
     * @param  string   $name
     * @param  boolean  $doFinalInitialization
     * @return Database
     */
    public function getDatabase($name = null, $doFinalInitialization = true)
    {
        // this is temporary until we'll have a clean solution
        // for packaging datamodels/requiring schemas
        if ($doFinalInitialization) {
            $this->doFinalInitialization();
        }

        if (null === $name) {
            return $this->databases[0];
        }

        $db = null;
        foreach ($this->databases as $database) {
            if ($database->getName() === $name) {
                $db = $database;
                break;
            }
        }

        return $db;
    }

    /**
     * Returns whether or not a database with the specified name exists in this
     * schema.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasDatabase($name)
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
     * @param  Database|array $database
     * @return Database
     */
    public function addDatabase($database)
    {
        if ($database instanceof Database) {
            $platform = null;
            $database->setParentSchema($this);
            if (null === $database->getPlatform()) {
                if ($config = $this->getGeneratorConfig()) {
                    $platform = $config->getConfiguredPlatform(null, $database->getName());
                }

                $database->setPlatform($platform ? $platform : $this->platform);
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
     */
    public function doFinalInitialization()
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
     * @param array $schemas
     */
    public function joinSchemas(array $schemas)
    {
        foreach ($schemas as $schema) {
            foreach ($schema->getDatabases(false) as $addDb) {
                $addDbName = $addDb->getName();
                if ($this->hasDatabase($addDbName)) {
                    $db = $this->getDatabase($addDbName, false);
                    // temporarily reset database namespace to avoid double namespace decoration (see ticket #1355)
                    $namespace = $db->getNamespace();
                    $db->setNamespace(null);
                    // join tables
                    foreach ($addDb->getTables() as $addTable) {
                        if ($db->getTable($addTable->getName())) {
                            throw new EngineException(sprintf('Duplicate table found: %s.', $addTable->getName()));
                        }
                        $db->addTable($addTable);
                    }
                    // join database behaviors
                    foreach ($addDb->getBehaviors() as $addBehavior) {
                        if (!$db->hasBehavior($addBehavior->getName())) {
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
     * @return integer
     */
    public function countTables()
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
    public function toString()
    {
        $dumper = new XmlDumper();
        
        return $dumper->dumpSchema($this);
    }

    /**
     * Magic string method
     *
     * @see toString()
     */
    public function __toString()
    {
        return $this->toString();
    }
}
