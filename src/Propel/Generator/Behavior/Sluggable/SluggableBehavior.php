<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sluggable;

use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Unique;

/**
 * Adds a slug field
 *
 * @author Francois Zaninotto
 * @author Massimiliano Arione
 */
class SluggableBehavior extends Behavior
{
    use ComponentTrait;

    protected $parameters = [
        'slug_field' => 'slug',
        'slug_pattern' => '',
        'replace_pattern' => '/\W+/',
        'replacement' => '-',
        'separator' => '-',
        'permanent' => 'false',
        'scope_field' => '',
    ];

    /**
     * Adds the slug_field to the current entity.
     */
    public function modifyEntity()
    {
        $entity = $this->getEntity();

        //Search a primary string

        if (null === $this->getPrimaryStringFieldName() && '' === $this->getParameter('slug_pattern')) {
            throw new BuildException('Sluggable behavior requires the entity has one primary string at least when no slug_pattern is set, to calculate the slug.');
        }

        if (!$entity->hasField($this->getParameter('slug_field'))) {
            $entity->addField(
                [
                    'name' => $this->getParameter('slug_field'),
                    'type' => 'VARCHAR',
                    'size' => 255,
                    'required' => false,
                ]
            );

            // add a unique to field
            $unique = new Unique($this->getFieldForParameter('slug_field'));
            $unique->setName($entity->getTableName() . '_slug');
            $unique->addField($entity->getField($this->getParameter('slug_field')));
            if ($this->getParameter('scope_field')) {
                $unique->addField($entity->getField($this->getParameter('scope_field')));
            }
            $entity->addUnique($unique);
        }
    }

    public function preSave(RepositoryBuilder $repositoryBuilder)
    {
        return <<<'EOF'
$this->preSaveSluggable($event);
EOF;
    }

    public function objectBuilderModification(ObjectBuilder $builder)
    {
        if ('slug' !== $this->getParameter('slug_field')) {
            $this->applyComponent('GetSlugMethod', $builder);
            $this->applyComponent('SetSlugMethod', $builder);
        }
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $this->applyComponent('Repository\\PreSaveSluggableMethod', $builder);
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        if ($this->getParameter('slug_field') != 'slug') {
            $this->applyComponent('Query\\FilterBySlugMethod', $builder);
            $this->applyComponent('Query\\FindOneBySlugMethod', $builder);
            $this->applyComponent('Query\\OrderBySlugMethod', $builder);
        } elseif ($this->getParameter('scope_field') != '') {
            $this->applyComponent('Query\\FindOneBySlugMethod', $builder);
        }
    }

    public function getPrimaryStringFieldName()
    {
        foreach ($this->getEntity()->getFields() as $field) {
            if ($field->isPrimaryString()) {
                return $field->getName();
            }
        }

        return null;
    }
}
