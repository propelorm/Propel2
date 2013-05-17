<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

use Propel\Generator\Model\Behavior;

/**
 * Gives a model class the ability to be ordered
 * Uses one additional column storing the rank
 *
 * @author Massimiliano Arione
 * @version     $Revision$
 */
class SortableBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'rank_column'  => 'sortable_rank',
        'use_scope'    => 'false',
        'scope_column' => '',
    );

    protected $objectBuilderModifier;
    protected $queryBuilderModifier;
    protected $tableMapBuilderModifier;

    /**
     * Add the rank_column to the current table
     */
    public function modifyTable()
    {
        $table = $this->getTable();

        if (!$table->hasColumn($this->getParameter('rank_column'))) {
            $table->addColumn(array(
                'name' => $this->getParameter('rank_column'),
                'type' => 'INTEGER'
            ));
        }

        if ($this->useScope()) {
            if (!$this->hasMultipleScopes() && !$table->hasColumn($this->getParameter('scope_column'))) {
                $table->addColumn(array(
                    'name' => $this->getParameter('scope_column'),
                    'type' => 'INTEGER'
                ));
            }

            $scopes = $this->getScopes();
            if (0 === count($scopes)) {
                throw new \InvalidArgumentException(sprintf(
                    'The sortable behavior in `%s` needs a `scope_column` parameter.',
                    $this->getTable()->getName()
                ));
            }
        }
    }

    public function getObjectBuilderModifier()
    {
        if (null === $this->objectBuilderModifier) {
            $this->objectBuilderModifier = new SortableBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    public function getQueryBuilderModifier()
    {
        if (null === $this->queryBuilderModifier) {
            $this->queryBuilderModifier = new SortableBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }

    public function getTableMapBuilderModifier()
    {
        if (null === $this->tableMapBuilderModifier) {
            $this->tableMapBuilderModifier = new SortableBehaviorTableMapBuilderModifier($this);
        }

        return $this->tableMapBuilderModifier;
    }

    public function useScope()
    {
        return 'true' === $this->getParameter('use_scope');
    }

    /**
     * Generates the method argument signature, the appropriate phpDoc for @params,
     * the scope builder php code and the scope variable builder php code/
     *
     * @return array ($methodSignature, $paramsDoc, $scopeBuilder, $buildScopeVars)
     */
    public function generateScopePhp()
    {

        $methodSignature = '';
        $paramsDoc       = '';
        $buildScope      = '';
        $buildScopeVars  = '';

        if ($this->hasMultipleScopes()) {

            $methodSignature = array();
            $buildScope      = array();
            $paramsDoc       = array();

            foreach ($this->getScopes() as $idx => $scope) {

                $column = $this->table->getColumn($scope);
                $param  = '$scope'.$column->getPhpName();

                $buildScope[]     = "    \$scope[] = $param;\n";
                $buildScopeVars[] = "    $param = \$scope[$idx];\n";
                $paramsDoc[]      = " * @param     ".$column->getPhpType()." $param Scope value for column `".$column->getPhpName()."`";

                if (!$column->isNotNull()) {
                    $param .= ' = null';
                }
                $methodSignature[] = $param;
            }

            $methodSignature = implode(', ', $methodSignature);
            $paramsDoc       = implode("\n", $paramsDoc);
            $buildScope      = "\n".implode('', $buildScope)."\n";
            $buildScopeVars  = "\n".implode('', $buildScopeVars)."\n";

        } elseif ($this->useScope()) {
            $methodSignature = '$scope';
            if ($column = $this->table->getColumn($this->getParameter('scope_column'))) {
                if (!$column->isNotNull()) {
                    $methodSignature .= ' = null';
                }
                $paramsDoc .= ' * @param '.$column->getPhpType().' $scope Scope to determine which objects node to return';
            }
        }

        return array($methodSignature, $paramsDoc, $buildScope, $buildScopeVars);
    }

    /**
     * Returns the getter method name.
     *
     * @param  string $name
     * @return string
     */
    public function getColumnGetter($name)
    {
        return 'get' . $this->getTable()->getColumn($name)->getPhpName();
    }

    /**
     * Returns the setter method name.
     *
     * @param  string $name
     * @return string
     */
    public function getColumnSetter($name)
    {
        return 'set' . $this->getTable()->getColumn($name)->getPhpName();
    }

    /**
     * {@inheritdoc}
     */
    public function addParameter(array $parameter)
    {
        if ('scope_column' === $parameter['name']) {
            $this->parameters['scope_column'] .= ($this->parameters['scope_column'] ? ',' : '') . $parameter['value'];
        } else {
            parent::addParameter($parameter);
        }
    }

    /**
     * Returns all scope columns as array.
     *
     * @return string[]
     */
    public function getScopes()
    {
        return $this->getParameter('scope_column')
            ? explode(',', str_replace(' ', '', trim($this->getParameter('scope_column'))))
            : array();
    }

    /**
     * Returns true if the behavior has multiple scope columns.
     *
     * @return bool
     */
    public function hasMultipleScopes()
    {
        return count($this->getScopes()) > 1;
    }
}
