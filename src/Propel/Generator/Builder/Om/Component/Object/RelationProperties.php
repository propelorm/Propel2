<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all properties for all relations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RelationProperties extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        $entity = $this->getEntity();

        foreach ($entity->getRelations() as $relation) {
            $this->addRelationAttribute($relation);
        }
    }

    /**
     * Adds the class attributes that are needed to store fkey related objects.
     *
     * @param Relation $relation
     */
    protected function addRelationAttribute(Relation $relation)
    {
        $className = $this->useClass($relation->getForeignEntity()->getFullClassName());
        $varName = $this->getRelationVarName($relation);

        $prop = $this->addProperty($varName)
            ->setType($className);

        if ($relation->isLocalPrimaryKey()) {
            $prop->setTypeDescription("one-to-one related $className object");
        } else {
            $prop->setTypeDescription("many-to-one related $className object");
        }
    }
} 