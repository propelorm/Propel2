<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateField\Component\Repository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
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
            $conditions[] = $local->getSqlName() . ' = :p' . ($index + 1);
            $bindings[$index + 1] = $foreign->getName();
        }

        $foreignEntity = $database->getEntity($behavior->getParameter('foreign_entity'));

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $behavior->getParameter('expression'),
            $behavior->getEntity()->quoteIdentifier($foreignEntity->getSqlName()),
            implode(' AND ', $conditions)
        );

        $body = "
\$connection = \$this->getConfiguration()->getConnectionManager('{$foreignEntity->getDatabase()->getName()}')->getWriteConnection();
\$stmt = \$connection->prepare('$sql');
\$lastKnownValues = \$this->getLastKnownValues(\$entity, true);
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

        $this->addMethod('compute' . $behavior->getField()->getMethodName())
            ->addSimpleDescParameter('entity', 'object', 'The entity object')
            ->setType('mixed')
            ->setTypeDescription('The scalar result from the aggregate query')
            ->setDescription("[AggregateField] Computes the value of the aggregate field {$behavior->getField()->getName()}.")
            ->setBody($body)
        ;
    }
}