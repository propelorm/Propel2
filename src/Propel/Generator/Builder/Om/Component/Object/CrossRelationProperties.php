<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all properties for crossRelations
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationProperties extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        // many-to-many relationships
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossRelationAttributes($crossRelation);
        }
    }

    /**
     * @param CrossRelation $crossRelation
     */
    protected function addCrossRelationAttributes(CrossRelation $crossRelation)
    {
        $relation = $crossRelation->getOutgoingRelation();
        $className = $relation->getForeignEntity()->getFullClassName();
        $varName = $this->getCrossRelationRelationVarName($relation);

        $this->addProperty($varName)
            ->setType("ObjectCollection|\\{$className}[]")
            ->setTypeDescription("Cross Collection to store aggregation of \\$className objects.");


        if ($crossRelation->isPolymorphic()) {
            $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCombinationCollection');
            $this->addConstructorBody("\$this->$varName = new ObjectCombinationCollection();");
        } else {
            $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
            $this->addConstructorBody("\$this->$varName = new ObjectCollection();");
        }

        if ($crossRelation->getEntity()->isActiveRecord()) {
            $partialVarName = $varName . 'Partial';

            $this->addProperty($partialVarName)
                ->setType('boolean')
                ->setTypeDescription("");
        }
    }
} 