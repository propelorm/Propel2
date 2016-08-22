<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;

/**
 * Adds __construct method
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ConstructorMethod extends BuildComponent
{
    use ComponentHelperTrait;

    public function process()
    {
        $this->addMethod('__construct')
            ->setBody($this->getDefinition()->getConstructorBodyExtras());
    }
}
