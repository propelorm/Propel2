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
        $this->addProperty('propelConfiguration');

        $body = '
if (!$this->propelConfiguration){
    $this->propelConfiguration = \Propel\Runtime\Configuration::getCurrentConfiguration();
}

return $this->propelConfiguration;';

        $this->addMethod('getPropelConfiguration')
            ->setType('\Propel\Runtime\Configuration')
            ->setBody($body);
    }

}