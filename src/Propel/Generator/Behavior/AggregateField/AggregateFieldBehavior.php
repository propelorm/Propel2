<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateField;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Relation;

/**
 * Keeps an aggregate field updated with related entity
 *
 * @author François Zaninotto
 */
class AggregateFieldBehavior extends Behavior
{
    use ComponentTrait;

    // default parameters value
    protected $parameters = array(
        'name' => null,
        'expression' => null,
        'condition' => null,
        'foreign_entity' => null,
        'foreign_schema' => null,
    );

    /**
     * Multiple aggregates on the same entity is OK.
     *
     * @return bool
     */
    public function allowMultiple()
    {
        return true;
    }

    /**
     * Add the aggregate key to the current entity
     */
    public function modifyEntity()
    {
        $entity = $this->getEntity();
        if (!$fieldName = $this->getParameter('name')) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You must define a \'name\' parameter for the \'aggregate_field\' behavior in the \'%s\' entity',
                    $entity->getName()
                )
            );
        }

        // add the aggregate field if not present
        if (!$entity->hasField($fieldName)) {
            $entity->addField(
                array(
                    'name' => $fieldName,
                    'type' => 'INTEGER',
                )
            );
        }

        // add a behavior in the foreign entity to autoupdate the aggregate field
        $foreignEntity = $this->getForeignEntity();
        if (!$foreignEntity->hasBehavior('concrete_inheritance_parent')) {
            $relationBehavior = new AggregateFieldRelationBehavior();
            $relationBehavior->setName('aggregate_field_relation');
            $relationBehavior->setId('aggregate_field_relation_' . $this->getId());
            $relationBehavior->addParameter(array('name' => 'foreign_entity', 'value' => $entity->getName()));
            $relationBehavior->addParameter(array('name' => 'aggregate_name', 'value' => $this->getField()->getName()));
            $relationBehavior->addParameter(
                array('name' => 'update_method', 'value' => 'update' . $this->getField()->getName())
            );
            $foreignEntity->addBehavior($relationBehavior);
        }
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        if (!$this->getParameter('foreign_entity')) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You must define a \'foreign_entity\' parameter for the \'aggregate_field\' behavior in the \'%s\' entity',
                    $this->getEntity()->getName()
                )
            );
        }

        $this->applyComponent('Repository\\ComputeMethod', $builder);
        $this->applyComponent('Repository\\UpdateMethod', $builder);
    }

    public function getForeignEntity()
    {
        $database = $this->getEntity()->getDatabase();
        $entityName = $this->getParameter('foreign_entity');

        return $database->getEntity($entityName);
    }

    /**
     * @return Relation
     */
    public function getRelation()
    {
        $foreignEntity = $this->getForeignEntity();
        // let's infer the relation from the foreign entity
        $fks = $foreignEntity->getRelationsReferencingEntity($this->getEntity()->getName());
        if (!$fks) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You must define a foreign key to the \'%s\' entity in the \'%s\' entity to enable the \'aggregate_field\' behavior',
                    $this->getEntity()->getName(),
                    $foreignEntity->getName()
                )
            );
        }

        // FIXME doesn't work when more than one fk to the same entity
        return array_shift($fks);
    }

    public function getField()
    {
        return $this->getEntity()->getField($this->getParameter('name'));
    }
}
