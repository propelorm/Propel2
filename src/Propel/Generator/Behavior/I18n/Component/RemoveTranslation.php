<?php

namespace Propel\Generator\Behavior\I18n\Component;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Add getter methods to the entity.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class RemoveTranslation extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $behavior = $this->getBehavior();
        $i18nEntity = $behavior->getI18nEntity();
        $relation = $behavior->getI18nRelation();

        $body = "
\$repository = {$this->getRepositoryGetter($behavior->getEntity())};
if (!\$repository->getConfiguration()->getSession()->isNew(\$this)) {
    \$i18nRepository = {$this->getRepositoryGetter($i18nEntity)};
    \$i18nRepository->createQuery()
            ->filterByPrimaryKey(array(\$repository->getEntityMap()->getPrimaryKey(\$this), \$locale))
            ->delete(\$con);
    }
    if (isset(\$this->currentTranslations[\$locale])) {
        unset(\$this->currentTranslations[\$locale]);
    }
    foreach (\$this->{$this->getRefRelationCollVarName($relation)} as \$key => \$translation) {
        if (\$translation->get{$behavior->getLocaleField()->getName()}() == \$locale) {
            \$this->{$this->getRefRelationCollVarName($relation)}->removeObject(\$this->{$this->getRefRelationCollVarName($relation)}[\$key]);
            break;
        }
    }

    return \$this;
        ";

        $this->addMethod('removeTranslation')
            ->setDescription('Remove the translation for a given locale')
            ->addParameter(PhpParameter::create('locale')
                ->setType('string', "Locale to use for the translation, e.g. 'fr_FR'")
                ->setDefaultValue($behavior->getDefaultLocale()))
            ->addParameter(PhpParameter::create('con')
                ->setType('ConnectionInterface', 'An optional connection object')
                ->setDefaultValue(null))
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setDescription('The current object (for fluent API support)')
            ->setBody($body);
    }
}