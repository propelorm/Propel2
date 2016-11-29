<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

/**
 * A class for information about entity cross relations which are used in many-to-many relations.
 *
 *
 *    ___CrossEntity1___                                   ___User___
 *   |  PK1 userId     |----------FK1------------------->|  id      |
 *   |                 |                    _Group__     |  name    |
 *   |  PK2 groupId    |-----+----FK2----->|  id    |    |__________|
 *   |                 |    /           \->|  id2   |
 *   |  PK3 relationId |   /               |  name  |
 *   |                 |  /                |________|
 *   |  PK4 groupId2   |-/
 *   |_________________|
 *
 *
 *    User->getCrossRelations():
 *      0:
 *         getEntity()                   -> User
 *         getRelations()        -> [FK2]
 *         getMiddleEntity()             -> CrossEntity1
 *         getIncomingRelation()      -> FK1
 *         getUnclassifiedPrimaryKeys() -> [PK3]
 *
 *    Group->getCrossRelations():
 *      0:
 *         getEntity()                   -> Group
 *         getRelations()        -> [FK1]
 *         getMiddleEntity()             -> CrossEntity1
 *         getIncomingRelation()      -> FK2
 *         getUnclassifiedPrimaryKeys() -> [PK3]
 */
class CrossRelation
{
    /**
     * The middle-entity.
     *
     * @var Entity
     */
    protected $entity;

    /**
     * The target entity (which has crossRef=true).
     *
     * @var Entity
     */
    protected $middleEntity;

    /**
     * All other outgoing relations from the middle-entity to other $entities.
     *
     * @var Relation[]
     */
    protected $relations = [];

    /**
     * The incoming foreign key from the middle-entity to this entity.
     *
     * @var Relation
     */
    protected $incomingRelation;

    /**
     * @param Relation $relation
     * @param Entity $crossEntity
     */
    public function __construct(Relation $relation, Entity $crossEntity)
    {
        $this->setIncomingRelation($relation);
        $this->setEntity($crossEntity);
    }

    /**
     * @param Relation $relation
     */
    public function setIncomingRelation($relation)
    {
        $this->setMiddleEntity($relation ? $relation->getEntity() : null);
        $this->incomingRelation = $relation;
    }

    /**
     * The relation from the middle-entity to the left entity.
     *
     * @return Relation
     */
    public function getIncomingRelation()
    {
        return $this->incomingRelation;
    }

    /**
     * In a cross relation setup, the foreign entity is always the first relation (of getRelations())
     *
     * @return Entity
     */
    public function getForeignEntity()
    {
        return $this->getOutgoingRelation()->getForeignEntity();
    }

    /**
     * Returns true if at least one of the local columns of $fk is not already covered by another
     * relation in our collection (getRelations)
     *
     * E.g.
     *
     * entity (local primary keys -> relation):
     *
     *   pk1  -> FK1
     *   pk2
     *      \
     *        -> FK2
     *      /
     *   pk3  -> FK3
     *      \
     *        -> FK4
     *      /
     *   pk4
     *
     *  => FK1(pk1), FK2(pk2, pk3), FK3(pk3), FK4(pk3, pk4).
     *
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK1) where none fks in our collection: true
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK2) where FK1 is in our collection: true
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK3) where FK1,FK2 is in our collection: false
     *  isAtLeastOneLocalPrimaryKeyNotCovered(FK4) where FK1,FK2 is in our collection: true
     *
     * @param  Relation $fk
     * @return bool
     */
    public function isAtLeastOneLocalPrimaryKeyNotCovered(Relation $fk)
    {
        $primaryKeys = $fk->getLocalPrimaryKeys();
        foreach ($primaryKeys as $primaryKey) {
            $covered = false;
            foreach ($this->getRelations() as $crossFK) {
                if ($crossFK->hasLocalField($primaryKey)) {
                    $covered = true;
                    break;
                }
            }
            //at least one is not covered, so return true
            if (!$covered) {
                return true;
            }
        }

        return false;
    }

    /**
     * This relation is polymorphic when we have more than the primary keys that are necessary
     * to links left and right entity. Those additional primary keys can be another relation
     * or a simple primary key like a 'type'.
     *
     * @return bool
     */
    public function isPolymorphic()
    {
        return 1 < count($this->getRelations()) || $this->getUnclassifiedPrimaryKeys();
    }

    /**
     * Returns all primary keys of middle-entity which are not already covered by at least on of our cross relation collection.
     *
     * @return Field[]
     */
    public function getUnclassifiedPrimaryKeys()
    {
        $pks = [];
        foreach ($this->getMiddleEntity()->getPrimaryKey() as $pk) {
            //required
            $unclassified = true;
            if ($this->getIncomingRelation()->hasLocalField($pk)) {
                $unclassified = false;
            }
            if ($unclassified) {
                foreach ($this->getRelations() as $crossFK) {
                    if ($crossFK->hasLocalField($pk)) {
                        $unclassified = false;
                        break;
                    }
                }
            }
            if ($unclassified) {
                $pks[] = $pk;
            }
        }

        return $pks;
    }

    /**
     * @return string[]
     */
    public function getUnclassifiedPrimaryKeyNames()
    {
        $names = [];
        foreach ($this->getUnclassifiedPrimaryKeys() as $primaryKey) {
            $names[] = $primaryKey->getName();
        }

        return $names;
    }

    /**
     * @param Relation $relation
     */
    public function addRelation(Relation $relation)
    {
        $this->relations[] = $relation;
    }

    /**
     * @return bool
     */
    public function hasRelations()
    {
        return !!$this->relations;
    }

    /**
     * @param Relation[] $relations
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;
    }

    /**
     * All other outgoing relations from the middle-entity to other $entities.
     *
     * @return Relation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Returns the first outgoing relation.
     *
     * Note: When this cross relation has more than one relation, it becomes a polymorphic relation. This means
     * that this method returns the relation from the middle-entity to the right entity.
     *
     * @return Relation
     */
    public function getOutgoingRelation()
    {
        return $this->relations[0];
    }

    /**
     * @param Entity $foreignEntity
     */
    public function setMiddleEntity(Entity $foreignEntity)
    {
        $this->middleEntity = $foreignEntity;
    }

    /**
     * The middle entity (which has crossRef=true).
     *
     * @return Entity
     */
    public function getMiddleEntity()
    {
        return $this->middleEntity;
    }

    /**
     * @param Entity $entity
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * The source entity.
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

}
