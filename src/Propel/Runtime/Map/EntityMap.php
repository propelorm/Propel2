<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Runtime\Map;

use Propel\Generator\Model\NamingTool;
use Propel\Runtime\Configuration;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\Exception\FieldNotFoundException;
use Propel\Runtime\Map\Exception\RelationNotFoundException;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Session\DependencyGraph;
use Propel\Runtime\Session\Session;

/**
 * EntityMap is used to model a entity in a database.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author John D. McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class EntityMap
{
    /**
     * phpname type, the actual name of the property in a entity.
     * e.g. 'authorId'
     */
    const TYPE_PHPNAME = 'phpName';

    /**
     * camelCase type
     * e.g. 'author_id'
     */
    const TYPE_COLNAME = 'colName';

    /**
     * field (entityMap) name type
     * e.g. 'book.author_id'
     */
    const TYPE_FULLCOLNAME = 'fullColName';

    /**
     * field fieldname type
     * e.g. 'AuthorId'
     */
    const TYPE_FIELDNAME = 'fieldName';

    /**
     * num type
     * simply the numerical array index, e.g. 4
     */
    const TYPE_NUM = 'num';

    /**
     * Fields in the entity
     *
     * @var FieldMap[]
     */
    protected $fields = array();

    /**
     * Fields in the entity, using entity phpName as key
     *
     * @var FieldMap[]
     */
    protected $fieldsByLowercaseName = array();

    /**
     * The (class) name of the entity
     *
     * @var string
     */
    protected $entityName;

    protected $fieldNames = [];
    protected $fieldKeys = [];

    /**
     * @var string
     */
    protected $tableName;

    /**
     * The full class name for this entity with namespace.
     *
     * @var string
     */
    protected $fullClassName;

//    /**
//     * The Package for this entity
//     *
//     * @var string
//     */
//    protected $package;

    /**
     * Whether to use an id generator for pkey
     *
     * @var boolean
     */
    protected $useIdGenerator;

    /**
     * Whether the entity uses single entity inheritance
     *
     * @var boolean
     */
    protected $singleEntityInheritance = false;

    /**
     * Whether the entity is a Many to Many entity
     *
     * @var boolean
     */
    protected $crossRef = false;

    /**
     * The primary key fields in the entity
     *
     * @var FieldMap[]
     */
    protected $primaryKeys = array();

    /**
     * The foreign key fields in the entity
     *
     * @var FieldMap[]
     */
    protected $foreignKeys = array();

    /**
     *  The relationships in the entity
     *
     * @var RelationMap[]
     */
    protected $relations = array();

    /**
     *  Relations are lazy loaded. This property tells if the relations are loaded or not
     *
     * @var boolean
     */
    protected $relationsBuilt = false;

    /**
     *  Object to store information that is needed if the for generating primary keys
     *
     * @var mixed
     */
    protected $pkInfo;

    /**
     * @var boolean
     */
    protected $identifierQuoting = null;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $databaseName;

    protected $classReader = [];
    protected $classIsser = [];
    protected $classWriter = [];
    protected $propReader;
    protected $propWriter;
    protected $propIsset;

    /**
     * @var bool
     */
    protected $allowPkInsert;

    /**
     * Construct a new EntityMap.
     */
    public function __construct($name, DatabaseMap $dbMap, Configuration $configuration)
    {
        $this->name = $name;
        $this->setConfiguration($configuration);
        $this->initialize();
    }

    abstract public function fromArray($entity, array $arr, $keyType = EntityMap::TYPE_FIELDNAME);

    /**
     * Active-Record like access to this entityMap. Primarily for prototyping usages.
     *
     * @return EntityMap
     */
    public static function getEntityMap()
    {
        return Configuration::getCurrentConfiguration()->getEntityMap(static::ENTITY_CLASS);
    }

    /**
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * @param string $databaseName
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * @return \Propel\Runtime\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->getConfiguration()->getAdapter($this->getDatabaseName());
    }

    public function getFieldType($fieldName)
    {
        $field = $this->getField($fieldName);
        return $this->getConfiguration()->getFieldType($field->getType());
    }

    /**
     * @see FieldTypeInterface::propertyToSnapshot
     *
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     */
    public function propertyToSnapshot($value, $fieldName)
    {
        $fieldType = $this->getFieldType($fieldName);
        return $fieldType->propertyToSnapshot($value, $this->getField($fieldName));
    }

    /**
     * @see FieldTypeInterface::snapshotToProperty
     *
     * @param mixed $value
     * @param string $fieldName
     */
    public function snapshotToProperty($value, $fieldName)
    {
        $fieldType = $this->getFieldType($fieldName);
        return $fieldType->snapshotToProperty($value, $this->getField($fieldName));
    }

    /**
     * @see FieldTypeInterface::propertyToDatabase
     *
     * @param mixed $value
     * @param string $fieldName
     *
     * @return mixed
     */
    public function propertyToDatabase($value, $fieldName)
    {
        $fieldType = $this->getFieldType($fieldName);

        return $fieldType->propertyToDatabase($value, $this->getField($fieldName));
    }

    /**
     * @see FieldTypeInterface::databaseToProperty
     *
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     */
    public function databaseToProperty($value, $fieldName)
    {
        $fieldType = $this->getFieldType($fieldName);

        return $fieldType->databaseToProperty($value, $this->getField($fieldName));
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    abstract public function populateDependencyGraph($entity, DependencyGraph $dependencyGraph);

    abstract public function populateObject(array $row, &$offset = 0, $indexType = EntityMap::TYPE_NUM, $entity = null);

    abstract public function isValidRow(array $row, $offset = 0);

    abstract public function getSnapshot($entity);

    abstract public function getPropWriter();

    abstract public function getPropReader();

    abstract public function getPropIsset();

    abstract public function persistDependencies(Session $session, $entity, $deep = false);

    public function getPersisterClass()
    {
        return '\Propel\Runtime\Persister\SqlPersister';
    }

    /**
     * @return \Propel\Runtime\Repository\Repository
     */
    public function getRepository()
    {
        return $this->getConfiguration()->getRepository($this->getFullClassName());
    }

    /**
     * Initialize the EntityMap to build fields, relations, etc
     * This method should be overridden by descendants
     */
    public function initialize()
    {
    }

    /**
     * Set the (class) name of the Entity without namespace.
     *
     * @param string $name The name of the entity.
     */
    public function setName($name)
    {
        $this->entityName = $name;
    }

    /**
     * Get the (class) name of the Entity without namespace.
     *
     * @return string A String with the name of the entity.
     */
    public function getName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string|null
     */
    public function getSchemaName()
    {
        if (defined('static::SCHEMA_NAME')) {
            return static::SCHEMA_NAME;
        }

        return null;
    }

    /**
     * Returns the full qualified table name (with schema).
     *
     * @return string
     */
    public function getFQTableName()
    {
        return static::FQ_TABLE_NAME;
    }

    /**
     * Set the full class of the Entity with namespace.
     *
     * @param string $className The ClassName
     */
    public function setFullClassName($className)
    {
        $this->fullClassName = $className;
    }

    /**
     * Get the full class of the Entity with namespace.
     *
     * @return string
     */
    public function getFullClassName()
    {
        return $this->fullClassName;
    }

    /**
     * @return boolean
     */
    public function isAllowPkInsert()
    {
        return $this->allowPkInsert;
    }

    /**
     * @param boolean $allowPkInsert
     */
    public function setAllowPkInsert($allowPkInsert)
    {
        $this->allowPkInsert = $allowPkInsert;
    }

//    /**
//     * Set the Package of the Entity
//     *
//     * @param string $package The Package
//     */
//    public function setPackage($package)
//    {
//        $this->package = $package;
//    }
//
//    /**
//     * Get the Package of the entity.
//     *
//     * @return string
//     */
//    public function getPackage()
//    {
//        return $this->package;
//    }


    /**
     * Set whether or not to use Id generator for primary key.
     *
     * @param boolean $bit
     */
    public function setUseIdGenerator($bit)
    {
        $this->useIdGenerator = $bit;
    }

    /**
     * Whether to use Id generator for primary key.
     *
     * @return boolean
     */
    public function isUseIdGenerator()
    {
        return $this->useIdGenerator;
    }

    /**
     * Set whether or not to this entity uses single entity inheritance
     *
     * @param boolean $bit
     */
    public function setSingleEntityInheritance($bit)
    {
        $this->singleEntityInheritance = $bit;
    }

    /**
     * Whether this entity uses single entity inheritance
     *
     * @return boolean
     */
    public function isSingleEntityInheritance()
    {
        return $this->singleEntityInheritance;
    }

    /**
     * Sets the name of the sequence used to generate a key
     *
     * @param mixed $pkInfo information needed to generate a key
     */
    public function setPrimaryKeyMethodInfo($pkInfo)
    {
        $this->pkInfo = $pkInfo;
    }

    /**
     * Get the name of the sequence used to generate a primary key
     *
     * @return mixed
     */
    public function getPrimaryKeyMethodInfo()
    {
        return $this->pkInfo;
    }

    /**
     * Helper method which returns the primary key contained
     * in the given Criteria object.
     *
     * @param  Criteria $criteria A Criteria.
     *
     * @return FieldMap If the Criteria object contains a primary key, or null if it doesn't.
     *
     * @throws \Propel\Runtime\Exception\RuntimeException
     */
    private function getPrimaryKey(Criteria $criteria)
    {
        // Assume all the keys are for the same entity.
        $keys = $criteria->keys();
        $key = $keys[0];
        $entityName = $criteria->getEntityName($key);

        $pk = null;

        if (!empty($entityName)) {
            $pks = $this->getConfiguration()->getEntityMap($entityName)->getPrimaryKeys();
            if (!empty($pks)) {
                $pk = array_shift($pks);
            }
        }

        return $pk;
    }

    /**
     * @param object $instance
     *
     * @return \Closure
     */
    public function newPropReader($instance)
    {
        $getter = \Closure::bind(
            function ($object, $prop) {
                return $object->$prop;
            },
            null,
            get_class($instance)
        );

        return function ($prop) use ($instance, $getter) {
            return $getter($instance, $prop);
        };
    }

    /**
     * @param string $className
     *
     * @return \Closure
     */
    public function getClassPropReader($className)
    {
        if (isset($this->classReader[$className])) {
            return $this->classReader[$className];
        }

        $this->classReader[$className] = \Closure::bind(
            function ($object, $prop) {
                return $object->$prop;
            },
            null,
            $className
        );

        return $this->classReader[$className];
    }

    /**
     * @param string $className
     *
     * @return \Closure
     */
    public function getClassPropIsset($className)
    {
        if (isset($this->classIsser[$className])) {
            return $this->classIsser[$className];
        }

        $this->classIsser[$className] = \Closure::bind(
            function ($object, $prop) {
                return isset($object->$prop);
            },
            null,
            $className
        );

        return $this->classIsser[$className];
    }

    /**
     * @param string $className
     *
     * @return \Closure
     */
    public function getClassPropWriter($className)
    {
        if (isset($this->classWriter[$className])) {
            return $this->classWriter[$className];
        }

        $this->classWriter[$className] = \Closure::bind(
            function ($object, $prop, $value) {
                $object->$prop = $value;
            },
            null,
            $className
        );

        return $this->classWriter[$className];
    }

    /**
     * @return string
     */
    public function getRepositoryClass()
    {
    }

    /**
     * Add a field to the entity.
     *
     * @param  string $name A String with the field name.
     * @param  string $phpName A string representing the PHP name.
     * @param  string $type A string specifying the Propel type.
     * @param  boolean $isNotNull Whether field does not allow NULL values.
     * @param  int $size An int specifying the size.
     * @param  boolean $pk True if field is a primary key.
     * @param  string $fkEntity A String with the foreign key entity name.
     * @param  string $fkField A String with the foreign key field name.
     * @param  string $defaultValue The default value for this field.
     *
     * @return \Propel\Runtime\Map\FieldMap The newly created field.
     */
    public function addField(
        $name,
        $type,
        $isNotNull = false,
        $size = null,
        $defaultValue = null,
        $pk = false,
        $implementationDetail = false,
        $fkEntity = null,
        $fkField = null
    )
    {
        $field = new FieldMap($name, $this);
        $field->setType($type);
        $field->setSize($size);
        $field->setNotNull($isNotNull);
        $field->setDefaultValue($defaultValue);
        $field->setImplementationDetail($implementationDetail);

        if ($pk) {
            $field->setPrimaryKey(true);
            $this->primaryKeys[$name] = $field;
        }

        if ($fkEntity && $fkField) {
            $field->setForeignKey($fkEntity, $fkField);
            $this->foreignKeys[$name] = $field;
        }

        $this->fields[FieldMap::normalizeName($name)] = $field;
        $this->fieldsByLowercaseName[strtolower(FieldMap::normalizeName($name))] = $field;

        return $field;
    }

    /**
     * Add a pre-created field to this entity. It will replace any
     * existing field.
     *
     * @param  \Propel\Runtime\Map\FieldMap $cmap A FieldMap.
     *
     * @return \Propel\Runtime\Map\FieldMap The added field map.
     */
    public function addConfiguredField(FieldMap $cmap)
    {
        $this->fields[$cmap->getName()] = $cmap;
        $this->fieldsByLowercaseName[strtolower($cmap->getName())] = $cmap;

        return $cmap;
    }

    /**
     * Does this entity contain the specified field?
     *
     * @param  mixed $name name of the field or FieldMap instance
     * @param  boolean $normalize Normalize the field name (if field name not like FIRST_NAME)
     *
     * @return boolean True if the entity contains the field.
     */
    public function hasField($name, $normalize = true)
    {
        if ($name instanceof FieldMap) {
            $name = $name->getName();
        } elseif ($normalize) {
            $name = FieldMap::normalizeName($name);
        }

        if(isset($this->fields[$name]) || isset($this->fieldsByLowercaseName[strtolower($name)])) {
            return true;
        }
        //Maybe it's phpName
        $name = NamingTool::toUnderscore($name);

        return isset($this->fields[$name]);
    }

    /**
     * Get a FieldMap for the entity.
     *
     * @param  string $name A String with the name of the entity.
     * @param  boolean $normalize Normalize the field name (if field name not like FIRST_NAME)
     *
     * @return \Propel\Runtime\Map\FieldMap                         A FieldMap.
     * @throws \Propel\Runtime\Map\Exception\FieldNotFoundException If the field is undefined
     */
    public function getField($name, $normalize = true)
    {
        if ($normalize) {
            $name = FieldMap::normalizeName($name);
        }
        if (!$this->hasField($name, false)) {
            throw new FieldNotFoundException(sprintf('Cannot fetch FieldMap for undefined field: %s.', $name));
        }

        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        if (isset($this->fieldsByLowercaseName[strtolower($name)])) {
            return $this->fieldsByLowercaseName[strtolower($name)];
        }

        $name = NamingTool::toUnderscore($name);

        return $this->fields[$name];
    }

    /**
     * Get a FieldMap[] of the fields in this entity.
     *
     * @return FieldMap[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Add a primary key field to this Entity.
     *
     * @param  string $fieldName A String with the field name.
     * @param  string $type A string specifying the Propel type.
     * @param  boolean $isNotNull Whether field does not allow NULL values.
     * @param  int $size An int specifying the size.
     * @param  string $defaultValue The default value for this field.
     *
     * @return \Propel\Runtime\Map\FieldMap Newly added PrimaryKey field.
     */
    public function addPrimaryKey(
        $fieldName,
        $type,
        $isNotNull = false,
        $size = null,
        $defaultValue = null,
        $implementationDetail = false
    )
    {
        return $this->addField(
            $fieldName,
            $type,
            $isNotNull,
            $size,
            $defaultValue,
            true,
            $implementationDetail,
            null,
            null
        );
    }

    /**
     * Add a foreign key field to the entity.
     *
     * @param  string $fieldName A String with the field name.
     * @param  string $type A string specifying the Propel type.
     * @param  string $fkEntity A String with the foreign key entity name.
     * @param  string $fkField A String with the foreign key field name.
     * @param  boolean $isNotNull Whether field does not allow NULL values.
     * @param  int $size An int specifying the size.
     * @param  string $defaultValue The default value for this field.
     *
     * @return \Propel\Runtime\Map\FieldMap Newly added ForeignKey field.
     */
    public function addForeignKey(
        $fieldName,
        $type,
        $fkEntity,
        $fkField,
        $isNotNull = false,
        $size = 0,
        $defaultValue = null
    )
    {
        return $this->addField(
            $fieldName,
            $type,
            $isNotNull,
            $size,
            $defaultValue,
            false,
            true,
            $fkEntity,
            $fkField
        );
    }

    /**
     * Add a foreign primary key field to the entity.
     *
     * @param  string $fieldName A String with the field name.
     * @param  string $type A string specifying the Propel type.
     * @param  string $fkEntity A String with the foreign key entity name.
     * @param  string $fkField A String with the foreign key field name.
     * @param  boolean $isNotNull Whether field does not allow NULL values.
     * @param  int $size An int specifying the size.
     * @param  string $defaultValue The default value for this field.
     *
     * @return \Propel\Runtime\Map\FieldMap Newly created foreign pkey field.
     */
    public function addForeignPrimaryKey(
        $fieldName,
        $type,
        $fkEntity,
        $fkField,
        $isNotNull = false,
        $size = 0,
        $defaultValue = null
    )
    {
        return $this->addField(
            $fieldName,
            $type,
            $isNotNull,
            $size,
            $defaultValue,
            true,
            true,
            $fkEntity,
            $fkField
        );
    }

    /**
     * @return boolean true if the entity is a many to many
     */
    public function isCrossRef()
    {
        return $this->crossRef;
    }

    /**
     * Set the isCrossRef
     *
     * @param boolean $isCrossRef
     */
    public function setIsCrossRef($isCrossRef)
    {
        $this->crossRef = $isCrossRef;
    }

    /**
     * Returns array of FieldMap objects that make up the primary key for this entity
     *
     * @return FieldMap[]
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Returns array of FieldMap objects that are foreign keys for this entity
     *
     * @return FieldMap[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * Build relations
     * Relations are lazy loaded for performance reasons
     * This method should be overridden by descendants
     */
    abstract public function buildRelations();

    /**
     * Build fields
     */
    abstract public function buildFields();

    /**
     * @return boolean
     */
    abstract public function hasAutoIncrement();

    /**
     * @return string[]
     */
    abstract public function getAutoIncrementFieldNames();

    /**
     * Adds a RelationMap to the entity
     *
     * @param  string $name The relation name
     * @param  string $foreignEntityName The related entity name
     * @param  integer $type The relation type (either RelationMap::MANY_TO_ONE, RelationMap::ONE_TO_MANY,
     *                                    or RelationMAp::ONE_TO_ONE)
     * @param  array $fieldMapping An associative array mapping field names (local => foreign)
     * @param  string $onDelete SQL behavior upon deletion ('SET NULL', 'CASCADE', ...)
     * @param  string $onUpdate SQL behavior upon update ('SET NULL', 'CASCADE', ...)
     * @param  string $pluralName Optional plural name for *_TO_MANY relationships
     *
     * @return \Propel\Runtime\Map\RelationMap the built RelationMap object
     */
    public function addRelation(
        $name,
        $foreignEntityName,
        $type,
        $fieldMapping = array(),
        $onDelete = null,
        $onUpdate = null,
        $pluralName = null
    )
    {
        $relation = new RelationMap($name);
        $relation->setType($type);
        $relation->setOnUpdate($onUpdate);
        $relation->setOnDelete($onDelete);
        if (null !== $pluralName) {
            $relation->setPluralName($pluralName);
        }
        // set entities
        if (RelationMap::MANY_TO_ONE === $type) {
            $relation->setLocalEntity($this);
            $relation->setForeignEntity($this->getConfiguration()->getEntityMap($foreignEntityName));
        } else {
            $relation->setLocalEntity($this->getConfiguration()->getEntityMap($foreignEntityName));
            $relation->setForeignEntity($this);
            $fieldMapping = array_flip($fieldMapping);
        }
        // set fields
        foreach ($fieldMapping as $local => $foreign) {
            $relation->addFieldMapping(
                $relation->getLocalEntity()->getField($local),
                $relation->getForeignEntity()->getField($foreign)
            );
        }
        $this->relations[$name] = $relation;

        return $relation;
    }

    /**
     * Gets a RelationMap of the entity by relation name
     * This method will build the relations if they are not built yet
     *
     * @param  string $name The relation name
     *
     * @return boolean true if the relation exists
     */
    public function hasRelation($name)
    {
        if (!$this->relationsBuilt) {
            $this->buildRelations();
            $this->relationsBuilt = true;
        }

        return isset($this->relations[$name]);
    }

    /**
     * Gets a RelationMap of the entity by relation name
     * This method will build the relations if they are not built yet
     *
     * @param  string $name The relation name
     *
     * @return \Propel\Runtime\Map\RelationMap                         The relation object
     * @throws \Propel\Runtime\Map\Exception\RelationNotFoundException When called on an inexistent relation
     */
    public function getRelation($name)
    {
        if (!$this->relationsBuilt) {
            $this->buildRelations();
            $this->relationsBuilt = true;
        }

        if (!isset($this->relations[$name])) {
            throw new RelationNotFoundException(sprintf('Calling getRelation() on an unknown relation: %s.', $name));
        }

        return $this->relations[$name];
    }

    /**
     * Gets the RelationMap objects of the entity
     * This method will build the relations if they are not built yet
     *
     * @return RelationMap[] list of RelationMap objects
     */
    public function getRelations()
    {
        if (!$this->relationsBuilt) {
            $this->buildRelations();
            $this->relationsBuilt = true;
        }

        return $this->relations;
    }

    /**
     *
     * Gets the list of behaviors registered for this entity
     *
     * @return array
     */
    public function getBehaviors()
    {
        return array();
    }

    /**
     * Does this entity has a primaryString field?
     *
     * @return boolean True if the entity has a primaryString field.
     */
    public function hasPrimaryStringField()
    {
        return null !== $this->getPrimaryStringField();
    }

    /**
     * Gets the FieldMap for the primary string field.
     *
     * @return \Propel\Runtime\Map\FieldMap
     */
    public function getPrimaryStringField()
    {
        foreach ($this->getFields() as $field) {
            if ($field->isPrimaryString()) {
                return $field;
            }
        }

        return null;
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

    /**
     * @return array|null null if not covered by only pk
     */
    public function extractPrimaryKey(Criteria $criteria)
    {
        $pkCols = $this->getPrimaryKeys();
        if (count($pkCols) !== count($criteria->getMap())) {
            return null;
        }

        $pk = [];
        foreach ($pkCols as $pkCol) {
            $fqName = $pkCol->getFullyQualifiedName();
            $name = $pkCol->getName();

            if ($criteria->containsKey($fqName)) {
                $value = $criteria->getValue($fqName);
            } else {
                if ($criteria->containsKey($name)) {
                    $value = $criteria->getValue($name);
                } else {
                    return null;
                }
            }

            $pk[$name] = $value;
        }

        return $pk;
    }

    /**
     * Returns an array of field names.
     *
     * @param  string $type The type of fieldnames to return:
     *                               One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_COLNAME
     *                               TableMap::TYPE_FULLCOLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return array           A list of field names
     * @throws PropelException
     */
    public function getFieldNames($type = EntityMap::TYPE_PHPNAME)
    {
        if (!array_key_exists($type, $this->fieldNames)) {
            throw new PropelException(
                'Method getFieldNames() expects the parameter \$type to be one of the class constants TableMap::TYPE_PHPNAME, TableMap::TYPE_COLNAME, TableMap::TYPE_FULLCOLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM. ' . $type . ' was given.'
            );
        }

        return $this->fieldNames[$type];
    }

    /**
     * Translates a fieldname to another type
     *
     * @param  string $name field name
     * @param  string $fromType One of the class type constants TableMap::TYPE_PHPNAME,
     *                                   TableMap::TYPE_COLNAME TableMap::TYPE_FULLCOLNAME, TableMap::TYPE_FIELDNAME,
     *                                   TableMap::TYPE_NUM
     * @param  string $toType One of the class type constants
     *
     * @return string          translated name of the field.
     * @throws PropelException - if the specified name could not be found in the fieldname mappings.
     */
    public function translateFieldName($name, $fromType, $toType)
    {
        $toNames = $this->getFieldNames($toType);
        $key = isset($this->fieldKeys[$fromType][$name]) ? $this->fieldKeys[$fromType][$name] : null;
        if (null === $key) {
            throw new PropelException(
                "'$name' could not be found in the field names of type '$fromType'. These are: " . print_r(
                    $this->fieldKeys[$fromType],
                    true
                )
            );
        }

        return $toNames[$key];
    }

    public function translateFieldNames($row, $fromType, $toType)
    {
        $toNames = $this->getFieldNames($toType);
        $newRow = array();
        foreach ($row as $name => $field) {
            if ($key = $this->fieldKeys[$fromType][$name]) {
                $newRow[$toNames[$key]] = $field;
            } else {
                $newRow[$name] = $field;
            }
        }

        return $newRow;
    }

    /**
     * Convenience method which changes table.column to alias.column.
     *
     * Using this method you can maintain SQL abstraction while using column aliases.
     * <code>
     *        $c->addAlias("alias1", TableTableMap::TABLE_NAME);
     *        $c->addJoin(TableTableMap::alias("alias1", TableTableMap::PRIMARY_KEY_COLUMN),
     *        TableTableMap::PRIMARY_KEY_COLUMN);
     * </code>
     *
     * @param  string $alias The alias for the current table.
     * @param  string $column The column name for current table. (i.e. BookTableMap::COLUMN_NAME).
     *
     * @return string
     */
    public function alias($alias, $column)
    {
        return str_replace(static::TABLE_NAME . '.', $alias . '.', $column);
    }
}
