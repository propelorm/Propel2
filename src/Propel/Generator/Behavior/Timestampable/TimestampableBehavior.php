<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Timestampable;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Gives a model class the ability to track creation and last modification dates
 * Uses two additional fields storing the creation and update date
 *
 * @author François Zaninotto
 */
class TimestampableBehavior extends Behavior
{
    use ComponentTrait;

    protected $parameters = [
        'create_field' => 'createdAt',
        'update_field' => 'updatedAt',
        'disable_created_at' => 'false',
        'disable_updated_at' => 'false',
    ];

    public function withUpdatedAt()
    {
        return !$this->booleanValue($this->getParameter('disable_updated_at'));
    }

    public function withCreatedAt()
    {
        return !$this->booleanValue($this->getParameter('disable_created_at'));
    }

    /**
     * Add the create_field and update_fields to the current entity
     */
    public function modifyEntity()
    {
        $entity = $this->getEntity();

        if ($this->withCreatedAt() && !$entity->hasField($this->getParameter('create_field'))) {
            $entity->addField(
                array(
                    'name' => $this->getParameter('create_field'),
                    'type' => 'TIMESTAMP'
                )
            );
        }
        if ($this->withUpdatedAt() && !$entity->hasField($this->getParameter('update_field'))) {
            $entity->addField(
                array(
                    'name' => $this->getParameter('update_field'),
                    'type' => 'TIMESTAMP'
                )
            );
        }
    }

    public function preUpdate(RepositoryBuilder $repositoryBuilder)
    {
        if ($this->withUpdatedAt()) {
            $field = $this->getEntity()->getField($this->getParameter('update_field'))->getName();

            return "
\$writer = \$this->getEntityMap()->getPropWriter();

foreach (\$event->getEntities() as \$entity) {
    if (!\$this->getEntityMap()->isFieldModified(\$entity, '$field')) {
        \$writer(\$entity, '$field', \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision());
    }
}
            ";
        }
    }

    public function preInsert(RepositoryBuilder $repositoryBuilder)
    {
        $script = "\$writer = \$this->getEntityMap()->getPropWriter();

foreach (\$event->getEntities() as \$entity) {
";


        if ($this->withCreatedAt()) {
            $createdAtField = $this->getEntity()->getField($this->getParameter('create_field'))->getName();
            $script .= "
    if (!\$this->getEntityMap()->isFieldModified(\$entity, '$createdAtField')) {
        \$writer(\$entity, '$createdAtField', \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision());
    }";
        }

        if ($this->withUpdatedAt()) {
            $updatedAtField = $this->getEntity()->getField($this->getParameter('update_field'))->getName();
            $script .= "
    if (!\$this->getEntityMap()->isFieldModified(\$entity, '$updatedAtField')) {
        \$writer(\$entity, '$updatedAtField', \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision());
    }";
        }

        $script .= "
}";

        return $script;
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        $this->applyComponent('Query\\FilterMethods', $builder);
    }
}
