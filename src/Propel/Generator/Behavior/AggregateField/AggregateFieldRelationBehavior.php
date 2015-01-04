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
 * Keeps an aggregate column updated with related table
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

    public function postSave($builder)
    {
        $this->builder = $builder;

        $relationName = $this->getRelationName();
        $aggregateName = $this->getParameter('aggregate_name');

        return "\$this->updateRelated{$relationName}{$aggregateName}(\$con);";
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
//        $this->applyComponent('RelationObject\\Attribute', $builder);
        $this->applyComponent('RelatedRepository\\FindRelatedMethod', $builder);
        $this->applyComponent('RelatedRepository\\UpdateRelatedMethod', $builder);
    }

    // no need for a postDelete() hook, since delete() uses Query::delete(),
    // which already has a hook

//    public function objectFilter(&$script, $builder)
//    {
//        $relationName = $this->getRelationName($builder);
//        $aggregateName = $this->getParameter('aggregate_name');
//        $relatedClass = $builder->getClassNameFromBuilder($builder->getNewStubObjectBuilder($this->getForeignTable()));
//        $search = "    public function set{$relationName}({$relatedClass} \$v = null)
//    {";
//        $replace = $search . "
//        // aggregate_column_relation behavior
//        if (null !== \$this->a{$relationName} && \$v !== \$this->a{$relationName}) {
//            \$this->old{$relationName}{$aggregateName} = \$this->a{$relationName};
//        }";
//        $script = str_replace($search, $replace, $script);
//    }

    public function preUpdate($builder)
    {
        return $this->getFindRelated($builder);
    }

    public function preDelete($builder)
    {
        return $this->getFindRelated($builder);
    }

    protected function getFindRelated($builder)
    {
        $this->builder = $builder;
        $relationName = $this->getRelationName();
        $aggregateName = $this->getParameter('aggregate_name');

        return "\$this->findRelated{$relationName}{$aggregateName}s();";
    }

    public function postUpdate($builder)
    {
        return $this->getUpdateRelated($builder);
    }

    public function postDelete($builder)
    {
        return $this->getUpdateRelated($builder);
    }

    protected function getUpdateRelated($builder)
    {
        $this->builder = $builder;
        $relationName = $this->getRelationName();
        $aggregateName = $this->getParameter('aggregate_name');

        return "\$this->updateRelated{$relationName}{$aggregateName}s(\$con);";
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

    public function getRelationName()
    {
        return $this->getRelationPhpName($this->getRelation());
    }
}
