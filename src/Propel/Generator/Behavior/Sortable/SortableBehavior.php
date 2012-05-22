<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

use Propel\Generator\Model\Behavior;

/**
 * Gives a model class the ability to be ordered
 * Uses one additional column storing the rank
 *
 * @author      Massimiliano Arione
 * @version     $Revision$
 */
class SortableBehavior extends Behavior
{
    // default parameters value
    protected $parameters = array(
        'rank_column'  => 'sortable_rank',
        'use_scope'    => 'false',
        'scope_column' => 'sortable_scope',
    );

    protected $objectBuilderModifier;
    protected $queryBuilderModifier;
    protected $peerBuilderModifier;

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

        if ('true' === $this->getParameter('use_scope')
            && !$table->hasColumn($this->getParameter('scope_column'))) {
            $table->addColumn(array(
                'name' => $this->getParameter('scope_column'),
                'type' => 'INTEGER'
            ));
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

    public function getPeerBuilderModifier()
    {
        if (null === $this->peerBuilderModifier) {
            $this->peerBuilderModifier = new SortableBehaviorPeerBuilderModifier($this);
        }

        return $this->peerBuilderModifier;
    }

    public function useScope()
    {
        return 'true' === $this->getParameter('use_scope');
    }
}
