<?php


namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\docblock\tags\TagFactory;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossRelationTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;

/**
 * Adds buildRelations method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class BuildRelationsMethod extends BuildComponent
{
    use CrossRelationTrait;

    public function process()
    {
        $body = "";

        $this->getDefinition()->declareUse('Propel\Runtime\Map\RelationMap');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $relationName = var_export($this->getRelationVarName($relation), true);
            $target = var_export($relation->getForeignEntity()->getFullClassName(), true);
            $columnMapping = var_export($relation->getLocalForeignMapping(), true);

            $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
            $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';
            $body .= "
\$this->addRelation($relationName, $target, RelationMap::MANY_TO_ONE, $columnMapping, $onDelete, $onUpdate);";
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $relationName = var_export($this->getRefRelationVarName($relation), true);
            $target = var_export($relation->getEntity()->getFullClassName(), true);
            $columnMapping = var_export(array_flip($relation->getForeignLocalMapping()), true);

            $onDelete = $relation->hasOnDelete() ? "'" . $relation->getOnDelete() . "'" : 'null';
            $onUpdate = $relation->hasOnUpdate() ? "'" . $relation->getOnUpdate() . "'" : 'null';

            $body .= "
//ref relation
\$this->addRelation($relationName, $target, RelationMap::ONE_TO_" . ($relation->isLocalPrimaryKey(
                ) ? "ONE" : "MANY") . ", $columnMapping, $onDelete, $onUpdate";
            if ($relation->isLocalPrimaryKey()) {
                $body .= ");";
            } else {
                $body .= ", '" . $this->getRefRelationVarName($relation, true) . "');";
            }
        }

        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $relationName = var_export($this->getCrossRelationVarName($crossRelation), true);
            $pluralName = var_export( $this->getCrossRelationVarName($crossRelation, true), true);
            $target = var_export($crossRelation->getForeignEntity()->getFullClassName(), true);
            
            $onDelete = $crossRelation->getIncomingRelation()->hasOnDelete() ? "'" . $crossRelation->getIncomingRelation()->getOnDelete() . "'" : 'null';
            $onUpdate = $crossRelation->getIncomingRelation()->hasOnUpdate() ? "'" . $crossRelation->getIncomingRelation()->getOnUpdate() . "'" : 'null';

            $fieldMapping = [];
            foreach ($crossRelation->getRelations() as $relation) {
                $fieldMapping[$relation->getField()] = array_merge($relation->getLocalForeignMapping(), $fieldMapping);
            }
            $primaryKeys = [];
            foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $pk) {
                $primaryKeys[] = $pk->getName();
            }

            $mapping = [
                'via' => $crossRelation->getMiddleEntity()->getFullClassName(),
                'viaTable' => $crossRelation->getMiddleEntity()->getFQTableName(),
                'isImplementationDetail' => $crossRelation->getMiddleEntity()->isImplementationDetail(),
                'fieldMappingIncomingName' => $crossRelation->getIncomingRelation()->getField(),
                'fieldMappingIncoming' => $crossRelation->getIncomingRelation()->getLocalForeignMapping(),
                'fieldMappingOutgoing' => $fieldMapping,
                'fieldMappingPrimaryKeys' => $crossRelation->getUnclassifiedPrimaryKeys(),
            ];

            $mapping = var_export($mapping, true);
            $body .= "
\$this->addRelation($relationName, $target, RelationMap::MANY_TO_MANY, $mapping, $onDelete, $onUpdate, $pluralName);";
        }

        $this->addMethod('buildRelations')
            ->setBody($body);
    }
}