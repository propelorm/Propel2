<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all referrer relations as class properties.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ReferrerRelationProperties extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();

        foreach ($entity->getReferrers() as $refRelation) {
            $this->addRefRelationAttributes($refRelation);
        }
    }

    /**
     * Adds the attributes used to store objects that have referrer fkey relationships to this object.
     *
     * @param Relation $refRelation
     */
    protected function addRefRelationAttributes(Relation $refRelation)
    {
        $className = $this->getClassNameFromEntity($refRelation->getEntity());

        if ($refRelation->isLocalPrimaryKey()) {

            $this->addProperty($this->getPKRefRelationVarName($refRelation))
                ->setType($className)
                ->setTypeDescription("one-to-one related $className object. (referrer relation)");

//            if ($refRelation->getEntity()->isActiveRecord()) {
//                $this->addProperty($this->getPKRefRelationVarName($refRelation).'Partial')
//                    ->setType('boolean');
//            }

        } else {
            $collection = $this->getDefinition()->declareUse('Propel\Runtime\Collection\Collection');

            $this->addProperty($this->getRefRelationCollVarName($refRelation))
                ->setType("$collection|{$className}[]")
                ->setTypeDescription("Collection of $className. (referrer relation)");

            if ($refRelation->getEntity()->isActiveRecord()) {
                $this->addProperty($this->getRefRelationCollVarName($refRelation).'Partial')
                    ->setType('boolean');
            }
        }
    }
}
