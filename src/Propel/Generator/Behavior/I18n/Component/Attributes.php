<?php

namespace Propel\Generator\Behavior\I18n\Component;

use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 * Add I18n attributes to the entity.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class Attributes extends BuildComponent
{
    public function process()
    {
        $behavior = $this->getBehavior();

        $this->addProperty('currentLocale', $behavior->getDefaultLocale())
            ->setDescription('Current locale');

        $this->addProperty('currentTranslations', null)
            ->setDescription('Current translation objects')
            ->setTypeDescription('array[' . $behavior->getI18nEntity()->getFullClassName() . ']');
    }
}