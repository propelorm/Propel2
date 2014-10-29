<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all getter methods for relations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RelationGetterMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;

    public function process()
    {
        foreach ($this->getEntity()->getRelations() as $relation) {
            $this->addRelationGetter($relation);
        }
    }

    /**
     * Adds the accessor (getter) method for getting an related object.
     *
     * @param Relation $relation
     */
    protected function addRelationGetter(Relation $relation)
    {
        $varName = $this->getRelationVarName($relation);
        $relationObjectBuilder = $this
            ->getBuilder()
            ->getNewObjectBuilder($relation->getForeignEntity())
            ->getObjectBuilder();

        $className = $this->getClassNameFromBuilder($relationObjectBuilder);

        $body = "
        return \$this->$varName;
";

        $methodName = 'get' . $this->getRelationPhpName($relation, false);
        $this->addMethod($methodName)
            ->setType("null|$className")
            ->setTypeDescription("The associated $className object")
            ->setBody($body)
            ->setDescription("Returns the associated $className object or null if none is associated.");
    }
}