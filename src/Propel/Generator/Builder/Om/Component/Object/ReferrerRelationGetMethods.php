<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all one-to-one referrer get methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ReferrerRelationGetMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();

        foreach ($entity->getReferrers() as $refRelation) {
            if ($refRelation->isLocalPrimaryKey()) {
                //one-to-one
                $this->addRefGetMethod($refRelation);
            } else {
                //one-to-many
                $this->addRefGetCollectionMethod($refRelation);
            }
        }
    }

    /**
     * Adds the accessor (getter) method for getting an related object collection.
     *
     * @param Relation $relation
     */
    protected function addRefGetCollectionMethod(Relation $relation)
    {
        $varName = $this->getRefRelationCollVarName($relation);
        $foreignClassName = $this->useClass($relation->getEntity()->getFullClassName());

        $body = "
return \$this->$varName;
";

        $internal = "\nMapped by fields " . implode(', ', $relation->getForeignFields());

        $methodName = 'get' . ucfirst($this->getRefRelationCollVarName($relation));
        $this->addMethod($methodName)
            ->setType("null|{$foreignClassName}[]")
            ->setTypeDescription("Collection of $foreignClassName objects.$internal")
            ->setBody($body);
    }

    /**
     * Adds the accessor (getter) method for getting an related object.
     *
     * @param Relation $relation
     */
    protected function addRefGetMethod(Relation $relation)
    {
        $varName = $this->getRefRelationVarName($relation);
        $foreignClassName = $this->getClassNameFromEntity($relation->getEntity());

        $body = "
return \$this->$varName;
";

        $internal = "\nMapped by fields " . implode(', ', $relation->getForeignFields());

        $methodName = 'get' . $this->getRefRelationPhpName($relation, false);
        $this->addMethod($methodName)
            ->setType("null|$foreignClassName")
            ->setTypeDescription("The associated $foreignClassName object$internal")
            ->setBody($body)
            ->setDescription("Returns the associated $foreignClassName object or null if none is associated.");
    }
} 