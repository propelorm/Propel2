<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;

/**
 * Adds a the getPropelConfiguration method for ActiveRecord interface.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPropelConfigurationMethod extends BuildComponent
{
    public function process()
    {
        $body = '
return \Propel\Runtime\Configuration::getCurrentConfiguration();';

        $this->addMethod('getPropelConfiguration')
            ->setType('\Propel\Runtime\Configuration')
            ->setBody($body);
    }

}