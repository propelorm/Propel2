<?php

namespace Propel\Generator\Behavior\AggregateField\Component\Repository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\Field;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ComputeMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldBehavior $behavior */
        $behavior = $this->getBehavior();

        $conditions = array();
        if ($behavior->getParameter('condition')) {
            $conditions[] = $behavior->getParameter('condition');
        }

        $bindings = array();
        $database = $this->getEntity()->getDatabase();
        foreach ($behavior->getRelation()->getFieldObjectsMapping() as $index => $fieldReference) {
            /** @var Field $local */
            $local = $fieldReference['local'];
            /** @var Field $foreign */
            $foreign = $fieldReference['foreign'];
            $conditions[] = $local->getColumnName() . ' = :p' . ($index + 1);
            $bindings[$index + 1] = $foreign->getName();
        }

        $foreignEntity = $database->getEntity($behavior->getParameter('foreign_entity'));
//
//        $tableName = $database->getEntityPrefix() . $foreignEntity->getEntityName();
//        if ($database->getPlatform()->supportsSchemas() && $behavior->getParameter('foreign_schema')) {
//            $tableName = $behavior->getParameter('foreign_schema')
//                . $database->getPlatform()->getSchemaDelimiter()
//                . $tableName;
//        }

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $behavior->getParameter('expression'),
            $behavior->getEntity()->quoteIdentifier($foreignEntity->getFQTableName()),
            implode(' AND ', $conditions)
        );

        $body = "
\$connection = \$this->getConfiguration()->getConnectionManager('{$foreignEntity->getDatabase()->getName()}')->getWriteConnection();
\$stmt = \$connection->prepare('$sql');
\$lastKnownValues = \$this->getEntityMap()->getLastKnownValues(\$entity, true);
";

foreach ($bindings as $key => $binding) {
    $body .= "
\$stmt->bindValue(':p{$key}', \$lastKnownValues['{$binding}']);
";
    }

    $body .= "
\$stmt->execute();

return \$stmt->fetchColumn();
";

        $this->addMethod('compute' . ucfirst($behavior->getField()->getName()))
            ->addSimpleDescParameter('entity', 'object', 'The entity object')
            ->setType('mixed')
            ->setTypeDescription('The scalar result from the aggregate query')
            ->setDescription("[AggregateField] Computes the value of the aggregate field {$behavior->getField()->getName()}.")
            ->setBody($body)
        ;
    }
}