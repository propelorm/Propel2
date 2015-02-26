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
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\RepositoryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Runtime\ActiveQuery\Criteria;

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

    protected $objectBuilderModifier;
    protected $queryBuilderModifier;
    protected $tableMapBuilderModifier;

    /**
     * Add the rank_field to the current table
     */
    public function modifyEntity()
    {
        $table = $this->getEntity();

        if (!$table->hasField($this->getParameter('rank_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('rank_field'),
                'type' => 'INTEGER'
            ));
        }

        if ($this->useScope()) {
            if (!$this->hasMultipleScopes() && !$table->hasField($this->getParameter('scope_field'))) {
                $table->addField(array(
                    'name' => $this->getParameter('scope_field'),
                    'type' => 'INTEGER'
                ));
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

    /**
     * @return string
     */
    public function getRankVarName()
    {
        return $this->getFieldForParameter('rank_field')->getName();
    }

    public function queryBuilderModification(QueryBuilder $builder)
    {
        if ('rank' !== $this->getParameter('rank_field')) {
            $this->applyComponent('Query\\FilterByRankMethod', $builder);
            $this->applyComponent('Query\\OrderByRankMethod', $builder);
        }

        if ('rank' !== $this->getParameter('rank_field') || $this->useScope()) {
            $this->applyComponent('Query\\FindOneByRankMethod', $builder);
        }

        $this->applyComponent('Query\\FindListMethod', $builder);

        // utilities
        $this->applyComponent('Query\\GetMaxRankMethod', $builder);
        $this->applyComponent('Query\\GetMaxRankArrayMethod', $builder);
//        $this->addRetrieveByRank($script); redundant to findOneByRank
//        $this->addReorder($script); not in use
//        $this->addDoSelectOrderByRank($script); redundant to orderByRank()->find()

        if ($this->useScope()) {
//            $this->addRetrieveList($script); not in use, redundant to findList()
//            $this->addCountList($script); redundant to inList()->count
//            $this->addDeleteList($script);  redundant to inList()->delete, move to repository
            $this->applyComponent('Query\\InListMethod', $builder);
            $this->applyComponent('Query\\FilterByNormalizedListScopeMethod', $builder);
        }
    }

    public function repositoryBuilderModification(RepositoryBuilder $builder)
    {
        $this->applyComponent('Repository\\SortableShiftRankMethod', $builder);
        $this->applyComponent('Repository\\IsFirstMethod', $builder);
        $this->applyComponent('Repository\\IsLastMethod', $builder);
        $this->applyComponent('Repository\\GetNextMethod', $builder);
        $this->applyComponent('Repository\\GetPreviousMethod', $builder);

        $this->addInsertAtRank($script);
        $this->addInsertAtBottom($script);
        $this->addInsertAtTop($script);
        $this->addMoveToRank($script);
        $this->addSwapWith($script);
        $this->addMoveUp($script);
        $this->addMoveDown($script);
        $this->addMoveToTop($script);
        $this->addMoveToBottom($script);
        $this->addRemoveFromList($script);
        $this->addProcessSortableQueries($script);
    }

    public function objectBuilderModification(ObjectBuilder $builder)
    {
        if ('rank' !== $this->getParameter('rank_field')) {
            $this->applyComponent('Object\\RankAccessorMethod', $builder);
        }

        if ($this->useScope() && 'scope_value' !== $this->getParameter('rank_field')) {
            $this->applyComponent('Object\\ScopeAccessorMethod', $builder);
        }
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
        $methodSignature = array();
        $buildScope      = '';
        $buildScopeVars  = '';

        if ($this->hasMultipleScopes()) {

            $methodSignature = array();
            $buildScope      = array();

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

        return array($methodSignature, $buildScope, $buildScopeVars);
    }

    /**
     * Returns the getter method name.
     *
     * @param  string $name
     * @return string
     */
    public function getFieldGetter($name)
    {
        return 'get' . $this->getEntity()->getField($name)->getName();
    }

    /**
     * Returns the setter method name.
     *
     * @param  string $name
     * @return string
     */
    public function getFieldSetter($name)
    {
        return 'set' . $this->getEntity()->getField($name)->getName();
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
