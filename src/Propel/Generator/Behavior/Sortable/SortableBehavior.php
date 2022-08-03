<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Sortable;

use InvalidArgumentException;
use Propel\Generator\Model\Behavior;

/**
 * Gives a model class the ability to be ordered
 * Uses one additional column storing the rank
 *
 * @author Massimiliano Arione
 * @version $Revision$
 */
class SortableBehavior extends Behavior
{
    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        'rank_column' => 'sortable_rank',
        'use_scope' => 'false',
        'scope_column' => '',
    ];

    /**
     * @var \Propel\Generator\Behavior\Sortable\SortableBehaviorObjectBuilderModifier|null
     */
    protected $objectBuilderModifier;

    /**
     * @var \Propel\Generator\Behavior\Sortable\SortableBehaviorQueryBuilderModifier|null
     */
    protected $queryBuilderModifier;

    /**
     * @var \Propel\Generator\Behavior\Sortable\SortableBehaviorTableMapBuilderModifier|null
     */
    protected $tableMapBuilderModifier;

    /**
     * Add the rank_column to the current table
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();

        if (!$table->hasColumn($this->getParameter('rank_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('rank_column'),
                'type' => 'INTEGER',
            ]);
        }

        if ($this->useScope()) {
            if (!$this->hasMultipleScopes() && !$table->hasColumn($this->getParameter('scope_column'))) {
                $table->addColumn([
                    'name' => $this->getParameter('scope_column'),
                    'type' => 'INTEGER',
                ]);
            }

            $scopes = $this->getScopes();
            if (count($scopes) === 0) {
                throw new InvalidArgumentException(sprintf(
                    'The sortable behavior in `%s` needs a `scope_column` parameter.',
                    $this->getTable()->getName(),
                ));
            }
        }
    }

    /**
     * @return $this|\Propel\Generator\Behavior\Sortable\SortableBehaviorObjectBuilderModifier
     */
    public function getObjectBuilderModifier()
    {
        if ($this->objectBuilderModifier === null) {
            $this->objectBuilderModifier = new SortableBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    /**
     * @return $this|\Propel\Generator\Behavior\Sortable\SortableBehaviorQueryBuilderModifier
     */
    public function getQueryBuilderModifier()
    {
        if ($this->queryBuilderModifier === null) {
            $this->queryBuilderModifier = new SortableBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }

    /**
     * @return $this|\Propel\Generator\Behavior\Sortable\SortableBehaviorTableMapBuilderModifier
     */
    public function getTableMapBuilderModifier()
    {
        if ($this->tableMapBuilderModifier === null) {
            $this->tableMapBuilderModifier = new SortableBehaviorTableMapBuilderModifier($this);
        }

        return $this->tableMapBuilderModifier;
    }

    /**
     * @return bool
     */
    public function useScope(): bool
    {
        return $this->getParameter('use_scope') === 'true';
    }

    /**
     * Generates the method argument signature, the appropriate phpDoc for @params,
     * the scope builder php code and the scope variable builder php code/
     *
     * @return array ($methodSignature, $paramsDoc, $scopeBuilder, $buildScopeVars)
     */
    public function generateScopePhp(): array
    {
        $methodSignature = '';
        $paramsDoc = '';
        $buildScope = '';
        $buildScopeVars = '';

        if ($this->hasMultipleScopes()) {
            $methodSignature = [];
            $buildScope = [];
            $buildScopeVars = [];
            $paramsDoc = [];

            foreach ($this->getScopes() as $idx => $scope) {
                $column = $this->table->getColumn($scope);
                $param = '$scope' . $column->getPhpName();

                $buildScope[] = "    \$scope[] = $param;\n";
                $buildScopeVars[] = "    $param = \$scope[$idx];\n";
                $paramsDoc[] = ' * @param ' . $column->getPhpType() . " $param Scope value for column `" . $column->getPhpName() . '`';

                if (!$column->isNotNull()) {
                    $param .= ' = null';
                }
                $methodSignature[] = $param;
            }

            $methodSignature = implode(', ', $methodSignature);
            $paramsDoc = implode("\n", $paramsDoc);
            $buildScope = "\n" . implode('', $buildScope) . "\n";
            $buildScopeVars = "\n" . implode('', $buildScopeVars) . "\n";
        } elseif ($this->useScope()) {
            $methodSignature = '$scope';
            $column = $this->table->getColumn($this->getParameter('scope_column'));
            if ($column) {
                if (!$column->isNotNull()) {
                    $methodSignature .= ' = null';
                }
                $paramsDoc .= ' * @param ' . $column->getPhpType() . ' $scope Scope to determine which objects node to return';
            }
        }

        return [$methodSignature, $paramsDoc, $buildScope, $buildScopeVars];
    }

    /**
     * Returns the getter method name.
     *
     * @param string $name
     *
     * @return string
     */
    public function getColumnGetter(string $name): string
    {
        return 'get' . $this->getTable()->getColumn($name)->getPhpName();
    }

    /**
     * Returns the setter method name.
     *
     * @param string $name
     *
     * @return string
     */
    public function getColumnSetter(string $name): string
    {
        return 'set' . $this->getTable()->getColumn($name)->getPhpName();
    }

    /**
     * @inheritDoc
     */
    public function addParameter(array $parameter): void
    {
        if ($parameter['name'] === 'scope_column') {
            $this->parameters['scope_column'] .= ($this->parameters['scope_column'] ? ',' : '') . $parameter['value'];
        } else {
            parent::addParameter($parameter);
        }
    }

    /**
     * Returns all scope columns as array.
     *
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->getParameter('scope_column')
            ? explode(',', str_replace(' ', '', trim($this->getParameter('scope_column'))))
            : [];
    }

    /**
     * Returns true if the behavior has multiple scope columns.
     *
     * @return bool
     */
    public function hasMultipleScopes(): bool
    {
        return count($this->getScopes()) > 1;
    }
}
