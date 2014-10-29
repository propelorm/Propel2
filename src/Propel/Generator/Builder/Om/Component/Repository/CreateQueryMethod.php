<?php

namespace Propel\Generator\Builder\Om\Component\Repository;

use gossi\codegen\model\PhpParameter;
use Mandango\Mondator\Definition\Method;
use Propel\Generator\Builder\ClassDefinition;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Tests\Bookstore\BookstoreQuery;

/**
 * Adds the createQuery method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CreateQueryMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();
        $entityQueryClassName = $this->getQueryClassName();

        $this->getDefinition()->declareUse('Propel\Runtime\ActiveQuery\Criteria');

        $body = <<<EOF
\$query = new $entityQueryClassName();
if (null !== \$alias) {
    \$query->setEntityAlias(\$alias);
}
if (\$criteria instanceof Criteria) {
    \$query->mergeWith(\$criteria);
}

\$query->setEntityMap(\$this->getEntityMap());
\$query->setConfiguration(\$this->getConfiguration());

return \$query;
EOF;

        $this->addMethod('createQuery')
            ->addSimpleParameter('alias', 'string', null)
            ->addSimpleParameter('criteria', 'Criteria', null)
            ->setType($entityQueryClassName)
            ->setDescription("Create a new query instance of $entityClassName.")
            ->setBody($body);
    }
}