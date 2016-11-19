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
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Platform\PlatformInterface;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally<jmcnally@collab.net> (Torque)
 * @author Martin Poeschl<mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall<dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Database extends ScopedMappingModel
{

    use BehaviorableTrait;

    /**
     * The database's platform.
     *
     * @var PlatformInterface
     */
    private $platform;

    /**
     * @var string
     */
    private $platformClass;

    /**
     * @var Entity[]
     */
    private $entities;

    /**
     * @var string
     */
    private $name;

    private $defaultIdMethod;

    /**
     * The default accessor visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $defaultAccessorVisibility;

    /**
     * The default mutator visibility.
     *
     * It may be one of public, private and protected.
     *
     * @var string
     */
    private $defaultMutatorVisibility;
    private $domainMap;
    private $heavyIndexing;

    /**
     * @var boolean
     */
    private $identifierQuoting;

    /** @var Schema */
    private $parentSchema;

    /**
     * @var Entity[]
     */
    private $entitiesByName;
    private $entitiesByFullClassName;
    private $entitiesByTableName;

    /**
     * @var Entity[]
     */
    private $entitiesByLowercaseName;

//    /**
//     * @var Entity[]
//     */
//    private $entitiesByPhpName;

    /**
     * @var string[]
     */
    private $sequences;

    protected $defaultStringFormat;
    protected $tablePrefix;

    /**
     * @var bool
     */
    protected $activeRecord = false;

    /**
     * Constructs a new Database object.
     *
     * @param string            $name     The database's name
     * @param PlatformInterface $platform The database's platform
     */
    public function __construct($name = null, PlatformInterface $platform = null)
    {
        parent::__construct();

        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        $this->heavyIndexing             = false;
        $this->identifierQuoting         = false;
        $this->defaultIdMethod           = IdMethod::NATIVE;
        $this->defaultStringFormat       = static::DEFAULT_STRING_FORMAT;
        $this->defaultAccessorVisibility = static::VISIBILITY_PUBLIC;
        $this->defaultMutatorVisibility  = static::VISIBILITY_PUBLIC;
        $this->behaviors                 = [];
        $this->domainMap                 = [];
        $this->entities                    = [];
        $this->entitiesByName              = [];
//        $this->entitiesByPhpName           = [];
        $this->entitiesByLowercaseName     = [];
    }

    protected function setupObject()
    {
        parent::setupObject();

        $this->name = $this->getAttribute('name');
        $this->platformClass = $this->getAttribute('platform');
        $this->baseClass = $this->getAttribute('baseClass');
        $this->defaultIdMethod = $this->getAttribute('defaultIdMethod', IdMethod::NATIVE);
        $this->heavyIndexing = $this->booleanValue($this->getAttribute('heavyIndexing'));
        $this->identifierQuoting = $this->getAttribute('identifierQuoting') ? $this->booleanValue($this->getAttribute('identifierQuoting')) : false;
        $this->tablePrefix = $this->getAttribute('tablePrefix', $this->getBuildProperty('generator.tablePrefix'));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat', static::DEFAULT_STRING_FORMAT);

        if ($this->getAttribute('activeRecord')) {
            $this->activeRecord = 'true' === $this->getAttribute('activeRecord');
        }
    }

    /**
     * Returns the PlatformInterface implementation for this database.
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        if (null === $this->platform) {
            if ($this->getParentSchema() && $this->getParentSchema()->getPlatform()) {
                return $this->getParentSchema()->getPlatform();
            }

            if ($this->getGeneratorConfig()) {
                if ($this->platformClass) {
                    $this->platform = $this->getGeneratorConfig()->createPlatform($this->platformClass);
                } else {
                    $this->platform = $this->getGeneratorConfig()->createPlatformForDatabase($this->getName());
                }
            }
        }

        return $this->platform;
    }

    /**
     * Sets the PlatformInterface implementation for this database.
     *
     * @param PlatformInterface $platform A Platform implementation
     */
    public function setPlatform(PlatformInterface $platform = null)
    {
        $this->platform = $platform;
    }

    /**
     * @return boolean
     */
    public function isActiveRecord()
    {
        return $this->activeRecord;
    }

    /**
     * @param boolean $activeRecord
     */
    public function setActiveRecord($activeRecord)
    {
        $this->activeRecord = $activeRecord;
    }

    /**
     * Returns the max column name's length.
     *
     * @return integer
     */
    public function getMaxFieldNameLength()
    {
        return $this->getPlatform()->getMaxFieldNameLength();
    }

    /**
     * Returns the database name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the database name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the default ID method strategy.
     * This parameter can be overridden at the entity level.
     *
     * @return string
     */
    public function getDefaultIdMethod()
    {
        return $this->defaultIdMethod;
    }

    /**
     * Sets the name of the default ID method strategy.
     * This parameter can be overridden at the entity level.
     *
     * @param string $strategy
     */
    public function setDefaultIdMethod($strategy)
    {
        $this->defaultIdMethod = $strategy;
    }

    /**
     * Returns the list of supported string formats
     *
     * @return array
     */
    public static function getSupportedStringFormats()
    {
        return [ 'XML', 'YAML', 'JSON', 'CSV' ];
    }

    /**
     * Sets the default string format for ActiveRecord objects in this entity.
     * This parameter can be overridden at the entity level.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param  string                   $format
     * @throws InvalidArgumentException
     */
    public function setDefaultStringFormat($format)
    {
        $formats = static::getSupportedStringFormats();

        $format = strtoupper($format);
        if (!in_array($format, $formats)) {
            throw new InvalidArgumentException(sprintf('Given "%s" default string format is not supported. Only "%s" are valid string formats.', $format, implode(', ', $formats)));
        }

        $this->defaultStringFormat = $format;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this entity.
     * This parameter can be overridden at the entity level.
     *
     * @return string
     */
    public function getDefaultStringFormat()
    {
        return $this->defaultStringFormat;
    }

    /**
     * Returns whether or not heavy indexing is enabled.
     *
     * This is an alias for getHeavyIndexing().
     *
     * @return boolean
     */
    public function isHeavyIndexing()
    {
        return $this->getHeavyIndexing();
    }

    /**
     * Returns whether or not heavy indexing is enabled.
     *
     * This is an alias for isHeavyIndexing().
     *
     * @return boolean
     */
    public function getHeavyIndexing()
    {
        return $this->heavyIndexing;
    }

    /**
     * Sets whether or not heavy indexing is enabled.
     *
     * @param boolean $flag
     */
    public function setHeavyIndexing($flag = true)
    {
        $this->heavyIndexing = (Boolean) $flag;
    }

    /**
     * Return the list of all entities.
     *
     * @return Entity[]
     */
    public function getEntities()
    {
        return (array)$this->entities;
    }

    /**
     * Return the number of entities in the database.
     *
     * Read-only entities are excluded from the count.
     *
     * @return integer
     */
    public function countEntities()
    {
        $count = 0;
        foreach ($this->entities as $entity) {
            if (!$entity->isReadOnly()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns the list of all entities that have a SQL representation.
     *
     * @return Entity[]
     */
    public function getEntitiesForSql()
    {
        $entities = [];
        foreach ($this->entities as $entity) {
            if (!$entity->isSkipSql()) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    /**
     * Returns whether or not the database has a entity.
     *
     * @param  string  $name
     * @param  boolean $caseInsensitive
     * @return boolean
     */
    public function hasEntity($name, $caseInsensitive = false)
    {
        if ($this->hasEntityByFullClassName($name)) {
            return true;
        }

        if ($caseInsensitive) {
            return isset($this->entitiesByLowercaseName[ strtolower($name) ]);
        }

        return isset($this->entitiesByName[$name]);
    }

    /**
     * @param string $fullClassName
     *
     * @return bool
     */
    public function hasEntityByFullClassName($fullClassName)
    {
        return isset($this->entitiesByFullClassName[$fullClassName]);
    }

    /**
     * @param string $fullClassName
     *
     * @return Entity
     */
    public function getEntityByFullClassName($fullClassName)
    {
        return $this->entitiesByFullClassName[$fullClassName];
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Entity
     */
    public function getEntityByTableName($tableName)
    {
        $schema = $this->getSchema() ?: $this->getName();

        if (!isset($this->entitiesByTableName[$tableName])) {
            if (isset($this->entitiesByTableName[$schema . $this->getSchemaDelimiter() . $tableName])) {
                return $this->entitiesByTableName[$schema . $this->getSchemaDelimiter() . $tableName];
            }

            throw new \InvalidArgumentException("Entity by table name $tableName not found in {$this->getName()}.");
        }

        return $this->entitiesByTableName[$tableName];
    }

    /**
     * Returns the entity with the specified name.
     *
     * @param  string  $name
     * @param  boolean $caseInsensitive
     * @return Entity
     */
    public function getEntity($name, $caseInsensitive = false)
    {
        if ($this->hasEntityByFullClassName($name)) {
            return $this->getEntityByFullClassName($name);
        }


        if (!$this->hasEntity($name, $caseInsensitive)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entity %s in database %s not found [%s]',
                    $name,
                    $this->getName(),
                    $this->getEntityNames()
                )
            );
        }

        if ($caseInsensitive) {
            return $this->entitiesByLowercaseName[strtolower($name)];
        }

        return $this->entitiesByName[$name];
    }

    /**
     * @return string
     */
    public function getEntityNames()
    {
        return implode(',', array_keys($this->entitiesByName));
    }

//    /**
//     * Returns whether or not the database has a entity identified by its
//     * PHP name.
//     *
//     * @param  string  $phpName
//     * @return boolean
//     */
//    public function hasEntityByPhpName($phpName)
//    {
//        return isset($this->entitiesByPhpName[$phpName]);
//    }
//
//    /**
//     * Returns the entity object with the specified PHP name.
//     *
//     * @param  string $phpName
//     * @return Entity
//     */
//    public function getEntityByPhpName($phpName)
//    {
//        if (isset($this->entitiesByPhpName[$phpName])) {
//            return $this->entitiesByPhpName[$phpName];
//        }
//
//        return null; // just to be explicit
//    }

    /**
     * Adds a new entity to this database.
     *
     * @param  Entity|array $entity
     * @return Entity
     */
    public function addEntity($entity)
    {
        if (!$entity instanceof Entity) {
            $tbl = new Entity();
            $tbl->setDatabase($this);
            $tbl->loadMapping($entity);

            return $this->addEntity($tbl);
        }

        $entity->setDatabase($this);

        if (isset($this->entitiesByFullClassName[$entity->getFullClassName()])) {
            throw new EngineException(sprintf('Entity "%s" declared twice', $entity->getName()));
        }

        $this->entities[] = $entity;
        $this->entitiesByFullClassName[$entity->getFullClassName()] = $entity;
        $this->entitiesByTableName[$entity->getFQTableName()] = $entity;
        $this->entitiesByName[$entity->getName()] = $entity;
        $this->entitiesByLowercaseName[strtolower($entity->getName())] = $entity;
//        $this->entitiesByPhpName[$entity->getName()] = $entity;

//        $this->computeEntityNamespace($entity);

        if (null === $entity->getPackage()) {
            $entity->setPackage($this->getPackage());
        }

        return $entity;
    }

    /**
     * Adds several entities at once.
     *
     * @param Entity[] $entities An array of Entity instances
     */
    public function addEntities(array $entities)
    {
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }
    }

    /**
     * @param string[] $sequences
     */
    public function setSequences($sequences)
    {
        $this->sequences = $sequences;
    }

    /**
     * @return string[]
     */
    public function getSequences()
    {
        return $this->sequences;
    }

    /**
     * @param string $sequence
     */
    public function addSequence($sequence)
    {
        $this->sequences[] = $sequence;
    }

    /**
     * @param string $sequence
     */
    public function removeSequence($sequence)
    {
        if ($this->sequences) {
            if (false !== ($idx = array_search($sequence, $this->sequences))) {
                unset($this->sequence[$idx]);
            }
        }
    }

    /**
     * @param  string $sequence
     * @return bool
     */
    public function hasSequence($sequence)
    {
        return $this->sequences && in_array($sequence, $this->sequences);
    }

    /**
     * Returns the schema delimiter character.
     *
     * For example, the dot character with mysql when
     * naming entities. For instance: schema.the_entity.
     *
     * @return string
     */
    public function getSchemaDelimiter()
    {
        return $this->getPlatform()->getSchemaDelimiter();
    }

//    /**
//     * Sets the database's schema.
//     *
//     * @param string $schema
//     */
//    public function setSchema($schema)
//    {
//        $oldSchema = $this->schema;
//        if ($this->schema !== $schema && $this->getPlatform()) {
//            $schemaDelimiter = $this->getPlatform()->getSchemaDelimiter();
//            $fixHash = function (&$array) use ($schema, $oldSchema, $schemaDelimiter) {
//                foreach ($array as $k => $v) {
//                    if ($schema && $this->getPlatform()->supportsSchemas()) {
//                        if (false === strpos($k, $schemaDelimiter)) {
//                            $array[$schema . $schemaDelimiter . $k] = $v;
//                            unset($array[$k]);
//                        }
//                    } elseif ($oldSchema) {
//                        if (false !== strpos($k, $schemaDelimiter)) {
//                            $array[explode($schemaDelimiter, $k)[1]] = $v;
//                            unset($array[$k]);
//                        }
//                    }
//                }
//            };
//
//            $fixHash($this->entitiesByName);
//            $fixHash($this->entitiesByLowercaseName);
//        }
//        parent::setSchema($schema);
//    }

//    /**
//     * Computes the entity namespace based on the current relative or
//     * absolute entity namespace and the database namespace.
//     *
//     * @param  Entity  $entity
//     * @return string
//     */
//    private function computeEntityNamespace(Entity $entity)
//    {
//        $namespace = $entity->getNamespace();
//        if ($this->isAbsoluteNamespace($namespace)) {
//            $namespace = ltrim($namespace, '\\');
//            $entity->setNamespace($namespace);
//
//            return $namespace;
//        }
//
//        if ($namespace = $this->getNamespace()) {
//            if ($entity->getNamespace()) {
//                $namespace .= '\\'.$entity->getNamespace();
//            }
//
//            $entity->setNamespace($namespace);
//        }
//
//        return $namespace;
//    }

    /**
     * Sets the parent schema
     *
     * @param Schema $parent The parent schema
     */
    public function setParentSchema(Schema $parent)
    {
        $this->parentSchema = $parent;
    }

    /**
     * Returns the parent schema
     *
     * @return Schema
     */
    public function getParentSchema()
    {
        return $this->parentSchema;
    }

    /**
     * Adds a domain object to this database.
     *
     * @param  Domain|array $data
     * @return Domain
     */
    public function addDomain($data)
    {
        if ($data instanceof Domain) {
            $domain = $data; // alias
            $domain->setDatabase($this);
            $this->domainMap[$domain->getName()] = $domain;

            return $domain;
        }

        $domain = new Domain();
        $domain->setDatabase($this);
        $domain->loadMapping($data);

        return $this->addDomain($domain); // call self w/ different param
    }

    /**
     * Returns the already configured domain object by its name.
     *
     * @param  string $name
     * @return Domain
     */
    public function getDomain($name)
    {
        if (isset($this->domainMap[$name])) {
            return $this->domainMap[$name];
        }

        return null;
    }

    /**
     * Returns the GeneratorConfigInterface object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig()
    {
        if ($this->parentSchema) {
            return $this->parentSchema->getGeneratorConfig();
        }
    }

    /**
     * Returns the configuration property identified by its name.
     *
     * @see \Propel\Common\Config\ConfigurationManager::getConfigProperty() method
     *
     * @param  string $name
     * @return string
     */
    public function getBuildProperty($name)
    {
        if ($config = $this->getGeneratorConfig()) {
            return $config->getConfigProperty($name);
        }
    }

    /**
     * Returns the entity prefix for this database.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Sets the entities' prefix.
     *
     * @param string $tablePrefix
     */
    public function setTablePrefix($tablePrefix)
    {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * Returns the next behavior on all entities, ordered by behavior priority,
     * and skipping the ones that were already executed.
     *
     * @return Behavior
     */
    public function getNextEntityBehavior()
    {
        // order the behaviors according to Behavior::$entityModificationOrder
        $behaviors = [];
        $nextBehavior = null;
        foreach ($this->entities as $entity) {
            foreach ($entity->getBehaviors() as $behavior) {
                if (!$behavior->isEntityModified()) {
                    $behaviors[$behavior->getEntityModificationOrder()][] = $behavior;
                }
            }
        }
        ksort($behaviors);
        if (count($behaviors)) {
            $nextBehavior = $behaviors[key($behaviors)][0];
        }

        return $nextBehavior;
    }

    /**
     * Finalizes the setup process.
     *
     */
    public function doFinalInitialization()
    {
        // execute database behaviors
        foreach ($this->getBehaviors() as $behavior) {
            $behavior->modifyDatabase();
        }

        // execute entity behaviors (may add new entities and new behaviors)
        while ($behavior = $this->getNextEntityBehavior()) {
            $behavior->getEntityModifier()->modifyEntity();
            $behavior->setEntityModified(true);
        }

        if ($this->getPlatform()) {
            $this->getPlatform()->finalizeDefinition($this);
        }
    }

    /**
     * @param Behavior $behavior
     */
    protected function registerBehavior(Behavior $behavior)
    {
        $behavior->setDatabase($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $entities = [];
        foreach ($this->getEntities() as $entity) {
            $columns = [];
            foreach ($entity->getFields() as $column) {
                $columns[] = sprintf("      %s %s %s %s %s %s",
                    $column->getName(),
                    $column->getType(),
                    $column->getSize() ? '(' . $column->getSize() . ')' : '',
                    $column->isPrimaryKey() ? 'PK' : '',
                    $column->isNotNull() ? 'NOT NULL' : '',
                    $column->getDefaultValueString() ? "'".$column->getDefaultValueString()."'" : '',
                    $column->isAutoIncrement() ? 'AUTO_INCREMENT' : ''
                );
            }

            $fks = [];
            foreach ($entity->getRelations() as $fk) {
                $fks[] = sprintf("      %s to %s.%s (%s => %s)",
                    $fk->getName(),
                    $fk->getForeignSchemaName(),
                    $fk->getForeignEntityCommonName(),
                    join(', ', $fk->getLocalFields()),
                    join(', ', $fk->getForeignFields())
                );
            }

            $indices = [];
            foreach ($entity->getIndices() as $index) {
                $indexFields = [];
                foreach ($index->getFields() as $indexFieldName) {
                    $indexFields[] = sprintf('%s (%s)', $indexFieldName, $index->getFieldSize($indexFieldName));
                }
                $indices[] = sprintf("      %s (%s)",
                    $index->getName(),
                    join(', ', $indexFields)
                );
            }

            $unices = [];
            foreach ($entity->getUnices() as $index) {
                $unices[] = sprintf("      %s (%s)",
                    $index->getName(),
                    join(', ', $index->getFields())
                );
            }

            $entityDef = sprintf("  %s (%s):\n%s",
                $entity->getName(),
                $entity->getCommonName(),
                implode("\n", $columns)
            );

            if ($fks) {
                $entityDef .= "\n    FKs:\n" . implode("\n", $fks);
            }

            if ($indices) {
                $entityDef .= "\n    indices:\n" . implode("\n", $indices);
            }

            if ($unices) {
                $entityDef .= "\n    unices:\n". implode("\n", $unices);
            }

            $entities[] = $entityDef;
        }

        return sprintf("%s:\n%s",
            $this->getName() . ($this->getSchema() ? '.'. $this->getSchema() : ''),
            implode("\n", $entities)
        );
    }

    /**
     * Sets the default accessor visibility.
     *
     * @param string $defaultAccessorVisibility
     */
    public function setDefaultAccessorVisibility($defaultAccessorVisibility)
    {
        $this->defaultAccessorVisibility = $defaultAccessorVisibility;
    }

    /**
     * Returns the default accessor visibility.
     *
     * @return string
     */
    public function getDefaultAccessorVisibility()
    {
        return $this->defaultAccessorVisibility;
    }

    /**
     * Sets the default mutator visibility.
     *
     * @param string $defaultMutatorVisibility
     */
    public function setDefaultMutatorVisibility($defaultMutatorVisibility)
    {
        $this->defaultMutatorVisibility = $defaultMutatorVisibility;
    }

    /**
     * Returns the default mutator visibility.
     *
     * @return string
     */
    public function getDefaultMutatorVisibility()
    {
        return $this->defaultMutatorVisibility;
    }

    public function __clone()
    {
        $entities = [];
        foreach ($this->entities as $oldEntity) {
            $entity = clone $oldEntity;
            $entities[] = $entity;
            $this->entitiesByName[$entity->getName()] = $entity;
            $this->entitiesByLowercaseName[strtolower($entity->getName())] = $entity;
//            $this->entitiesByPhpName[$entity->getName()] = $entity;
        }
        $this->entities = $entities;
    }

    /**
     * @return boolean
     */
    public function isIdentifierQuotingEnabled()
    {
        return $this->identifierQuoting;
    }

    /**
     * @param boolean $identifierQuoting
     */
    public function setIdentifierQuoting($identifierQuoting)
    {
        $this->identifierQuoting = $identifierQuoting;
    }

}
