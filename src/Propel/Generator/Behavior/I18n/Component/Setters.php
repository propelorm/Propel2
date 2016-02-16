<?php

namespace Propel\Generator\Behavior\I18n\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\Object\PropertySetterMethods;
use Propel\Generator\Model\NamingTool;

/**
 * Add setter methods to the entity.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Setters extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $behavior = $this->getBehavior();

        $this->addSetLocale($behavior);
        if ($alias = $behavior->getParameter('locale_alias')) {
            $this->addSetLocaleAlias($alias, $behavior);
        }
        $this->addSetTranslation($behavior);
        $this->addTranslatedColumnSetter($behavior);
    }

    private function addSetLocale($behavior)
    {
        $this->addMethod('set' . NamingTool::toUpperCamelCase($behavior->getLocaleField()->getName()))
            ->setDescription('Sets the locale for translations')
            ->addParameter(PhpParameter::create('locale')
                ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
                ->setDefaultValue($behavior->getDefaultLocale())
            )
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setTypeDescription('The current object (for fluent API support)')
            ->setBody("
\$this->currentLocale = \$locale;

return \$this;
"
            );
    }

    private function addSetLocaleAlias($alias, $behavior)
    {
        $this->addMethod('set' . NamingTool::toUpperCamelCase($alias))
            ->setDescription("
Sets the locale for translations.
Alias for setLocale(), for BC purpose.
"
            )
            ->addParameter(PhpParameter::create('locale')
                ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
                ->setDefaultValue($behavior->getDefaultLocale())
            )
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setTypeDescription('The current object (for fluent API support)')
            ->setBody("
return \$this->set{$behavior->getLocaleField()->getName()}(\$locale);
"
            );
    }

    private function addSetTranslation($behavior)
    {
        $body = "
\$translation->set{$behavior->getLocaleField()->getName()}(\$locale);
\$this->add{$this->getClassNameFromEntity($behavior->getI18nEntity())}(\$translation);
\$this->currentTranslations[\$locale] = \$translation;

return \$this;
        ";

        $this->addMethod('setTranslation')
            ->setDescription('Sets the translation for a given locale')
            ->addParameter(PhpParameter::create('translation')
                ->setType($this->getClassNameFromEntity($behavior->getI18nEntity()))
                ->setDescription('The translation object')
            )
            ->addParameter(PhpParameter::create('locale')
                ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
                ->setDefaultValue($behavior->getDefaultLocale())
            )
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setTypeDescription('The current object (for fluent API support)')
            ->setBody($body);
    }

    private function addTranslatedColumnSetter($behavior)
    {
        foreach ($behavior->getI18nFieldNamesFromConfig() as $fieldName) {
            $field = $behavior->getI18nEntity()->getField($fieldName);
            $methodName = 'set' . ucfirst($fieldName);
            $body = "
\$this->getCurrentTranslation()->{$methodName}(\${$fieldName});

return \$this;
 ";

            $method = $this->addMethod($methodName, $field->getAccessorVisibility())
                ->setType($this->getObjectClassName() . '|$this')
                ->setDescription("Sets the value of $fieldName.")
                ->setBody($body);

            if ($field->isNotNull()) {
                $method->addSimpleParameter($fieldName, $field->getPhpType());
            } else {
                $method->addSimpleParameter($fieldName, $field->getPhpType(), null);
            }
        }
    }
}
