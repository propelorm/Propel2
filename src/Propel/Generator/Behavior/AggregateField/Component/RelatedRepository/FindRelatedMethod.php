<?php

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FindRelatedMethod extends BuildComponent
{
    use RelationTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relation = $behavior->getRelation();
        $relationName = $behavior->getRelationName();
        $refRelationName = $this->getRefRelationPhpName($relation);
        $aggregateName = $behavior->getParameter('aggregate_name');
        $foreignEntity = $behavior->getForeignEntity();

        $variableName = lcfirst($relationName . $behavior->getParameter('aggregate_name'));
        $foreignEntityClassName = $foreignEntity->getFullClassName();

        $body = "
\$criteria = \$this->createQuery();
\$this->afCache{$variableName}s = \$this->getConfiguration()->getRepository('$foreignEntityClassName')->createQuery()
    ->use{$refRelationName}Query()
        ->filterByPrimaryKeys(\$this->getOriginPKs(\$event->getEntities()))
    ->endUse()
    ->find();
";
        $name = 'findRelated' . $relationName . $aggregateName;

        $this->addMethod($name)
            ->setDescription("[AggregateField-related] Finds the related {$foreignEntity->getName()} objects and keep them for later.")
            ->addSimpleDescParameter('event', '\Propel\Runtime\Event\UpdateEvent', 'update event')
            ->setBody($body);
    }
}