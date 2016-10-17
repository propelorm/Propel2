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
        /** @var I18nBehavior $behavior */
        $behavior = $this->getBehavior();
        $i18nEntity = $behavior->getI18nEntity();
        $relation = $behavior->getI18nRelation();

        $body = "
if (!\$this->isNew()) {
    \$i18nRepository = {$this->getRepositoryGetter($i18nEntity)};
    \$i18nRepository->createQuery()
            ->filterByPrimaryKey(array(\$this->getPrimaryKey(), \$locale))
            ->delete(\$con);
    }
    if (isset(\$this->currentTranslations[\$locale])) {
        unset(\$this->currentTranslations[\$locale]);
    }
    foreach (\$this->{$this->getRefRelationCollVarName($relation)} as \$key => \$translation) {
        if (\$translation->get{$behavior->getLocaleField()->getMethodName()}() == \$locale) {
            \$this->{$this->getRefRelationCollVarName($relation)}->removeObject(\$this->{$this->getRefRelationCollVarName($relation)}[\$key]);
            break;
        }
    }

    return \$this;
        ";

        $this->addMethod('removeTranslation')
            ->setDescription('Remove the translation for a given locale')
            ->addSimpleDescParameter('locale', 'string', "Locale to use for the translation, e.g. 'fr_FR'", $behavior->getDefaultLocale())
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'An optional connection object', null)
            ->setType('$this|' . $this->getClassNameFromEntity($behavior->getEntity()))
            ->setDescription('The current object (for fluent API support)')
            ->setBody($body);
    }
}