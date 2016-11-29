<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all remove* methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationRemoverMethods extends BuildComponent
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
        $relation = $crossRelation->getOutgoingRelation();
        $collName = $this->getRelationVarName($relation, true);

        $relatedObjectClassName = $this->getRelationPhpName($relation, false);
        $crossObjectClassName = $relation->getForeignEntity()->getFullClassName();

        list ($signature, , $normalizedShortSignature, $phpDoc) = $this->getCrossRelationAddMethodInformation($crossRelation, $relation);

        $crossObjectName = '$' . $this->getRelationVarName($relation);
        $getterName = $this->getCrossRefRelationGetterName($crossRelation, $relation);

        $body = <<<EOF
if (false !== \$pos = \$this->{$collName}->search({$normalizedShortSignature})) {
\$this->{$collName}->remove(\$pos);

//remove back reference
{$crossObjectName}->get{$getterName}()->removeObject(\$this);
}

return \$this;
EOF;

        $description = <<<EOF
Dissociate a $crossObjectClassName from this object
through the {$crossRelation->getMiddleEntity()->getFullClassName()} cross reference entity.
$phpDoc
EOF;


        $method = $this->addMethod('remove' . $relatedObjectClassName)
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