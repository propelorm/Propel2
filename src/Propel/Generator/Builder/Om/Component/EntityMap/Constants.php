<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpConstant;
use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Platform\PlatformInterface;

/**
 * Adds DATABASE_NAME, TABLE_NAME constant.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Constants extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $entity = $this->getEntity();

        $constant = new PhpConstant('DATABASE_NAME');
        $constant->setDescription("The database and connection name for the {$entity->getName()} entity.");
        $constant->setValue($entity->getDatabase()->getName());
        $this->getDefinition()->setConstant($constant);

        $constant = new PhpConstant('TABLE_NAME');
        $constant->setDescription("The table name");
        $constant->setValue($entity->getTableName());
        $this->getDefinition()->setConstant($constant);

        $constant = new PhpConstant('ENTITY_CLASS');
        $constant->setDescription("The full entity class name");
        $constant->setValue($entity->getFullClassName());
        $this->getDefinition()->setConstant($constant);


        $constant = new PhpConstant('QUERY_CLASS');
        $constant->setDescription("The full query class name");
        $constant->setValue($this->getQueryClassName(true));
        $this->getDefinition()->setConstant($constant);

        $constant = new PhpConstant('PROXY_CLASS');
        $constant->setDescription("The full proxy class name");
        $constant->setValue($this->getProxyClassName(true));
        $this->getDefinition()->setConstant($constant);

        if ($this->getEntity()->hasSchema()) {
            $constant = new PhpConstant('SCHEMA_NAME');
            $constant->setDescription("The schema name of the underlying database this table belongs to.");
            $constant->setValue($entity->guessSchemaName());
            $this->getDefinition()->setConstant($constant);
        }

        $constant = new PhpConstant('FQ_TABLE_NAME');
        $constant->setDescription("The full qualified table name (with schema name)");
        $constant->setValue($entity->getFQTableName());
        $this->getDefinition()->setConstant($constant);

        $constant = new PhpConstant('DEFAULT_STRING_FORMAT');
        $constant->setValue($entity->getDefaultStringFormat());
        $this->getDefinition()->setConstant($constant);
    }
}