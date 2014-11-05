<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Tests\Bookstore\BookstoreQuery;

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

        $body = <<<EOF
return new \\$proxyClass(\$this);
EOF;

        $this->addMethod('createProxy')
            ->setType('\\' . $proxyClass)
            ->setDescription("Create a new proxy instance of $objectClass.")
            ->setBody($body);
    }
}