<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Adds getPersisterClass method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPersisterClassMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = "
return parent::getPersisterClass();
";

        $this->addMethod('getPersisterClass')
            ->setBody($body);
    }
}