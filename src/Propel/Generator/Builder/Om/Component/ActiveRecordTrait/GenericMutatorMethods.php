<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds all generic mutator methods setByName()`, `setByPosition()`, and `fromArray`.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GenericMutatorMethods extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
    }

}