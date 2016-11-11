<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getPropUnsetter method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPropUnsetterMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $className = $this->getObjectClassName(true);

        $body = "
return \$this->getClassPropUnsetter('$className');
        ";

        $this->addMethod('getPropUnsetter')
            ->setBody($body);
    }
}