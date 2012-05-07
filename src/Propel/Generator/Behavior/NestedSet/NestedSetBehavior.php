<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Behavior\NestedSet;

use Propel\Generator\Model\Behavior;

/**
 * Behavior to adds nested set tree structure columns and abilities
 *
 * @author     François Zaninotto
 */
class NestedSetBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'left_column'       => 'tree_left',
        'right_column'      => 'tree_right',
        'level_column'      => 'tree_level',
        'use_scope'         => 'false',
        'scope_column'      => 'tree_scope',
        'method_proxies'    => 'false'
    );

    protected $objectBuilderModifier;

    protected $queryBuilderModifier;

    protected $peerBuilderModifier;

    /**
     * Add the left, right and scope to the current table
     */
    public function modifyTable()
    {
        if (!$this->getTable()->containsColumn($this->getParameter('left_column'))) {
            $this->getTable()->addColumn(array(
                'name' => $this->getParameter('left_column'),
                'type' => 'INTEGER'
            ));
        }
        if (!$this->getTable()->containsColumn($this->getParameter('right_column'))) {
            $this->getTable()->addColumn(array(
                'name' => $this->getParameter('right_column'),
                'type' => 'INTEGER'
            ));
        }
        if (!$this->getTable()->containsColumn($this->getParameter('level_column'))) {
            $this->getTable()->addColumn(array(
                'name' => $this->getParameter('level_column'),
                'type' => 'INTEGER'
            ));
        }
        if ('true' === $this->getParameter('use_scope') &&
             !$this->getTable()->containsColumn($this->getParameter('scope_column'))) {
            $this->getTable()->addColumn(array(
                'name' => $this->getParameter('scope_column'),
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

    public function getPeerBuilderModifier()
    {
        if (null === $this->peerBuilderModifier) {
            $this->peerBuilderModifier = new NestedSetBehaviorPeerBuilderModifier($this);
        }

        return $this->peerBuilderModifier;
    }

    public function useScope()
    {
        return 'true' === $this->getParameter('use_scope');
    }
}