<?php

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the createProxy method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CreateProxyMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $proxyClass = $this->getProxyClassName(true);
        $objectClass = $this->getObjectClassName();

        $propertiesToUnset = [];
        $this->getDefinition()->declareUse($this->getRepositoryClassName(true));

        //reset lazy loaded properties
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isPrimaryKey()) continue;
            if (!$field->isLazyLoad()) continue;

            $propertiesToUnset[] = '$unset($object, ' . json_encode($field->getName()) . ');';
        }

        $unsetLazyLoadProperties = implode(PHP_EOL, $propertiesToUnset);

        $body = <<<EOF
\$reflection = new \ReflectionClass('$proxyClass');
\$unset = \$this->getPropUnsetter();
\$object = \$reflection->newInstanceWithoutConstructor();
\$object->_repository = \$this;
$unsetLazyLoadProperties
return \$object;
EOF;

        $this->addMethod('createProxy')
            ->setType('\\' . $proxyClass)
            ->setDescription("Create a new proxy instance of $objectClass.")
            ->setBody($body);
    }
}