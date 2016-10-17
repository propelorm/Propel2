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
use gossi\codegen\model\PhpParameter;
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
        /** @var I18nBehavior $behavior */
        $behavior = $this->getBehavior();

        $this->addSetLocale($behavior);
        if ($alias = $behavior->getParameter('locale_alias')) {
            $this->addSetLocaleAlias($alias, $behavior);
        }
        $this->addSetTranslation($behavior);
        $this->addTranslatedColumnSetter($behavior);
    }

    private function addSetLocale(I18nBehavior $behavior)
    {
        $this->addMethod('set' . $behavior->getLocaleField()->getMethodName())
            ->setDescription('Sets the locale for translations')
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the translation, e.g. 'fr_FR'", $behavior->getDefaultLocale())
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setTypeDescription('The current object (for fluent API support)')
            ->setBody("
\$this->currentLocale = \$locale;

return \$this;
"
            );
    }

    private function addSetLocaleAlias($alias, I18nBehavior $behavior)
    {
        $this->addMethod('set' . NamingTool::toUpperCamelCase($alias))
            ->setDescription("
Sets the locale for translations.
Alias for setLocale(), for BC purpose.
"
            )
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the translation, e.g. 'fr_FR'", $behavior->getDefaultLocale())
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setTypeDescription('The current object (for fluent API support)')
            ->setBody("
return \$this->set{$behavior->getLocaleField()->getMethodName()}(\$locale);
"
            );
    }

    private function addSetTranslation(I18nBehavior $behavior)
    {
        $body = "
\$translation->set{$behavior->getLocaleField()->getMethodName()}(\$locale);
\$this->add{$this->getClassNameFromEntity($behavior->getI18nEntity())}(\$translation);
\$this->currentTranslations[\$locale] = \$translation;

return \$this;
        ";

        $this->addMethod('setTranslation')
            ->setDescription('Sets the translation for a given locale')
            ->addSimpleDescParameter('translation', $this->getClassNameFromEntity($behavior->getI18nEntity()), 'The translation object.')
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the translation, e.g. 'fr_FR'", $behavior->getDefaultLocale())
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setTypeDescription('The current object (for fluent API support)')
            ->setBody($body);
    }

    private function addTranslatedColumnSetter(I18nBehavior $behavior)
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
