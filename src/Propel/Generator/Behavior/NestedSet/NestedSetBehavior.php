<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;

/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author François Zaninotto
 */
class NestedSetBehavior extends Behavior
{
    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        'left_column' => 'tree_left',
        'right_column' => 'tree_right',
        'level_column' => 'tree_level',
        'use_scope' => 'false',
        'scope_column' => 'tree_scope',
        'method_proxies' => 'false',
    ];

    /**
     * @var \Propel\Generator\Behavior\NestedSet\NestedSetBehaviorObjectBuilderModifier|null
     */
    protected $objectBuilderModifier;

    /**
     * @var \Propel\Generator\Behavior\NestedSet\NestedSetBehaviorQueryBuilderModifier|null
     */
    protected $queryBuilderModifier;

    /**
     * Add the left, right and scope to the current table
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();

        if (!$table->hasColumn($this->getParameter('left_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('left_column'),
                'type' => 'INTEGER',
            ]);
        }

        if (!$table->hasColumn($this->getParameter('right_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('right_column'),
                'type' => 'INTEGER',
            ]);
        }

        if (!$table->hasColumn($this->getParameter('level_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('level_column'),
                'type' => 'INTEGER',
            ]);
        }

        if ($this->getParameter('use_scope') === 'true' && !$table->hasColumn($this->getParameter('scope_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('scope_column'),
                'type' => 'INTEGER',
            ]);
        }
    }

    /**
     * @return $this|\Propel\Generator\Behavior\NestedSet\NestedSetBehaviorObjectBuilderModifier
     */
    public function getObjectBuilderModifier()
    {
        if ($this->objectBuilderModifier === null) {
            $this->objectBuilderModifier = new NestedSetBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    /**
     * @return $this|\Propel\Generator\Behavior\NestedSet\NestedSetBehaviorQueryBuilderModifier
     */
    public function getQueryBuilderModifier()
    {
        if ($this->queryBuilderModifier === null) {
            $this->queryBuilderModifier = new NestedSetBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }

    /**
     * @return bool
     */
    public function useScope(): bool
    {
        return $this->getParameter('use_scope') === 'true';
    }

    /**
     * @param string $columnName
     *
     * @return string
     */
    public function getColumnConstant(string $columnName): string
    {
        return $this->getColumn($columnName)->getName();
    }

    /**
     * @param string $columnName
     *
     * @return \Propel\Generator\Model\Column
     */
    public function getColumn(string $columnName): Column
    {
        return $this->getColumnForParameter($columnName);
    }
}
