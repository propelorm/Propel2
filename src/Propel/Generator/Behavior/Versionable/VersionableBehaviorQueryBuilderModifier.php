<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Versionable;

/**
 * Behavior to add versionable fields and abilities
 *
 * @author François Zaninotto
 */
class VersionableBehaviorQueryBuilderModifier
{
    protected $behavior;

    protected $table;

    protected $builder;

    protected $objectClassName;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table    = $behavior->getEntity();
    }

    public function queryAttributes()
    {
        return "
/**
 * Whether the versioning is enabled
 */
static \$isVersioningEnabled = true;
";
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getFieldAttribute($name = 'version_field')
    {
        return strtolower($this->behavior->getFieldForParameter($name)->getName());
    }

    protected function getFieldPhpName($name = 'version_field')
    {
        return $this->behavior->getFieldForParameter($name)->getName();
    }

    protected function getVersionQueryClassName()
    {
        return $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($this->behavior->getVersionEntity()));
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassName = $builder->getObjectClassName();
        $this->queryClassName = $builder->getQueryClassName();
    }

    /**
     * Get the getter of the field of the behavior
     *
     * @return string The related getter, e.g. 'getVersion'
     */
    protected function getFieldGetter($name = 'version_field')
    {
        return 'get' . $this->getFieldPhpName($name);
    }

    /**
     * Get the setter of the field of the behavior
     *
     * @return string The related setter, e.g. 'setVersion'
     */
    protected function getFieldSetter($name = 'version_field')
    {
        return 'set' . $this->getFieldPhpName($name);
    }

    public function queryMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';
        if ('version' !== $this->getParameter('version_field')) {
            $this->addFilterByVersion($script);
            $this->addOrderByVersion($script);
        }

        $script .= $this->addIsVersioningEnabled();
        $script .= $this->addEnableVersioning();
        $script .= $this->addDisableVersioning();

        return $script;
    }

    protected function addFilterByVersion(&$script)
    {
        $script .= "
/**
 * Wrap the filter on the version field
 *
 * @param     integer \$version
 * @param     string  \$comparison Operator to use for the field comparison, defaults to Criteria::EQUAL
 * @return    \$this|" . $this->builder->getQueryClassName() . " The current query, for fluid interface
 */
public function filterByVersion(\$version = null, \$comparison = null)
{
    return \$this->filterBy{$this->getFieldPhpName()}(\$version, \$comparison);
}
";
    }

    protected function addOrderByVersion(&$script)
    {
        $script .= "
/**
 * Wrap the order on the version field
 *
 * @param   string \$order The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
 * @return  \$this|" . $this->builder->getQueryClassName() . " The current query, for fluid interface
 */
public function orderByVersion(\$order = Criteria::ASC)
{
    return \$this->orderBy('{$this->getFieldPhpName()}', \$order);
}
";
    }

    protected function addIsVersioningEnabled()
    {
        return "
/**
 * Checks whether versioning is enabled
 *
 * @return boolean
 */
static public function isVersioningEnabled()
{
    return self::\$isVersioningEnabled;
}
";
    }

    protected function addEnableVersioning()
    {
        return "
/**
 * Enables versioning
 */
static public function enableVersioning()
{
    self::\$isVersioningEnabled = true;
}
";
    }

    protected function addDisableVersioning()
    {
        return "
/**
 * Disables versioning
 */
static public function disableVersioning()
{
    self::\$isVersioningEnabled = false;
}
";
    }
}
