<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

/**
 * Adds the isNew method for ActiveRecord interface.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IsNewMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
return \$this->getPropelConfiguration()->getSession()->isNew(\$this);";

        $this->addMethod('isNew')
            ->setDescription('Returns true if this is a new (not yet saved/committed) instance')
            ->setType('boolean')
            ->setBody($body);
    }
}