<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\MultiExtendBuilder;
use Propel\Generator\Builder\Om\MultiExtendObjectBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\ProxyBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\QueryInheritanceBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Om\StubQueryBuilder;
use Propel\Generator\Builder\Om\StubQueryInheritanceBuilder;
use Propel\Generator\Builder\Om\StubRepositoryBuilder;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Entity;
use Propel\Generator\Platform\PlatformInterface;

/**
 * This is the base class for any builder class that is using the data model.
 *
 * This could be extended by classes that build SQL DDL, PHP classes, configuration
 * files, input forms, etc.
 *
 * The GeneratorConfig needs to be set on this class in order for the builders
 * to be able to access the propel generator build properties.  You should be
 * safe if you always use the GeneratorConfig to get a configured builder class
 * anyway.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class DataModelBuilder
{

    /**
     * The current entity.
     *
     * @var Entity
     */
    private $entity;

    /**
     * The generator config object holding build properties, etc.
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * An array of warning messages that can be retrieved for display.
     *
     * @var array string[]
     */
    private $warnings = array();

    /**
     * Object builder class for current entity.
     *
     * @var DataModelBuilder
     */
    private $objectBuilder;

    /**
     * Proxy builder class for current entity.
     *
     * @var DataModelBuilder
     */
    private $proxyBuilder;

    /**
     * Stub Object builder class for current entity.
     *
     * @var DataModelBuilder
     */
    private $activeRecordTraitBuilder;

    /**
     * Query builder class for current entity.
     *
     * @var DataModelBuilder
     */
    private $queryBuilder;

    /**
     * Stub Query builder class for current entity.
     *
     * @var DataModelBuilder
     */
    private $stubQueryBuilder;

    /**
     * EntityMap builder class for current entity.
     *
     * @var DataModelBuilder
     */
    protected $entitymapBuilder;

    /**
     * Stub Interface builder class for current entity.
     *
     * @var DataModelBuilder
     */
    private $interfaceBuilder;

    /**
     * Stub child object for current entity.
     *
     * @var DataModelBuilder
     */
    private $multiExtendObjectBuilder;

    /**
     * The Pluralizer class to use.
     *
     * @var PluralizerInterface
     */
    private $pluralizer;

    /**
     * The platform class
     *
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * Creates new instance of DataModelBuilder subclass.
     *
     * @param Entity $entity The Entity which we are using to build [OM, DDL, etc.].
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Returns new or existing Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getPluralizer()
    {
        if (!isset($this->pluralizer)) {
            $this->pluralizer = $this->getGeneratorConfig()->getConfiguredPluralizer();
        }

        return $this->pluralizer;
    }

    protected function validateModel()
    {
        // Validation is currently only implemented in the subclasses.
    }

    /**
     * Returns new or existing Object builder class for this entity.
     *
     * @return ObjectBuilder
     */
    public function getObjectBuilder()
    {
        if (!isset($this->objectBuilder)) {
            $this->objectBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getEntity(), 'object');
        }

        return $this->objectBuilder;
    }

    /**
     * Returns new or existing Proxy builder class for this entity.
     *
     * @return ProxyBuilder
     */
    public function getProxyBuilder()
    {
        if (!isset($this->proxyBuilder)) {
            $this->proxyBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getEntity(), 'proxy');
        }

        return $this->proxyBuilder;
    }

    /**
     * Returns new or existing stub Object builder class for this entity.
     *
     * @return ActiveRecordTraitBuilder
     */
    public function getActiveRecordTraitBuilder()
    {
        if (!isset($this->activeRecordTraitBuilder)) {
            $this->activeRecordTraitBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'activerecordtrait'
            );
        }

        return $this->activeRecordTraitBuilder;
    }

    /**
     * Returns new or existing Object builder class for this entity.
     *
     * @return RepositoryBuilder
     */
    public function getRepositoryBuilder()
    {
        if (!isset($this->objectRepository)) {
            $this->objectRepository = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'repository'
            );
        }

        return $this->objectRepository;
    }

    /**
     * Returns new or existing stub Repository builder class for this entity.
     *
     * @return StubRepositoryBuilder
     */
    public function getStubRepositoryBuilder()
    {
        if (!isset($this->stubRepositoryBuilder)) {
            $this->stubRepositoryBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'repositorystub'
            );
        }

        return $this->stubRepositoryBuilder;
    }

    /**
     * Returns new or existing Query builder class for this entity.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!isset($this->queryBuilder)) {
            $this->queryBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getEntity(), 'query');
        }

        return $this->queryBuilder;
    }

    /**
     * Returns new or existing stub Query builder class for this entity.
     *
     * @return StubQueryBuilder
     */
    public function getStubQueryBuilder()
    {
        if (!isset($this->stubQueryBuilder)) {
            $this->stubQueryBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'querystub'
            );
        }

        return $this->stubQueryBuilder;
    }

    /**
     * Returns new or existing Object builder class for this entity.
     *
     * @return EntityMapBuilder
     */
    public function getEntityMapBuilder()
    {
        if (!isset($this->entitymapBuilder)) {
            $this->entitymapBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'entitymap'
            );
        }

        return $this->entitymapBuilder;
    }

    /**
     * Returns new or existing stub Interface builder class for this entity.
     *
     * @return AbstractBuilder
     */
    public function getInterfaceBuilder()
    {
        if (!isset($this->interfaceBuilder)) {
            $this->interfaceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'interface'
            );
        }

        return $this->interfaceBuilder;
    }

    /**
     * Returns new or existing stub child object builder class for this entity.
     *
     * @return MultiExtendObjectBuilder
     */
    public function getMultiExtendObjectBuilder()
    {
        if (!isset($this->multiExtendObjectBuilder)) {
            $this->multiExtendObjectBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
                $this->getEntity(),
                'objectmultiextend'
            );
        }

        return $this->multiExtendObjectBuilder;
    }

    /**
     * Gets a new data model builder class for specified entity and classname.
     *
     * @param  Entity $entity
     * @param  string $classname The class of builder
     *
     * @return DataModelBuilder
     */
    public function getNewBuilder(Entity $entity, $classname)
    {
        /** @var DataModelBuilder $builder */
        $builder = new $classname($entity);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Convenience method to return a NEW Object class builder instance.
     *
     * This is used very frequently from the entityMap and object builders to get
     * an object builder for a RELATED entity.
     *
     * @param  Entity $entity
     *
     * @return ObjectBuilder
     */
    public function getNewObjectBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'object');
    }

    /**
     * Convenience method to return a NEW Object stub class builder instance.
     *
     * This is used from the query builders to get
     * an object builder for a RELATED entity.
     *
     * @param  Entity $entity
     *
     * @return ActiveRecordTraitBuilder
     */
    public function getNewActiveRecordTraitObjectBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'activerecordtrait');
    }

    /**
     * Convenience method to return a NEW repository class builder instance.
     *
     * This is used very frequently from the entityMap and object builders to get
     * an object builder for a RELATED entity.
     *
     * @param  Entity $entity
     *
     * @return RepositoryBuilder
     */
    public function getNewRepositoryBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'repository');
    }

    /**
     * Convenience method to return a NEW repository stub class builder instance.
     *
     * This is used from the query builders to get
     * an object builder for a RELATED entity.
     *
     * @param  Entity $entity
     *
     * @return StubRepositoryBuilder
     */
    public function getNewStubRepositoryBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'repositorystub');
    }

    /**
     * Convenience method to return a NEW query class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED entity.
     *
     * @param  Entity $entity
     *
     * @return QueryBuilder
     */
    public function getNewQueryBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'query');
    }

    /**
     * Convenience method to return a NEW query stub class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED entity.
     *
     * @param  Entity $entity
     *
     * @return QueryBuilder
     */
    public function getNewStubQueryBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'querystub');
    }

    /**
     * Returns new Query Inheritance builder class for this entity.
     *
     * @param  Inheritance $child
     *
     * @return ObjectBuilder
     */
    public function getNewQueryInheritanceBuilder(Inheritance $child)
    {
        /** @var QueryInheritanceBuilder $queryInheritanceBuilder */
        $queryInheritanceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
            $this->getEntity(),
            'queryinheritance'
        );
        $queryInheritanceBuilder->setChild($child);

        return $queryInheritanceBuilder;
    }

    /**
     * Returns new stub Query Inheritance builder class for this entity.
     *
     * @param  Inheritance $child
     *
     * @return StubQueryInheritanceBuilder
     */
    public function getNewStubQueryInheritanceBuilder(Inheritance $child)
    {
        /** @var QueryInheritanceBuilder $stubQueryInheritanceBuilder */
        $stubQueryInheritanceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder(
            $this->getEntity(),
            'queryinheritancestub'
        );
        $stubQueryInheritanceBuilder->setChild($child);

        return $stubQueryInheritanceBuilder;
    }

    /**
     * Returns new stub Query Inheritance builder class for this entity.
     *
     * @return EntityMapBuilder
     */
    public function getNewEntityMapBuilder(Entity $entity)
    {
        return $this->getGeneratorConfig()->getConfiguredBuilder($entity, 'entitymap');
    }

    /**
     * Gets the GeneratorConfig object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig()
    {
        return $this->generatorConfig;
    }

    /**
     * Get a specific configuration property.
     *
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['entityType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.entityType</code>
     *
     * @param  string $name
     *
     * @return string
     */
    public function getBuildProperty($name)
    {
        if ($this->getGeneratorConfig()) {
            return $this->getGeneratorConfig()->getConfigProperty($name);
        }

        return null; // just to be explicit
    }

    /**
     * Sets the GeneratorConfig object.
     *
     * @param GeneratorConfigInterface $v
     */
    public function setGeneratorConfig(GeneratorConfigInterface $v)
    {
        $this->generatorConfig = $v;
    }

    /**
     * Sets the entity for this builder.
     *
     * @param Entity $entity
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Returns the current Entity object.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Convenience method to returns the Platform class for this entity (database).
     *
     * @return PlatformInterface
     */
    public function getPlatform()
    {
        if (null === $this->platform) {
            // try to load the platform from the entity
            $entity = $this->getEntity();
            if ($entity && $database = $entity->getDatabase()) {
                $this->setPlatform($database->getPlatform());
            }
        }

        if (!$this->entity->isIdentifierQuotingEnabled()) {
            $this->platform->setIdentifierQuoting(false);
        }

        return $this->platform;
    }

    /**
     * Platform setter
     *
     * @param PlatformInterface $platform
     */
    public function setPlatform(PlatformInterface $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Quotes identifier based on $this->getEntity()->isIdentifierQuotingEnabled.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier($text)
    {
        if ($this->getEntity()->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * Convenience method to returns the database for current entity.
     *
     * @return Database
     */
    public function getDatabase()
    {
        if ($this->getEntity()) {
            return $this->getEntity()->getDatabase();
        }
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
     * Returns the name of the current class being built, with a possible prefix.
     *
     * @return string
     * @see OMBuilder#getClassName()
     */
    public function prefixClassName($identifier)
    {
        return $this->getBuildProperty('generator.objectModel.classPrefix') . $identifier;
    }
}
