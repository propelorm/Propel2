<?php

namespace Propel\Generator\Builder\Om\Component\Proxy;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the __construct method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class LazyLoadedStateProperties extends BuildComponent
{
    public function process()
    {
        foreach ($this->getEntity()->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                continue;
            }

            $this->addProperty('_' . $field->getName().'_loaded', false)
                ->setType('boolean');
        }
    }
}