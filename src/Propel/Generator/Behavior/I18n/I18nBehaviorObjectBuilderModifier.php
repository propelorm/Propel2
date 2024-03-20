<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\I18n;

use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the object builder.
 *
 * @author François Zaninotto
 */
class I18nBehaviorObjectBuilderModifier
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
     * @var \Propel\Generator\Builder\Om\ObjectBuilder
     */
    protected $builder;

    /**
     * @param \Propel\Generator\Behavior\I18n\I18nBehavior $behavior
     */
    public function __construct(I18nBehavior $behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string|null
     */
    public function postDelete(ObjectBuilder $builder): ?string
    {
        $this->builder = $builder;
        if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->getBuildProperty('generator.objectModel.emulateForeignKeyConstraints')) {
            $i18nTable = $this->behavior->getI18nTable();

            return $this->behavior->renderTemplate('objectPostDelete', [
                'i18nQueryName' => $builder->getClassNameFromBuilder($builder->getNewStubQueryBuilder($i18nTable)),
                'objectClassName' => $builder->getNewStubObjectBuilder($this->behavior->getTable())->getUnqualifiedClassName(),
            ]);
        }

        return null;
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
    public function objectAttributes(ObjectBuilder $builder): string
    {
        return $this->behavior->renderTemplate('objectAttributes', [
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'objectClassName' => $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($this->behavior->getI18nTable())),
        ]);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
    public function objectClearReferences(ObjectBuilder $builder): string
    {
        return $this->behavior->renderTemplate('objectClearReferences', [
            'defaultLocale' => $this->behavior->getDefaultLocale(),
        ]);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return string
     */
    public function objectMethods(ObjectBuilder $builder): string
    {
        $this->builder = $builder;

        $script = $this->addSetLocale();
        $script .= $this->addGetLocale();

        $alias = $this->behavior->getParameter('locale_alias');
        if ($alias) {
            $script .= $this->addGetLocaleAlias($alias);
            $script .= $this->addSetLocaleAlias($alias);
        }

        $script .= $this->addGetTranslation();
        $script .= $this->addRemoveTranslation();
        $script .= $this->addGetCurrentTranslation();

        foreach ($this->behavior->getI18nColumns() as $column) {
            $script .= $this->addTranslatedColumnGetter($column);
            $script .= $this->addTranslatedColumnSetter($column);
        }

        return $script;
    }

    /**
     * @return string
     */
    protected function addSetLocale(): string
    {
        return $this->behavior->renderTemplate('objectSetLocale', [
            'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @return string
     */
    protected function addGetLocale(): string
    {
        return $this->behavior->renderTemplate('objectGetLocale', [
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    protected function addSetLocaleAlias(string $alias): string
    {
        return $this->behavior->renderTemplate('objectSetLocaleAlias', [
            'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'alias' => ucfirst($alias),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    protected function addGetLocaleAlias(string $alias): string
    {
        return $this->behavior->renderTemplate('objectGetLocaleAlias', [
            'alias' => ucfirst($alias),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @return string
     */
    protected function addGetTranslation(): string
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('objectGetTranslation', [
            'i18nTablePhpName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($i18nTable)),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'i18nListVariable' => $this->builder->getRefFKCollVarName($fk),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            'i18nQueryName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nTable)),
            'i18nSetterMethod' => $this->builder->getRefFKPhpNameAffix($fk),
        ]);
    }

    /**
     * @return string
     */
    protected function addRemoveTranslation(): string
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('objectRemoveTranslation', [
            'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder()),
            'defaultLocale' => $this->behavior->getDefaultLocale(),
            'i18nQueryName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nTable)),
            'i18nCollection' => $this->builder->getRefFKCollVarName($fk),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @return string
     */
    protected function addGetCurrentTranslation(): string
    {
        return $this->behavior->renderTemplate('objectGetCurrentTranslation', [
            'i18nTablePhpName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable())),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ]);
    }

    /**
     * @todo The connection used by getCurrentTranslation in the generated code cannot be specified by the user
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    protected function addTranslatedColumnGetter(Column $column): string
    {
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
        $comment = '';
        $functionStatement = '';
        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalAccessorComment($comment, $column);
            $objectBuilder->addTemporalAccessorOpen($functionStatement, $column);
        } else {
            $objectBuilder->addDefaultAccessorComment($comment, $column);
            $objectBuilder->addDefaultAccessorOpen($functionStatement, $column);
        }
        $comment = preg_replace('/^\t/m', '', $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        return $this->behavior->renderTemplate('objectTranslatedColumnGetter', [
            'comment' => $comment,
            'functionStatement' => $functionStatement,
            'columnPhpName' => $column->getPhpName(),
            'params' => implode(', ', $params[0]),
        ]);
    }

    /**
     * @todo The connection used by getCurrentTranslation in the generated code cannot be specified by the user
     *
     * @param \Propel\Generator\Model\Column $column
     *
     * @return string
     */
    protected function addTranslatedColumnSetter(Column $column): string
    {
        $i18nTablePhpName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable()));
        $tablePhpName = $this->builder->getObjectClassName();
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
        $comment = '';
        $functionStatement = '';

        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalMutatorComment($comment, $column);
        } else {
            $objectBuilder->addMutatorComment($comment, $column);
        }

        $objectBuilder->addMutatorOpenOpen($functionStatement, $column);
        $comment = preg_replace('/^\t/m', '', $comment);
        $comment = str_replace('@return $this|' . $i18nTablePhpName, '@return $this|' . $tablePhpName, $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        return $this->behavior->renderTemplate('objectTranslatedColumnSetter', [
            'comment' => $comment,
            'functionStatement' => $functionStatement,
            'columnPhpName' => $column->getPhpName(),
            'params' => implode(', ', $params[0]),
        ]);
    }

    /**
     * @param string $script
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $builder
     *
     * @return void
     */
    public function objectFilter(string &$script, ObjectBuilder $builder): void
    {
        $i18nTable = $this->behavior->getI18nTable();
        $i18nTablePhpName = $this->builder->getNewStubObjectBuilder($i18nTable)->getUnprefixedClassName();
        $localeColumnName = $this->behavior->getLocaleColumn()->getPhpName();
        $pattern = '/public function add' . $i18nTablePhpName . '.*[\r\n]\s*\{/';
        $addition = "
        if (\$l && \$locale = \$l->get$localeColumnName()) {
            \$this->set{$localeColumnName}(\$locale);
            \$this->currentTranslations[\$locale] = \$l;
        }";
        $replacement = "\$0$addition";
        $script = preg_replace($pattern, $replacement, $script);
    }

    /**
     * @param string $columnType
     *
     * @return bool
     */
    protected function isDateType(string $columnType): bool
    {
        return in_array($columnType, [
            PropelTypes::DATE,
            PropelTypes::DATETIME,
            PropelTypes::TIME,
            PropelTypes::TIMESTAMP,
        ], true);
    }
}
