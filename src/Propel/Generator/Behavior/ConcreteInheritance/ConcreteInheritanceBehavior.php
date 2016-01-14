<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\ConcreteInheritance;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Relation;

/**
 * Makes a model inherit another one. The model with this behavior gets a copy
 * of the structure of the parent model. In addition, both the ActiveRecord and
 * ActiveQuery classes will extend the related classes of the parent model.
 * Lastly (an optionally), the data from a model with this behavior is copied
 * to the parent model.
 *
 * @author François Zaninotto
 */
class ConcreteInheritanceBehavior extends Behavior
{
    use ComponentTrait;

    // default parameters value
    protected $parameters = array(
        'extends' => '',
        'copy_data_to_parent' => 'true',
        'descendant_field'   => 'descendantClass',
    );

    public function modifyEntity()
    {
        $entity = $this->getEntity();
        $parentEntity = $this->getParentEntity();

        if ($this->isCopyData()) {
            // tell the parent table that it has a descendant
            if (!$parentEntity->hasBehavior('concrete_inheritance_parent')) {
                $parentBehavior = new ConcreteInheritanceParentBehavior();
                $parentBehavior->setName('concrete_inheritance_parent');
                $parentBehavior->addParameter(array('name' => 'descendant_field', 'value' => $this->getParameter('descendant_field')));

                $parentEntity->addBehavior($parentBehavior);
                // The parent table's behavior modifyTable() must be executed before this one
                $parentBehavior->getEntityModifier()->modifyEntity();
                $parentBehavior->setEntityModified(true);
            }
        }

        // Add the fields of the parent entity
        foreach ($parentEntity->getFields() as $field) {
            if ($field->getName() == $this->getParameter('descendant_field')) {
                continue;
            }

            if ($entity->hasField($field->getName())) {
                continue;
            }
            $copiedField = clone $field;
            $copiedField->setSkipCodeGeneration(true);
            if ($field->isAutoIncrement() && $this->isCopyData()) {
                $copiedField->setAutoIncrement(false);
            }
            $entity->addField($copiedField);

            //add a 1-to-1 relation to parent
            if ($field->isPrimaryKey() && $this->isCopyData()) {
                $relation = new Relation();
                $relation->setForeignEntityName($field->getEntity()->getName());
                $relation->setOnDelete('CASCADE');
                $relation->setOnUpdate('CASCADE');
                $relation->addReference($copiedField, $field);
                $entity->concreteParentRelation = $relation;
                $entity->addRelation($relation);
            }
        }

        // add the relations of the parent entity
        foreach ($parentEntity->getRelations() as $relation) {
            $copiedFk = clone $relation;
            $copiedFk->setName('');
            $copiedFk->setRefField('');
            $copiedFk->setSkipCodeGeneration(true);
            $this->getEntity()->addRelation($copiedFk);
        }

        // add the indices of the parent entity
        foreach ($parentEntity->getIndices() as $index) {
            $copiedIndex = clone $index;
            $copiedIndex->setName('');
            $this->getEntity()->addIndex($copiedIndex);
        }

        // add the unique indices of the parent entity
        foreach ($parentEntity->getUnices() as $unique) {
            $copiedUnique = clone $unique;
            $copiedUnique->setName('');
            $this->getEntity()->addUnique($copiedUnique);
        }
    }

    public function getParentEntity()
    {
        $database = $this->getEntity()->getDatabase();
        $entityName = $this->getParameter('extends');

        if (!$entity = $database->getEntity($entityName)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entity "%s" used in the concrete_inheritance behavior at entity "%s" not exist.',
                    $entityName,
                    $this->getEntity()->getName()
                )
            );
        }

        return $entity;
    }

    protected function isCopyData()
    {
        return 'true' === $this->getParameter('copy_data_to_parent');
    }

    public function preSave(RepositoryBuilder $repositoryBuilder)
    {
        if ($this->isCopyData()) {
            return $this->applyComponent('PreSave', $repositoryBuilder, $this);
        }
    }

    public function postSave(RepositoryBuilder $repositoryBuilder)
    {
        $entityClass = $this->getParentEntity()->getFullClassName();

        if ($this->isCopyData()) {

            $getter = 'get' . $this->getParentEntity()->getName();

            $code = <<<EOF
\$session = \$this->getConfiguration()->getSession();
\$parentRepository = \$this->getConfiguration()->getRepository('$entityClass');
foreach (\$event->getEntities() as \$entity) {
    \$parent = \$entity->$getter();

    \$parentRepository->getEntityMap()->copyInto(\$parent, \$entity);
}
EOF;

            return $code;
        }
    }

    public function preDelete(RepositoryBuilder $repositoryBuilder  )
    {
        if ($this->isCopyData()) {
            $getter = 'get' . $this->getParentEntity()->getName();

            $code = <<<EOF
\$parentRepository = \$this->getConfiguration()->getRepository('%s');
\$session = \$this->getConfiguration()->getSession();

foreach (\$event->getEntities() as \$entity) {
    if (!\$session->isNew(\$entity)) {
        \$parent = \$entity->$getter();
        \$session->remove(\$parent);
    }
}
EOF;

            return sprintf($code, $this->getParentEntity()->getFullClassName());
        }
    }

    public function objectBuilderModification(ObjectBuilder $builder)
    {
        $builder->getDefinition()->setParentClassName('\\' . $this->getParentEntity()->getFullClassName());

        if (!isset($this->getEntity()->concreteParentRelation)) {
            return;
        }

        $this->applyComponent('OverwritePrimaryKeyGetter', $builder, $this, $this->getEntity()->concreteParentRelation);
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        $builder->getDefinition()->setParentClassName(
            '\\' . $builder->getNewStubQueryBuilder($this->getParentEntity())->getFullClassName()
        );
    }
}
