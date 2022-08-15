<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\QueryCache;

use Propel\Generator\Model\Behavior;

/**
 * Speeds up queries on a model by caching the query
 *
 * @author François Zaninotto
 */
class QueryCacheBehavior extends Behavior
{
    /**
     * Default parameters value
     *
     * @var string[]
     */
    protected $parameters = [
        'backend' => 'apc',
        'lifetime' => '3600',
    ];

    /**
     * @var string
     */
    private $tableClassName;

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryAttributes($builder)
    {
        $script = "protected \$queryKey = '';
";
        switch ($this->getParameter('backend')) {
            case 'backend':
                $script .= "protected static \$cacheBackend = array();
            ";

                break;
            case 'apc':
                break;
            case 'custom':
            default:
                $script .= "protected static \$cacheBackend;
            ";

                break;
        }

        return $script;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryMethods($builder)
    {
        $builder->declareClasses('\Propel\Runtime\Propel');
        $this->tableClassName = $builder->getTableMapClassName();
        $script = '';
        $this->addSetQueryKey($script);
        $this->addGetQueryKey($script);
        $this->addCacheContains($script);
        $this->addCacheFetch($script);
        $this->addCacheStore($script);
        $this->addDoSelect($script);
        $this->addDoCount($script);
        $this->addGetParams($script);

        return $script;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addSetQueryKey(&$script)
    {
        $script .= "
public function setQueryKey(\$key)
{
    \$this->queryKey = \$key;

    return \$this;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addGetQueryKey(&$script)
    {
        $script .= "
public function getQueryKey()
{
    return \$this->queryKey;
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCacheContains(&$script)
    {
        $script .= "
public function cacheContains(\$key)
{";
        switch ($this->getParameter('backend')) {
            case 'apc':
                $script .= "

    return apc_fetch(\$key);";

                break;
            case 'apcu':
                $script .= "return apcu_fetch(\$key);";
                break;
            case 'array':
                $script .= "

    return isset(self::\$cacheBackend[\$key]);";

                break;
            case 'custom':
            default:
                $script .= "
    throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');";

                break;
        }
        $script .= "
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCacheStore(&$script)
    {
        $script .= "
public function cacheStore(\$key, \$value, \$lifetime = " . $this->getParameter('lifetime') . ")
{";
        switch ($this->getParameter('backend')) {
            case 'apc':
                $script .= "
    apc_store(\$key, \$value, \$lifetime);";

                break;
            case 'apcu':
                $script .= "apcu_store(\$key, \$value, \$lifetime);";
                break;
            case 'array':
                $script .= "
    self::\$cacheBackend[\$key] = \$value;";

                break;
            case 'custom':
            default:
                $script .= "
    throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');";

                break;
        }
        $script .= "
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addCacheFetch(&$script)
    {
        $script .= "
public function cacheFetch(\$key)
{";
        switch ($this->getParameter('backend')) {
            case 'apc':
                $script .= "

    return apc_fetch(\$key);";

                break;
            case 'apcu':
                $script .= "return apcu_fetch(\$key);";
                break;
            case 'array':
                $script .= "

    return isset(self::\$cacheBackend[\$key]) ? self::\$cacheBackend[\$key] : null;";

                break;
            case 'custom':
            default:
                $script .= "
    throw new PropelException('You must override the cacheContains(), cacheStore(), and cacheFetch() methods to enable query cache');";

                break;
        }
        $script .= "
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addDoSelect(&$script)
    {
        $script .= "
public function doSelect(ConnectionInterface \$con = null)
{
    // check that the columns of the main class are already added (if this is the primary ModelCriteria)
    if (!\$this->hasSelectClause() && !\$this->getPrimaryCriteria()) {
        \$this->addSelfSelectColumns();
    }

    \$dbMap = Propel::getServiceContainer()->getDatabaseMap(" . $this->tableClassName . "::DATABASE_NAME);
    \$db = Propel::getServiceContainer()->getAdapter(" . $this->tableClassName . "::DATABASE_NAME);

    \$key = \$this->getQueryKey();
    if (\$key && \$this->cacheContains(\$key)) {
        \$params = \$this->getParams();
        \$sql = \$this->cacheFetch(\$key);
    } else {
        \$params = array();
        \$sql = \$this->createSelectSql(\$params);
    }

    try {
        \$stmt = \$con->prepare(\$sql);
        \$db->bindValues(\$stmt, \$params, \$dbMap);
        \$stmt->execute();
        } catch (Exception \$e) {
            Propel::log(\$e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', \$sql), 0, \$e);
        }

    if (\$key && !\$this->cacheContains(\$key)) {
            \$this->cacheStore(\$key, \$sql);
    }

    return \$con->getDataFetcher(\$stmt);
}
";
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected function addDoCount(&$script)
    {
        $script .= "
public function doCount(ConnectionInterface \$con = null)
{
    \$dbMap = Propel::getServiceContainer()->getDatabaseMap(\$this->getDbName());
    \$db = Propel::getServiceContainer()->getAdapter(\$this->getDbName());

    \$key = \$this->getQueryKey();
    if (\$key && \$this->cacheContains(\$key)) {
        \$params = \$this->>getParams();
        \$sql = \$this->cacheFetch(\$key);
    } else {
        // check that the columns of the main class are already added (if this is the primary ModelCriteria)
        if (!\$this->hasSelectClause() && !\$this->getPrimaryCriteria()) {
            \$this->addSelfSelectColumns();
        }

        \$needsComplexCount = \$this->getGroupByColumns()
            || \$this->getOffset()
            || \$this->getLimit() >= 0
            || \$this->getHaving()
            || in_array(Criteria::DISTINCT, \$this->getSelectModifiers())
            || count(\$this->selectQueries) > 0
        ;

        \$params = array();
        if (\$needsComplexCount) {
            if (\$this->needsSelectAliases()) {
                if (\$this->getHaving()) {
                    throw new PropelException('Propel cannot create a COUNT query when using HAVING and  duplicate column names in the SELECT part');
                }
                \$db->turnSelectColumnsToAliases(\$this);
            }
            \$selectSql = \$this->createSelectSql(\$params);
            \$sql = 'SELECT COUNT(*) FROM (' . \$selectSql . ') propelmatch4cnt';
        } else {
            // Replace SELECT columns with COUNT(*)
            \$this->clearSelectColumns()->addSelectColumn('COUNT(*)');
            \$sql = \$this->createSelectSql(\$params);
        }
    }

    try {
        \$stmt = \$con->prepare(\$sql);
        \$db->bindValues(\$stmt, \$params, \$dbMap);
        \$stmt->execute();
    } catch (Exception \$e) {
        Propel::log(\$e->getMessage(), Propel::LOG_ERR);
        throw new PropelException(sprintf('Unable to execute COUNT statement [%s]', \$sql), 0, \$e);
    }

    if (\$key && !\$this->cacheContains(\$key)) {
            \$this->cacheStore(\$key, \$sql);
    }

    return \$con->getDataFetcher(\$stmt);
}
";
    }

    protected function addGetParams(&$script)
    {
        $script .= "
            public function getParams()
                {
                    \$params = [];
                    \$dbMap = Propel::getServiceContainer()->getDatabaseMap(\$this->getDbName());

                    \$joins = \$this->getJoins();
                    foreach (\$joins as \$join) {
                        if (count(\$join?->getJoinCondition()?->getClauses() ?? []) > 0) {
                            foreach (\$join->getJoinCondition()->getClauses() as \$clause) {
                                \$params[] = [
                                    'table' => \$clause->getTable(),
                                    'column' => \$clause->getColumn(),
                                    'value' => \$clause->getValue(),
                                ];
                            }
                        }
                    }

                    \$map = \$this->getMap();
                    foreach (\$this->getMap() as \$criterion) {
                        \$table = null;
                        foreach (\$criterion->getAttachedCriterion() as \$attachedCriterion) {
                            \$tableName = \$attachedCriterion->getTable();

                            \$table = \$this->getTableForAlias(\$tableName);
                            if (\$table === null) {
                                \$table = \$tableName;
                            }

                            if (
                                (\$this->isIgnoreCase() || method_exists(\$attachedCriterion, 'setIgnoreCase'))
                                && \$dbMap->getTable(\$table)->getColumn(\$attachedCriterion->getColumn())->isText()
                            ) {
                                \$attachedCriterion->setIgnoreCase(true);
                            }
                        }

                        \$sb = '';
                        \$criterion->appendPsTo(\$sb, \$params);
                    }

                    \$having = \$this->getHaving();
                    if (\$having !== null) {
                        \$sb = '';
                        \$having->appendPsTo(\$sb, \$params);
                    }

                    return \$params;
                }
        ";
    }
}
