<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Behavior to adds nested set tree structure fields and abilities
 *
 * @author François Zaninotto
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class NestedSetBehavior extends Behavior
{
    use ComponentTrait;
    
    // default parameters value
    protected $parameters = array(
        'left_field'       => 'tree_left',
        'right_field'      => 'tree_right',
        'level_field'      => 'tree_level',
        'use_scope'         => 'false',
        'scope_field'      => 'tree_scope',
        'method_proxies'    => 'false'
    );

    public function __construct()
    {
        $this->additionalBuilders[] = '\Propel\Generator\Behavior\NestedSet\NestedManagerBuilder';

        parent::__construct();
    }

    /**
     * Add the left, right, level and scope properties to the current entity
     */
    public function modifyEntity()
    {
        $entity = $this->getEntity();

        if (!$entity->hasField($this->getParameter('left_field'))) {
            $entity->addField(array(
                'name' => $this->getParameter('left_field'),
                'type' => 'INTEGER'
            ));
        }

        if (!$entity->hasField($this->getParameter('right_field'))) {
            $entity->addField(array(
                'name' => $this->getParameter('right_field'),
                'type' => 'INTEGER'
            ));
        }

        if (!$entity->hasField($this->getParameter('level_field'))) {
            $entity->addField(array(
                'name' => $this->getParameter('level_field'),
                'type' => 'INTEGER'
            ));
        }

        if ('true' === $this->getParameter('use_scope') && !$entity->hasField($this->getParameter('scope_field'))) {
            $entity->addField(array(
                'name' => $this->getParameter('scope_field'),
                'type' => 'INTEGER'
            ));
        }
    }

    public function getFieldAttribute($name)
    {
        return strtolower($this->getFieldForParameter($name)->getName());
    }

    public function useScope()
    {
        return 'true' === $this->getParameter('use_scope');
    }

    public function objectBuilderModification(ObjectBuilder $builder)
    {
        $this->applyComponent('Getters', $builder);
        $this->applyComponent('Setters', $builder);
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        $this->applyComponent('Query\Filters', $builder);
        $this->applyComponent('Query\Orders', $builder);
        $this->applyComponent('Query\Terminations', $builder);
        $this->applyComponent('Query\UseStatements', $builder);
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $this->applyComponent('Repository\AddNestedSetQueryMethod', $builder);
        $this->applyComponent('Repository\Attributes', $builder);
        $this->applyComponent('Repository\FixLevelsMethod', $builder);
        $this->applyComponent('Repository\GetNestedManagerMethod', $builder);
        $this->applyComponent('Repository\MakeRoomForLeafMethod', $builder);
        $this->applyComponent('Repository\NestedSetEntityPoolMethods', $builder);
        $this->applyComponent('Repository\ProcessNestedSetQueriesMethod', $builder);
        $this->applyComponent('Repository\ShiftLevelMethod', $builder);
        $this->applyComponent('Repository\ShiftLRValuesMethod', $builder);
        $this->applyComponent('Repository\ShiftLevelMethod', $builder);
        $this->applyComponent('Repository\UpdateLoadedNodesMethod', $builder);
        $this->applyComponent('Repository\UseStatements', $builder);

        if($this->useScope()) {
            $this->applyComponent('Repository\SetNegativeScopeMethod', $builder);
        }
    }

    public function activeRecordTraitBuilderModification(ActiveRecordTraitBuilder $builder)
    {
        $this->applyComponent('ActiveRecordTrait\Counters', $builder);
        $this->applyComponent('ActiveRecordTrait\DeleteDescendantsMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\Getters', $builder);
        $this->applyComponent('ActiveRecordTrait\GetIteratorMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\Hassers', $builder);
        $this->applyComponent('ActiveRecordTrait\Inserts', $builder);
        $this->applyComponent('ActiveRecordTrait\Issers', $builder);
        $this->applyComponent('ActiveRecordTrait\MakeRootMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\Movers', $builder);
        $this->applyComponent('ActiveRecordTrait\UseStatements', $builder);
    }

    public function entityMapBuilderModification(EntityMapBuilder $builder)
    {
        $this->applyComponent('EntityMap\Constants', $builder);
    }

    public function preSave(RepositoryBuilder $builder)
    {
        return $this->applyComponent('Repository\PreSave', $builder);
    }

    public function postSave(RepositoryBuilder $builder)
    {
        return $this->applyComponent('Repository\PostSave', $builder);
    }

    public function preDelete(RepositoryBuilder $builder)
    {
        return $this->applyComponent('Repository\PreDelete', $builder);
    }

    public function postDelete(RepositoryBuilder $builder)
    {
        return $this->applyComponent('Repository\PostDelete', $builder);
    }
}
