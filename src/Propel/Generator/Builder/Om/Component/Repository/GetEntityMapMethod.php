<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the getEntityMap method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetEntityMapMethod extends BuildComponent
{
    use NamingTrait;
    use RepositoryTrait;

    public function process()
    {
        $entityMapClassName = $this->getEntityMapClassName();

        $body = "return parent::getEntityMap();";

        $this->addMethod('getEntityMap')
            ->setType($entityMapClassName)
            ->setBody($body);
    }
}