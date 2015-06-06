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
 * Behavior to add sortable methods
 *
 * @author Jérémie Augustin
 */
class SortableBehaviorEntityMapBuilderModifier
{
    /**
     * @var SortableBehavior
     */
    protected $behavior;

    protected $table;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
        $this->table = $behavior->getEntity();
    }

    public function staticAttributes($builder)
    {
        $tableName = $this->table->getName();
        $col = '';

        if ($this->behavior->useScope()) {

            if ($this->behavior->hasMultipleScopes()) {
                foreach ($this->behavior->getScopes() as $scope) {
                    $col[] = "$tableName.".strtoupper($scope);
                }
                $col = json_encode($col);
                $col = "'$col'";
            } else {
                $colNames = $this->getFieldConstant('scope_field');
                $col =  "'$tableName.$colNames'";
            }
        }

        return $this->behavior->renderTemplate('tableMapSortable', array(
            'rankField' => $this->getFieldConstant('rank_field'),
            'multiScope' => $this->behavior->hasMultipleScopes(),
            'scope'      => $col,
            'tableName'      => $this->table->getName(),
            'useScope'   => $this->behavior->useScope(),
        ));
    }

    protected function getFieldConstant($name)
    {
        return $this->behavior->getFieldForParameter($name)->getName();
    }

}
