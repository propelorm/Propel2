<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Model\Behavior;

/**
 * Behavior to adds nested set tree structure fields and abilities
 *
 * @author François Zaninotto
 */
class NestedSetBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'left_field'       => 'tree_left',
        'right_field'      => 'tree_right',
        'level_field'      => 'tree_level',
        'use_scope'         => 'false',
        'scope_field'      => 'tree_scope',
        'method_proxies'    => 'false'
    );

    protected $objectBuilderModifier;

    protected $queryBuilderModifier;

    /**
     * Add the left, right and scope to the current table
     */
    public function modifyEntity()
    {
        $table = $this->getEntity();

        if (!$table->hasField($this->getParameter('left_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('left_field'),
                'type' => 'INTEGER'
            ));
        }

        if (!$table->hasField($this->getParameter('right_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('right_field'),
                'type' => 'INTEGER'
            ));
        }

        if (!$table->hasField($this->getParameter('level_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('level_field'),
                'type' => 'INTEGER'
            ));
        }

        if ('true' === $this->getParameter('use_scope') && !$table->hasField($this->getParameter('scope_field'))) {
            $table->addField(array(
                'name' => $this->getParameter('scope_field'),
                'type' => 'INTEGER'
            ));
        }
    }

    public function getObjectBuilderModifier()
    {
        if (null === $this->objectBuilderModifier) {
            $this->objectBuilderModifier = new NestedSetBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }

    public function getQueryBuilderModifier()
    {
        if (null === $this->queryBuilderModifier) {
            $this->queryBuilderModifier = new NestedSetBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }

    public function useScope()
    {
        return 'true' === $this->getParameter('use_scope');
    }

    public function getFieldConstant($fieldName)
    {
        return $this->getField($fieldName)->getName();
    }

    public function getField($fieldName)
    {
        return $this->getFieldForParameter($fieldName);
    }
}
