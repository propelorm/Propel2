<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Behavior\Versionable;

/**
 * Behavior to add versionable columns and abilities
 *
 * @author     François Zaninotto
 */
class VersionableBehaviorQueryBuilderModifier
{
    protected $behavior;
    protected $table;
    protected $builder;
    protected $objectClassname;
    protected $peerClassname;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    protected function getParameter($key)
    {
        return $this->behavior->getParameter($key);
    }

    protected function getColumnAttribute($name = 'version_column')
    {
        return strtolower($this->behavior->getColumnForParameter($name)->getName());
    }

    protected function getColumnPhpName($name = 'version_column')
    {
        return $this->behavior->getColumnForParameter($name)->getPhpName();
    }

    protected function getVersionQueryClassName()
    {
        return $this->builder->getClassnameFromBuilder($this->builder->getNewStubQueryBuilder($this->behavior->getVersionTable()));
    }

    protected function setBuilder($builder)
    {
        $this->builder = $builder;
        $this->objectClassname = $builder->getObjectClassname();
        $this->queryClassname = $builder->getQueryClassname();
        $this->peerClassname = $builder->getPeerClassname();
    }

    /**
     * Get the getter of the column of the behavior
     *
     * @return string The related getter, e.g. 'getVersion'
     */
    protected function getColumnGetter($name = 'version_column')
    {
        return 'get' . $this->getColumnPhpName($name);
    }

    /**
     * Get the setter of the column of the behavior
     *
     * @return string The related setter, e.g. 'setVersion'
     */
    protected function getColumnSetter($name = 'version_column')
    {
        return 'set' . $this->getColumnPhpName($name);
    }

    public function queryMethods($builder)
    {
        $this->setBuilder($builder);
        $script = '';
        if ($this->getParameter('version_column') != 'version') {
            $this->addFilterByVersion($script);
            $this->addOrderByVersion($script);
        }

        return $script;
    }

    protected function addFilterByVersion(&$script)
    {
        $script .= "
/**
 * Wrap the filter on the version column
 *
 * @param     integer \$version
 * @param     string  \$comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
 * @return    " . $this->builder->getQueryClassname() . " The current query, for fluid interface
 */
public function filterByVersion(\$version = null, \$comparison = null)
{
    return \$this->filterBy{$this->getColumnPhpName()}(\$version, \$comparison);
}
";
    }

    protected function addOrderByVersion(&$script)
    {
        $script .= "
/**
 * Wrap the order on the version volumn
 *
 * @param   string \$order The sorting order. Criteria::ASC by default, also accepts Criteria::DESC
 * @return  " . $this->builder->getQueryClassname() . " The current query, for fluid interface
 */
public function orderByVersion(\$order = Criteria::ASC)
{
    return \$this->orderBy('{$this->getColumnPhpName()}', \$order);
}
";
    }

}
