<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Versionable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;

/**
 * Behavior to add versionable columns and abilities
 *
 * @author François Zaninotto
 */
class VersionableBehaviorQueryBuilderModifier
{
    /**
     * @var \Propel\Generator\Behavior\Versionable\VersionableBehavior
     */
    protected $behavior;

    /**
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * @var \Propel\Generator\Builder\Om\AbstractOMBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    protected $objectClassName;

    /**
     * @var string
     */
    protected $queryClassName;

    /**
     * @param \Propel\Generator\Behavior\Versionable\VersionableBehavior $behavior
     */
    public function __construct(VersionableBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @return string
     */
    public function queryAttributes(): string
    {
        return "
/**
 * Whether the versioning is enabled
 */
static \$isVersioningEnabled = true;
";
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getParameter(string $key)
    {
        return $this->behavior->getParameter($key);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getColumnAttribute(string $name = 'version_column'): string
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getColumnPhpName(string $name = 'version_column'): string
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    /**
     * @return string
     */
    protected function getVersionQueryClassName(): string
    {
        return $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($this->behavior->getVersionTable()));
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return void
     */
    protected function setBuilder(AbstractOMBuilder $builder): void
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @param string $name
     *
     * @return string The related getter, e.g. 'getVersion'
     */
    protected function getColumnGetter(string $name = 'version_column'): string
    {
        return 'get' . $this->getColumnPhpName($name);
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @param string $name
     *
     * @return string The related setter, e.g. 'setVersion'
     */
    protected function getColumnSetter(string $name = 'version_column'): string
    {
        return 'set' . $this->getColumnPhpName($name);
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryMethods(AbstractOMBuilder $builder): string
    {
        $this->setBuilder($builder);
        $script = '';
        if ($this->getParameter('version_column') !== 'version') {
            $this->addFilterByVersion($script);
            $this->addOrderByVersion($script);
        }

        $script .= $this->addIsVersioningEnabled();
        $script .= $this->addEnableVersioning();
        $script .= $this->addDisableVersioning();

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addFilterByVersion(string &$script): void
    {
        $script .= "
/**
 * Wrap the filter on the version column
 *
 * @param int|null \$version
 * @param string|null  \$comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
 * @return \$this The current query, for fluid interface
 */
public function filterByVersion(\$version = null, ?string \$comparison = null)
{
    \$this->filterBy{$this->getColumnPhpName()}(\$version, \$comparison);

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addOrderByVersion(string &$script): void
    {
        $script .= "
/**
 * Wrap the order on the version column
 *
 * @param string \$order The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
 * @return \$this The current query, for fluid interface
 */
public function orderByVersion(string \$order = Criteria::ASC)
{
    \$this->orderBy('{$this->getColumnPhpName()}', \$order);

    return \$this;
}
";
    }

    /**
     * @return string
     */
    protected function addIsVersioningEnabled(): string
    {
        return "
/**
 * Checks whether versioning is enabled
 *
 * @return bool
 */
static public function isVersioningEnabled(): bool
{
    return self::\$isVersioningEnabled;
}
";
    }

    /**
     * @return string
     */
    protected function addEnableVersioning(): string
    {
        return "
/**
 * Enables versioning
 */
static public function enableVersioning(): void
{
    self::\$isVersioningEnabled = true;
}
";
    }

    /**
     * @return string
     */
    protected function addDisableVersioning(): string
    {
        return "
/**
 * Disables versioning
 */
static public function disableVersioning(): void
{
    self::\$isVersioningEnabled = false;
}
";
    }
}
