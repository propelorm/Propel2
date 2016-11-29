<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 13.10.14
 * Time: 02:20
 */

namespace Propel\Generator\Builder\Om\Component;

use gossi\codegen\model\PhpParameter;
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
     * @deprecated use getRelationVarName instead with $crossRelation->getOutgoingRelation()
     *
     * @return string
     */
    protected function getCrossRelationVarName(CrossRelation $crossRelation, $plural = true)
    {
        return lcfirst($this->getCrossRelationPhpName($crossRelation, $plural));
    }

    /**
     * @param  CrossRelation $crossRelation
     * @param  bool          $plural
     *
     * @deprecated use getRelationPhpName instead with $crossRelation->getOutgoingRelation()
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

    /**
     * Returns the relation name for a relation of a CrossRelation.
     *
     * @param Relation $relation
     *
     * @return string
     */
    protected function getCrossRelationRelationVarName(Relation $relation)
    {
        return $this->getRelationVarName($relation, true);
    }

    /**
     * @param  CrossRelation $crossRelation
     * @param  Relation      $excludeRelation
     *
     * @return string
     */
    protected function getCrossRefRelationGetterName(CrossRelation $crossRelation, Relation $excludeRelation)
    {
        $names = [];

        $fks = $crossRelation->getRelations();

        foreach ($crossRelation->getMiddleEntity()->getRelations() as $relation) {
            if ($relation !== $excludeRelation && ($relation === $crossRelation->getIncomingRelation() || in_array(
                        $relation,
                        $fks
                    ))
            ) {
                $names[] = $this->getRelationPhpName($relation, false);
            }
        }

        foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName();
        }

        $name = implode($names);

        return $this->getBuilder()->getPluralizer()->getPluralForm($name);
    }

    /**
     * Returns a function signature comma separated.
     *
     * @param  CrossRelation $crossRelation
     * @param  string        $excludeSignatureItem Which variable to exclude.
     *
     * @return string
     */
    protected function getCrossFKGetterSignature(CrossRelation $crossRelation, $excludeSignatureItem)
    {
        list (, $getSignature) = $this->getCrossRelationAddMethodInformation($crossRelation);
        $getSignature = explode(', ', $getSignature);

        if (false !== ($pos = array_search($excludeSignatureItem, $getSignature))) {
            unset($getSignature[$pos]);
        }

        return implode(', ', $getSignature);
    }

    /**
     * @param  CrossRelation $crossRelation
     * @param  Relation      $excludeRelation
     *
     * @return string
     */
    protected function getCrossRefFKRemoveObjectNames(CrossRelation $crossRelation, Relation $excludeRelation)
    {
        $names = [];

        $fks = $crossRelation->getRelations();

        foreach ($crossRelation->getMiddleEntity()->getRelations() as $relation) {
            if ($relation !== $excludeRelation && ($relation === $crossRelation->getIncomingRelation() || in_array(
                        $relation,
                        $fks
                    ))
            ) {
                if ($relation === $crossRelation->getIncomingRelation()) {
                    $names[] = '$this';
                } else {
                    $names[] = '$' . lcfirst($this->getRelationPhpName($relation, false));
                }
            }
        }

        foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = '$' . lcfirst($pk->getName());
        }

        return implode(', ', $names);
    }

    /**
     * Extracts some useful information from a CrossForeignKeys object.
     *
     * @param CrossRelation  $crossRelation
     * @param array|Relation $relationToIgnore
     * @param PhpParameter[] $signature
     * @param array          $shortSignature
     * @param array          $normalizedShortSignature
     * @param array          $phpDoc
     */
    protected function extractCrossInformation(
        CrossRelation $crossRelation,
        $relationToIgnore = null,
        &$signature,
        &$shortSignature,
        &$normalizedShortSignature,
        &$phpDoc
    ) {
        foreach ($crossRelation->getRelations() as $fk) {
            if (is_array($relationToIgnore) && in_array($fk, $relationToIgnore)) {
                continue;
            } else {
                if ($fk === $relationToIgnore) {
                    continue;
                }
            }

            $phpType = $typeHint = $this->getClassNameFromEntity($fk->getForeignEntity());
            $name = '$' . lcfirst($this->getRelationPhpName($fk));

            $normalizedShortSignature[] = $name;

            $parameter = new PhpParameter(lcfirst($this->getRelationPhpName($fk)));
            if ($typeHint) {
                $parameter->setType($typeHint);
            }
            $signature[] = $parameter;

            $shortSignature[] = $name;
            $phpDoc[] = "
     * @param $phpType $name";
        }

        foreach ($crossRelation->getUnclassifiedPrimaryKeys() as $primaryKey) {
            //we need to add all those $primaryKey s as additional parameter as they are needed
            //to create the entry in the middle-entity.
            $defaultValue = $primaryKey->getDefaultValueString();

            $phpType = $primaryKey->getPhpType();
            $typeHint = $primaryKey->isPhpArrayType() ? 'array' : '';
            $name = '$' . lcfirst($primaryKey->getName());

            $normalizedShortSignature[] = $name;


            $parameter = new PhpParameter(lcfirst($primaryKey->getName()));
            if ($typeHint) {
                $parameter->setType($typeHint);
            }
            if ('null' !== $defaultValue) {
                $parameter->setDefaultValue($defaultValue);
            }
            $signature[] = $parameter;

            $shortSignature[] = $name;
            $phpDoc[] = "
     * @param $phpType $name";
        }
    }

    /**
     * @param  CrossRelation  $crossRelation
     * @param  array|Relation $relation will be the first variable defined
     *
     * @return array [$signature, $shortSignature, $normalizedShortSignature, $phpDoc]
     */
    protected function getCrossRelationAddMethodInformation(CrossRelation $crossRelation, $relation = null)
    {
        if ($relation instanceof Relation) {
            $crossObjectName = '$' . $this->getRelationVarName($relation);
            $crossObjectClassName = $this->getClassNameFromEntity($relation->getForeignEntity());

            $parameter = new PhpParameter($this->getRelationVarName($relation));
            $parameter->setType($crossObjectClassName);
            if ($relation->isAtLeastOneLocalFieldRequired()) {
                $parameter->setDefaultValue(null);
            }
            $signature[] = $parameter;

            $shortSignature[] = $crossObjectName;
            $normalizedShortSignature[] = $crossObjectName;
            $phpDoc[] = "
     * @param $crossObjectClassName $crossObjectName";
        }

        $this->extractCrossInformation(
            $crossRelation,
            $relation,
            $signature,
            $shortSignature,
            $normalizedShortSignature,
            $phpDoc
        );

        $shortSignature = implode(', ', $shortSignature);
        $normalizedShortSignature = implode(', ', $normalizedShortSignature);
        $phpDoc = implode(', ', $phpDoc);

        return [$signature, $shortSignature, $normalizedShortSignature, $phpDoc];
    }
}