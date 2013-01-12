<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

/**
 * Behavior to add sortable peer methods
 *
 * @author Jérémie Augustin
 */
class SortableBehaviorTableMapBuilderModifier
{
    protected $behavior;

    protected $table;


    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getTable();
    }

    public function staticAttributes($builder)
    {
        $tableName = $this->table->getName();

        return $this->behavior->renderTemplate('tableMapSortable', array(
                'rankColumn'     => $this->getColumnConstant('rank_column'),
                'scopeColumn'    => $this->behavior->useScope()? $this->getColumnConstant('scope_column') : '',
                'tableName'      => $this->table->getName(),
                'useScope'       => $this->behavior->useScope(),
        ));
    }

    protected function getColumnConstant($name)
    {
        return strtoupper($this->behavior->getColumnForParameter($name)->getName());
    }

}
