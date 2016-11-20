<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the createObject method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CreateObjectMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();

        $body = <<<EOF
throw \InvalidArgumentException('Not Implemented. Will be removed.');
EOF;

        $this->addMethod('createObject')
            ->setBody($body)
            ->setType($entityClassName)
            ->setDescription('Create a new instance of $entityClassName.');
    }
}