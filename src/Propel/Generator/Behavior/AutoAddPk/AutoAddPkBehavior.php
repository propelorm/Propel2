<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Generator\Behavior\AutoAddPk;

use Propel\Generator\Model\Behavior;

/**
 * Adds a primary key to models defined without one
 *
 * @author François Zaninotto
 */
class AutoAddPkBehavior extends Behavior
{
    /**
     * Default parameters value
     *
     * @var array<string, mixed>
     */
    protected $parameters = [
        'name' => 'id',
        'autoIncrement' => 'true',
        'type' => 'INTEGER',
    ];

    /**
     * Copy the behavior to the database tables
     * Only for tables that have no Pk
     *
     * @return void
     */
    public function modifyDatabase(): void
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            if (!$table->hasPrimaryKey()) {
                $b = clone $this;
                $table->addBehavior($b);
            }
        }
    }

    /**
     * Add the primary key to the current table
     *
     * @return void
     */
    public function modifyTable(): void
    {
        $table = $this->getTable();
        if (!$table->hasPrimaryKey() && !$table->hasBehavior('concrete_inheritance')) {
            $columnAttributes = array_merge(['primaryKey' => 'true'], $this->getParameters());
            $this->getTable()->addColumn($columnAttributes);
        }
    }
}
