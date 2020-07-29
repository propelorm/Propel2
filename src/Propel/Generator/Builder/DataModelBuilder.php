<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Builder;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;

/**
 * This is the base class for any builder class that is using the data model.
 *
 * This could be extended by classes that build SQL DDL, PHP classes, configuration
 * files, input forms, etc.
 *
 * The GeneratorConfig needs to be set on this class in order for the builders
 * to be able to access the propel generator build properties. You should be
 * safe if you always use the GeneratorConfig to get a configured builder class
 * anyway.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class DataModelBuilder
{
    /**
     * The current table.
     *
     * @var \Propel\Generator\Model\Table
     */
    private $table;

    /**
     * The generator config object holding build properties, etc.
     *
     * @var \Propel\Generator\Config\GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * An array of warning messages that can be retrieved for display.
     *
     * @var array string[]
     */
    private $warnings = [];

    /**
     * Object builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    private $objectBuilder;

    /**
     * Stub Object builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    private $stubObjectBuilder;

    /**
     * Query builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    private $queryBuilder;

    /**
     * Stub Query builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    private $stubQueryBuilder;

    /**
     * TableMap builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\TableMapBuilder
     */
    protected $tablemapBuilder;

    /**
     * Stub Interface builder class for current table.
     *
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    private $interfaceBuilder;

    /**
     * Stub child object for current table.
     *
     * @var \Propel\Generator\Builder\Om\MultiExtendObjectBuilder
     */
    private $multiExtendObjectBuilder;

    /**
     * The Pluralizer class to use.
     *
     * @var \Propel\Common\Pluralizer\PluralizerInterface
     */
    private $pluralizer;

    /**
     * The platform class
     *
     * @var \Propel\Generator\Platform\PlatformInterface
     */
    protected $platform;

    /**
     * Creates new instance of DataModelBuilder subclass.
     *
     * @param \Propel\Generator\Model\Table $table The Table which we are using to build [OM, DDL, etc.].
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns new or existing Pluralizer class.
     *
     * @return \Propel\Common\Pluralizer\PluralizerInterface
     */
    public function getPluralizer()
    {
        if (!isset($this->pluralizer)) {
            $this->pluralizer = $this->getGeneratorConfig()->getConfiguredPluralizer();
        }

        return $this->pluralizer;
    }

    /**
     * Returns new or existing Object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getObjectBuilder()
    {
        if (!isset($this->objectBuilder)) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'object');
            $this->objectBuilder = $builder;
        }

        return $this->objectBuilder;
    }

    /**
     * Returns new or existing stub Object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getStubObjectBuilder()
    {
        if (!isset($this->stubObjectBuilder)) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'objectstub');
            $this->stubObjectBuilder = $builder;
        }

        return $this->stubObjectBuilder;
    }

    /**
     * Returns new or existing Query builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getQueryBuilder()
    {
        if (!isset($this->queryBuilder)) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'query');
            $this->queryBuilder = $builder;
        }

        return $this->queryBuilder;
    }

    /**
     * Returns new or existing stub Query builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getStubQueryBuilder()
    {
        if (!isset($this->stubQueryBuilder)) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'querystub');
            $this->stubQueryBuilder = $builder;
        }

        return $this->stubQueryBuilder;
    }

    /**
     * Returns new or existing Object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\TableMapBuilder
     */
    public function getTableMapBuilder()
    {
        if (!isset($this->tablemapBuilder)) {
            /** @var \Propel\Generator\Builder\Om\TableMapBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'tablemap');
            $this->tablemapBuilder = $builder;
        }

        return $this->tablemapBuilder;
    }

    /**
     * Returns new or existing stub Interface builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getInterfaceBuilder()
    {
        if (!isset($this->interfaceBuilder)) {
            /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'interface');
            $this->interfaceBuilder = $builder;
        }

        return $this->interfaceBuilder;
    }

    /**
     * Returns new or existing stub child object builder class for this table.
     *
     * @return \Propel\Generator\Builder\Om\MultiExtendObjectBuilder
     */
    public function getMultiExtendObjectBuilder()
    {
        if (!isset($this->multiExtendObjectBuilder)) {
            /** @var \Propel\Generator\Builder\Om\MultiExtendObjectBuilder $builder */
            $builder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'objectmultiextend');
            $this->multiExtendObjectBuilder = $builder;
        }

        return $this->multiExtendObjectBuilder;
    }

    /**
     * Gets a new data model builder class for specified table and classname.
     *
     * @param \Propel\Generator\Model\Table $table
     * @param string $classname The class of builder
     *
     * @return \Propel\Generator\Builder\DataModelBuilder
     */
    public function getNewBuilder(Table $table, $classname)
    {
        /** @var \Propel\Generator\Builder\DataModelBuilder $builder */
        $builder = new $classname($table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Convenience method to return a NEW Object class builder instance.
     *
     * This is used very frequently from the tableMap and object builders to get
     * an object builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getNewObjectBuilder(Table $table)
    {
        /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'object');

        return $builder;
    }

    /**
     * Convenience method to return a NEW Object stub class builder instance.
     *
     * This is used from the query builders to get
     * an object builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\ObjectBuilder
     */
    public function getNewStubObjectBuilder(Table $table)
    {
        /** @var \Propel\Generator\Builder\Om\ObjectBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'objectstub');

        return $builder;
    }

    /**
     * Convenience method to return a NEW query class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\QueryBuilder
     */
    public function getNewQueryBuilder(Table $table)
    {
        /** @var \Propel\Generator\Builder\Om\QueryBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'query');

        return $builder;
    }

    /**
     * Convenience method to return a NEW query stub class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\QueryBuilder
     */
    public function getNewStubQueryBuilder(Table $table)
    {
        /** @var \Propel\Generator\Builder\Om\QueryBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'querystub');

        return $builder;
    }

    /**
     * Returns new Query Inheritance builder class for this table.
     *
     * @param \Propel\Generator\Model\Inheritance $child
     *
     * @return \Propel\Generator\Builder\Om\QueryInheritanceBuilder
     */
    public function getNewQueryInheritanceBuilder(Inheritance $child)
    {
        /** @var \Propel\Generator\Builder\Om\QueryInheritanceBuilder $queryInheritanceBuilder */
        $queryInheritanceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'queryinheritance');
        $queryInheritanceBuilder->setChild($child);

        return $queryInheritanceBuilder;
    }

    /**
     * Returns new stub Query Inheritance builder class for this table.
     *
     * @param \Propel\Generator\Model\Inheritance $child
     *
     * @return \Propel\Generator\Builder\Om\QueryInheritanceBuilder
     */
    public function getNewStubQueryInheritanceBuilder(Inheritance $child)
    {
        /** @var \Propel\Generator\Builder\Om\QueryInheritanceBuilder $stubQueryInheritanceBuilder */
        $stubQueryInheritanceBuilder = $this->getGeneratorConfig()->getConfiguredBuilder($this->getTable(), 'queryinheritancestub');
        $stubQueryInheritanceBuilder->setChild($child);

        return $stubQueryInheritanceBuilder;
    }

    /**
     * Returns new stub Query Inheritance builder class for this table.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\TableMapBuilder
     */
    public function getNewTableMapBuilder(Table $table)
    {
        /** @var \Propel\Generator\Builder\Om\TableMapBuilder $builder */
        $builder = $this->getGeneratorConfig()->getConfiguredBuilder($table, 'tablemap');

        return $builder;
    }

    /**
     * Gets the GeneratorConfig object.
     *
     * @return \Propel\Generator\Config\GeneratorConfigInterface
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
     * <code> $config['database']['adapter']['mysql']['tableType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.tableType</code>
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getBuildProperty($name)
    {
        if ($this->getGeneratorConfig()) {
            return $this->getGeneratorConfig()->getConfigProperty($name);
        }

        return null;
    }

    /**
     * Sets the GeneratorConfig object.
     *
     * @param \Propel\Generator\Config\GeneratorConfigInterface $v
     *
     * @return void
     */
    public function setGeneratorConfig(GeneratorConfigInterface $v)
    {
        $this->generatorConfig = $v;
    }

    /**
     * Sets the table for this builder.
     *
     * @param \Propel\Generator\Model\Table $table
     *
     * @return void
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns the current Table object.
     *
     * @return \Propel\Generator\Model\Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Convenience method to returns the Platform class for this table (database).
     *
     * @return \Propel\Generator\Platform\PlatformInterface
     */
    public function getPlatform()
    {
        if ($this->platform === null) {
            // try to load the platform from the table
            $table = $this->getTable();
            if ($table && $database = $table->getDatabase()) {
                $this->setPlatform($database->getPlatform());
            }
        }

        if (!$this->table->isIdentifierQuotingEnabled()) {
            $this->platform->setIdentifierQuoting(false);
        }

        return $this->platform;
    }

    /**
     * Platform setter
     *
     * @param \Propel\Generator\Platform\PlatformInterface $platform
     *
     * @return void
     */
    public function setPlatform(PlatformInterface $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Quotes identifier based on $this->getTable()->isIdentifierQuotingEnabled.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier($text)
    {
        if ($this->getTable()->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * Convenience method to returns the database for current table.
     *
     * @return \Propel\Generator\Model\Database
     */
    public function getDatabase()
    {
        if ($this->getTable()) {
            return $this->getTable()->getDatabase();
        }
    }

    /**
     * Pushes a message onto the stack of warnings.
     *
     * @param string $msg The warning message.
     *
     * @return void
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
     * @see OMBuilder#getClassName()
     *
     * @param string $identifier
     *
     * @return string
     */
    public function prefixClassName($identifier)
    {
        return $this->getBuildProperty('generator.objectModel.classPrefix') . $identifier;
    }
}
