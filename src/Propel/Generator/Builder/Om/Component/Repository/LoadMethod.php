<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RepositoryTrait;

/**
 * Adds the load method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class LoadMethod extends BuildComponent
{
    use NamingTrait;
    use RepositoryTrait;

    public function process()
    {
        $entity = $this->getEntity();

            $body = "
\$reader = \$this->getEntityMap()->getPropReader();
";
            if ($entity->hasCompositePrimaryKey()) {
                foreach ($entity->getPrimaryKey() as $index => $field) {
                    $body .= "\$key[] = \$reader(\$entity, '{$field->getName()}');\n";
                }
            } else {
                $body .= "\$key = \$reader(\$entity, '{$entity->getFirstPrimaryKeyField()->getName()}');";
            }

            $body .= "
\$dataFetcher = \$this
    ->createQuery()
    ->filterByPrimaryKey(\$key)
    ->doSelect();

\$row = \$dataFetcher->fetch();
\$indexStart = 0;
\$this->getEntityMap()->populateObject(\$row, \$indexStart, \$dataFetcher->getIndexType(), \$entity);
";

        $this->addMethod('load')
            ->addSimpleParameter('entity', 'object')
            ->setBody($body);
    }
}