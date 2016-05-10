<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Behavior\Sortable;

use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Entity;

/**
 * Generate the Sortable behavior entity manager.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class SortableManagerBuilder extends AbstractBuilder
{
    public function __construct(Entity $entity)
    {
        $this->overwrite = true;

        parent::__construct($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getFullClassName($injectNamespace = '', $classPrefix = '')
    {
        return parent::getFullClassName($injectNamespace, $classPrefix) . 'SortableManager';
    }

    public function buildClass()
    {
        $behavior = $this->getEntity()->getBehavior('sortable');
        $this->getDefinition()->addInterface('\Propel\Runtime\EntityManager\SortableManagerInterface');
        $this->applyComponent('SortableManager\GetNextMethod', $this, $behavior);
        $this->applyComponent('SortableManager\GetPreviousMethod', $this, $behavior);
        $this->applyComponent('SortableManager\InsertAtBottomMethod', $this, $behavior);
        $this->applyComponent('SortableManager\InsertAtRankMethod', $this, $behavior);
        $this->applyComponent('SortableManager\InsertAtTopMethod', $this, $behavior);
        $this->applyComponent('SortableManager\IsFirstMethod', $this, $behavior);
        $this->applyComponent('SortableManager\IsLastMethod', $this, $behavior);
        $this->applyComponent('SortableManager\MoveDownMethod', $this, $behavior);
        $this->applyComponent('SortableManager\MoveToBottomMethod', $this, $behavior);
        $this->applyComponent('SortableManager\MoveToRankMethod', $this, $behavior);
        $this->applyComponent('SortableManager\MoveToTopMethod', $this, $behavior);
        $this->applyComponent('SortableManager\MoveUpMethod', $this, $behavior);
        $this->applyComponent('SortableManager\RemoveFromListMethod', $this, $behavior);
        $this->applyComponent('SortableManager\SwapWithMethod', $this, $behavior);
        $this->applyComponent('SortableManager\UseStatements', $this, $behavior);
    }
}
