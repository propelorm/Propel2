<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all add* methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationAdderMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        // many-to-many relationships
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossFKAdd($crossRelation);
        }
    }

    protected function addCrossFKAdd(CrossRelation $crossRelation)
    {
        $refFK = $crossRelation->getIncomingRelation();

        foreach ($crossRelation->getRelations() as $relation) {
            $relSingleNamePlural = $this->getRelationPhpName($relation, $plural = true);
            $relSingleName = $this->getRelationPhpName($relation, $plural = false);
            $collSingleName = $this->getRelationVarName($relation, true);

            $relCombineNamePlural = $this->getCrossRelationPhpName($crossRelation, $plural = true);
            $relCombineName = $this->getCrossRelationPhpName($crossRelation, $plural = false);
            $collCombinationVarName = 'combination' . ucfirst($this->getCrossRelationVarName($crossRelation));

            $collName = 1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys() ? $collCombinationVarName : $collSingleName;
            $relNamePlural = ucfirst(1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys() ? $relCombineNamePlural : $relSingleNamePlural);
            $relName = ucfirst(1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys() ? $relCombineName : $relSingleName);

            $foreignEntity = $refFK->getEntity();
            $relatedObjectClassName = $this->getRelationPhpName($relation, false);
            $crossObjectClassName = $this->getClassNameFromEntity($relation->getForeignEntity());
            list ($signature, , $normalizedShortSignature) = $this->getCrossRelationAddMethodInformation($crossRelation, $relation);

            $body = <<<EOF
if (!\$this->get{$relNamePlural}()->contains({$normalizedShortSignature})) {
    \$this->{$collName}->push({$normalizedShortSignature});
    
    //setup bidirectional relation
    {$this->getBiDirectional($crossRelation)}
}

return \$this;
EOF;


            $description = <<<EOF
Associate a $crossObjectClassName to this object
through the {$foreignEntity->getFullClassName()} cross reference entity.
EOF;


            $method = $this->addMethod('add' . $relatedObjectClassName)
                ->setDescription($description)
                ->setType($this->getObjectClassName())
                ->setTypeDescription("The current object (for fluent API support)")
                ->setBody($body)
            ;

            foreach ($signature as $parameter) {
                $method->addParameter($parameter);
            }

        }
    }

    protected function getBiDirectional(CrossRelation $crossRelation)
    {
        $body = '';
        $setterName = 'add' . $this->getRelationPhpName($crossRelation->getIncomingRelation(), false);

        foreach ($crossRelation->getRelations() as $relation) {
            $varName = $this->getRelationVarName($relation);

            $body .= "
\${$varName}->{$setterName}(\$this);";
        }

        return $body;
    }
}