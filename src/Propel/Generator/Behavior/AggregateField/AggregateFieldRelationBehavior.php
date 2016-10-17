<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\AggregateField;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\Component\RelationTrait;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Entity;
use Propel\Generator\Model\Relation;

/**
 * Keeps an aggregate field updated with related table
 *
 * @author François Zaninotto
 */
class AggregateFieldRelationBehavior extends Behavior
{
    use RelationTrait;
    use ComponentTrait;

    // default parameters value
    protected $parameters = array(
        'foreign_entity' => '',
        'update_method' => '',
        'aggregate_name' => '',
    );

    protected $builder;

    public function getBuilder()
    {
        return $this->builder;
    }

    public function allowMultiple()
    {
        return true;
    }

    public function postSave(RepositoryBuilder $builder)
    {
        $this->builder = $builder;

        $relationName = $this->getRelation()->getName();
        $aggregateName = ucfirst($this->getParameter('aggregate_name'));

        return "
\$this->updateRelated{$relationName}{$aggregateName}(\$event->getEntitiesToInsert());
\$this->updateRelated{$relationName}{$aggregateName}(\$event->getEntitiesToUpdate());
";
    }

    public function postDelete(RepositoryBuilder $builder)
    {
        $this->builder = $builder;

        $relationName = $this->getRelation()->getName();
        $aggregateName = ucfirst($this->getParameter('aggregate_name'));

        return "
\$this->updateRelated{$relationName}{$aggregateName}(\$event->getEntities());
";
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $this->applyComponent('RelatedRepository\\UpdateRelatedMethod', $builder);
    }

    /**
     * @return Entity
     */
    public function getForeignEntity()
    {
        return $this->getEntity()->getDatabase()->getEntity($this->getParameter('foreign_entity'));
    }

    /**
     * @return Relation
     */
    public function getRelation()
    {
        $foreignEntity = $this->getForeignEntity();
        // let's infer the relation from the foreign table
        $fks = $this->getEntity()->getRelationsReferencingEntity($foreignEntity->getName());

        // FIXME doesn't work when more than one fk to the same table
        return array_shift($fks);
    }
}
