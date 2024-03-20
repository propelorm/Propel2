<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\Timestampable;

use Propel\Generator\Builder\Om\AbstractOMBuilder;
use Propel\Generator\Model\Behavior;

/**
 * Gives a model class the ability to track creation and last modification dates
 * Uses two additional columns storing the creation and update date
 *
 * @author François Zaninotto
 */
class TimestampableBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected $parameters = [
        'create_column' => 'created_at',
        'update_column' => 'updated_at',
        'disable_created_at' => 'false',
        'disable_updated_at' => 'false',
    ];

    /**
     * @return bool
     */
    protected function withUpdatedAt(): bool
    {
        return !$this->booleanValue($this->getParameter('disable_updated_at'));
    }

    /**
     * @return bool
     */
    protected function withCreatedAt(): bool
    {
        return !$this->booleanValue($this->getParameter('disable_created_at'));
    }

    /**
     * Add the create_column and update_columns to the current table
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();

        if ($this->withCreatedAt() && !$table->hasColumn($this->getParameter('create_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('create_column'),
                'type' => 'TIMESTAMP',
            ]);
        }
        if ($this->withUpdatedAt() && !$table->hasColumn($this->getParameter('update_column'))) {
            $table->addColumn([
                'name' => $this->getParameter('update_column'),
                'type' => 'TIMESTAMP',
            ]);
        }
    }

    /**
     * Get the setter of one of the columns of the behavior
     *
     * @param string $column One of the behavior columns, 'create_column' or 'update_column'
     *
     * @return string The related setter, 'setCreatedOn' or 'setUpdatedOn'
     */
    protected function getColumnSetter(string $column): string
    {
        return 'set' . $this->getColumnForParameter($column)->getPhpName();
    }

    /**
     * @param string $columnName
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    protected function getColumnConstant(string $columnName, AbstractOMBuilder $builder): string
    {
        return $builder->getColumnConstant($this->getColumnForParameter($columnName));
    }

    /**
     * Add code in ObjectBuilder::preUpdate
     *
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string The code to put at the hook
     */
    public function preUpdate(AbstractOMBuilder $builder): string
    {
        if ($this->withUpdatedAt()) {
            $valueSource = strtoupper($this->getTable()->getColumn($this->getParameter('update_column'))->getType()) === 'INTEGER'
                ? 'time()'
                : '\\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision()';

            return 'if ($this->isModified() && !$this->isColumnModified(' . $this->getColumnConstant('update_column', $builder) . ")) {
    \$this->" . $this->getColumnSetter('update_column') . "({$valueSource});
}";
        }

        return '';
    }

    /**
     * Add code in ObjectBuilder::preInsert
     *
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string The code to put at the hook
     */
    public function preInsert(AbstractOMBuilder $builder): string
    {
        $script = '$time = time();
$highPrecision = \\Propel\\Runtime\\Util\\PropelDateTime::createHighPrecision();';

        if ($this->withCreatedAt()) {
            $valueSource = strtoupper($this->getTable()->getColumn($this->getParameter('create_column'))->getType()) === 'INTEGER'
                ? '$time'
                : '$highPrecision';
            $script .= "
if (!\$this->isColumnModified(" . $this->getColumnConstant('create_column', $builder) . ")) {
    \$this->" . $this->getColumnSetter('create_column') . "({$valueSource});
}";
        }

        if ($this->withUpdatedAt()) {
            $valueSource = strtoupper($this->getTable()->getColumn($this->getParameter('update_column'))->getType()) === 'INTEGER'
                ? '$time'
                : '$highPrecision';
            $script .= "
if (!\$this->isColumnModified(" . $this->getColumnConstant('update_column', $builder) . ")) {
    \$this->" . $this->getColumnSetter('update_column') . "({$valueSource});
}";
        }

        return $script;
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function objectMethods(AbstractOMBuilder $builder): string
    {
        if (!$this->withUpdatedAt()) {
            return '';
        }

        return "
/**
 * Mark the current object so that the update date doesn't get updated during next save
 *
 * @return \$this The current object (for fluent API support)
 */
public function keepUpdateDateUnchanged()
{
    \$this->modifiedColumns[" . $this->getColumnConstant('update_column', $builder) . "] = true;

    return \$this;
}
";
    }

    /**
     * @param \Propel\Generator\Builder\Om\AbstractOMBuilder $builder
     *
     * @return string
     */
    public function queryMethods(AbstractOMBuilder $builder): string
    {
        $script = '';

        if ($this->withUpdatedAt()) {
            $updateColumnConstant = $this->getColumnConstant('update_column', $builder);
            $script .= "
/**
 * Filter by the latest updated
 *
 * @param int \$nbDays Maximum age of the latest update in days
 *
 * @return \$this The current query, for fluid interface
 */
public function recentlyUpdated(\$nbDays = 7)
{
    \$this->addUsingAlias($updateColumnConstant, time() - \$nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);

    return \$this;
}

/**
 * Order by update date desc
 *
 * @return \$this The current query, for fluid interface
 */
public function lastUpdatedFirst()
{
    \$this->addDescendingOrderByColumn($updateColumnConstant);

    return \$this;
}

/**
 * Order by update date asc
 *
 * @return \$this The current query, for fluid interface
 */
public function firstUpdatedFirst()
{
    \$this->addAscendingOrderByColumn($updateColumnConstant);

    return \$this;
}
";
        }

        if ($this->withCreatedAt()) {
            $createColumnConstant = $this->getColumnConstant('create_column', $builder);
            $script .= "
/**
 * Order by create date desc
 *
 * @return \$this The current query, for fluid interface
 */
public function lastCreatedFirst()
{
    \$this->addDescendingOrderByColumn($createColumnConstant);

    return \$this;
}

/**
 * Filter by the latest created
 *
 * @param int \$nbDays Maximum age of in days
 *
 * @return \$this The current query, for fluid interface
 */
public function recentlyCreated(\$nbDays = 7)
{
    \$this->addUsingAlias($createColumnConstant, time() - \$nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);

    return \$this;
}

/**
 * Order by create date asc
 *
 * @return \$this The current query, for fluid interface
 */
public function firstCreatedFirst()
{
    \$this->addAscendingOrderByColumn($createColumnConstant);

    return \$this;
}
";
        }

        return $script;
    }
}
