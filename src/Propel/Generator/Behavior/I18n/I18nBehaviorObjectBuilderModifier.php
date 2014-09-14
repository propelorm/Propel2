<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n;

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
    protected $behavior;
    protected $table;
    protected $builder;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    public function postDelete($builder)
    {
        $this->builder = $builder;
        if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->get()['generator']['objectModel']['emulateForeignKeyConstraints']) {
            $i18nTable = $this->behavior->getI18nTable();

            return $this->behavior->renderTemplate('objectPostDelete', array(
                'i18nQueryName'    => $builder->getClassNameFromBuilder($builder->getNewStubQueryBuilder($i18nTable)),
                'objectClassName' => $builder->getNewStubObjectBuilder($this->behavior->getTable())->getUnqualifiedClassName(),
            ));
        }
    }

    public function objectAttributes($builder)
    {
        return $this->behavior->renderTemplate('objectAttributes', array(
            'defaultLocale'   => $this->behavior->getDefaultLocale(),
            'objectClassName' => $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($this->behavior->getI18nTable())),
        ));
    }

    public function objectClearReferences($builder)
    {
        return $this->behavior->renderTemplate('objectClearReferences', array(
            'defaultLocale'   => $this->behavior->getDefaultLocale(),
        ));
    }

    public function objectMethods($builder)
    {
        $this->builder = $builder;

        $script = '';
        $script .= $this->addSetLocale();
        $script .= $this->addGetLocale();

        if ($alias = $this->behavior->getParameter('locale_alias')) {
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

    protected function addSetLocale()
    {
        return $this->behavior->renderTemplate('objectSetLocale', array(
            'objectClassName'   => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder($this->table)),
            'defaultLocale'     => $this->behavior->getDefaultLocale(),
            'localeColumnName'  => $this->behavior->getLocaleColumn()->getPhpName(),
        ));
    }

    protected function addGetLocale()
    {
        return $this->behavior->renderTemplate('objectGetLocale', array(
            'localeColumnName'  => $this->behavior->getLocaleColumn()->getPhpName(),
        ));
    }

    protected function addSetLocaleAlias($alias)
    {
        return $this->behavior->renderTemplate('objectSetLocaleAlias', array(
            'objectClassName'  => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder($this->table)),
            'defaultLocale'    => $this->behavior->getDefaultLocale(),
            'alias'            => ucfirst($alias),
            'localeColumnName'  => $this->behavior->getLocaleColumn()->getPhpName(),
        ));
    }

    protected function addGetLocaleAlias($alias)
    {
        return $this->behavior->renderTemplate('objectGetLocaleAlias', array(
            'alias' => ucfirst($alias),
            'localeColumnName'  => $this->behavior->getLocaleColumn()->getPhpName(),
        ));
    }

    protected function addGetTranslation()
    {
        $plural = false;
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('objectGetTranslation', array(
            'i18nTablePhpName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($i18nTable)),
            'defaultLocale'    => $this->behavior->getDefaultLocale(),
            'i18nListVariable' => $this->builder->getRefFKCollVarName($fk),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
            'i18nQueryName'    => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nTable)),
            'i18nSetterMethod' => $this->builder->getRefFKPhpNameAffix($fk, $plural),
        ));
    }

    protected function addRemoveTranslation()
    {
        $i18nTable = $this->behavior->getI18nTable();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('objectRemoveTranslation', array(
            'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder($this->table)),
            'defaultLocale'    => $this->behavior->getDefaultLocale(),
            'i18nQueryName'    => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nTable)),
            'i18nCollection'   => $this->builder->getRefFKCollVarName($fk),
            'localeColumnName' => $this->behavior->getLocaleColumn()->getPhpName(),
        ));
    }

    protected function addGetCurrentTranslation()
    {
        return $this->behavior->renderTemplate('objectGetCurrentTranslation', array(
            'i18nTablePhpName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable())),
            'localeColumnName'  => $this->behavior->getLocaleColumn()->getPhpName(),
        ));
    }

    // FIXME: the connection used by getCurrentTranslation in the generated code
    // cannot be specified by the user
    protected function addTranslatedColumnGetter(Column $column)
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

        return $this->behavior->renderTemplate('objectTranslatedColumnGetter', array(
            'comment'           => $comment,
            'functionStatement' => $functionStatement,
            'columnPhpName'     => $column->getPhpName(),
            'params'            => implode(', ', $params[0]),
        ));
    }

    // FIXME: the connection used by getCurrentTranslation in the generated code
    // cannot be specified by the user
    protected function addTranslatedColumnSetter(Column $column)
    {
        $i18nTablePhpName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable()));
        $tablePhpName = $this->builder->getObjectClassName();
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
        $comment = '';
        $functionStatement = '';
        if ($this->isDateType($column->getType())) {
            $objectBuilder->addTemporalMutatorComment($comment, $column);
            $objectBuilder->addMutatorOpenOpen($functionStatement, $column);
        } else {
            $objectBuilder->addMutatorComment($comment, $column);
            $objectBuilder->addMutatorOpenOpen($functionStatement, $column);
        }
        $comment = preg_replace('/^\t/m', '', $comment);
        $comment = str_replace('@return     $this|' . $i18nTablePhpName, '@return     $this|' . $tablePhpName, $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        return $this->behavior->renderTemplate('objectTranslatedColumnSetter', array(
            'comment'           => $comment,
            'functionStatement' => $functionStatement,
            'columnPhpName'     => $column->getPhpName(),
            'params'            => implode(', ', $params[0]),
        ));
    }

    public function objectFilter(&$script, $builder)
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

    protected function isDateType($columnType)
    {
        return in_array($columnType, array(
            PropelTypes::DATE,
            PropelTypes::TIME,
            PropelTypes::TIMESTAMP
        ));
    }
}
