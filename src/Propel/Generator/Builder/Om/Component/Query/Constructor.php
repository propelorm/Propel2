<?php


namespace Propel\Generator\Builder\Om\Component\Query;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class Constructor extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $databaseName = $this->getEntity()->getDatabase()->getName();
        $modelClass = $this->getObjectClassName(true);
        $queryClass = $this->getQueryClassName(true);

        $body = <<<EOF
parent::__construct(\$dbName, \$entityName, \$entityAlias);
EOF;

        $this->addMethod('__construct')
            ->addSimpleDescParameter('dbName', 'string', 'The database name', $databaseName)
            ->addSimpleDescParameter('entityName', 'string', 'The full entity class name', $modelClass)
            ->addSimpleDescParameter('entityAlias', 'string', "The alias for the entity in this query, e.g. 'b'", null)
            ->setDescription("Initializes internal state of $queryClass object.")
            ->setBody($body);

    }
}