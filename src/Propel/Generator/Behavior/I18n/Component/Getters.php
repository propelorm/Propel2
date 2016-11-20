<?php

namespace Propel\Generator\Behavior\I18n\Component;

use gossi\codegen\model\PhpParameter;
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
        $behavior = $this->getBehavior();

        $this->addGetLocale($behavior);
        if ($alias = $behavior->getParameter('locale_alias')) {
            $this->addGetLocaleAlias($alias, $behavior);
        }
        $this->addGetTranslation($behavior);
        $this->addGetCurrentTranslation($behavior);
        $this->addTranslatedColumnGetter($behavior);
    }

    private function addGetLocale($behavior)
    {
        $this->addMethod('get' . NamingTool::toUpperCamelCase($behavior->getLocaleField()->getName()))
            ->setDescription('Gets the locale for translations')
            ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
            ->setBody("
return \$this->currentLocale;
"
            );
    }

    private function addGetLocaleAlias($alias, $behavior)
    {
        $aliasMethod = 'get' . NamingTool::toUpperCamelCase($alias);
        $method = ucfirst($behavior->getLocaleField()->getName());

        $this->addMethod($aliasMethod)
            ->setDescription("
Gets the locale for translations.
Alias for getLocale(), for BC purpose.
                ")
            ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
            ->setBody("
return \$this->get$method();
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
            if (\$translation->get{$behavior->getLocaleField()->getName()}() == \$locale) {
                \$this->currentTranslations[\$locale] = \$translation;

                return \$translation;
            }
        }
    }
    \$repository = {$this->getRepositoryGetter($behavior->getEntity())};
    if (\$repository->getConfiguration()->getSession()->isNew(\$this)) {
        \$translation = new {$this->getClassNameFromEntity($behavior->getI18nEntity())}();
        \$translation->set{$behavior->getLocaleField()->getName()}(\$locale);
    } else {
        \$i18nRepository = {$this->getRepositoryGetter($behavior->getI18nEntity())};
        \$translation = \$i18nRepository->createQuery()
            ->filterByPrimaryKey(array(\$repository->getEntityMap()->getPrimaryKey(\$this), \$locale))
            ->findOneOrCreate(\$con);
        \$this->currentTranslations[\$locale] = \$translation;
    }
    \$this->add{$this->getRefRelationPhpName($relation)}(\$translation);
}

return \$this->currentTranslations[\$locale];
";
        $definition = $this->getDefinition();
        $definition->addUseStatement('Propel\Runtime\Connection\ConnectionInterface');

        $this->addMethod('getTranslation')
            ->setDescription('Returns the current translation for a given locale')
            ->addParameter(PhpParameter::create('locale')
                ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
                ->setDefaultValue($behavior->getDefaultLocale())
            )
            ->addParameter(PhpParameter::create('con')
                ->setType('ConnectionInterface', 'an optional connection object')
                ->setDefaultValue(null)
            )
            ->setType($this->getClassNameFromEntity($behavior->getI18nEntity()))
            ->setBody($body);
    }

    private function addGetCurrentTranslation($behavior)
    {
        $this->addMethod('getCurrentTranslation')
            ->setDescription('Returns the current translation')
            ->addParameter(PhpParameter::create('con')
                ->setType('ConnectionInterface', 'An optional connection object')
                ->setDefaultValue(null)
            )
            ->setType($this->getClassNameFromEntity($behavior->getI18nEntity()))
            ->setBody("return \$this->getTranslation(\$this->get{$behavior->getLocaleField()->getName()}(), \$con);");
    }

    private function addTranslatedColumnGetter($behavior)
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