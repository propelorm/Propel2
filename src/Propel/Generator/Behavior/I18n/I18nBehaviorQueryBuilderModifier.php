<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\I18n;

/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the query builder.
 *
 * @author François Zaninotto
 */
class I18nBehaviorQueryBuilderModifier
{
    /**
     * @var \Propel\Generator\Behavior\I18n\I18nBehavior
     */
    protected $behavior;

    /**
     * @var \Propel\Generator\Model\Table
     */
    protected $table;

    /**
     * @var \Propel\Generator\Builder\Om\QueryBuilder
     */
    protected $builder;

    /**
     * @param \Propel\Generator\Behavior\I18n\I18nBehavior $behavior
     */
    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @param \Propel\Generator\Builder\Om\QueryBuilder $builder
     *
     * @return string
     */
    public function queryMethods($builder)
    {
        $this->builder = $builder;
        $script = '';
        $script .= $this->addJoinI18n();
        $script .= $this->addJoinWithI18n();
        $script .= $this->addUseI18nQuery();

        return $script;
    }

    /**
     * @return string
     */
    protected function addJoinI18n()
    {
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('queryJoinI18n', [
            'queryClass' => $this->builder->getQueryClassName(),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
            'localeColumn' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @return string
     */
    protected function addJoinWithI18n()
    {
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('queryJoinWithI18n', [
            'queryClass' => $this->builder->getQueryClassName(),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
        ]);
    }

    /**
     * @return string
     */
    protected function addUseI18nQuery()
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('queryUseI18nQuery', [
            'queryClass' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nTable)),
            'namespacedQueryClass' => $this->builder->getNewStubQueryBuilder($i18nTable)->getFullyQualifiedClassName(),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
            'localeColumn' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }
}
