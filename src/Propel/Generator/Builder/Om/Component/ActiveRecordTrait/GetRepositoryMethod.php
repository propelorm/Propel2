<?php

namespace Propel\Generator\Builder\Om\Component\ActiveRecordTrait;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetRepositoryMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {

        $body = "
{$this->getRepositoryAssignment()}
return \$repository;";

        $this->addMethod('getRepository')
            ->setDescription('Returns the repository for this entity')
            ->setType($this->getRepositoryClassName())
            ->setBody($body);
    }

}