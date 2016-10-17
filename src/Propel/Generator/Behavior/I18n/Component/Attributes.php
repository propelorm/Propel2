<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

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