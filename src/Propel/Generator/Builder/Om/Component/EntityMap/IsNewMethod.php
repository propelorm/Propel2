<?php

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Tests\Bookstore\BookstoreQuery;

/**
 * Adds the isNew method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IsNewMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();

        $body = <<<EOF
return \$this->getConfiguration()->getSession()->isNew(\$entity);
\$id = spl_object_hash(\$entity);
if (\$entity instanceof \Propel\Runtime\EntityProxyInterface) {
    if (isset(\$this->deletedIds[\$id])) {
        //it has been deleted after receiving from the database,
        return true;
    }

    return false;
} else {
    if (isset(\$this->committedIds[\$id])) {
        //it has been committed
        return false;
    }

    return true;
}
EOF;

        $this->addMethod('isNew')
            ->addSimpleParameter('entity', $entityClassName)
            ->setType('boolean')
            ->setDescription("Returns true if this is a new (not yet saved/committed) instance.")
            ->setBody($body);
    }
}