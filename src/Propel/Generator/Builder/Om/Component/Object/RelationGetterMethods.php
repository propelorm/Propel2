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

        $foreignClassName = $this->useClass($relation->getForeignEntity()->getFullClassName());

        $body = "
return \$this->$varName;
";

        $internal = "\nMapped by fields " . implode(', ', $relation->getLocalFields());

        $methodName = 'get' . $this->getRelationPhpName($relation, false);
        $this->addMethod($methodName)
            ->setType("null|$foreignClassName")
            ->setTypeDescription("The associated $foreignClassName object")
            ->setBody($body)
            ->setDescription("Returns the associated $foreignClassName object or null if none is associated.$internal");
    }
}