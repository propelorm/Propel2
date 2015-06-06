<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getPropWriter method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPropWriterMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $className = $this->getObjectClassName(true);

        $body = "
if (!\$this->propWriter) {
\$this->propWriter = \$this->getClassPropWriter('$className');
}
return \$this->propWriter;
        ";

        $this->addMethod('getPropWriter')
            ->setBody($body);
    }
}