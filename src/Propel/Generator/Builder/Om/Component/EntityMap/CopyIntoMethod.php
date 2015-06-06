<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds copyInto method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CopyIntoMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
\$entityReader = \$this->getPropReader();
\$targetWriter = \$this->getConfiguration()->getEntityMapForEntity(\$target)->getPropWriter();
";

        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isImplementationDetail()) {
                continue;
            }
            $body .= "\$targetWriter(\$target, '{$field->getName()}', \$entityReader(\$entity, '{$field->getName()}'));\n";
        }

        foreach ($this->getEntity()->getRelations() as $relation) {
            $body .= "\$targetWriter(\$target, '{$relation->getField()}', \$entityReader(\$entity, '{$relation->getField()}'));\n";
        }

        $this->addMethod('copyInto')
            ->addSimpleParameter('entity')
            ->addSimpleParameter('target')
            ->setBody($body);
    }
}