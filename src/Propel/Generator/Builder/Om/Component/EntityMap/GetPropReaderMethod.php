<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getPropReader method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPropReaderMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $className = $this->getObjectClassName(true);

        $body = "
        if (!\$this->propReader) {
            \$this->propReader = \$this->getClassPropReader('$className');
        }
        return \$this->propReader;
        ";

        $this->addMethod('getPropReader')
            ->setBody($body);
    }
}