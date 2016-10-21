<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FindRelatedMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relation = $behavior->getRelation();
        $relationName = $behavior->getRelation()->getName();
        $refRelationName = $this->getRefRelationName($relation);
        $aggregateName = $behavior->getParameter('aggregate_name');
        $foreignEntity = $behavior->getForeignEntity();

        $variableName = $relationName . ucfirst($behavior->getParameter('aggregate_name'));
        $foreignEntityClassName = $foreignEntity->getFullClassName();
        $foreignClass = $this->getQueryClassNameForEntity($foreignEntity, true);

        $body = "
\$criteria = \$this->createQuery();
\$repository = \$this->getConfiguration()->getRepository('$foreignEntityClassName');
/** @var \\$foreignClass \$query */
\$query = \$repository->createQuery();
\$this->afCache{$variableName} = \$query
    ->use{$refRelationName}Query()
        ->filterByPrimaryKeys(\$this->getOriginPKs(\$event->getEntities()))
    ->endUse()
    ->find();
";
        $name = 'findRelated' . $relationName . ucfirst($aggregateName);

        $this->addMethod($name)
            ->setDescription("[AggregateField-related] Finds the related {$foreignEntity->getName()} objects and keep them for later.")
            ->addSimpleDescParameter('event', '\Propel\Runtime\Event\UpdateEvent', 'update event')
            ->setBody($body);
    }
}