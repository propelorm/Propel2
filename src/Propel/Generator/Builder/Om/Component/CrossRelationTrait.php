<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 13.10.14
 * Time: 02:20
 */

namespace Propel\Generator\Builder\Om\Component;

use Propel\Generator\Builder\Om\NewAbstractObjectBuilder;
use Propel\Generator\Model\CrossRelation;
use Propel\Generator\Model\Relation;

/**
 * This trait provied usefull helper methods for handling cross relations (CrossRelation).
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
trait CrossRelationTrait
{
    use RelationTrait;
    use NamingTrait;

    /**
     * @param CrossRelation $crossRelation
     *
     * @return array
     */
    protected function getCrossRelationInformation(CrossRelation $crossRelation)
    {
        $names = [];
        $signatures = [];
        $shortSignature = [];
        $phpDoc = [];

        foreach ($crossRelation->getRelations() as $relation) {
            $crossObjectName = '$' . $this->getRelationVarName($relation);
            $crossObjectClassName = $this
                ->getBuilder()
                ->getNewObjectBuilder($relation->getForeignEntity())
                ->getClassName();

            $names[] = $crossObjectClassName;
            $signatures[] = "$crossObjectClassName $crossObjectName"
                . ($relation->isAtLeastOneLocalFieldRequired() ? '' : ' = null');

            $shortSignature[] = $crossObjectName;
            $phpDoc[] = "
     * @param $crossObjectClassName $crossObjectName The object to relate";
        }

        $names = implode(', ', $names) . (1 < count($names) ? ' combination' : '');
        $phpDoc = implode($phpDoc);
        $signatures = implode(', ', $signatures);
        $shortSignature = implode(', ', $shortSignature);

        return [
            $names,
            $phpDoc,
            $signatures,
            $shortSignature
        ];
    }

    /**
     * @param  CrossRelation $crossRelation
     *
     * @return string
     */
    protected function getCrossRelationVarName(CrossRelation $crossRelation)
    {
        return lcfirst($this->getCrossRelationPhpName($crossRelation));
    }

    /**
     * @param  CrossRelation $crossRelation
     * @param  bool          $plural
     *
     * @return string
     */
    protected function getCrossRelationPhpName(CrossRelation $crossRelation, $plural = true)
    {
        $names = [];

        if ($plural) {
            if ($pks = $crossRelation->getUnclassifiedPrimaryKeys()) {
                //we have a non fk as pk as well, so we need to make pluralisation on our own and can't
                //rely on getRelationPhpName`s pluralisation
                foreach ($crossRelation->getRelations() as $relation) {
                    $names[] = $this->getRelationPhpName($relation, false);
                }
            } else {
                //we have only fks, so give us names with plural and return those
                $lastIdx = count($crossRelation->getRelations()) - 1;
                foreach ($crossRelation->getRelations() as $idx => $relation) {
                    $needPlural = $idx === $lastIdx; //only last fk should be plural
                    $names[] = $this->getRelationPhpName($relation, $needPlural);
                }

                return implode($names);
            }
        } else {
            // no plural, so $plural=false
            foreach ($crossRelation->getRelations() as $relation) {
                $names[] = $this->getRelationPhpName($relation, false);
            }
        }

        foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName();
        }

        $name = implode($names);

        return (true === $plural ? $this->getBuilder()->getPluralizer()->getPluralForm($name) : $name);
    }
}