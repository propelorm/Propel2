<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Util;

use InvalidArgumentException;
use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Table;

/**
 * Used internally to put code on another class. WIP - only currently used
 * functionality is implemented. Enhance if needed.
 */
class InsertCodeBehavior extends Behavior
{
    /**
     * @var array<string, string|callable|\Propel\Generator\Model\Table>
     */
    protected $codeForHooks = [];

    /**
     * Add this behavior to a table.
     *
     * @param \Propel\Generator\Model\Behavior $insertingBehavior
     * @param \Propel\Generator\Model\Table $table
     * @param array $codeForHooks
     *
     * @return \Propel\Generator\Behavior\Util\InsertCodeBehavior|self
     */
    public static function addToTable(Behavior $insertingBehavior, Table $table, array $codeForHooks): self
    {
        $behavior = new self();
        $behavior->setup($insertingBehavior, $table, $codeForHooks);

        return $behavior;
    }

    /**
     * @param \Propel\Generator\Model\Behavior $insertingBehavior
     * @param \Propel\Generator\Model\Table $table
     * @param array $codeForHooks
     *
     * @return void
     */
    public function setup(Behavior $insertingBehavior, Table $table, array $codeForHooks)
    {
        $id = "insert_code_from_{$insertingBehavior->getName()}_behavior_on_table_{$insertingBehavior->getTable()->getName()}";
        $this->setId($id);
        $this->setDatabase($table->getDatabase());
        $this->setTable($table);
        $this->codeForHooks = $codeForHooks;
        $table->addBehavior($this);
    }

    /**
     * @see \Propel\Generator\Model\Behavior::allowMultiple()
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return true;
    }

    // object builder hooks

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function objectAttributes(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('objectAttributes', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function objectMethods(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('objectMethods', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function objectCall(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('objectCall', $objectBuilder);
    }

    /**
     * @see \Propel\Generator\Model\Behavior::objectFilter()
     *
     * @param string $script
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function objectFilter(string &$script, ObjectBuilder $objectBuilder)
    {
        $fun = $this->codeForHooks['objectFilter'] ?? null;
        if (!$fun) {
            return;
        }
        if (!is_callable($fun)) {
            throw new InvalidArgumentException("Value in 'objectFilter' has to be callable.");
        }

        $script = $fun($script, $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function preInsert(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('preInsert', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function postInsert(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('postInsert', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function preUpdate(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('preUpdate', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function postUpdate(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('postUpdate', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function preDelete(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('preDelete', $objectBuilder);
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    public function postDelete(ObjectBuilder $objectBuilder): string
    {
        return $this->resolveCode('postDelete', $objectBuilder);
    }

    /**
     * @param string $hook
     * @param \Propel\Generator\Builder\Om\ObjectBuilder $objectBuilder
     *
     * @return string
     */
    protected function resolveCode(string $hook, ObjectBuilder $objectBuilder): string
    {
        $code = $this->codeForHooks[$hook] ?? null;
        if (!$code) {
            return '';
        }

        return is_callable($code) ? $code($objectBuilder) : $code;
    }

    // parentClass

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder|\Propel\Generator\Builder\Om\QueryBuilder $builder
     *
     * @return string|null
     */
    public function parentClass($builder): ?string
    {
        $parentTable = $this->resolveTableFromHookContent($builder, 'parentClass');
        if (!$parentTable) {
            return null;
        }
        $stubBuilder = $this->buildStubBuilder($builder, $parentTable);

        return $stubBuilder ? $builder->declareClassFromBuilder($stubBuilder, true) : null;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     * @param string $hook
     *
     * @throws \InvalidArgumentException If the table cannot be resolved
     *
     * @return \Propel\Generator\Model\Table|null
     */
    protected function resolveTableFromHookContent(AbstractOMBuilder $builder, string $hook): ?Table
    {
        $parentTable = $this->codeForHooks[$hook] ?? null;
        if (is_string($parentTable)) {
            $resolvedTable = $builder->getDatabase()->getTable($parentTable);
            if (!$resolvedTable) {
                throw new InvalidArgumentException("Could not find table in '$hook': '$parentTable'");
            }

            return $resolvedTable;
        }
        if ($parentTable && !($parentTable instanceof Table)) {
            throw new InvalidArgumentException("Value in '$hook' has to be an instance of Table");
        }

        return $parentTable;
    }

    /**
     * @param \Propel\Generator\Builder\Om\ObjectBuilder|\Propel\Generator\Builder\Om\QueryBuilder $builder
     * @param \Propel\Generator\Model\Table $table
     *
     * @return \Propel\Generator\Builder\Om\AbstractOMBuilder|null
     */
    protected function buildStubBuilder($builder, Table $table): ?AbstractOMBuilder
    {
        switch (get_class($builder)) {
            case ObjectBuilder::class:
                return $builder->getNewStubObjectBuilder($table);
            case QueryBuilder::class:
                return $builder->getNewStubQueryBuilder($table);
            default:
                return null;
        }
    }
}
