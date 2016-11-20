<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\CrossRelation;

/**
 * Adds all setter methods for crossRelations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class CrossRelationSetterMethods extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addCrossFKSet($crossRelation);
        }
    }

    /**
     * Adds the standard variant for cross relation getters.
     *
     * @param CrossRelation $crossRelation
     */
    protected function addCrossFKSet(CrossRelation $crossRelation)
    {
        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
            throw new BuildException('Cross relations with more than 2 relations are not supported currently.');
        } else {
            $this->addNormalCrossRelationSetter($crossRelation);
        }
    }

//    /**
//     * Adds the active record variant of cross relation getters.
//     *
//     * @param CrossRelation $crossRelation
//     */
//    protected function addActiveCrossFKGet(CrossRelation $crossRelation)
//    {
//        if (1 < count($crossRelation->getRelations()) || $crossRelation->getUnclassifiedPrimaryKeys()) {
//            $this->addActiveCombinedCrossRelationGetter($crossRelation);
//        } else {
//            $this->addActiveNormalCrossRelationGetter($crossRelation);
//        }
//    }

    protected function addNormalCrossRelationSetter(CrossRelation $crossRelation)
    {
        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();

        foreach ($crossRelation->getRelations() as $relation) {
            $relatedName = $this->getRelationPhpName($relation, true);
            $relatedObjectClassName = $this->useClass($relation->getForeignEntity()->getFullClassName());

            $collName = $this->getCrossRelationRelationVarName($relation);

        $body = "
\$this->$collName = \$$collName;

return \$this;";

            $description = <<<EOF
Sets a collection of $relatedObjectClassName objects related by a many-to-many relationship
to the current object by way of the $crossRefEntityName cross-reference entity.
EOF;

            $this->addMethod('set' . $relatedName)
                ->addSimpleParameter($collName)
                ->setType($this->getObjectClassName())
                ->setTypeDescription("The current object (for fluent API support)")
                ->setDescription($description)
                ->setBody($body);
        }
    }
//
//    protected function addCombinedCrossRelationGetter(CrossRelation $crossRelation)
//    {
//        $crossRefEntityName = $crossRelation->getMiddleEntity()->getName();
//
//        $relatedName = $this->getCrossRelationPhpName($crossRelation, true);
//        $collVarName = 'combination' . ucfirst($this->getCrossRelationVarName($crossRelation));
//
//        $classNames = [];
//        foreach ($crossRelation->getRelations() as $relation) {
//            $classNames[] = $this->getClassNameFromBuilder(
//                $this->getBuilder()->getNewObjectBuilder($relation->getForeignEntity())
//            );
//        }
//        $classNames = implode(', ', $classNames);
//
//        $body = "
//\$this->$collVarName = \$$collVarName;
//
//return \$this;";
//
//        $description = <<<EOF
//Sets a combined collection of $classNames objects related by a many-to-many relationship
//to the current object by way of the $crossRefEntityName cross-reference entity.
//EOF;
//
//        $this->addMethod('get' . $relatedName)
//            ->setType('ObjectCombinationCollection')
//            ->setTypeDescription("Combination list of {$classNames} objects")
//            ->setDescription($description)
//            ->setBody($body);
//    }
} 