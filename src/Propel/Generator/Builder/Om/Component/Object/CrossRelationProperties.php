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
        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {

            list($names) = $this->getCrossRelationInformation($crossRelation);
            $varName = 'combination' . ucfirst($this->getCrossRelationVarName($crossRelation));

            $this->addProperty($varName)
                ->setType('ObjectCombinationCollection')
                ->setTypeDescription("Cross CombinationCollection to store aggregation of $names combinations.");

            $this->addConstructorBody("\$this->$varName = new ObjectCollection();");

            if ($crossRelation->getEntity()->isActiveRecord()) {
                $partialVarName = $varName . 'Partial';

                $this->addProperty($partialVarName)
                    ->setType('boolean')
                    ->setTypeDescription("");
            }

            return;
        }

        $relation = $crossRelation->getRelations()[0];
        $className = $this->getClassNameFromEntity($relation->getForeignEntity());
        $varName = $this->getCrossRelationRelationVarName($relation);

        $this->addProperty($varName)
            ->setType("ObjectCollection|{$className}[]")
            ->setTypeDescription("Cross Collection to store aggregation of $className objects.");

        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
        $this->addConstructorBody("\$this->$varName = new ObjectCollection();");

        if ($crossRelation->getEntity()->isActiveRecord()) {
            $partialVarName = $varName . 'Partial';

            $this->addProperty($partialVarName)
                ->setType('boolean')
                ->setTypeDescription("");
        }
    }
} 