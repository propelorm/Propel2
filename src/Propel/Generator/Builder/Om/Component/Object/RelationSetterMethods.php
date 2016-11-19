<?php

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ComponentHelperTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all setter methods for relations.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class RelationSetterMethods extends BuildComponent
{
    use RelationTrait;
    use NamingTrait;
    use SimpleTemplateTrait;

    public function process()
    {
        foreach ($this->getEntity()->getRelations() as $relation) {
            $this->addRelationSetter($relation);
        }
    }

    /**
     * Adds the mutator (setter) method for setting an related object.
     *
     * @param Relation $relation
     */
    protected function addRelationSetter(Relation $relation)
    {
        $varName = $this->getRelationVarName($relation);
        $className = $this->getObjectClassName(true);
        $setterName = 'set' . $this->getRelationPhpName($relation, false);
        $relationEntity = $relation->getForeignEntity();

        $relationClassName = $this->useClass($relationEntity->getFullClassName());

        $body = $this->renderTemplate(
            [
                'adder' => 'add' . $this->getRefRelationPhpName($relation, false),
                'setter' => 'set' . $this->getRefRelationPhpName($relation, false),
                'varName' => $varName,
                'isOneToOne' => $relation->isLocalPrimaryKey(),
                'isManyToOne' => !$relation->isLocalPrimaryKey()
            ],
            'RelationSetterMethod'
        );

        $internal = "\nMapped by fields " . implode(', ', $relation->getLocalFields());

        $this->addMethod($setterName)
            ->setDescription("Declares an association between this object and a $relationClassName object.$internal")
            ->setType("\$this|\\$className")
            ->setTypeDescription("The current object (for fluent API support)")
            ->addSimpleParameter($varName, $relationClassName, null)
            ->setBody($body);
    }
}