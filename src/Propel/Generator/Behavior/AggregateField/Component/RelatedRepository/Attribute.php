<?php

namespace Propel\Generator\Behavior\AggregateField\Component\RelatedRepository;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Behavior\AggregateField\AggregateFieldBehavior;
use Propel\Generator\Behavior\AggregateField\AggregateFieldRelationBehavior;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;

/**
 *
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Attribute extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var AggregateFieldRelationBehavior $behavior */
        $behavior = $this->getBehavior();

        $relationName = $behavior->getRelationName();
        $variableName = lcfirst($relationName);

//        $relationName = $behavior->getRelationName();
        $relatedClass = $behavior->getForeignEntity()->getFullClassName();
//        $aggregateName = $behavior->getParameter('aggregate_name');

        $property = new PhpProperty("afCache{$variableName}s");
        $property->setType($relatedClass . '[]');
        $property->setDescription('[AggregateField-related]');
        $property->setVisibility('protected');
        $this->getDefinition()->setProperty($property);
    }
}