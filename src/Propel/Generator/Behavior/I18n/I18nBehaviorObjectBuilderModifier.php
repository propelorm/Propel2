<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n;

use Propel\Generator\Model\Field;
use Propel\Generator\Model\PropelTypes;

/**
 * Allows translation of text fields through transparent one-to-many relationship.
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
        $this->table = $behavior->getEntity();
    }

    public function postDelete($builder)
    {
        $this->builder = $builder;
        if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->get()['generator']['objectModel']['emulateForeignKeyConstraints']) {
            $i18nEntity = $this->behavior->getI18nEntity();

            return $this->behavior->renderTemplate('objectPostDelete', array(
                'i18nQueryName'    => $builder->getClassNameFromBuilder($builder->getNewStubQueryBuilder($i18nEntity)),
                'objectClassName' => $builder->getNewStubObjectBuilder($this->behavior->getEntity())->getUnqualifiedClassName(),
            ));
        }
    }

    public function objectAttributes($builder)
    {
        return $this->behavior->renderTemplate('objectAttributes', array(
            'defaultLocale'   => $this->behavior->getDefaultLocale(),
            'objectClassName' => $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($this->behavior->getI18nEntity())),
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

        foreach ($this->behavior->getI18nFields() as $field) {
            $script .= $this->addTranslatedFieldGetter($field);
            $script .= $this->addTranslatedFieldSetter($field);
        }

        return $script;
    }

    protected function addSetLocale()
    {
        return $this->behavior->renderTemplate('objectSetLocale', array(
            'objectClassName'   => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder($this->table)),
            'defaultLocale'     => $this->behavior->getDefaultLocale(),
            'localeFieldName'  => $this->behavior->getLocaleField()->getName(),
        ));
    }

    protected function addGetLocale()
    {
        return $this->behavior->renderTemplate('objectGetLocale', array(
            'localeFieldName'  => $this->behavior->getLocaleField()->getName(),
        ));
    }

    protected function addSetLocaleAlias($alias)
    {
        return $this->behavior->renderTemplate('objectSetLocaleAlias', array(
            'objectClassName'  => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder($this->table)),
            'defaultLocale'    => $this->behavior->getDefaultLocale(),
            'alias'            => ucfirst($alias),
            'localeFieldName'  => $this->behavior->getLocaleField()->getName(),
        ));
    }

    protected function addGetLocaleAlias($alias)
    {
        return $this->behavior->renderTemplate('objectGetLocaleAlias', array(
            'alias' => ucfirst($alias),
            'localeFieldName'  => $this->behavior->getLocaleField()->getName(),
        ));
    }

    protected function addGetTranslation()
    {
        $plural = false;
        $i18nEntity = $this->behavior->getI18nEntity();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('objectGetTranslation', array(
            'i18nEntityPhpName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($i18nEntity)),
            'defaultLocale'    => $this->behavior->getDefaultLocale(),
            'i18nListVariable' => $this->builder->getRefFKCollVarName($fk),
            'localeFieldName' => $this->behavior->getLocaleField()->getName(),
            'i18nQueryName'    => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nEntity)),
            'i18nSetterMethod' => $this->builder->getRefFKPhpNameAffix($fk, $plural),
        ));
    }

    protected function addRemoveTranslation()
    {
        $i18nEntity = $this->behavior->getI18nEntity();
        $fk = $this->behavior->getI18nForeignKey();

        return $this->behavior->renderTemplate('objectRemoveTranslation', array(
            'objectClassName' => $this->builder->getClassNameFromBuilder($this->builder->getStubObjectBuilder($this->table)),
            'defaultLocale'    => $this->behavior->getDefaultLocale(),
            'i18nQueryName'    => $this->builder->getClassNameFromBuilder($this->builder->getNewStubQueryBuilder($i18nEntity)),
            'i18nCollection'   => $this->builder->getRefFKCollVarName($fk),
            'localeFieldName' => $this->behavior->getLocaleField()->getName(),
        ));
    }

    protected function addGetCurrentTranslation()
    {
        return $this->behavior->renderTemplate('objectGetCurrentTranslation', array(
            'i18nEntityPhpName' => $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getI18nEntity())),
            'localeFieldName'  => $this->behavior->getLocaleField()->getName(),
        ));
    }

    // FIXME: the connection used by getCurrentTranslation in the generated code
    // cannot be specified by the user
    protected function addTranslatedFieldGetter(Field $field)
    {
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nEntity());
        $comment = '';
        $functionStatement = '';
        if ($this->isDateType($field->getType())) {
            $objectBuilder->addTemporalAccessorComment($comment, $field);
            $objectBuilder->addTemporalAccessorOpen($functionStatement, $field);
        } else {
            $objectBuilder->addDefaultAccessorComment($comment, $field);
            $objectBuilder->addDefaultAccessorOpen($functionStatement, $field);
        }
        $comment = preg_replace('/^\t/m', '', $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        return $this->behavior->renderTemplate('objectTranslatedFieldGetter', array(
            'comment'           => $comment,
            'functionStatement' => $functionStatement,
            'fieldPhpName'     => $field->getName(),
            'params'            => implode(', ', $params[0]),
        ));
    }

    // FIXME: the connection used by getCurrentTranslation in the generated code
    // cannot be specified by the user
    protected function addTranslatedFieldSetter(Field $field)
    {
        $i18nEntityPhpName = $this->builder->getClassNameFromBuilder($this->builder->getNewStubObjectBuilder($this->behavior->getI18nEntity()));
        $tablePhpName = $this->builder->getObjectClassName();
        $objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nEntity());
        $comment = '';
        $functionStatement = '';
        if ($this->isDateType($field->getType())) {
            $objectBuilder->addTemporalMutatorComment($comment, $field);
            $objectBuilder->addMutatorOpenOpen($functionStatement, $field);
        } else {
            $objectBuilder->addMutatorComment($comment, $field);
            $objectBuilder->addMutatorOpenOpen($functionStatement, $field);
        }
        $comment = preg_replace('/^\t/m', '', $comment);
        $comment = str_replace('@return     $this|' . $i18nEntityPhpName, '@return     $this|' . $tablePhpName, $comment);
        $functionStatement = preg_replace('/^\t/m', '', $functionStatement);
        preg_match_all('/\$[a-z]+/i', $functionStatement, $params);

        return $this->behavior->renderTemplate('objectTranslatedFieldSetter', array(
            'comment'           => $comment,
            'functionStatement' => $functionStatement,
            'fieldPhpName'     => $field->getName(),
            'params'            => implode(', ', $params[0]),
        ));
    }

    public function objectFilter(&$script, $builder)
    {
        $i18nEntity = $this->behavior->getI18nEntity();
        $i18nEntityPhpName = $this->builder->getNewStubObjectBuilder($i18nEntity)->getUnprefixedClassName();
        $localeFieldName = $this->behavior->getLocaleField()->getName();
        $pattern = '/public function add' . $i18nEntityPhpName . '.*[\r\n]\s*\{/';
        $addition = "
        if (\$l && \$locale = \$l->get$localeFieldName()) {
            \$this->set{$localeFieldName}(\$locale);
            \$this->currentTranslations[\$locale] = \$l;
        }";
        $replacement = "\$0$addition";
        $script = preg_replace($pattern, $replacement, $script);
    }

    protected function isDateType($fieldType)
    {
        return in_array($fieldType, array(
            PropelTypes::DATE,
            PropelTypes::TIME,
            PropelTypes::TIMESTAMP
        ));
    }
}
