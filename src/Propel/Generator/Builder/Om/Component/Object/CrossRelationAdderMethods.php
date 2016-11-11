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
            $this->addCrossFKDoAdd($crossRelation);
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
            list ($signature, , $normalizedShortSignature, $phpDoc) = $this->getCrossRelationAddMethodInformation($crossRelation, $relation);

            $body = <<<EOF
if (!\$this->get{$relNamePlural}()->contains({$normalizedShortSignature})) {
    \$this->{$collName}->push({$normalizedShortSignature});
    
    \$this->doAdd{$relName}($normalizedShortSignature); //add actual cross object
}

return \$this;
EOF;


            $description = <<<EOF
Associate a $crossObjectClassName to this object
through the {$foreignEntity->getFullClassName()} cross reference entity.
$phpDoc
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
    /**
     * @param CrossRelation $crossRelation
     */
    protected function addCrossFKDoAdd(CrossRelation $crossRelation)
    {
        $selfRelationNamePlural = $this->getRelationPhpName($crossRelation->getIncomingRelation(), $plural = true);
        $relatedObjectClassName = $this->getCrossRelationPhpName($crossRelation, $plural = false);
        $className = $this->getClassNameFromEntity($crossRelation->getIncomingRelation()->getEntity());

        $refKObjectClassName = $this->getRefRelationPhpName($crossRelation->getIncomingRelation(), $plural = false);
        $entity = $crossRelation->getIncomingRelation()->getEntity();
        $foreignObjectName = '$' . $entity->getCamelCaseName();

        list ($signature, , , $phpDoc) =
            $this->getCrossRelationAddMethodInformation($crossRelation);

        $body = "{$foreignObjectName} = new {$className}();";

        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
            foreach ($crossRelation->getRelations() as $relation) {
                $relatedObjectClassName = $this->getRelationPhpName($relation, $plural = false);
                $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);
                $body .= "
    {$foreignObjectName}->set{$relatedObjectClassName}(\${$lowerRelatedObjectClassName});";
            }

            foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $primaryKey) {
                $paramName = lcfirst($primaryKey->getName());
                $body .= "
    {$foreignObjectName}->set{$primaryKey->getName()}(\$$paramName);
";
            }
        } else {
            $relation = $crossRelation->getRelations()[0];
            $relatedObjectClassName = $this->getRelationPhpName($relation, $plural = false);
            $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);
            $body .= "
        {$foreignObjectName}->set{$relatedObjectClassName}(\${$lowerRelatedObjectClassName});";
        }

        $refFK = $crossRelation->getIncomingRelation();
        $body .= "

    {$foreignObjectName}->set" . $this->getRelationPhpName($refFK, $plural = false) . "(\$this);

    \$this->add{$refKObjectClassName}({$foreignObjectName});\n";

        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
            foreach ($crossRelation->getRelations() as $relation) {
                $relatedObjectClassName = $this->getRelationPhpName($relation, $plural = false);
                $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);

                $getterName = $this->getCrossRefRelationGetterName($crossRelation, $relation);
                $getterRemoveObjectName = $this->getCrossRefFKRemoveObjectNames($crossRelation, $relation);

                $body .= "
    if (!\${$lowerRelatedObjectClassName}->get{$getterName}()->contains($getterRemoveObjectName)) {
        \${$lowerRelatedObjectClassName}->get{$getterName}()->push($getterRemoveObjectName);
    }\n";
            }
        } else {
            $relation = $crossRelation->getRelations()[0];
            $relatedObjectClassName = $this->getRelationPhpName($relation, $plural = false);
            $lowerRelatedObjectClassName = lcfirst($relatedObjectClassName);
            $getterSignature = $this->getCrossFKGetterSignature($crossRelation, '$' . $lowerRelatedObjectClassName);
            $body .= "
    if (!\${$lowerRelatedObjectClassName}->get{$selfRelationNamePlural}($getterSignature)->contains(\$this)) {
        \${$lowerRelatedObjectClassName}->get{$selfRelationNamePlural}($getterSignature)->push(\$this);
    }\n";
        }

        $method = $this->addMethod('doAdd' . $relatedObjectClassName)
            ->setDescription($phpDoc)
            ->setBody($body);

        foreach ($signature as $parameter) {
            $method->addParameter($parameter);
        }
    }
} 