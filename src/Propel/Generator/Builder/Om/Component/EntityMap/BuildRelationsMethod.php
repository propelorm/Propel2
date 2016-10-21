<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Adds buildRelations method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildRelationsMethod extends BuildComponent
{
    use NamingTrait;
    use RelationTrait;

    public function process()
    {
        $body = "";

        $this->getDefinition()->declareUse('Propel\Runtime\Map\RelationMap');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $columnMapping = 'array(';
            foreach ($relation->getLocalForeignMapping() as $key => $value) {
                $columnMapping .= "'$key' => '$value', ";
            }
            $columnMapping .= ')';
            $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
            $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';
            $body .= "
        \$this->addRelation('" . $this->getRelationVarName($relation) . "', '" . addslashes(
                    $this->getClassNameFromEntity($relation->getForeignEntity(), true)
                ) . "', RelationMap::MANY_TO_ONE, $columnMapping, $onDelete, $onUpdate);";
        }
        foreach ($this->getEntity()->getReferrers() as $relation) {
            $relationName = $this->getRefRelationVarName($relation);
            $columnMapping = 'array(';
            foreach ($relation->getForeignLocalMapping() as $key => $value) {
                $columnMapping .= "'$key' => '$value', ";
            }
            $columnMapping .= ')';
            $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
            $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';
            $body .= "
        \$this->addRelation('$relationName', '" . addslashes(
                    $this->getClassNameFromEntity($relation->getEntity(), true)
                ) . "', RelationMap::ONE_TO_" . ($relation->isLocalPrimaryKey(
                ) ? "ONE" : "MANY") . ", $columnMapping, $onDelete, $onUpdate";
            if ($relation->isLocalPrimaryKey()) {
                $body .= ");";
            } else {
                $body .= ", '" . $this->getRefRelationVarName($relation, true) . "');";
            }
        }
        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            foreach ($crossRelation->getRelations() as $relation) {
                $relationName = $this->getRelationName($relation);
                $pluralName = "'" . $this->getRelationName($relation, true) . "'";
                $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
                $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';
                $body .= "
        \$this->addRelation('$relationName', '" . addslashes(
                        $this->getClassNameFromEntity($relation->getForeignEntity(), true)
                    ) . "', RelationMap::MANY_TO_MANY, array(), $onDelete, $onUpdate, $pluralName);";
            }
        }

        $this->addMethod('buildRelations')
            ->setBody($body);
    }
}