<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 13.10.14
 * Time: 02:32
 */

namespace Propel\Generator\Builder\Om\Component;


use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Relation;

trait RelationTrait
{
    /**
     * @return AbstractBuilder
     */
    abstract protected function getBuilder();

    /**
     * Gets the PHP name for the given relation.
     *
     * @param  Relation $relation The local Relation that we need a name for.
     * @param  boolean  $plural   Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     *
     * @return string
     */
    public function getRelationPhpName(Relation $relation, $plural = false)
    {
        if ($relation->getField()) {
            if ($plural) {
                return ucfirst($this->getBuilder()->getPluralizer()->getPluralForm($relation->getField()));
            }

            return ucfirst($relation->getField());
        }

        if ($relation->hasName()) {
            if ($plural) {
                return ucfirst($this->getBuilder()->getPluralizer()->getPluralForm($relation->getName()));
            }

            return ucfirst($relation->getName());
        }

        $className = $relation->getForeignEntity()->getName();
        if ($plural) {
            $className = $this->getBuilder()->getPluralizer()->getPluralForm($className);
        }

        return ucfirst($className . $this->getRelatedBySuffix($relation));
    }

    /**
     * Convenience method to get the default Join Type for a relation.
     * If the key is required, an INNER JOIN will be returned, else a LEFT JOIN will be suggested,
     * unless the schema is provided with the DefaultJoin attribute, which overrules the default Join Type
     *
     * @param  Relation $relation
     * @return string|PhpConstant
     */
    protected function getJoinType(Relation $relation)
    {
        if ($defaultJoin = $relation->getDefaultJoin()) {
            return "'" . $defaultJoin . "'";
        }

        if ($relation->isLocalFieldsRequired()) {
            return PhpConstant::create('Criteria::INNER_JOIN');
        }

        return PhpConstant::create('Criteria::LEFT_JOIN');
    }

    /**
     * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
     *
     * The related by suffix is based on the local fields of the foreign key.  If there is more than
     * one field in a entity that points to the same foreign entity, then a 'RelatedByLocalColName' suffix
     * will be appended.
     *
     * @param Relation $relation
     *
     * @throws BuildException
     * @return string
     */
    protected function getRelatedBySuffix(Relation $relation)
    {
        $relField = '';
        foreach ($relation->getLocalForeignMapping() as $localFieldName => $foreignFieldName) {
            $localEntity = $relation->getEntity();
            $localField = $localEntity->getField($localFieldName);
            if (!$localField) {
                throw new BuildException(
                    sprintf('Could not fetch field: %s in entity %s.', $localFieldName, $localEntity->getName())
                );
            }

            if (count($localEntity->getRelationsReferencingEntity($relation->getForeignEntityName())) > 1
                || count($relation->getForeignEntity()->getRelationsReferencingEntity($relation->getEntityName())) > 0
                || $relation->getForeignEntityName() == $relation->getEntityName()
            ) {
                // self referential foreign key, or several foreign keys to the same entity, or cross-reference fkey
                $relField .= $localField->getName();
            }
        }

        if (!empty($relField)) {
            $relField = 'RelatedBy' . $relField;
        }

        return $relField;
    }

    /**
     * Constructs variable name for fkey-related objects.
     *
     * @param  Relation $relation
     * @param  boolean  $plural
     *
     * @return string
     */
    public function getRelationVarName(Relation $relation, $plural = false)
    {
        return lcfirst($this->getRelationPhpName($relation, $plural));
    }

    /**
     * @param Relation $relation
     * @param bool     $plural
     *
     * @return string
     */
    public function getRefRelationVarName(Relation $relation, $plural = false)
    {
        return lcfirst($this->getRefRelationPhpName($relation, $plural));
    }

    /**
     * Constructs variable name for single object which references current entity by specified foreign key
     * which is ALSO a primary key (hence one-to-one relationship).
     *
     * @param  Relation $relation
     *
     * @return string
     */
    public function getPKRefRelationVarName(Relation $relation)
    {
        return lcfirst($this->getRefRelationPhpName($relation, false));
    }

    /**
     * Gets the PHP  name affix to be used for referencing relation.
     *
     * @param  Relation $relation The referrer Relation that we need a name for.
     * @param  boolean  $plural   Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     *
     * @return string
     */
    public function getRefRelationPhpName(Relation $relation, $plural = false)
    {
        $pluralizer = $this->getBuilder()->getPluralizer();
        if ($relation->getRefField()) {
            return ucfirst($plural ? $pluralizer->getPluralForm($relation->getRefField()) : $relation->getRefField());
        }

        $className = $relation->getEntity()->getName();
        if ($plural) {
            $className = $pluralizer->getPluralForm($className);
        }

        //since we have no refField name we need to generate one based on the entity.
        //this can go wrong when we have two relations to the same entity, as it would generate
        //same relation name again. so we need to affix the name with the actual $relation->getField()
        //this is what getRefRelatedBySuffix is doing.

        return ucfirst($className . $this->getRefRelatedBySuffix($relation));
    }

    /**
     * Returns a prefix 'RelatedBy*' if needed.
     *
     * @param Relation $relation
     *
     * @return string
     */
    protected static function getRefRelatedBySuffix(Relation $relation)
    {
        $hasOtherRelationToSameEntityAndName = false;
        foreach ($relation->getEntity()->getRelations() as $otherRelation) {
            if ($otherRelation !== $relation && $otherRelation->getForeignEntity() === $relation->getForeignEntity()) {
                $hasOtherRelationToSameEntityAndName = true;
                break;
            }
        }

        if (!$hasOtherRelationToSameEntityAndName) {
            return '';
        }

        return 'By' . ucfirst($relation->getField());
    }

    /**
     * Constructs variable name for objects which referencing current entity by specified foreign key.
     *
     * @param  Relation $relation
     *
     * @return string
     */
    public function getRefRelationCollVarName(Relation $relation)
    {
        return lcfirst($this->getRefRelationPhpName($relation, true));
    }
} 