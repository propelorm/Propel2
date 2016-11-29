<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Exception\RuntimeException;

/**
 * Data about a entity used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Entity extends ScopedMappingModel implements IdMethod
{
    use BehaviorableTrait;

    /**
     * @var Field[]
     */
    private $fields;

    /**
     * @var Relation[]
     */
    private $relations;
    private $foreignEntityNames;

    /**
     * @var Index[]
     */
    private $indices;

    /**
     * @var Unique[]
     */
    private $unices;
    private $idMethodParameters;
    private $name;
    private $tableName;
    private $description;
//    private $phpName;
    private $idMethod;

    /**
     * @var bool
     */
    private $allowPkInsert;

    /**
     * Whether this entity is an implementation detail. Implementation details are entities that are only
     * relevant in the current persister api, like implicit pivot tables in n-n relations, or foreign key columns.
     * @var bool
     */
    private $implementationDetail = false;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Relation[]
     */
    private $referrers;
    private $containsForeignPK;

    /**
     * RepositoryClass
     *
     * @var boolean|string
     */
    private $repository;
    /**
     * @var Field
     */
    private $inheritanceField;
    private $skipSql;
    private $readOnly;
    private $isAbstract;
    private $alias;
    private $fieldsByName;
    private $fieldsByLowercaseName;
//    private $fieldsByPhpName;
    private $needsTransactionInPostgres;

    /**
     * @var boolean
     */
    private $heavyIndexing;

    /**
     * @var boolean
     */
    private $identifierQuoting;

    /**
     * @var bool|null
     */
    private $activeRecord;

    private $forReferenceOnly;
    private $reloadOnInsert;
    private $reloadOnUpdate;

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

    protected $isCrossRef;
    protected $defaultStringFormat;

    /**
     * Constructs a entity object with a name
     *
     * @param string $name entity name
     */
    public function __construct($name = null)
    {
        parent::__construct();

        if (null !== $name) {
            $this->name = $name;
        }

        $this->idMethod = IdMethod::NO_ID_METHOD;
        $this->defaultAccessorVisibility = static::VISIBILITY_PUBLIC;
        $this->defaultMutatorVisibility = static::VISIBILITY_PUBLIC;
        $this->allowPkInsert = false;
        $this->isAbstract = false;
        $this->isCrossRef = false;
        $this->readOnly = false;
        $this->reloadOnInsert = false;
        $this->reloadOnUpdate = false;
        $this->skipSql = false;
        $this->behaviors = [];
        $this->fields = [];
        $this->fieldsByName = [];
//        $this->fieldsByPhpName = [];
        $this->fieldsByLowercaseName = [];
        $this->relations = [];
        $this->foreignEntityNames = [];
        $this->idMethodParameters = [];
        $this->indices = [];
        $this->referrers = [];
        $this->unices = [];
    }

    /**
     * @return bool|string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param bool|string $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function setupObject()
    {
        parent::setupObject();

        $this->setName($this->getAttribute('name'));
        $this->tableName = $this->getAttribute('tableName');

        if ($this->getAttribute('activeRecord')) {
            $this->activeRecord = 'true' === $this->getAttribute('activeRecord');
        }

        $this->idMethod = $this->getAttribute('idMethod', $this->database->getDefaultIdMethod());
        $this->allowPkInsert = $this->booleanValue($this->getAttribute('allowPkInsert'));

        $this->skipSql = $this->booleanValue($this->getAttribute('skipSql'));
        $this->readOnly = $this->booleanValue($this->getAttribute('readOnly'));

        $this->isAbstract = $this->booleanValue($this->getAttribute('abstract'));
        $this->baseClass = $this->getAttribute('baseClass');
        $this->alias = $this->getAttribute('alias');
        $this->repository = $this->getAttribute('repository');

        if ('true' === $this->repository) {
            $this->repository = true;
        } else if ('false' === $this->repository) {
            $this->repository = false;
        }

        $this->heavyIndexing = (
            $this->booleanValue($this->getAttribute('heavyIndexing'))
            || (
                'false' !== $this->getAttribute('heavyIndexing')
                && $this->database->isHeavyIndexing()
            )
        );

        if ($this->getAttribute('identifierQuoting')) {
            $this->identifierQuoting = $this->booleanValue($this->getAttribute('identifierQuoting'));
        }

        $this->description = $this->getAttribute('description');

        $this->reloadOnInsert = $this->booleanValue($this->getAttribute('reloadOnInsert'));
        $this->reloadOnUpdate = $this->booleanValue($this->getAttribute('reloadOnUpdate'));
        $this->isCrossRef = $this->booleanValue($this->getAttribute('isCrossRef', false));
        $this->defaultStringFormat = $this->getAttribute('defaultStringFormat');
        $this->defaultAccessorVisibility = $this->getAttribute(
            'defaultAccessorVisibility',
            $this->database->getAttribute('defaultAccessorVisibility', static::VISIBILITY_PUBLIC)
        );
        $this->defaultMutatorVisibility = $this->getAttribute(
            'defaultMutatorVisibility',
            $this->database->getAttribute('defaultMutatorVisibility', static::VISIBILITY_PUBLIC)
        );
    }

    public function finalizeDefinition($throwErrors = false)
    {
        $this->setupReferrers($throwErrors);
    }

    /**
     * Browses the foreign keys and creates referrers for the foreign entity.
     * This method can be called several times on the same entity. It only
     * adds the missing referrers and is non-destructive.
     * Warning: only use when all the entitys were created.
     *
     * @param  boolean $throwErrors
     *
     * @throws BuildException
     */
    protected function setupReferrers($throwErrors = false)
    {
        foreach ($this->getRelations() as $relation) {
            $this->setupReferrer($relation, $throwErrors);
        }
    }

    /**
     * @param Relation $relation
     * @param bool     $throwErrors
     */
    protected function setupReferrer(Relation $relation, $throwErrors = false)
    {
        $entity = $relation->getEntity();
        // entity referrers
        $hasEntity = $entity->getDatabase()->hasEntity($relation->getForeignEntityName());
        if (!$hasEntity) {
            throw new BuildException(
                sprintf(
                    'Entity "%s" contains a relation to nonexistent entity "%s". [%s]',
                    $entity->getName(),
                    $relation->getForeignEntityName(),
                    $entity->getDatabase()->getEntityNames()
                )
            );
        }

        $foreignEntity = $entity->getDatabase()->getEntity($relation->getForeignEntityName());
        $referrers = $foreignEntity->getReferrers();
        if (null === $referrers || !in_array($relation, $referrers, true)) {
            $foreignEntity->addReferrer($relation);
        }

        // foreign pk's
        $localFieldNames = $relation->getLocalFields();
        foreach ($localFieldNames as $localFieldName) {
            $localField = $entity->getField($localFieldName);
            if (null !== $localField) {
                if ($localField->isPrimaryKey() && !$entity->getContainsForeignPK()) {
                    $entity->setContainsForeignPK(true);
                }
            } elseif ($throwErrors) {
                // give notice of a schema inconsistency.
                // note we do not prevent the npe as there is nothing
                // that we can do, if it is to occur.
                throw new BuildException(
                    sprintf(
                        'Entity "%s" contains a foreign key with nonexistent local field "%s"',
                        $entity->getName(),
                        $localFieldName
                    )
                );
            }
        }

        // foreign field references
        $foreignFields = $relation->getForeignFieldObjects();
        foreach ($foreignFields as $foreignField) {
            if (null === $foreignEntity) {
                continue;
            }
            if (null !== $foreignField) {
                if (!$foreignField->hasReferrer($relation)) {
                    $foreignField->addReferrer($relation);
                }
            } elseif ($throwErrors) {
                // if the foreign field does not exist, we may have an
                // external reference or a misspelling
                throw new BuildException(
                    sprintf(
                        'Entity "%s" contains a foreign key to entity "%s" with nonexistent field "%s"',
                        $entity->getName(),
                        $foreignEntity->getName(),
                        $foreignField->getName()
                    )
                );
            }
        }
    }

    /**
     * @return boolean
     */
    public function isImplementationDetail()
    {
        return $this->implementationDetail;
    }

    /**
     * @param boolean $implementationDetail
     */
    public function setImplementationDetail($implementationDetail)
    {
        $this->implementationDetail = $implementationDetail;
    }

    /**
     * @return boolean
     */
    public function isHeavyIndexing()
    {
        return $this->heavyIndexing;
    }

    /**
     * Returns a build property value for the database this entity belongs to.
     *
     * @param  string $key
     *
     * @return string
     */
    public function getBuildProperty($key)
    {
        return $this->database ? $this->database->getBuildProperty($key) : '';
    }

    /**
     * Executes behavior entity modifiers.
     * This is only for testing purposes. Model\Database calls already `modifyEntity` on each behavior.
     */
    public function applyBehaviors()
    {
        foreach ($this->behaviors as $behavior) {
            if (!$behavior->isEntityModified()) {
                $behavior->getEntityModifier()->modifyEntity();
                $behavior->setEntityModified(true);
            }
        }
    }

    protected function registerBehavior(Behavior $behavior)
    {
        $behavior->setEntity($this);
    }

    /**
     * Creates a new index.
     *
     * @param  string $name    The index name
     * @param  array  $fields The list of fields to index
     *
     * @return Index  $index   The created index
     */
    public function createIndex($name, array $fields)
    {
        $index = new Index($name);
        $index->setFields($fields);
        $index->resetFieldsSize();

        $this->addIndex($index);

        return $index;
    }

    /**
     * Adds a new field to the entity.
     *
     * @param  Field|array $col
     *
     * @throws EngineException
     * @return Field
     */
    public function addField($col)
    {
        if ($col instanceof Field) {

            if (isset($this->fieldsByName[$col->getName()])) {
                throw new EngineException(
                    sprintf('Field "%s" declared twice in entity "%s"', $col->getName(), $this->getName())
                );
            }

            $col->setEntity($this);

            if ($col->isInheritance()) {
                $this->inheritanceField = $col;
            }

            $this->fields[] = $col;
            $this->fieldsByName[$col->getName()] = $col;
            $this->fieldsByLowercaseName[strtolower($col->getName())] = $col;
//            $this->fieldsByPhpName[$col->getName()] = $col;
            $col->setPosition(count($this->fields));

            if ($col->requiresTransactionInPostgres()) {
                $this->needsTransactionInPostgres = true;
            }

            return $col;
        }

        $field = new Field();
        $field->setEntity($this);
        $field->loadMapping($col);

        return $this->addField($field); // call self w/ different param
    }

    /**
     * Adds several fields at once.
     *
     * @param Field[] $fields An array of Field instance
     */
    public function addFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * Removes a field from the entity.
     *
     * @param  Field|string $field The Field or its name
     *
     * @throws EngineException
     */
    public function removeField($field)
    {
        if (is_string($field)) {
            $field = $this->getField($field);
        }

        $pos = $this->getFieldPosition($field);
        if (false === $pos) {
            throw new EngineException(
                sprintf('No field named %s found in entity %s.', $field->getName(), $this->getName())
            );
        }

        unset($this->fields[$pos]);
        unset($this->fieldsByName[$field->getName()]);
        unset($this->fieldsByLowercaseName[strtolower($field->getName())]);
//        unset($this->fieldsByPhpName[$field->getName()]);

        $this->adjustFieldPositions();
        // @FIXME: also remove indexes and validators on this field?
    }

    private function getFieldPosition(Field $field)
    {
        $position = false;
        $nbFields = $this->getNumFields();
        for ($pos = 0; $pos < $nbFields; $pos++) {
            if ($this->fields[$pos]->getName() === $field->getName()) {
                $position = $pos;
            }
        }

        return $position;
    }

    public function adjustFieldPositions()
    {
        $this->fields = array_values($this->fields);
        $nbFields = $this->getNumFields();
        for ($i = 0; $i < $nbFields; $i++) {
            $this->fields[$i]->setPosition($i + 1);
        }
    }

    /**
     * Adds a new foreign key to this entity.
     *
     * @param  Relation|array $data The foreign key mapping
     *
     * @return Relation
     */
    public function addRelation($data)
    {
        if ($data instanceof Relation) {
            $relation = $data;
            $relation->setEntity($this);
            $this->relations[] = $relation;

            if (!in_array($relation->getForeignEntityName(), $this->foreignEntityNames)) {
                $this->foreignEntityNames[] = $relation->getForeignEntityName();
            }

            return $relation;
        }

        $relation = new Relation();
        $relation->setEntity($this);
        $relation->loadMapping($data);

        return $this->addRelation($relation);
    }

    /**
     * Adds several foreign keys at once.
     *
     * @param Relation[] $relations An array of Relation objects
     */
    public function addRelations(array $relations)
    {
        foreach ($relations as $relation) {
            $this->addRelation($relation);
        }
    }

    /**
     * Returns the field that subclasses the class representing this
     * entity can be produced from.
     *
     * @return Field
     */
    public function getChildrenField()
    {
        return $this->inheritanceField;
    }

    /**
     * Returns the subclasses that can be created from this entity.
     *
     * @return array
     */
    public function getChildrenNames()
    {
        if (null === $this->inheritanceField
            || !$this->inheritanceField->isEnumeratedClasses()
        ) {
            return null;
        }

        $names = [];
        foreach ($this->inheritanceField->getChildren() as $child) {
            $names[] = get_class($child);
        }

        return $names;
    }

    /**
     * Adds the foreign key from another entity that refers to this entity.
     *
     * @param Relation $relation
     */
    public function addReferrer(Relation $relation)
    {
        $this->referrers[] = $relation;
    }

    /**
     * Returns the list of references to this entity.
     *
     * @return Relation[]
     */
    public function getReferrers()
    {
        return $this->referrers;
    }

    /**
     * Returns the list of cross foreign keys.
     *
     * @return CrossRelation[]
     */
    public function getCrossRelations()
    {
        $crossFks = [];
        foreach ($this->referrers as $refRelation) {
            if ($refRelation->getEntity()->isCrossRef()) {
                $crossRelation = new CrossRelation($refRelation, $this);
                foreach ($refRelation->getOtherFks() as $relation) {
                    if ($relation->isAtLeastOneLocalPrimaryKeyIsRequired() && $crossRelation->isAtLeastOneLocalPrimaryKeyNotCovered($relation)) {
                        $crossRelation->addRelation($relation);
                    }
                }
                if ($crossRelation->hasRelations()) {
                    $crossFks[] = $crossRelation;
                }
            }
        }

        return $crossFks;
    }

    /**
     * Returns all required(notNull && no defaultValue) primary keys which are not in $primaryKeys.
     *
     * @param  Field[] $primaryKeys
     *
     * @return Field[]
     */
    public function getOtherRequiredPrimaryKeys(array $primaryKeys)
    {
        $pks = [];
        foreach ($this->getPrimaryKey() as $primaryKey) {
            if ($primaryKey->isNotNull() && !$primaryKey->hasDefaultValue() && !in_array(
                    $primaryKey,
                    $primaryKeys,
                    true
                )
            ) {
                $pks = $primaryKey;
            }
        }

        return $pks;
    }

    /**
     * Sets whether or not this entity contains a foreign primary key.
     *
     * @param $containsForeignPK
     *
     * @return boolean
     */
    public function setContainsForeignPK($containsForeignPK)
    {
        $this->containsForeignPK = (Boolean)$containsForeignPK;
    }

    /**
     * Returns whether or not this entity contains a foreign primary key.
     *
     * @return boolean
     */
    public function getContainsForeignPK()
    {
        return $this->containsForeignPK;
    }

    /**
     * Returns the list of entitys referenced by foreign keys in this entity.
     *
     * @return array
     */
    public function getForeignEntityNames()
    {
        return $this->foreignEntityNames;
    }

    /**
     * Return true if the field requires a transaction in Postgres.
     *
     * @return boolean
     */
    public function requiresTransactionInPostgres()
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * Adds a new parameter for the strategy that generates primary keys.
     *
     * @param  IdMethodParameter|array $idMethodParameter
     *
     * @return IdMethodParameter
     */
    public function addIdMethodParameter($idMethodParameter)
    {
        if ($idMethodParameter instanceof IdMethodParameter) {
            $idMethodParameter->setEntity($this);
            $this->idMethodParameters[] = $idMethodParameter;

            return $idMethodParameter;
        }

        $imp = new IdMethodParameter();
        $imp->setEntity($this);
        $imp->loadMapping($idMethodParameter);

        return $this->addIdMethodParameter($imp);
    }

    /**
     * Removes a index from the entity.
     *
     * @param string $name
     */
    public function removeIndex($name)
    {
        // check if we have a index with this name already, then delete it
        foreach ($this->indices as $n => $idx) {
            if ($idx->getName() == $name) {
                unset($this->indices[$n]);

                return;
            }
        }
    }

    /**
     * Checks if the entity has a index by name.
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function hasIndex($name)
    {
        foreach ($this->indices as $idx) {
            if ($idx->getName() == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds a new index to the indices list and set the
     * parent entity of the field to the current entity.
     *
     * @param  Index|array $index
     *
     * @return Index
     *
     * @throw  InvalidArgumentException
     */
    public function addIndex($index)
    {
        if ($index instanceof Index) {
            if ($this->hasIndex($index->getName())) {
                throw new InvalidArgumentException(sprintf('Index "%s" already exist.', $index->getName()));
            }
            if (!$index->getFields()) {
                throw new InvalidArgumentException(sprintf('Index "%s" has no fields.', $index->getName()));
            }
            $index->setEntity($this);
            // force the name to be created if empty.
            $this->indices[] = $index;

            return $index;
        }

        $idx = new Index();
        $idx->loadMapping($index);
        foreach ((array)@$index['fields'] as $field) {
            $idx->addField($field);
        }

        return $this->addIndex($idx);
    }

    /**
     * Adds a new Unique index to the list of unique indices and set the
     * parent entity of the field to the current entity.
     *
     * @param  Unique|array $unique
     *
     * @return Unique
     */
    public function addUnique($unique)
    {
        if ($unique instanceof Unique) {
            $unique->setEntity($this);
            $unique->getName(); // we call this method so that the name is created now if it doesn't already exist.
            $this->unices[] = $unique;

            return $unique;
        }

        $unik = new Unique();
        $unik->loadMapping($unique);

        return $this->addUnique($unik);
    }

    /**
     * Retrieves the configuration object.
     *
     * @return GeneratorConfig
     */
    public function getGeneratorConfig()
    {
        return $this->database->getGeneratorConfig();
    }

    /**
     * Returns whether or not the entity behaviors offer additional builders.
     *
     * @return boolean
     */
    public function hasAdditionalBuilders()
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior->hasAdditionalBuilders()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the early entity behaviors
     *
     * @return Array of Behavior objects
     */
    public function getEarlyBehaviors()
    {
        $behaviors = [];
        foreach ($this->behaviors as $name => $behavior) {
            if ($behavior->isEarly()) {
                $behaviors[$name] = $behavior;
            }
        }

        return $behaviors;
    }

    /**
     * Returns the list of additional builders provided by the entity behaviors.
     *
     * @return array
     */
    public function getAdditionalBuilders()
    {
        $additionalBuilders = [];
        foreach ($this->behaviors as $behavior) {
            $additionalBuilders = array_merge($additionalBuilders, $behavior->getAdditionalBuilders());
        }

        return $additionalBuilders;
    }

    /**
     * Returns the entity (class) name without namespace.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        if (false !== strpos($name, '\\')) {
            $namespace = explode('\\', trim($name, '\\'));
            $this->name = array_pop($namespace);
            $this->namespace = implode('\\', $namespace);
        } else {
            $this->name = $name;
        }
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        if (!$this->tableName) {
            $shortName = basename($this->name);
            $this->tableName = NamingTool::toUnderscore($shortName);
        }

        if ($this->getDatabase()) {
            return $this->getDatabase()->getTablePrefix() . $this->tableName;
        }

        return $this->tableName;
    }

    /**
     * Table name without table prefix.
     *
     * @return string
     */
    public function getCommonTableName()
    {
        if (!$this->tableName) {
            $shortName = basename($this->name);
            $this->tableName = NamingTool::toUnderscore($shortName);
        }

        return $this->tableName;
    }

    /**
     * Full table name with possible schema.
     *
     * @return string
     */
    public function getFQTableName()
    {
        $fqTableName = $this->getTableName();

        if ($this->hasSchema()) {
            $fqTableName = $this->guessSchemaName() . $this->getPlatform()->getSchemaDelimiter() . $fqTableName;
        }

        return $fqTableName;
    }

    /**
     * @param mixed $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * Returns the full entity class name with namespace.
     *
     * @return string
     */
    public function getFullClassName()
    {
        $name = $this->getName();
        $namespace = $this->getNamespace();

        if (!$namespace && $this->getDatabase()) {
            $namespace = $this->getDatabase()->getNamespace();
        }

        if ($namespace) {
            return trim($namespace, '\\') . '\\' . $name;
        } else {
            return $name;
        }
    }

    /**
     * Returns the schema name from this entity or from its database.
     *
     * @return string
     */
    public function guessSchemaName()
    {
        if (!$this->schema && $this->database) {
            return $this->database->getSchema();
        }

        return $this->schema;
    }

    /**
     * Returns whether or not this entity is linked to a schema.
     *
     * @return boolean
     */
    public function hasSchema()
    {
        return $this->database
        && ($this->schema ?: $this->database->getSchema())
        && ($platform = $this->getPlatform())
        && $platform->supportsSchemas();
    }

    /**
     * Returns the entity description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns whether or not the entity has a description.
     *
     * @return boolean
     */
    public function hasDescription()
    {
        return !empty($this->description);
    }

    /**
     * Sets the entity description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the camelCase version of PHP name.
     *
     * The studly name is the PHP name with the first character lowercase.
     *
     * @return string
     */
    public function getCamelCaseName()
    {
        return lcfirst($this->getName());
    }

//    /**
//     * Returns the common name (without schema name), but with entity prefix if defined.
//     *
//     * @return string
//     */
//    public function getCommonName()
//    {
//        return $this->commonName;
//    }
//
//    /**
//     * Sets the entity common name (without schema name).
//     *
//     * @param string $name
//     */
//    public function setCommonName($name)
//    {
//        $this->commonName = $this->originCommonName = $name;
//    }

    /**
     * Returns the unmodified common name (not modified by entity prefix).
     *
     * @return string
     */
    public function getOriginCommonName()
    {
        return $this->originCommonName;
    }

    /**
     * Sets the default string format for ActiveRecord objects in this entity.
     *
     * Any of 'XML', 'YAML', 'JSON', or 'CSV'.
     *
     * @param  string $format
     *
     * @throws InvalidArgumentException
     */
    public function setDefaultStringFormat($format)
    {
        $formats = Database::getSupportedStringFormats();

        $format = strtoupper($format);
        if (!in_array($format, $formats)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Given "%s" default string format is not supported. Only "%s" are valid string formats.',
                    $format,
                    implode(', ', $formats)
                )
            );
        }

        $this->defaultStringFormat = $format;
    }

    /**
     * Returns the default string format for ActiveRecord objects in this entity,
     * or the one for the whole database if not set.
     *
     * @return string
     */
    public function getDefaultStringFormat()
    {
        if (null !== $this->defaultStringFormat) {
            return $this->defaultStringFormat;
        }

        return $this->database->getDefaultStringFormat();
    }

    /**
     * Returns the method strategy for generating primary keys.
     *
     * [HL] changing behavior so that Database default method is returned
     * if no method has been specified for the entity.
     *
     * @return string
     */
    public function getIdMethod()
    {
        return $this->idMethod;
    }

    /**
     * Returns whether we allow to insert primary keys on entitys with
     * native id method.
     *
     * @return boolean
     */
    public function isAllowPkInsert()
    {
        return $this->allowPkInsert;
    }

    /**
     * Sets the method strategy for generating primary keys.
     *
     * @param string $idMethod
     */
    public function setIdMethod($idMethod)
    {
        $this->idMethod = $idMethod;
    }

    /**
     * Returns whether or not Propel has to skip DDL SQL generation for this
     * entity (in the event it should not be created from scratch).
     *
     * @return boolean
     */
    public function isSkipSql()
    {
        return ($this->skipSql || $this->isAlias() || $this->isForReferenceOnly());
    }

    /**
     * Sets whether or not this entity should have its SQL DDL code generated.
     *
     * @param boolean $skip
     */
    public function setSkipSql($skip)
    {
        $this->skipSql = (Boolean)$skip;
    }

    /**
     * Returns whether or not this entity is read-only. If yes, only only
     * accessors and relationship accessors and mutators will be generated.
     *
     * @return boolean
     */
    public function isReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Makes this database in read-only mode.
     *
     * @param boolean $flag True by default
     */
    public function setReadOnly($flag = true)
    {
        $this->readOnly = (boolean)$flag;
    }

    /**
     * Whether to force object to reload on INSERT.
     *
     * @return boolean
     */
    public function isReloadOnInsert()
    {
        return $this->reloadOnInsert;
    }

    /**
     * Makes this database reload on insert statement.
     *
     * @param boolean $flag True by default
     */
    public function setReloadOnInsert($flag = true)
    {
        $this->reloadOnInsert = (boolean)$flag;
    }

    /**
     * Returns whether or not to force object to reload on UPDATE.
     *
     * @return boolean
     */
    public function isReloadOnUpdate()
    {
        return $this->reloadOnUpdate;
    }

    /**
     * Makes this database reload on update statement.
     *
     * @param boolean $flag True by default
     */
    public function setReloadOnUpdate($flag = true)
    {
        $this->reloadOnUpdate = (boolean)$flag;
    }

    /**
     * Returns the PHP name of an active record object this entry references.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns whether or not this entity is specified in the schema or if there
     * is just a foreign key reference to it.
     *
     * @return boolean
     */
    public function isAlias()
    {
        return null !== $this->alias;
    }

    /**
     * Sets whether or not this entity is specified in the schema or if there is
     * just a foreign key reference to it.
     *
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Returns whether or not a entity is abstract, it marks the business object
     * class that is generated as being abstract. If you have a entity called
     * "FOO", then the Foo business object class will be declared abstract. This
     * helps support class hierarchies
     *
     * @return boolean
     */
    public function isAbstract()
    {
        return $this->isAbstract;
    }

    /**
     * Sets whether or not a entity is abstract, it marks the business object
     * class that is generated as being abstract. If you have a
     * entity called "FOO", then the Foo business object class will be
     * declared abstract. This helps support class hierarchies
     *
     * @param boolean $flag
     */
    public function setAbstract($flag = true)
    {
        $this->isAbstract = (boolean)$flag;
    }

    /**
     * Returns an array containing all Field objects in the entity.
     *
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns a delimiter-delimited string list of field names.
     *
     * @see SqlDefaultPlatform::getFieldList() if quoting is required
     *
     * @param array
     * @param  string $delimiter
     * @return string
     */
    public function getFieldList($columns, $delimiter = ',')
    {
        $list = [];
        foreach ($columns as $col) {
            if ($col instanceof Field) {
                $col = $col->getName();
            }
            $list[] = $col;
        }
        return implode($delimiter, $list);
    }

    /**
     * Returns the number of fields in this entity.
     *
     * @return integer
     */
    public function getNumFields()
    {
        return count($this->fields);
    }

    /**
     * Returns the number of lazy loaded fields in this entity.
     *
     * @return integer
     */
    public function getNumLazyLoadFields()
    {
        $count = 0;
        foreach ($this->fields as $col) {
            if ($col->isLazyLoad()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns whether or not one of the fields is of type ENUM.
     *
     * @return boolean
     */
    public function hasEnumFields()
    {
        foreach ($this->fields as $col) {
            if ($col->isEnumType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of all foreign keys.
     *
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param string $fieldName
     *
     * @return Relation
     */
    public function getRelation($fieldName)
    {
        foreach ($this->relations as $relation) {
            if ($relation->getField() == $fieldName) {
                return $relation;
            }
        }
    }

    /**
     * Returns a Collection of parameters relevant for the chosen
     * id generation method.
     *
     * @return IdMethodParameter[]
     */
    public function getIdMethodParameters()
    {
        return $this->idMethodParameters;
    }

    /**
     * Returns the list of all indices of this entity.
     *
     * @return Index[]
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Returns the list of all unique indices of this entity.
     *
     * @return Unique[]
     */
    public function getUnices()
    {
        return $this->unices;
    }

    /**
     * Checks if $keys are a unique constraint in the entity.
     * (through primaryKey, through a regular unices constraints or for single keys when it has isUnique=true)
     *
     * @param  Field[]|string[] $keys
     *
     * @return boolean
     */
    public function isUnique(array $keys)
    {
        if (1 === count($keys)) {
            $field = $keys[0] instanceof Field ? $keys[0] : $this->getField($keys[0]);
            if ($field) {
                if ($field->isUnique()) {
                    return true;
                }

                if ($field->isPrimaryKey() && 1 === count($field->getEntity()->getPrimaryKey())) {
                    return true;
                }
            }
        }

        // check if pk == $keys
        if (count($this->getPrimaryKey()) === count($keys)) {
            $allPk = true;
            $stringArray = is_string($keys[0]);
            foreach ($this->getPrimaryKey() as $pk) {
                if ($stringArray) {
                    if (!in_array($pk->getName(), $keys)) {
                        $allPk = false;
                        break;
                    }
                } else {
                    if (!in_array($pk, $keys)) {
                        $allPk = false;
                        break;
                    }
                }
            }

            if ($allPk) {
                return true;
            }
        }

        // check if there is a unique constrains that contains exactly the $keys
        if ($this->unices) {
            foreach ($this->unices as $unique) {
                if (count($unique->getFields()) === count($keys)) {
                    $allAvailable = true;
                    foreach ($keys as $key) {
                        if (!$unique->hasField($key instanceof Field ? $key->getName() : $key)) {
                            $allAvailable = false;
                            break;
                        }
                    }
                    if ($allAvailable) {
                        return true;
                    }
                } else {
                    continue;
                }
            }
        }

        return false;
    }

    /**
     * Checks if a index exists with the given $keys.
     *
     * @param  array $keys
     *
     * @return boolean
     */
    public function isIndex(array $keys)
    {
        if ($this->indices) {
            foreach ($this->indices as $index) {
                if (count($keys) === count($index->getFields())) {
                    $allAvailable = true;
                    foreach ($keys as $key) {
                        if (!$index->hasField($key instanceof Field ? $key->getName() : $key)) {
                            $allAvailable = false;
                            break;
                        }
                    }
                    if ($allAvailable) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns whether or not the entity has a field.
     *
     * @param  Field|string $field          The Field object or its name
     * @param  boolean      $caseInsensitive Whether the check is case insensitive.
     *
     * @return boolean
     */
    public function hasField($field, $caseInsensitive = false)
    {
        if ($field instanceof Field) {
            $field = $field->getName();
        }

        if ($caseInsensitive) {
            return isset($this->fieldsByLowercaseName[strtolower($field)]);
        }

        return isset($this->fieldsByName[$field]);
    }

    /**
     * Returns the Field object with the specified name.
     *
     * @param  string  $name            The name of the field (e.g. 'my_field')
     * @param  boolean $caseInsensitive Whether the check is case insensitive.
     *
     * @return Field
     */
    public function getField($name, $caseInsensitive = false)
    {
        if (!$this->hasField($name, $caseInsensitive)) {
            throw new \InvalidArgumentException(sprintf('Field `%s` not found in Entity `%s` [%s]', $name, $this->getName(), implode(',', array_keys($this->fieldsByName))));
        }

        if ($caseInsensitive) {
            return $this->fieldsByLowercaseName[strtolower($name)];
        }

        return $this->fieldsByName[$name];
    }

//    /**
//     * Returns a specified field by its php name.
//     *
//     * @param  string $phpName
//     *
//     * @return Field
//     */
//    public function getFieldByPhpName($phpName)
//    {
//        if (isset($this->fieldsByPhpName[$phpName])) {
//            return $this->fieldsByPhpName[$phpName];
//        }
//
//        return null;
//    }

    /**
     * Returns all foreign keys from this entity that reference the entity passed
     * in argument.
     *
     * @param  string $entityName
     *
     * @return array
     */
    public function getRelationsReferencingEntity($entityName)
    {
        $matches = [];
        foreach ($this->relations as $relation) {
            if ($relation->getForeignEntityName() === $entityName) {
                $matches[] = $relation;
            }
        }

        return $matches;
    }

    /**
     * Returns the foreign keys that include $field in it's list of local
     * fields.
     *
     * Eg. Foreign key (a, b, c) references tbl(x, y, z) will be returned of $field
     * is either a, b or c.
     *
     * @param  string $field Name of the field
     *
     * @return array
     */
    public function getFieldRelations($field)
    {
        $matches = [];
        foreach ($this->relations as $relation) {
            if (in_array($field, $relation->getLocalFields())) {
                $matches[] = $relation;
            }
        }

        return $matches;
    }

    /**
     * Set the database that contains this entity.
     *
     * @param Database $database
     */
    public function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get the database that contains this entity.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Returns the Database platform.
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        return $this->database ? $this->database->getPlatform() : null;
    }

    /**
     * Quotes a identifier depending on identifierQuotingEnabled.
     *
     * Needs a platform assigned to its database.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier($text)
    {
        if (!$this->getPlatform()) {
            throw new RuntimeException(
                'No platform specified. Can not quote without knowing which platform this entity\'s database is using.'
            );
        }

        if ($this->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * Returns whether or not code and SQL must be created for this entity.
     *
     * Entity will be skipped, if return true.
     *
     * @return boolean
     */
    public function isForReferenceOnly()
    {
        return $this->forReferenceOnly;
    }

    /**
     * Returns whether or not to determine if code/sql gets created for this entity.
     * Entity will be skipped, if set to true.
     *
     * @param boolean $flag
     */
    public function setForReferenceOnly($flag = true)
    {
        $this->forReferenceOnly = (boolean)$flag;
    }

    /**
     * Returns the collection of Fields which make up the single primary
     * key for this entity.
     *
     * @return Field[]
     */
    public function getPrimaryKey()
    {
        $pk = [];
        foreach ($this->fields as $col) {
            if ($col->isPrimaryKey()) {
                $pk[] = $col;
            }
        }

        return $pk;
    }

    /**
     * Returns whether or not this entity has a primary key.
     *
     * @return boolean
     */
    public function hasPrimaryKey()
    {
        return count($this->getPrimaryKey()) > 0;
    }

    /**
     * Returns whether or not this entity has a composite primary key.
     *
     * @return boolean
     */
    public function hasCompositePrimaryKey()
    {
        return count($this->getPrimaryKey()) > 1;
    }

    /**
     * Returns the first primary key field.
     *
     * Useful for entitys with a PK using a single field.
     *
     * @return Field
     */
    public function getFirstPrimaryKeyField()
    {
        foreach ($this->fields as $col) {
            if ($col->isPrimaryKey()) {
                return $col;
            }
        }
    }

    public function __clone()
    {
        $fields = [];
        if ($this->fields) {
            foreach ($this->fields as $oldCol) {
                $col = clone $oldCol;
                $fields[] = $col;
                $this->fieldsByName[$col->getName()] = $col;
                $this->fieldsByLowercaseName[strtolower($col->getName())] = $col;
//            $this->fieldsByPhpName[$col->getName()] = $col;
            }
            $this->fields = $fields;
        }
    }

    /**
     * Returns whether or not this entity has any auto-increment primary keys.
     *
     * @return boolean
     */
    public function hasAutoIncrementPrimaryKey()
    {
        return null !== $this->getAutoIncrementPrimaryKey();
    }

    /**
     * @return string[]
     */
    public function getAutoIncrementFieldNames()
    {
        $names = [];
        foreach ($this->getFields() as $field) {
            if ($field->isAutoIncrement()) {
                $names[] = $field->getName();
            }
        }

        return $names;
    }

    /**
     * Returns the auto incremented primary key.
     *
     * @return Field
     */
    public function getAutoIncrementPrimaryKey()
    {
        if (IdMethod::NO_ID_METHOD !== $this->getIdMethod()) {
            foreach ($this->getPrimaryKey() as $pk) {
                if ($pk->isAutoIncrement()) {
                    return $pk;
                }
            }
        }
    }

    /**
     * Returns whether or not this entity has at least one auto increment field.
     *
     * @return boolean
     */
    public function hasAutoIncrement()
    {
        foreach ($this->getFields() as $field) {
            if ($field->isAutoIncrement()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether or not there is a cross reference status for this foreign
     * key.
     *
     * @return boolean
     */
    public function getIsCrossRef()
    {
        return $this->isCrossRef;
    }

    /**
     * Alias for Entity::getIsCrossRef.
     *
     * @return boolean
     */
    public function isCrossRef()
    {
        return $this->isCrossRef;
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param boolean $flag
     */
    public function setIsCrossRef($flag = true)
    {
        $this->setCrossRef($flag);
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param boolean $flag
     */
    public function setCrossRef($flag = true)
    {
        $this->isCrossRef = (boolean)$flag;
    }

    /**
     * Returns whether or not the entity has foreign keys.
     *
     * @return boolean
     */
    public function hasRelations()
    {
        return 0 !== count($this->relations);
    }

    /**
     * Returns whether the entity has cross foreign keys or not.
     *
     * @return boolean
     */
    public function hasCrossRelations()
    {
        return 0 !== count($this->getCrossFks());
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

    /**
     * @return boolean
     */
    public function isActiveRecord()
    {
        if (null === $this->activeRecord) {
            return $this->getDatabase()->isActiveRecord();
        }

        return $this->activeRecord;
    }

    /**
     * @return bool|null
     */
    public function getActiveRecord()
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
     * Checks if identifierQuoting is enabled. Looks up to its database->isIdentifierQuotingEnabled
     * if identifierQuoting is null hence undefined.
     *
     * Use getIdentifierQuoting() if you need the raw value.
     *
     * @return boolean
     */
    public function isIdentifierQuotingEnabled()
    {
        return (null !== $this->identifierQuoting || !$this->database) ? $this->identifierQuoting : $this->database->isIdentifierQuotingEnabled(
        );
    }

    /**
     * @return bool|null
     */
    public function getIdentifierQuoting()
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
