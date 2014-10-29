<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getRepositoryClass method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetRepositoryClassMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
        return '{$this->getRepositoryClassName(true)}';
        ";

        $this->addMethod('getRepositoryClass')
            ->setBody($body)
            ->setType('string')
            ->setTypeDescription('full call name of the repository');
    }
}