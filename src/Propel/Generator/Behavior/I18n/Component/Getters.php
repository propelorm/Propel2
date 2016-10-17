<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\I18n\Component;

use Propel\Generator\Behavior\I18n\I18nBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\NamingTool;

/**
 * Add getter methods to the entity.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Getters extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        /** @var I18nBehavior $behavior */
        $behavior = $this->getBehavior();

        $this->addGetLocale($behavior);
        if ($alias = $behavior->getParameter('locale_alias')) {
            $this->addGetLocaleAlias($alias, $behavior);
        }
        $this->addGetTranslation($behavior);
        $this->addGetCurrentTranslation($behavior);
        $this->addTranslatedColumnGetter($behavior);
    }

    private function addGetLocale(I18nBehavior $behavior)
    {
        $this->addMethod('get' . $behavior->getLocaleField()->getMethodName())
            ->setDescription('Gets the locale for translations')
            ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
            ->setBody("
return \$this->currentLocale;
"
            );
    }

    private function addGetLocaleAlias($alias, I18nBehavior $behavior)
    {
        $aliasMethod = 'get' . NamingTool::toUpperCamelCase($alias);

        $this->addMethod($aliasMethod)
            ->setDescription("
Gets the locale for translations.
Alias for getLocale(), for BC purpose.
                ")
            ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
            ->setBody("
return \$this->get{$behavior->getLocaleField()->getMethodName()}();
"
            );
    }

    private function addGetTranslation(I18nBehavior $behavior)
    {
        $relation = $behavior->getI18nRelation();

        $body = "
if (!isset(\$this->currentTranslations[\$locale])) {
    if (null !== \$this->{$this->getRefRelationCollVarName($relation)}) {
        foreach (\$this->{$this->getRefRelationCollVarName($relation)} as \$translation) {
            if (\$translation->get{$behavior->getLocaleField()->getMethodName()}() == \$locale) {
                \$this->currentTranslations[\$locale] = \$translation;

                return \$translation;
            }
        }
    }
    if (\$this->isNew()) {
        \$translation = new {$this->getClassNameFromEntity($behavior->getI18nEntity())}();
        \$translation->set{$behavior->getLocaleField()->getMethodName()}(\$locale);
    } else {
        \$i18nRepository = {$this->getRepositoryGetter($behavior->getI18nEntity())};
        \$translation = \$i18nRepository->createQuery()
            ->filterByPrimaryKey(array(\$this->getPrimaryKey(), \$locale))
            ->findOneOrCreate(\$con);
        if (null === \$translation->getLocale()) {
            \$translation->setLocale(\$locale);
        }
        \$this->currentTranslations[\$locale] = \$translation;
    }
    \$this->add{$this->getRefRelationName($relation)}(\$translation);
}

return \$this->currentTranslations[\$locale];
";
        $definition = $this->getDefinition();
        $definition->addUseStatement('Propel\Runtime\Connection\ConnectionInterface');

        $this->addMethod('getTranslation')
            ->setDescription('Returns the current translation for a given locale')
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the translation, e.g. 'fr_FR'", $behavior->getDefaultLocale())
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'An optional connection object', null)
            ->setType($this->getClassNameFromEntity($behavior->getI18nEntity()))
            ->setBody($body);
    }

    private function addGetCurrentTranslation(I18nBehavior $behavior)
    {
        $this->addMethod('getCurrentTranslation')
            ->setDescription('Returns the current translation')
            ->addSimpleDescParameter('con', 'ConnectionInterface','An optional connection object', null)
            ->setType($this->getClassNameFromEntity($behavior->getI18nEntity()))
            ->setBody("return \$this->getTranslation(\$this->get{$behavior->getLocaleField()->getMethodName()}(), \$con);");
    }

    private function addTranslatedColumnGetter(I18nBehavior $behavior)
    {
        foreach ($behavior->getI18nFieldNamesFromConfig() as $fieldName) {
            $field = $behavior->getI18nEntity()->getField($fieldName);
            $methodName = 'get' . NamingTool::toUpperCamelCase(($fieldName));
            $this->addMethod($methodName, $field->getAccessorVisibility())
                ->setType($field->getPhpType())
                ->setDescription("Returns the value of $fieldName.")
                ->setBody("return \$this->getCurrentTranslation()->{$methodName}();");
        }
    }
}
