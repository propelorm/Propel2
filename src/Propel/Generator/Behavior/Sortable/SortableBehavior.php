<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\ActiveRecordTraitBuilder;
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\EntityMapBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Gives a model class the ability to be ordered
 * Uses one additional Field storing the rank
 *
 * @author Massimiliano Arione
 * @version     $Revision$
 */
class SortableBehavior extends Behavior
{
    use ComponentTrait;

    // default parameters value
    protected $parameters = array(
        'rank_field'  => 'sortable_rank',
        'use_scope'    => 'false',
        'scope_field' => '',
    );

    public function __construct()
    {
        $this->additionalBuilders[] = '\Propel\Generator\Behavior\Sortable\SortableManagerBuilder';

        parent::__construct();
    }

    /**
     * Add the rank_field to the current table
     */
    public function modifyEntity()
    {
        $entity = $this->getEntity();

        if (!$entity->hasField($this->getParameter('rank_field'))) {
            $entity->addField(array(
                'name' => $this->getParameter('rank_field'),
                'type' => 'INTEGER'
            ));
        }

        if ($this->useScope()) {
            foreach ($this->getScopes() as $scopeFieldName) {
                if (!$entity->hasField($scopeFieldName)) {
                    $entity->addField(
                        array(
                            'name' => $scopeFieldName,
                            'type' => 'INTEGER'
                        )
                    );
                }
            }

            $scopes = $this->getScopes();
            if (0 === count($scopes)) {
                throw new \InvalidArgumentException(sprintf(
                    'The sortable behavior in `%s` needs a `scope_field` parameter.',
                    $this->getEntity()->getName()
                ));
            }
        }
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        if ('rank' !== $this->getParameter('rank_field')) {
            $this->applyComponent('Query\FilterByRankMethod', $builder);
            $this->applyComponent('Query\OrderByRankMethod', $builder);
        }

        if ('rank' !== $this->getParameter('rank_field') || $this->useScope()) {
            $this->applyComponent('Query\FindOneByRankMethod', $builder);
        }

        $this->applyComponent('Query\FindListMethod', $builder);

        // utilities
        $this->applyComponent('Query\GetMaxRankMethod', $builder);
        $this->applyComponent('Query\GetMaxRankArrayMethod', $builder);

        if ($this->useScope()) {
            $this->applyComponent('Query\InListMethod', $builder);
            $this->applyComponent('Query\FilterByNormalizedListScopeMethod', $builder);
        }
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $this->applyComponent('Repository\AddSortableQueryMethod', $builder);
        $this->applyComponent('Repository\Attributes', $builder);
        $this->applyComponent('Repository\GetSortableManagerMethod', $builder);
        $this->applyComponent('Repository\ProcessSortableQueriesMethod', $builder);
        $this->applyComponent('Repository\SortableShiftRankMethod', $builder);
    }

    public function preSave(RepositoryBuilder $repositoryBuilder)
    {
        return "\$this->processSortableQueries();";
    }

    public function preInsert(RepositoryBuilder $repositoryBuilder)
    {
        return $this->applyComponent('Repository\PreInsertMethod', $repositoryBuilder);
    }

    public function preDelete(RepositoryBuilder $repositoryBuilder)
    {
        return $this->applyComponent('Repository\PreDeleteMethod', $repositoryBuilder);
    }

    public function preUpdate(RepositoryBuilder $repositoryBuilder)
    {
        if ($this->useScope()) {
            return $this->applyComponent('Repository\PreUpdateMethod', $repositoryBuilder);
        }
    }

    public function objectBuilderModification(ObjectBuilder $builder)
    {
        if ('rank' !== $this->getParameter('rank_field')) {
            $this->applyComponent('Object\RankAccessorMethod', $builder);
        }

        if ($this->useScope()) {
            $this->applyComponent('Object\OldScope', $builder);

            if ('scope_value' !== $this->getParameter('scope_field')) {
                $this->applyComponent('Object\ScopeAccessorMethod', $builder);
            }
        }
    }

    public function entityMapBuilderModification(EntityMapBuilder $builder)
    {
        $this->applyComponent('EntityMap\Constants', $builder);
    }

    public function activeRecordTraitBuilderModification(ActiveRecordTraitBuilder $builder)
    {
        $this->applyComponent('ActiveRecordTrait\IsFirstMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\IsLastMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\GetNextMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\GetPreviousMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\InsertAtRankMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\InsertAtBottomMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\InsertAtTopMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\MoveDownMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\MoveToBottomMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\MoveToRankMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\MoveToTopMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\MoveUpMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\RemoveFromListMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\SwapWithMethod', $builder);
        $this->applyComponent('ActiveRecordTrait\UseStatements', $builder);
    }

    public function useScope()
    {
        return 'true' === $this->getParameter('use_scope');
    }

    /**
     * Generates the method argument signature, the appropriate phpDoc for @params,
     * the scope builder php code and the scope variable builder php code/
     *
     * @return array ($methodSignature, $scopeBuilder, $buildScopeVars)
     */
    public function generateScopePhp()
    {
        $methodSignature = [];
        $buildScope      = '';
        $buildScopeVars  = '';

        if ($this->hasMultipleScopes()) {

            $methodSignature = [];
            $buildScope      = [];
            $buildScopeVars  = [];

            foreach ($this->getScopes() as $idx => $scope) {

                $field = $this->getEntity()->getField($scope);
                $paramName  = 'scope' . $field->getName();

                $buildScope[]     = "    \$scope[] = \$$paramName;\n";
                $buildScopeVars[] = "    \$$paramName = \$scope[$idx];\n";
                $param = PhpParameter::create($paramName)
                    ->setType($field->getPhpType())
                    ->setDescription("Scope value for Field `{$field->getName()}`");

                if (!$field->isNotNull()) {
                    $param->setDefaultValue(null);
                }

                $methodSignature[] = $param;
            }

            $buildScope      = "\n".implode('', $buildScope)."\n";
            $buildScopeVars  = "\n".implode('', $buildScopeVars)."\n";

        } elseif ($this->useScope()) {
            $field = $this->getEntity()->getField($this->getParameter('scope_field'));

            $paramName = PhpParameter::create('scope')
                ->setType($field->getPhpType())
                ->setDescription("Scope to determine which objects node to return");

            if (!$field->isNotNull()) {
                $paramName->setDefaultValue(null);
            }
            $methodSignature[] = $paramName;
        }

        return [$methodSignature, $buildScope, $buildScopeVars];
    }

    /**
     * {@inheritdoc}
     */
    public function addParameter(array $parameter)
    {
        if ('scope_field' === $parameter['name']) {
            $this->parameters['scope_field'] .= ($this->parameters['scope_field'] ? ',' : '') . $parameter['value'];
        } else {
            parent::addParameter($parameter);
        }
    }

    /**
     * Returns all scope Fields as array.
     *
     * @return string[]
     */
    public function getScopes()
    {
        return $this->getParameter('scope_field')
            ? explode(',', str_replace(' ', '', trim($this->getParameter('scope_field'))))
            : array();
    }

    /**
     * Returns true if the behavior has multiple scope Fields.
     *
     * @return bool
     */
    public function hasMultipleScopes()
    {
        return count($this->getScopes()) > 1;
    }
}
